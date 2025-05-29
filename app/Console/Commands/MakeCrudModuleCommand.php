<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class MakeCrudModuleCommand extends Command
{
    protected $signature = 'make:crud-module {name : The name of the module (e.g., Post, ProductCategory)}
                                             {--table= : The name of the database table (optional)}
                                             {--dry-run : Generate files in a "DryRun/{ModuleName}" directory at the project root}
                                             {--no-views : Do not create view files}
                                             {--no-api : Do not create API controller and resource}
                                             {--service : Generate a service class for business logic}
                                             {--policy : Generate a policy class for authorization}
                                             {--events : Generate basic model event classes (Created, Updated, Deleted)}
                                             {--observer : Generate a model observer for audit logging}
                                             {--force : Overwrite existing files without asking}';

    protected $description = 'Create an advanced CRUD module with migrations, enhanced relationship detection, field stubs, DataTables, audit logs, and soft deletes.';

    // Core Properties
    protected $moduleName;
    protected $modelName;
    protected $tableName;

    // Options
    protected $isDryRun = false;
    protected $createViews = true;
    protected $createApi = true;
    protected $generateService = false;
    protected $generatePolicy = false;
    protected $generateEvents = false;
    protected $generateObserver = false;
    protected $forceOverwrite = false;

    // Paths
    protected $basePath; // Actual generation path (DryRun or Modules)
    protected $stubsPath;
    protected $actualModulesPath; // Always Modules/ModuleName

    // Cache for table columns and details to avoid redundant DB queries
    protected $tableColumnsCache = null;
    protected $columnDetailsCache = [];
    protected $foreignKeyConstraintsCache = null;

    // Flag to track if a migration was generated
    protected $migrationGenerated = false;


    public function handle()
    {
        $this->initializeProperties();
        $this->prepareDirectories();

        $this->info("Starting CRUD module: {$this->moduleName} (Model: {$this->modelName}, Table: {$this->tableName}) -> {$this->basePath}");

        if (!$this->validateStubsDirectory()) {
            return 1;
        }

        $this->generateModuleStructure(); // Ensures database/migrations path exists
        $this->generateDatabaseRelatedComponents(); // Includes migration generation
        $this->generateCoreComponents();
        $this->generateOptionalComponents();
        $this->generateRoutingAndProviders(); // Service provider will load migrations

        $this->info("CRUD module '{$this->moduleName}' generated successfully in {$this->basePath}.");
        $this->outputAutoloadingAndRegistrationInstructions();
        $this->attemptAutoRegisterServiceProvider();
        $this->runPostGenerationCommands();

        return 0;
    }

    protected function initializeProperties()
    {
        $this->moduleName = Str::studly($this->argument('name'));
        $this->modelName = Str::singular($this->moduleName);
        $this->tableName = $this->option('table') ?: Str::snake(Str::plural($this->modelName));

        $this->isDryRun = $this->option('dry-run');
        $this->createViews = !$this->option('no-views');
        $this->createApi = !$this->option('no-api');
        $this->generateService = $this->option('service');
        $this->generatePolicy = $this->option('policy');
        $this->generateEvents = $this->option('events');
        $this->generateObserver = $this->option('observer');
        $this->forceOverwrite = $this->option('force');

        $this->actualModulesPath = base_path('Modules/' . $this->moduleName);
        $this->stubsPath = rtrim(config('crud-module.stubs_path', base_path('stubs/crud-module')), '/');


        if ($this->isDryRun) {
            $this->basePath = base_path('DryRun/' . $this->moduleName);
            $this->alert("Dry run mode: Files will be generated in: {$this->basePath}");
        } else {
            $this->basePath = $this->actualModulesPath;
        }
    }

    protected function prepareDirectories()
    {
        if ($this->isDryRun) {
            if (!File::isDirectory(base_path('DryRun'))) {
                File::makeDirectory(base_path('DryRun'), 0755, true, true);
            }
            if (File::isDirectory($this->basePath) && !$this->forceOverwrite) {
                if ($this->confirm("Dry-run directory [{$this->basePath}] exists. Clear it?")) {
                    File::deleteDirectory($this->basePath);
                } else {
                    $this->comment("Skipping generation as dry-run directory was not cleared.");
                }
            }
        } else {
            if (!File::isDirectory(base_path('Modules'))) {
                File::makeDirectory(base_path('Modules'), 0755, true, true);
            }
        }
    }

    protected function validateStubsDirectory()
    {
        if (!File::isDirectory($this->stubsPath)) {
            $this->error("Stubs directory not found: {$this->stubsPath}");
            $this->line("Please ensure stubs are available. You might need to publish them or configure 'crud-module.stubs_path' in a config file.");
            return false;
        }
        if (!File::isDirectory($this->stubsPath . '/fields')) {
            $this->alert("Field stubs directory not found: {$this->stubsPath}/fields. Form generation will use basic text inputs for unknown field types.");
        }
        if (!File::exists($this->stubsPath . '/migration.create.stub')) {
            $this->warn("Migration stub (migration.create.stub) not found in {$this->stubsPath}. Migration generation will be skipped if needed.");
        }
        return true;
    }

    protected function generateDatabaseRelatedComponents()
    {
        $this->generateMigrationFile();
    }

    protected function generateCoreComponents()
    {
        $this->generateModel();
        $this->generateRequest();
        $this->generateWebController();
    }

    protected function generateOptionalComponents()
    {
        if ($this->generateService) {
            $this->generateServiceClasses();
        }
        if ($this->generatePolicy) {
            $this->generatePolicyClass();
        }
        if ($this->generateEvents) {
            $this->generateEventClasses();
        }
        if ($this->generateObserver) {
            $this->generateObserverClass();
        }
        if ($this->createViews) {
            $this->generateViews();
        }
        if ($this->createApi) {
            $this->generateApiController();
            $this->generateApiResource();
        }
    }

    protected function generateRoutingAndProviders()
    {
        $this->generateRoutes();
        $this->generateServiceProvider();
    }

    protected function outputAutoloadingAndRegistrationInstructions()
    {
        $this->newLine(2);
        $this->line("<fg=blue>======================================================================</>");
        $this->line("<fg=blue> MODULE GENERATION SUMMARY & NEXT STEPS FOR '{$this->moduleName}' </>");
        $this->line("<fg=blue>======================================================================</>");
        $this->newLine();

        $dryRunPathInfo = base_path('DryRun/' . $this->moduleName);
        $dryRunNamespaceInfo = 'DryRun\\' . $this->moduleName;
        $actualModulePathInfo = $this->actualModulesPath;
        $actualModuleNamespaceInfo = 'Modules\\' . $this->moduleName;

        $this->line("<fg=magenta>--- Path & Namespace Information ---</>");
        if ($this->isDryRun) {
            $this->line("<fg=cyan>Current Operation (Dry Run):</>");
            $this->line("  <options=bold>Generated Files Location:</> {$this->basePath}");
            $this->line("  <options=bold>Generated Files Namespace Root:</> {$dryRunNamespaceInfo}");
            $this->newLine();
            $this->line("<fg=green>For Actual Module (if you proceed after dry run):</>");
            $this->line("  <options=bold>Target Actual Module Path:</> {$actualModulePathInfo}");
            $this->line("  <options=bold>Target Actual Module Namespace Root:</> {$actualModuleNamespaceInfo}");
        } else {
            $this->line("<fg=green>Current Operation (Actual Module Generation):</>");
            $this->line("  <options=bold>Generated Files Location:</> {$this->basePath}");
            $this->line("  <options=bold>Generated Files Namespace Root:</> {$actualModuleNamespaceInfo}");
        }
        $this->newLine();

        $this->warn("======================================================================");
        $this->warn(" IMPORTANT NEXT STEPS TO ACTIVATE MODULE '{$this->moduleName}' ");
        $this->warn("======================================================================");
        $this->newLine();

        if ($this->isDryRun) {
            $this->info("<fg=cyan>You used --dry-run. Module '{$this->moduleName}' is in: {$this->basePath}</>");
            $this->line("<fg=cyan>To use it:</>");
            $this->line("<fg=yellow>  Step 1: Move Module</>");
            $this->line("     Move '{$this->moduleName}' from '{$this->basePath}'");
            $this->line("     to your project's root 'Modules/' directory: " . $this->actualModulesPath);
            $this->newLine();
        }

        $this->line("<fg=yellow>Step A: Configure PSR-4 Autoloading (if 'Modules/' isn't already)</>");
        $this->line("   In `composer.json`, under `\"autoload\"` -> `\"psr-4\"`, ensure:");
        $this->comment('     "Modules\\\\": "Modules/",');
        if ($this->isDryRun) {
            $this->comment('     // For dry-run (if you were to use it directly, not typical for production):');
            $this->comment('     // "DryRun\\\\": "DryRun/",');
        }
        $this->newLine();

        $this->line("<fg=yellow>Step B: Update Composer's Autoloader</>");
        $this->comment("     composer dump-autoload -o");
        $this->newLine();

        $this->line("<fg=yellow>Step C: Register Service Provider (if not auto-registered)</>");
        $this->line("   The command attempted to auto-register. If it failed or you prefer manual setup:");
        $this->line("   In `config/app.php` (older Laravel) or `bootstrap/providers.php` (Laravel 11+), add:");
        $this->comment("     {$actualModuleNamespaceInfo}\\Providers\\{$this->moduleName}ServiceProvider::class,");
        $this->newLine();

        if ($this->generatePolicy) {
            $this->line("<fg=yellow>Step D: Review Policy Registration</>");
            $this->line("   The generated `{$this->moduleName}ServiceProvider` attempts to register `{$this->modelName}Policy`.");
            $this->line("   Ensure this aligns with your `AuthServiceProvider` and permission system.");
            $this->newLine();
        }
        if ($this->generateObserver) {
            $this->line("<fg=yellow>Step E: Review Observer Registration & Audit Logic</>");
            $this->line("   The generated `{$this->moduleName}ServiceProvider` attempts to register `{$this->modelName}Observer` for auditing.");
            $this->line("   Customize the observer's logging logic (e.g., use a dedicated audit table/package).");
            $this->newLine();
        }
        $this->line("<fg=yellow>Step F: Review and Run Migrations</>");
        if ($this->migrationGenerated) {
            $this->line("   A new migration file was generated for the '{$this->tableName}' table.");
            $this->line("   <options=bold>Please review and customize the migration file before running.</>");
        }
        $this->comment("     php artisan migrate");
        $this->newLine();
        $this->warn("======================================================================");
        $this->newLine();
    }

    protected function generateModuleStructure()
    {
        $paths = [
            $this->basePath . '/Http/Controllers/Web',
            $this->basePath . '/Http/Requests',
            $this->basePath . '/Models',
            $this->basePath . '/Providers',
            $this->basePath . '/routes',
            $this->basePath . '/database/migrations', // Crucial for migrations
        ];

        if ($this->createViews) {
            $paths[] = $this->basePath . '/views/' . Str::kebab(Str::plural($this->modelName));
        }
        if ($this->createApi) {
            $paths[] = $this->basePath . '/Http/Controllers/Api';
            $paths[] = $this->basePath . '/Http/Resources';
        }
        if ($this->generateService) {
            $paths[] = $this->basePath . '/Services';
            $paths[] = $this->basePath . '/Contracts';
        }
        if ($this->generatePolicy) {
            $paths[] = $this->basePath . '/Policies';
        }
        if ($this->generateEvents) {
            $paths[] = $this->basePath . '/Events';
        }
        if ($this->generateObserver) {
            $paths[] = $this->basePath . '/Observers';
        }

        foreach ($paths as $path) {
            $this->makeDirectory($path);
        }
    }

    protected function makeDirectory($path)
    {
        if (!File::isDirectory($path)) {
            if (File::makeDirectory($path, 0755, true, true)) {
                $this->info("Created directory: {$path}");
            } else {
                $this->error("Failed to create directory: {$path}. Check permissions.");
            }
        } else {
            $this->comment("Directory already exists: {$path}");
        }
    }

    protected function getStubContent($stubName)
    {
        $stubName = str_replace('.stub', '', $stubName);
        $stubPath = $this->stubsPath . '/' . $stubName . '.stub';

        if (!File::exists($stubPath)) {
            if (Str::endsWith($stubName, '.blade')) {
                $stubPath = $this->stubsPath . '/' . $stubName . '.stub';
                if (File::exists($stubPath)) {
                    return File::get($stubPath);
                }
            }
            if (str_starts_with($stubName, 'fields/')) {
                $this->alert("Field stub not found: {$stubPath}. Falling back to basic text input.");
                $fallbackFieldStubPath = $this->stubsPath . '/fields/text.stub';
                return File::exists($fallbackFieldStubPath) ? File::get($fallbackFieldStubPath) : "<input type=\"text\" name=\"{{fieldName}}\" value=\"{{fieldValue}}\">";
            }
            if ($stubName === 'migration.create') {
                // Specific handling for missing migration stub to prevent cascading errors
                $this->error("Critical stub file not found: {$stubPath}. Migration generation will fail.");
                return "/* STUB NOT FOUND: {$stubPath} - CRITICAL */";
            }
            $this->error("Stub file not found: {$stubPath} (original request: {$stubName})");
            return "/* STUB NOT FOUND: {$stubPath} */";
        }
        return File::get($stubPath);
    }


    protected function writeFile($path, $content)
    {
        if (str_contains($content, "/* STUB NOT FOUND:")) {
            $this->error("Skipping file write for {$path} due to missing stub content.");
            if (str_contains($content, "CRITICAL")) {
                $this->error("This was a critical stub. Subsequent operations might be affected.");
            }
            return;
        }

        if (File::exists($path) && !$this->forceOverwrite) {
            if (!$this->confirm("The file [{$path}] already exists. Do you want to overwrite it?")) {
                $this->comment("Skipped file: {$path}");
                return;
            }
        }
        try {
            File::put($path, $content);
            $this->info("Created file: {$path}");
        } catch (\Exception $e) {
            $this->error("Failed to write file {$path}: " . $e->getMessage());
        }
    }

    protected function getReplacements()
    {
        $modelNameSingularLowerCase = Str::lower($this->modelName);
        $modelNamePluralLowerCase = Str::lower(Str::plural($this->modelName));
        $moduleNameKebab = Str::kebab($this->moduleName);

        $rootModuleNamespaceForGeneration = $this->isDryRun ? 'DryRun\\' . $this->moduleName : 'Modules\\' . $this->moduleName;
        $actualModelNamespace = 'Modules\\' . $this->moduleName . '\\Models\\' . $this->modelName;

        return [
            '{{namespace}}' => $rootModuleNamespaceForGeneration,
            '{{moduleName}}' => $this->moduleName,
            '{{modelName}}' => $this->modelName,
            '{{modelFullName}}' => $rootModuleNamespaceForGeneration . '\\Models\\' . $this->modelName,
            '{{actualModelFullName}}' => $actualModelNamespace,
            '{{modelNamePlural}}' => Str::plural($this->modelName),
            '{{modelNameSingularLowerCase}}' => $modelNameSingularLowerCase,
            '{{modelNamePluralLowerCase}}' => $modelNamePluralLowerCase,
            '{{tableName}}' => $this->tableName,
            '{{migrationClassName}}' => 'Create' . Str::studly($this->tableName) . 'Table',
            '{{viewPath}}' => $moduleNameKebab . '::' . Str::kebab(Str::plural($this->modelName)),
            '{{viewDirName}}' => Str::kebab(Str::plural($this->modelName)),
            '{{routeNamePrefix}}' => $moduleNameKebab . '.' . $modelNamePluralLowerCase,
            '{{apiRouteNamePrefix}}' => 'api.' . $moduleNameKebab . '.' . $modelNamePluralLowerCase,
            '{{webRoutePrefix}}' => $modelNamePluralLowerCase,
            '{{apiRoutePrefix}}' => $modelNamePluralLowerCase,
            '{{moduleNamespaceKebab}}' => $moduleNameKebab,
            '{{serviceName}}' => $this->modelName . 'Service',
            '{{serviceInterfaceName}}' => $this->modelName . 'ServiceInterface',
            '{{policyName}}' => $this->modelName . 'Policy',
            '{{observerName}}' => $this->modelName . 'Observer',
            '{{eventNamespace}}' => $rootModuleNamespaceForGeneration . '\\Events',
            '{{modelCreatedEvent}}' => $this->modelName . 'Created',
            '{{modelUpdatedEvent}}' => $this->modelName . 'Updated',
            '{{modelDeletedEvent}}' => $this->modelName . 'Deleted',
            '{{modelVarName}}' => Str::camel($this->modelName),
            '{{modelPluralVarName}}' => Str::plural(Str::camel($this->modelName)),
            '{{serviceVarName}}' => Str::camel($this->modelName . 'Service'),
        ];
    }

    protected function populateStub($stubName, $replacements)
    {
        $stub = $this->getStubContent($stubName);
        if (str_contains($stub, "/* STUB NOT FOUND:")) {
            return $stub;
        }
        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    // --- Database Schema Methods ---
    protected function getTableColumns()
    {
        if ($this->tableColumnsCache !== null) {
            return $this->tableColumnsCache;
        }
        try {
            if (!Schema::hasTable($this->tableName)) {
                $this->alert("Table '{$this->tableName}' does not exist. Cannot infer columns for Model/Request. A new migration will be generated if not a dry run.");
                $this->tableColumnsCache = [];
                return [];
            }
            $this->tableColumnsCache = Schema::getColumnListing($this->tableName);
            return $this->tableColumnsCache;
        } catch (\Exception $e) {
            $this->error("Could not get columns for table '{$this->tableName}': {$e->getMessage()}");
            $this->tableColumnsCache = [];
            return [];
        }
    }

    protected function getColumnDetails($columnName)
    {
        if (isset($this->columnDetailsCache[$columnName])) {
            return $this->columnDetailsCache[$columnName];
        }
        if (!Schema::hasTable($this->tableName) || !Schema::hasColumn($this->tableName, $columnName)) {
            return null;
        }
        try {
            $type = Schema::getColumnType($this->tableName, $columnName);
            $details = (object) [
                'type' => $type,
                'nullable' => true,
                'length' => null,
            ];
            // Attempt to get more details using Doctrine if available
            try {
                $doctrineColumn = Schema::getConnection()->getDoctrineSchemaManager()->listTableDetails($this->tableName)->getColumn($columnName);
                $details->nullable = !$doctrineColumn->getNotnull();
                $details->length = $doctrineColumn->getLength();
            } catch (\Throwable $th) {
                // Doctrine might not be available or fail for some types/drivers
                $this->comment("Note: Could not get detailed column info for '{$columnName}' using Doctrine. Using basic type info.");
            }
            $this->columnDetailsCache[$columnName] = $details;
            return $details;
        } catch (\Exception $e) {
            $this->error("Could not get details for column '{$columnName}': {$e->getMessage()}");
            return null;
        }
    }

    protected function getForeignKeyConstraints(string $tableName): array
    {
        if ($this->foreignKeyConstraintsCache !== null) {
            return $this->foreignKeyConstraintsCache;
        }
        $foreignKeys = [];
        if (!Schema::hasTable($tableName)) {
            $this->foreignKeyConstraintsCache = [];
            return [];
        }
        $dbDriver = DB::connection()->getDriverName();
        try {
            // Using Doctrine for a more unified approach if available
            $schemaManager = DB::connection()->getDoctrineSchemaManager();
            $tableForeignKeys = $schemaManager->listTableForeignKeys($tableName);
            foreach ($tableForeignKeys as $foreignKey) {
                $localColumn = count($foreignKey->getLocalColumns()) === 1 ? $foreignKey->getLocalColumns()[0] : null;
                if ($localColumn) {
                    $foreignKeys[$localColumn] = (object)[
                        'CONSTRAINT_NAME' => $foreignKey->getName(),
                        'COLUMN_NAME' => $localColumn,
                        'REFERENCED_TABLE_SCHEMA' => $foreignKey->getForeignTableName(), // This is table name, schema might not be directly available or needed here
                        'REFERENCED_TABLE_NAME' => $foreignKey->getForeignTableName(),
                        'REFERENCED_COLUMN_NAME' => count($foreignKey->getForeignColumns()) === 1 ? $foreignKey->getForeignColumns()[0] : null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            $this->warn("Doctrine DBAL failed to get foreign keys for '{$tableName}': " . $e->getMessage() . ". Falling back to driver-specific queries.");
            // Fallback to driver-specific queries
            try {
                if ($dbDriver === 'mysql') {
                    $results = DB::select("
                        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_SCHEMA, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
                    ", [DB::getDatabaseName(), $tableName]);
                    foreach ($results as $result) {
                        $foreignKeys[$result->COLUMN_NAME] = $result;
                    }
                } elseif ($dbDriver === 'sqlite') {
                    $results = DB::select("PRAGMA foreign_key_list('{$tableName}')");
                    foreach ($results as $result) {
                        $foreignKeys[$result->from] = (object) [
                            'CONSTRAINT_NAME' => null, 'COLUMN_NAME' => $result->from,
                            'REFERENCED_TABLE_SCHEMA' => null, 'REFERENCED_TABLE_NAME' => $result->table,
                            'REFERENCED_COLUMN_NAME' => $result->to,
                        ];
                    }
                } elseif ($dbDriver === 'pgsql') {
                    $results = DB::select(
                        "
                        SELECT tc.constraint_name AS CONSTRAINT_NAME, kcu.column_name AS COLUMN_NAME,
                               ccu.table_schema AS REFERENCED_TABLE_SCHEMA, ccu.table_name AS REFERENCED_TABLE_NAME,
                               ccu.column_name AS REFERENCED_COLUMN_NAME
                        FROM information_schema.table_constraints AS tc
                        JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
                        JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
                        WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name=? AND tc.table_schema=?",
                        [$tableName, DB::connection()->getConfig('schema') ?? 'public']
                    );
                    foreach ($results as $result) {
                        $foreignKeys[$result->column_name] = $result;
                    }
                } else {
                    $this->comment("Foreign key constraint detection for '{$dbDriver}' fallback is not fully implemented.");
                }
            } catch (\Exception $e2) {
                $this->error("Fallback foreign key retrieval also failed for '{$tableName}' ({$dbDriver}): " . $e2->getMessage());
            }
        }
        $this->foreignKeyConstraintsCache = $foreignKeys;
        return $foreignKeys;
    }

    // --- Migration Generation ---
    protected function generateMigrationFile()
    {
        if ($this->isDryRun) {
            $this->line("<fg=cyan>[Dry Run] Skipping actual migration file generation for table '{$this->tableName}'.</>");
            if (!Schema::hasTable($this->tableName)) {
                $this->line("<fg=cyan>  If not a dry run, a 'create' migration would be generated.</>");
            } else {
                $this->line("<fg=cyan>  Table '{$this->tableName}' exists; 'create' migration would be skipped.</>");
            }
            return;
        }

        if (Schema::hasTable($this->tableName)) {
            $this->comment("Table '{$this->tableName}' already exists. Skipping creation of a 'create' migration file.");
            return;
        }

        $migrationStubContent = $this->getStubContent('migration.create');
        if (str_contains($migrationStubContent, "/* STUB NOT FOUND:")) {
            $this->error("Cannot generate migration file because 'migration.create.stub' is missing or contains errors.");
            return;
        }

        $replacements = $this->getReplacements(); // Includes {{tableName}} and {{migrationClassName}}
        // {{schemaUp}} is part of the stub itself with example columns

        $migrationName = 'create_' . $this->tableName . '_table';
        // Ensure filename is unique enough for multiple generations if needed, though date prefix handles this.
        $fileName = date('Y_m_d_His') . '_' . $migrationName . '.php';
        $filePath = $this->basePath . '/database/migrations/' . $fileName;

        $content = $this->populateStub('migration.create', $replacements);
        $this->writeFile($filePath, $content);
        $this->migrationGenerated = true; // Set flag
    }


    // --- Model Generation ---
    protected function generateModel()
    {
        $columns = $this->getTableColumns(); // Call this after potential migration generation
        $foreignKeyData = $this->getForeignKeyConstraints($this->tableName);
        $replacementsGlobal = $this->getReplacements();
        $currentGeneratingModelModuleNamespace = $replacementsGlobal['{{namespace}}'];

        $uses = ["use Illuminate\\Database\\Eloquent\\SoftDeletes;"];
        // $uses[] = "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;";

        $fillable = $this->generateModelFillable($columns);
        $casts = $this->generateModelCasts($columns);
        $dates = ["'deleted_at'"];

        $relationships = $this->generateModelBelongsTo($columns, $foreignKeyData, $uses, $currentGeneratingModelModuleNamespace);
        $this->generateModelOtherRelations($relationships, $uses, $currentGeneratingModelModuleNamespace);

        $dispatchesEvents = $this->generateModelDispatchesEvents($uses, $replacementsGlobal);

        $replacements = $replacementsGlobal + [
            '{{uses}}' => implode("\n", array_unique($uses)),
            '{{fillableProperties}}' => empty($fillable) ? "protected \$guarded = ['id'];" : implode(",\n        ", array_unique($fillable)) . "\n    ",
            '{{casts}}' => empty($casts) ? "" : implode(",\n        ", array_unique($casts)) . "\n    ",
            '{{dates}}' => empty($dates) ? "" : implode(",\n        ", array_unique($dates)) . "\n    ", // Laravel handles 'deleted_at' in $dates if SoftDeletes is used.
            '{{relationships}}' => implode("\n\n    ", $relationships),
            '{{dispatchesEvents}}' => $dispatchesEvents,
        ];
        $content = $this->populateStub('model', $replacements);
        $this->writeFile($this->basePath . '/Models/' . $this->modelName . '.php', $content);
    }

    protected function generateModelFillable(array $columns): array
    {
        $fillable = [];
        foreach ($columns as $columnName) {
            if (!in_array($columnName, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $fillable[] = "'{$columnName}'";
            }
        }
        return $fillable;
    }

    protected function generateModelCasts(array $columns): array
    {
        $casts = [];
        foreach ($columns as $columnName) {
            $columnDetail = $this->getColumnDetails($columnName);
            if ($columnDetail) {
                $columnType = $columnDetail->type;
                if (in_array($columnType, ['date', 'datetime', 'timestamp'])) {
                    $casts[] = "'{$columnName}' => 'datetime'";
                } elseif ($columnType === 'json' || $columnType === 'jsonb') {
                    $casts[] = "'{$columnName}' => 'array'";
                } elseif (in_array($columnType, ['boolean', 'bool'])) {
                    $casts[] = "'{$columnName}' => 'boolean'";
                } elseif ($columnType === 'tinyint') {
                    if (!$this->isLikelyTinyIntNotBoolean($columnName)) {
                        $casts[] = "'{$columnName}' => 'boolean'";
                        $this->comment("Column '{$columnName}' is tinyint, cast to boolean. Adjust if it's a small integer.");
                    } else {
                        $casts[] = "'{$columnName}' => 'integer'";
                    }
                } elseif (in_array($columnType, ['integer', 'int', 'smallint', 'mediumint', 'bigint'])) {
                    $casts[] = "'{$columnName}' => 'integer'";
                } elseif (in_array($columnType, ['float', 'double', 'decimal', 'numeric'])) {
                    $casts[] = "'{$columnName}' => 'float'";
                }
            }
        }
        return $casts;
    }

    protected function isLikelyTinyIntNotBoolean(string $columnName): bool
    {
        return Str::contains($columnName, ['count', 'quantity', 'level', 'status_code', 'order', 'number', 'age', 'qty', 'type', 'enum', 'status'])
            && !Str::contains($columnName, ['is_', 'has_', '_flag', 'active']);
    }

    protected function generateModelBelongsTo(array $columns, array $foreignKeyData, array &$uses, string $currentModelModuleNamespace): array
    {
        $relationships = [];
        foreach ($columns as $columnName) {
            if (Str::endsWith($columnName, '_id') && $columnName !== 'id') {
                $relationName = Str::camel(Str::beforeLast($columnName, '_id'));
                $relatedModelName = null;
                if (isset($foreignKeyData[$columnName])) {
                    $fkInfo = $foreignKeyData[$columnName];
                    $referencedTable = $fkInfo->REFERENCED_TABLE_NAME ?? $fkInfo->referenced_table_name ?? null;
                    if ($referencedTable) {
                        $relatedModelName = Str::studly(Str::singular($referencedTable));
                    }
                }
                if (!$relatedModelName) {
                    $relatedModelName = Str::studly(Str::singular(str_replace('_id', '', $columnName)));
                }

                if ($relatedModelName) {
                    $relationshipTargetClass = $this->determineRelationshipTargetClass($relatedModelName, $uses, $currentModelModuleNamespace);
                    $relationships[] = "public function {$relationName}()\n    {\n        return \$this->belongsTo({$relationshipTargetClass}, '{$columnName}');\n    }";
                }
            }
        }
        return $relationships;
    }

    protected function generateModelOtherRelations(array &$relationships, array &$uses, string $currentModelModuleNamespaceRoot)
    {
        $allTableNames = $this->getAllTableNames();
        $currentModelForeignKey = Str::snake($this->modelName) . '_id';

        foreach ($allTableNames as $otherTableName) {
            if (strtolower($otherTableName) === strtolower($this->tableName)) {
                continue;
            }

            if (Schema::hasColumn($otherTableName, $currentModelForeignKey)) {
                $relatedModelName = Str::studly(Str::singular($otherTableName));
                $relationBaseName = Str::camel(Str::singular($otherTableName));
                $relationPluralName = Str::camel(Str::plural($otherTableName));
                $relationshipTargetClass = $this->determineRelationshipTargetClass($relatedModelName, $uses, $currentModelModuleNamespaceRoot);

                if (Str::singular($otherTableName) === $otherTableName && $relationBaseName !== $relationPluralName) {
                    $relationships[] = "public function {$relationBaseName}()\n    {\n        return \$this->hasOne({$relationshipTargetClass}, '{$currentModelForeignKey}');\n    }";
                } else {
                    $relationships[] = "public function {$relationPluralName}()\n    {\n        return \$this->hasMany({$relationshipTargetClass}, '{$currentModelForeignKey}');\n    }";
                }
            }

            $pivotParts = explode('_', $otherTableName);
            if (count($pivotParts) >= 2) {
                $currentModelSingularSnake = Str::singular(Str::snake($this->modelName));
                $potentialOtherModelSingularSnake = null;
                $tableParts = collect($pivotParts)->map(fn ($part) => Str::singular($part))->sort()->values();

                if ($tableParts->count() === 2 && $tableParts->contains($currentModelSingularSnake)) {
                    $potentialOtherModelSingularSnake = $tableParts->first(fn ($part) => $part !== $currentModelSingularSnake);
                }

                if ($potentialOtherModelSingularSnake) {
                    $relatedModelName = Str::studly($potentialOtherModelSingularSnake);
                    $fk1 = Str::snake($currentModelSingularSnake) . '_id';
                    $fk2 = Str::snake($potentialOtherModelSingularSnake) . '_id';

                    if (Schema::hasTable($otherTableName) && Schema::hasColumn($otherTableName, $fk1) && Schema::hasColumn($otherTableName, $fk2)) {
                        $relationName = Str::plural(Str::camel($potentialOtherModelSingularSnake));
                        $relationshipTargetClass = $this->determineRelationshipTargetClass($relatedModelName, $uses, $currentModelModuleNamespaceRoot);
                        $relationships[] = "public function {$relationName}()\n    {\n        return \$this->belongsToMany({$relationshipTargetClass}, '{$otherTableName}', '{$fk1}', '{$fk2}');\n    }";
                    }
                }
            }
        }
    }

    protected function determineRelationshipTargetClass(string $relatedModelName, array &$uses, string $currentModelModuleNamespaceRoot): string
    {
        $fqcn = $this->findRelatedModelFQN($relatedModelName, $currentModelModuleNamespaceRoot);
        $currentModelModelsNamespace = $currentModelModuleNamespaceRoot . '\\Models';

        if ($fqcn) {
            if ($fqcn === ($currentModelModelsNamespace . '\\' . $this->modelName)) {
                return "{$this->modelName}::class"; // Self-referencing
            }
            if (str_starts_with($fqcn, $currentModelModelsNamespace . '\\')) {
                return Str::afterLast($fqcn, '\\') . "::class"; // Same module, different model
            }
            $uses[] = "use {$fqcn};";
            return Str::afterLast($fqcn, '\\') . "::class"; // Different namespace
        }

        $this->warn("Model '{$relatedModelName}' not found. Generating placeholder relationship. Create the model or adjust namespace.");
        $useStatementComment = " // WARNING: Model {$relatedModelName} not found. Create or verify namespace.";
        $potentialAppModel = "App\\Models\\{$relatedModelName}";

        if (in_array($relatedModelName, ['User', 'Team', 'Category', 'Tag', 'Role', 'Permission'])) {
            if (class_exists($potentialAppModel)) {
                $uses[] = "use {$potentialAppModel};";
                return "{$relatedModelName}::class";
            }
            $uses[] = "use {$potentialAppModel};{$useStatementComment}";
            return "{$relatedModelName}::class";
        }
        // Default to assuming it's in the current module's Models directory or a sibling, needs 'use'
        $potentialModuleModelFQN = $currentModelModelsNamespace . "\\{$relatedModelName}";
        $uses[] = "use {$potentialModuleModelFQN};{$useStatementComment}";
        return "{$relatedModelName}::class";
    }


    protected function findRelatedModelFQN($relatedModelName, $currentModelModuleNamespaceRoot)
    {
        $currentModuleModelsNamespace = $currentModelModuleNamespaceRoot . '\\Models';
        if ($relatedModelName === $this->modelName && $currentModelModuleNamespaceRoot === ($this->isDryRun ? 'DryRun\\' . $this->moduleName : 'Modules\\' . $this->moduleName)) {
            return "{$currentModuleModelsNamespace}\\{$relatedModelName}";
        }
        $potentialFQN = "{$currentModuleModelsNamespace}\\{$relatedModelName}";
        if (class_exists($potentialFQN)) {
            return $potentialFQN;
        }

        $potentialFQN = "App\\Models\\{$relatedModelName}";
        if (class_exists($potentialFQN)) {
            return $potentialFQN;
        }

        $allActualModules = File::isDirectory(base_path('Modules')) ? File::glob(base_path('Modules/*'), GLOB_ONLYDIR) : [];
        foreach ($allActualModules as $modulePath) {
            $module = basename($modulePath);
            if ($module === $this->moduleName && !$this->isDryRun) {
                continue;
            }
            $potentialFQN = "Modules\\{$module}\\Models\\{$relatedModelName}";
            if (class_exists($potentialFQN)) {
                return $potentialFQN;
            }
        }

        if ($this->isDryRun) {
            $allDryRunModules = File::isDirectory(base_path('DryRun')) ? File::glob(base_path('DryRun/*'), GLOB_ONLYDIR) : [];
            foreach ($allDryRunModules as $modulePath) {
                $module = basename($modulePath);
                if ($module === $this->moduleName) {
                    continue;
                }
                $potentialFQN = "DryRun\\{$module}\\Models\\{$relatedModelName}";
                if (class_exists($potentialFQN)) {
                    return $potentialFQN;
                }
            }
        }
        return null;
    }

    protected function getAllTableNames(): array
    {
        $allTableNames = [];
        try {
            $schemaManager = DB::connection()->getDoctrineSchemaManager();
            $tables = $schemaManager->listTableNames();
            foreach ($tables as $tableName) {
                if (strtolower($tableName) !== strtolower($this->tableName)) {
                    $allTableNames[] = $tableName;
                }
            }
        } catch (\Throwable $e) {
            $this->warn("Doctrine DBAL failed to list tables: " . $e->getMessage() . ". Falling back to driver-specific queries.");
            $dbDriver = DB::connection()->getDriverName();
            try {
                if ($dbDriver === 'mysql') {
                    $tablesResult = DB::select('SHOW TABLES');
                    $dbNameKey = 'Tables_in_' . DB::getDatabaseName();
                    foreach ($tablesResult as $table) {
                        if (strtolower($table->$dbNameKey) !== strtolower($this->tableName)) {
                            $allTableNames[] = $table->$dbNameKey;
                        }
                    }
                } elseif ($dbDriver === 'sqlite') {
                    $tablesResult = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';");
                    foreach ($tablesResult as $table) {
                        if (strtolower($table->name) !== strtolower($this->tableName)) {
                            $allTableNames[] = $table->name;
                        }
                    }
                } elseif ($dbDriver === 'pgsql') {
                    $tablesResult = DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema';");
                    foreach ($tablesResult as $table) {
                        if (strtolower($table->tablename) !== strtolower($this->tableName)) {
                            $allTableNames[] = $table->tablename;
                        }
                    }
                } else {
                    $this->warn("Fallback table listing for '{$dbDriver}' is limited.");
                }
            } catch (\Exception $e2) {
                $this->error("Fallback table listing also failed: " . $e2->getMessage());
            }
        }
        return $allTableNames;
    }


    protected function generateModelDispatchesEvents(array &$uses, array $replacementsGlobal): string
    {
        if (!$this->generateEvents) {
            return '';
        }
        $eventReplacements = $replacementsGlobal;
        $dispatchesEventsLines = [
            "'created' => ".$eventReplacements['{{modelCreatedEvent}}']."::class",
            "'updated' => ".$eventReplacements['{{modelUpdatedEvent}}']."::class",
            "'deleted' => ".$eventReplacements['{{modelDeletedEvent}}']."::class",
        ];
        $uses[] = "use {$eventReplacements['{{eventNamespace}}']}\\{$eventReplacements['{{modelCreatedEvent}}']};";
        $uses[] = "use {$eventReplacements['{{eventNamespace}}']}\\{$eventReplacements['{{modelUpdatedEvent}}']};";
        $uses[] = "use {$eventReplacements['{{eventNamespace}}']}\\{$eventReplacements['{{modelDeletedEvent}}']};";
        return "protected \$dispatchesEvents = [\n        " . implode(",\n        ", $dispatchesEventsLines) . "\n    ];";
    }

    // --- Request Generation ---
    protected function generateRequest()
    {
        $columns = $this->getTableColumns();
        $rulesArray = [];
        $excludedColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];

        if (!empty($columns)) {
            foreach ($columns as $column) {
                if (in_array($column, $excludedColumns)) {
                    continue;
                }
                $rulesArray[] = $this->generateValidationRuleForColumn($column);
            }
        } else {
            $rulesArray = ["// Example: 'title' => 'required|string|max:255',"];
            if (!$this->migrationGenerated) { // Only comment if we didn't just create a migration for it
                $this->comment("No table columns found for '{$this->tableName}'. Request validation rules will be basic.");
            }
        }

        $replacements = $this->getReplacements() + ['{{validationRules}}' => implode(",\n            ", $rulesArray)];
        $content = $this->populateStub('request', $replacements);
        $this->writeFile($this->basePath . '/Http/Requests/' . $this->modelName . 'Request.php', $content);
    }

    protected function generateValidationRuleForColumn(string $column): string
    {
        $columnRules = [];
        $columnDetail = $this->getColumnDetails($column);
        $type = $columnDetail ? $columnDetail->type : 'string';
        $isNullable = $columnDetail ? ($columnDetail->nullable ?? true) : true;

        $columnRules[] = $isNullable ? 'nullable' : 'required';

        switch ($type) {
            case 'string': case 'text': case 'char': case 'varchar': case 'mediumtext': case 'longtext':
                $columnRules[] = 'string';
                if (!in_array($type, ['text', 'mediumtext', 'longtext'])) {
                    $maxLength = $columnDetail && $columnDetail->length ? $columnDetail->length : 255;
                    $columnRules[] = 'max:' . $maxLength;
                }
                break;
            case 'integer': case 'bigint': case 'smallint': case 'mediumint': case 'tinyint':
                $columnRules[] = 'integer';
                break;
            case 'decimal': case 'float': case 'double': case 'numeric':
                $columnRules[] = 'numeric';
                break;
            case 'boolean': case 'bool':
                $columnRules[] = 'boolean';
                break;
            case 'date': $columnRules[] = 'date';
                break;
            case 'datetime': case 'timestamp': $columnRules[] = 'date';
                break;
            case 'json': case 'jsonb': $columnRules[] = 'array';
                break; // Laravel 9+ prefers 'array' for json validation
            default: $columnRules[] = 'string';
                break;
        }

        if (in_array($column, ['email', 'slug', 'username', 'uuid']) || Str::endsWith($column, '_uuid')) {
            $modelVarName = $this->getReplacements()['{{modelVarName}}'];
            $uniqueRule = "unique:{$this->tableName},{$column}";
            $routeParam = $modelVarName;
            $uniqueRule .= ",' . (\$this->route('{$routeParam}') ? \$this->route('{$routeParam}')->id : 'NULL') . ',id";
            $columnRules[] = $uniqueRule;
        }
        return "'{$column}' => '" . implode('|', $columnRules) . "'";
    }

    // --- Service, Policy, Events, Observer Generation ---
    protected function generateServiceClasses()
    {
        $replacements = $this->getReplacements();
        $this->writeFile($this->basePath . '/Contracts/' . $this->modelName . 'ServiceInterface.php', $this->populateStub('service.interface', $replacements));
        $this->writeFile($this->basePath . '/Services/' . $this->modelName . 'Service.php', $this->populateStub('service', $replacements));
    }

    protected function generatePolicyClass()
    {
        $replacements = $this->getReplacements();
        $this->writeFile($this->basePath . '/Policies/' . $this->modelName . 'Policy.php', $this->populateStub('policy', $replacements));
    }

    protected function generateEventClasses()
    {
        $eventTypes = ['model_created', 'model_updated', 'model_deleted'];
        foreach ($eventTypes as $eventTypeKey) {
            $replacements = $this->getReplacements();
            // Correctly construct the placeholder key to match getReplacements()
            // e.g., for 'model_created', this becomes '{{modelCreatedEvent}}'
            $eventClassNamePlaceholderKey = '{{model' . Str::studly(str_replace('model_', '', $eventTypeKey)) . 'Event}}';

            // Get the actual event class name (e.g., PostCreated) from the replacements array
            if (!isset($replacements[$eventClassNamePlaceholderKey])) {
                $this->error("Placeholder key '{$eventClassNamePlaceholderKey}' not found in replacements array. Skipping event generation for {$eventTypeKey}.");
                continue;
            }
            $actualEventClassName = $replacements[$eventClassNamePlaceholderKey];

            // The stub should be named like 'event.model_created.stub'
            // The stub itself uses placeholders like {{modelCreatedEvent}} for the class name, which is correct.
            $content = $this->populateStub('event.' . $eventTypeKey, $replacements);
            if (!str_contains($content, "/* STUB NOT FOUND:")) {
                $this->writeFile($this->basePath . '/Events/' . $actualEventClassName . '.php', $content);
            } else {
                $this->error("Stub for event '{$eventTypeKey}' not found. Skipping generation of {$actualEventClassName}.php");
            }
        }
    }

    protected function generateObserverClass()
    {
        $replacements = $this->getReplacements();
        $content = $this->populateStub('observer', $replacements);
        $filePath = $this->basePath . '/Observers/' . $this->modelName . 'Observer.php';
        $this->writeFile($filePath, $content);
    }

    // --- Controller and Resource Generation ---
    protected function generateWebController()
    {
        $replacementsGlobal = $this->getReplacements();
        $modelFQN = $replacementsGlobal['{{modelFullName}}'];
        $serviceSpecifics = $this->getServiceSpecificReplacements($replacementsGlobal);

        $replacements = array_merge($replacementsGlobal, $serviceSpecifics, [
            '{{serviceCallGetAll}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->getAllPaginated(\$request->all())" : "\\{$modelFQN}::latest()->paginate(10)",
            '{{serviceCallGetById}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->getById(\${$replacementsGlobal['{{modelVarName}}']}->id)" : "\${$replacementsGlobal['{{modelVarName}}']}",
            '{{serviceCallCreate}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->create(\$request->validated())" : "\\{$modelFQN}::create(\$request->validated())",
            '{{serviceCallUpdate}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->update(\${$replacementsGlobal['{{modelVarName}}']}->id, \$request->validated())" : "\${$replacementsGlobal['{{modelVarName}}']}->update(\$request->validated())",
            '{{serviceCallDelete}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->delete(\${$replacementsGlobal['{{modelVarName}}']}->id)" : "\${$replacementsGlobal['{{modelVarName}}']}->delete()",
            '{{serviceCallRestore}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->restore(\$id)" : "\\{$modelFQN}::withTrashed()->find(\$id)?->restore()",
            '{{serviceCallForceDelete}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->forceDelete(\$id)" : "\\{$modelFQN}::withTrashed()->find(\$id)?->forceDelete()",
            '{{serviceCallGetDataForDataTable}}' => $this->generateService
                ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->getDataForDataTable(\$request->all())"
                : "['data'=>[], 'recordsTotal'=>0, 'recordsFiltered'=>0]; // Service not generated. Implement {$modelFQN} query for DataTables.",
        ]);
        $content = $this->populateStub('controller.web', $replacements);
        $this->writeFile($this->basePath . '/Http/Controllers/Web/' . $this->modelName . 'Controller.php', $content);
    }

    protected function generateApiController()
    {
        $replacementsGlobal = $this->getReplacements();
        $modelFQN = $replacementsGlobal['{{modelFullName}}'];
        $serviceSpecifics = $this->getServiceSpecificReplacements($replacementsGlobal);

        $replacements = array_merge($replacementsGlobal, $serviceSpecifics, [
            '{{serviceCallGetAllApi}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->getAllPaginated(\$request->all(), \$request->input('per_page', 15))" : "\\{$modelFQN}::latest()->paginate(\$request->input('per_page', 15))",
            '{{serviceCallGetByIdApi}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->getById(\${$replacementsGlobal['{{modelVarName}}']}->id)" : "\${$replacementsGlobal['{{modelVarName}}']}",
            '{{serviceCallCreateApi}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->create(\$request->validated())" : "\\{$modelFQN}::create(\$request->validated())",
            '{{serviceCallUpdateApi}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->update(\${$replacementsGlobal['{{modelVarName}}']}->id, \$request->validated())" : "\${$replacementsGlobal['{{modelVarName}}']}->update(\$request->validated())",
            '{{serviceCallDeleteApi}}' => $this->generateService ? "\$this->".$replacementsGlobal['{{serviceVarName}}']."->delete(\${$replacementsGlobal['{{modelVarName}}']}->id)" : "\${$replacementsGlobal['{{modelVarName}}']}->delete()",
        ]);
        $content = $this->populateStub('controller.api', $replacements);
        $this->writeFile($this->basePath . '/Http/Controllers/Api/' . $this->modelName . 'Controller.php', $content);
    }

    protected function getServiceSpecificReplacements(array $replacementsGlobal): array
    {
        if (!$this->generateService) {
            return ['{{useService}}' => '', '{{serviceVariable}}' => '', '{{serviceInjection}}' => '', '{{serviceAssignment}}' => ''];
        }
        $serviceInterfaceFQN = $replacementsGlobal['{{namespace}}'] . '\\Contracts\\' . $replacementsGlobal['{{serviceInterfaceName}}'];
        return [
            '{{useService}}' => "use {$serviceInterfaceFQN};",
            '{{serviceVariable}}' => "protected {$replacementsGlobal['{{serviceInterfaceName}}']} \${$replacementsGlobal['{{serviceVarName}}']};",
            '{{serviceInjection}}' => "{$replacementsGlobal['{{serviceInterfaceName}}']} \${$replacementsGlobal['{{serviceVarName}}']}",
            '{{serviceAssignment}}' => "\$this->{$replacementsGlobal['{{serviceVarName}}']} = \${$replacementsGlobal['{{serviceVarName}}']};",
        ];
    }

    protected function generateApiResource()
    {
        $columns = $this->getTableColumns();
        $resourceFields = [];
        $excludedColumns = ['password', 'remember_token'];

        if (in_array('id', $columns) && !in_array('id', $excludedColumns)) {
            $resourceFields[] = "'id' => \$this->id";
        }

        if (!empty($columns)) {
            foreach ($columns as $column) {
                if ($column === 'id' || in_array($column, $excludedColumns)) {
                    continue;
                }
                if (Str::endsWith($column, '_at') || $column === 'deleted_at') {
                    $resourceFields[] = "'{$column}' => \$this->whenNotNull(\$this->{$column} ? \$this->{$column}->toIso8601String() : null)";
                } else {
                    $resourceFields[] = "'{$column}' => \$this->whenNotNull(\$this->{$column})";
                }
            }
        } else {
            $resourceFields[] = "// Example: 'name' => \$this->name,";
            if (!$this->migrationGenerated) {
                $this->comment("No table columns found for '{$this->tableName}'. API Resource fields will be basic.");
            }
        }

        $replacements = $this->getReplacements() + ['{{resourceFields}}' => implode(",\n            ", $resourceFields)];
        $content = $this->populateStub('resource', $replacements);
        $this->writeFile($this->basePath . '/Http/Resources/' . $this->modelName . 'Resource.php', $content);
    }

    // --- View Generation ---
    protected function generateViews()
    {
        $replacementsGlobal = $this->getReplacements();
        $viewDirName = $replacementsGlobal['{{viewDirName}}'];
        $viewPathBase = $this->basePath . '/views/' . $viewDirName;

        $columns = $this->getTableColumns();
        $excludedColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];

        $formFieldsString = $this->generateFormFieldsString($columns, $excludedColumns, $replacementsGlobal['{{modelVarName}}']);
        $tableHeaders = $this->generateTableHeaders($columns, $excludedColumns);
        $viewFieldsForShow = $this->generateShowViewFields($columns, $excludedColumns);

        $actionColumnContent = $this->populateStub('views/actions.blade', $replacementsGlobal + ['itemVar' => '$item']);
        if (str_contains($actionColumnContent, "/* STUB NOT FOUND:")) {
            $this->warn("Actions column stub (views/actions.blade.stub) not found. Table rows will not have action buttons.");
            $actionColumnContent = "";
        }

        $viewReplacements = $replacementsGlobal + [
            '{{formFields}}' => rtrim($formFieldsString),
            '{{tableHeaders}}' => rtrim($tableHeaders),
            '{{viewFieldsForShowArray}}' => empty($viewFieldsForShow) ? "[]" : "['" . implode("', '", $viewFieldsForShow) . "']",
            '{{actionColumnStub}}' => $actionColumnContent,
        ];

        $this->writeFile($viewPathBase . '/_form.blade.php', $this->populateStub('views/_form.blade', $viewReplacements));

        foreach (['index', 'create', 'edit', 'show', 'trashed'] as $view) {
            $stubPath = 'views/' . $view . '.blade';
            $content = $this->populateStub($stubPath, $viewReplacements);
            if (!str_contains($content, "/* STUB NOT FOUND:")) {
                $this->writeFile($viewPathBase . '/' . $view . '.blade.php', $content);
            } else {
                $this->alert("View stub for '{$view}' not found. Skipping {$view}.blade.php.");
            }
        }
    }

    protected function generateFormFieldsString(array $columns, array $excludedColumns, string $modelVarName): string
    {
        $formFieldsString = '';
        if (empty($columns) && !$this->migrationGenerated) { // If migration was just generated, columns will be empty but that's expected
            return "<p class='text-muted'>No table schema found for '{$this->tableName}'. Add form fields manually or ensure migration defines columns.</p>";
        }
        if (empty($columns) && $this->migrationGenerated) {
            return "<p class='text-info'>Table '{$this->tableName}' migration generated. Please define columns in the migration and then update this form, or re-run the command after migrating if you want fields based on schema.</p>";
        }


        foreach ($columns as $columnName) {
            if (in_array($columnName, $excludedColumns)) {
                continue;
            }
            $label = Str::title(str_replace('_', ' ', $columnName));
            $fieldType = $this->determineFieldType($columnName);
            $fieldStubContent = $this->getStubContent("fields/{$fieldType}");

            if (str_contains($fieldStubContent, "/* STUB NOT FOUND:")) {
                $fieldStubContent = $this->getStubContent("fields/text"); // Fallback to text
                $this->warn("Stub for field type '{$fieldType}' (column '{$columnName}') not found. Using text input.");
            }

            $fieldReplacements = [
                '{{fieldName}}' => $columnName,
                '{{fieldLabel}}' => $label,
                '{{fieldValue}}' => "{{ old('{$columnName}', \${$modelVarName}->{$columnName} ?? '') }}",
                '{{modelVarName}}' => $modelVarName,
                '{{options}}' => $this->getOptionsForSelect($columnName, $modelVarName),
            ];
            $formFieldsString .= str_replace(array_keys($fieldReplacements), array_values($fieldReplacements), $fieldStubContent) . "\n";
        }
        return $formFieldsString;
    }

    protected function generateTableHeaders(array $columns, array $excludedColumns): string
    {
        $tableHeaders = "<th>ID</th>\n                    ";
        if (empty($columns) && !$this->migrationGenerated) {
            return "<th>Example Header</th>";
        }
        if (empty($columns) && $this->migrationGenerated) {
            return "<th>ID</th><th>Name (Example)</th><th>Created At</th>";
        }


        foreach ($columns as $columnName) {
            if (in_array($columnName, $excludedColumns) || $columnName === 'id') {
                continue;
            }
            $label = Str::title(str_replace('_', ' ', $columnName));
            $tableHeaders .= "<th>{$label}</th>\n                    ";
        }
        return $tableHeaders;
    }

    protected function generateShowViewFields(array $columns, array $excludedColumns): array
    {
        $viewFieldsForShow = [];
        if (empty($columns) && !$this->migrationGenerated) {
            return [];
        }
        if (empty($columns) && $this->migrationGenerated) {
            return ['id', 'name' /* example */, 'created_at', 'updated_at'];
        }


        if (!in_array('id', $excludedColumns)) {
            $viewFieldsForShow[] = 'id';
        }
        foreach ($columns as $columnName) {
            if (in_array($columnName, $excludedColumns) || $columnName === 'id') {
                continue;
            }
            $viewFieldsForShow[] = $columnName;
        }
        return $viewFieldsForShow;
    }


    protected function determineFieldType($columnName)
    {
        if (Str::endsWith($columnName, '_id') && $columnName !== 'id') {
            return 'select';
        }
        if (Str::contains($columnName, ['description', 'notes', 'content', 'details', 'message', 'bio', 'summary', 'body'])) {
            return 'textarea';
        }
        if (Str::contains($columnName, 'email')) {
            return 'email';
        }
        if (Str::contains($columnName, 'password')) {
            return 'password';
        }
        if (Str::startsWith($columnName, 'is_') || Str::startsWith($columnName, 'has_') || Str::endsWith($columnName, '_flag') || $columnName === 'active') {
            return 'checkbox';
        }
        if (Str::contains($columnName, ['image', 'avatar', 'logo', 'file', 'document', 'attachment', 'photo', 'picture'])) {
            return 'file';
        }
        if (Str::contains($columnName, ['url', 'link', 'website'])) {
            return 'url';
        }
        if (Str::contains($columnName, ['phone', 'mobile', 'tel', 'fax'])) {
            return 'tel';
        }
        if (Str::contains($columnName, ['color', 'hexcode'])) {
            return 'color';
        }
        if (Str::contains($columnName, ['_date', 'dated_on', 'birth_date'])) {
            return 'date';
        }
        if (Str::contains($columnName, ['_at', 'timestamp', 'published_at', 'created_at', 'updated_at'])) {
            return 'datetime';
        }

        $dbType = null;
        if (Schema::hasTable($this->tableName) && Schema::hasColumn($this->tableName, $columnName)) {
            try {
                $dbType = Schema::getColumnType($this->tableName, $columnName);
            } catch (\Exception $e) { /* ignore */
            }
        }
        if ($dbType) {
            if (in_array($dbType, ['text', 'mediumtext', 'longtext'])) {
                return 'textarea';
            }
            if ($dbType === 'date') {
                return 'date';
            }
            if (in_array($dbType, ['datetime', 'timestamp'])) {
                return 'datetime';
            }
            if (in_array($dbType, ['boolean', 'bool'])) {
                return 'checkbox';
            }
            if ($dbType === 'tinyint' && !$this->isLikelyTinyIntNotBoolean($columnName)) {
                return 'checkbox';
            }
            if ($dbType === 'json' || $dbType === 'jsonb') {
                return 'textarea';
            }
        }
        return 'text';
    }

    protected function getOptionsForSelect($columnName, $modelVarName)
    {
        if (Str::endsWith($columnName, '_id')) {
            $relatedModelNameGuess = Str::studly(Str::singular(str_replace('_id', '', $columnName)));
            $relatedPluralVar = Str::plural(Str::camel($relatedModelNameGuess));
            $displayNameOptions = ['name', 'title', 'label', 'username', 'email']; // Common display attributes

            $optionsHtml = "<option value=\"\">-- Select {$relatedModelNameGuess} --</option>\n";
            $optionsHtml .= "                    @foreach(\${$relatedPluralVar} ?? [] as \$relatedItem) "; // Assume $relatedPluralVar is passed to the view
            $optionsHtml .= "<option value=\"{{\$relatedItem->id}}\" {{ (old('{$columnName}', \${$modelVarName}->{$columnName} ?? null) == \$relatedItem->id) ? 'selected' : '' }}>";
            $optionsHtml .= "{{ ";
            $conditions = [];
            foreach ($displayNameOptions as $option) {
                $conditions[] = "\$relatedItem->{$option} ??";
            }
            $optionsHtml .= implode(" ", $conditions) . " \$relatedItem->id "; // Fallback to ID
            $optionsHtml .= "}}</option> @endforeach";
            return $optionsHtml;
        }
        return '';
    }

    // --- Routes and Service Provider ---
    protected function generateRoutes()
    {
        $replacements = $this->getReplacements();
        $this->writeFile($this->basePath . '/routes/web.php', $this->populateStub('routes.web', $replacements));
        if ($this->createApi) {
            $this->writeFile($this->basePath . '/routes/api.php', $this->populateStub('routes.api', $replacements));
        }
    }

    protected function generateServiceProvider()
    {
        $replacementsGlobal = $this->getReplacements();
        $generatedNamespaceRoot = $replacementsGlobal['{{namespace}}'];

        $policiesArray = [];
        $bindingsArray = [];
        $observersArray = [];
        $loadMigrationsLine = '';

        if ($this->generatePolicy) {
            $policiesArray[] = "\\{$replacementsGlobal['{{actualModelFullName}}']}::class => \\{$generatedNamespaceRoot}\\Policies\\".$this->modelName."Policy::class,";
        }
        if ($this->generateService) {
            $serviceInterfaceFQN = $generatedNamespaceRoot . '\\Contracts\\' . $replacementsGlobal['{{serviceInterfaceName}}'];
            $serviceImplementationFQN = $generatedNamespaceRoot . '\\Services\\' . $replacementsGlobal['{{serviceName}}'];
            $bindingsArray[] = "\$this->app->bind(\\{$serviceInterfaceFQN}::class, \\{$serviceImplementationFQN}::class);";
        }
        if ($this->generateObserver) {
            $observersArray[] = "\\{$replacementsGlobal['{{actualModelFullName}}']}::class => [\\{$generatedNamespaceRoot}\\Observers\\".$this->modelName."Observer::class],";
        }

        // Always add loadMigrationsFrom if the directory structure is created.
        // The command ensures `database/migrations` directory exists.
        $loadMigrationsLine = "\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');";


        $replacements = $replacementsGlobal + [
            '{{hasApiRoutes}}' => $this->createApi ? 'true' : 'false',
            '{{hasWebRoutes}}' => 'true',
            '{{policiesForProvider}}' => empty($policiesArray) ? "// No policies to register." : implode("\n        ", $policiesArray),
            '{{bindingsForProvider}}' => empty($bindingsArray) ? "// No service bindings." : implode("\n        ", $bindingsArray),
            '{{observersForProvider}}' => empty($observersArray) ? "// No observers to register." : implode("\n        ", $observersArray),
            '{{loadMigrations}}' => $loadMigrationsLine,
        ];
        $this->writeFile($this->basePath . '/Providers/' . $this->moduleName . 'ServiceProvider.php', $this->populateStub('provider', $replacements));
    }


    // --- Auto Registration & Post Commands ---
    protected function attemptAutoRegisterServiceProvider()
    {
        if ($this->isDryRun) {
            $this->line("<fg=cyan>[Dry Run] Skipping automatic Service Provider registration.</>");
            // ... (rest of dry run message as before)
            return;
        }

        $bootstrapProvidersPath = base_path('bootstrap/providers.php');
        $configAppPath = config_path('app.php');
        $actualModuleNamespace = 'Modules\\' . $this->moduleName;
        $serviceProviderClass = "{$actualModuleNamespace}\\Providers\\{$this->moduleName}ServiceProvider";

        if (File::exists($bootstrapProvidersPath)) {
            $this->line("<fg=blue>Attempting Service Provider registration in bootstrap/providers.php (Laravel 11+ style)...</>");
            $this->registerServiceProviderInBootstrapProviders($serviceProviderClass, $bootstrapProvidersPath);
        } elseif (File::exists($configAppPath)) {
            $this->line("<fg=blue>bootstrap/providers.php not found. Attempting Service Provider registration in config/app.php (older style)...</>");
            $this->registerServiceProviderInConfig($serviceProviderClass, $configAppPath);
        } else {
            $this->error("Neither bootstrap/providers.php nor config/app.php found. Cannot auto-register Service Provider.");
            $this->line("Please register '{$serviceProviderClass}' manually.");
        }
    }

    protected function registerServiceProviderInBootstrapProviders(string $serviceProviderClass, string $filePath)
    {
        if (!File::exists($filePath)) {
            $this->error("File {$filePath} not found for SP registration.");
            return false;
        }
        $content = File::get($filePath);
        $serviceProviderClassEscaped = str_replace('\\', '\\\\', $serviceProviderClass);

        if (Str::contains($content, $serviceProviderClassEscaped . '::class')) {
            $this->comment("Service Provider '{$serviceProviderClass}' already registered in {$filePath}.");
            return true;
        }

        $newProviderLine = "    {$serviceProviderClass}::class,";
        $lines = explode("\n", $content);
        $insertAtIndex = -1;

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (trim($lines[$i]) === '];') {
                $insertAtIndex = $i;
                break;
            }
        }

        if ($insertAtIndex !== -1) {
            if ($insertAtIndex > 0 && Str::contains($lines[$insertAtIndex - 1], '::class') && !Str::endsWith(rtrim($lines[$insertAtIndex - 1]), ',')) {
                $lines[$insertAtIndex - 1] = rtrim($lines[$insertAtIndex - 1]) . ',';
            }
            array_splice($lines, $insertAtIndex, 0, [$newProviderLine]);
            $newContent = implode("\n", $lines);

            if (File::put($filePath, $newContent)) {
                $this->info("Service Provider '{$serviceProviderClass}' was successfully added to {$filePath}.");
                $this->line("Please <fg=yellow;options=bold>verify the registration</> and formatting.");
                return true;
            } else {
                $this->error("Failed to write to {$filePath}. Please check permissions and register '{$serviceProviderClass}' manually.");
                return false;
            }
        } else {
            $this->error("Could not find a clear insertion point in {$filePath}. Please register '{$serviceProviderClass}' manually.");
            return false;
        }
    }

    protected function registerServiceProviderInConfig(string $serviceProviderClass, string $filePath)
    {
        if (!File::exists($filePath)) {
            $this->error("File {$filePath} not found for SP registration.");
            return false;
        }
        $content = File::get($filePath);
        $serviceProviderClassEscaped = str_replace('\\', '\\\\', $serviceProviderClass);

        if (Str::contains($content, $serviceProviderClassEscaped . '::class')) {
            $this->comment("Service Provider '{$serviceProviderClass}' already registered in {$filePath}.");
            return true;
        }

        $newProviderEntry = "        {$serviceProviderClass}::class,";
        $anchorProvider = 'App\\Providers\\RouteServiceProvider::class,';
        $anchorProviderEscaped = str_replace('\\', '\\\\', $anchorProvider);
        $newContent = $content;
        $madeChange = false;

        if (Str::contains($content, $anchorProviderEscaped)) {
            $newContent = Str::replaceFirst($anchorProviderEscaped, $anchorProviderEscaped . "\n" . $newProviderEntry, $content);
            $madeChange = ($newContent !== $content);
        } else {
            $pattern = '/(\'providers\'\s*=>\s*\[\s*)((?:.|\s)*?)(\s*\])/ms';
            if (preg_match($pattern, $content, $matches)) {
                $arrayOpen = $matches[1];
                $arrayContent = rtrim($matches[2]);
                $arrayClose = $matches[3];
                $tempContent = $arrayOpen . $arrayContent;
                if (!empty($arrayContent) && !Str::endsWith($arrayContent, ',')) {
                    $tempContent .= ',';
                }
                $tempContent .= "\n" . $newProviderEntry . "\n" . $arrayClose;
                $newContent = preg_replace($pattern, addcslashes($tempContent, '\\$'), $content, 1);
                $madeChange = ($newContent !== $content && !is_null($newContent));
            } else {
                $this->error("Could not find 'providers' array in {$filePath}.");
            }
        }

        if ($madeChange) {
            if (File::put($filePath, $newContent)) {
                $this->info("Service Provider '{$serviceProviderClass}' was successfully added to {$filePath}.");
                $this->line("Please <fg=yellow;options=bold>verify the registration</> and formatting.");
                return true;
            } else {
                $this->error("Failed to write to {$filePath}. Register '{$serviceProviderClass}' manually.");
                return false;
            }
        } else {
            $this->error("Could not automatically register '{$serviceProviderClass}' in {$filePath}. Please do so manually.");
            return false;
        }
    }

    protected function runPostGenerationCommands()
    {
        /*if ($this->isDryRun) {
            $this->line("<fg=cyan>[Dry Run] Skipping post-generation commands (composer dump-autoload, optimize:clear).</>");
            return;
        }*/

        $this->line('Executing: composer dump-autoload -o');
        $composerProcess = new Process(['composer', 'dump-autoload', '-o']);
        $composerProcess->setWorkingDirectory(base_path());
        try {
            $composerProcess->mustRun();
            $this->info($composerProcess->getOutput());
        } catch (ProcessFailedException $exception) {
            $this->error('Composer dump-autoload failed: ' . $exception->getMessage());
        }

        $this->line('Executing: php artisan optimize:clear');
        try {
            $this->call('optimize:clear');
            $this->info('Cache clearing command executed.');
        } catch (\Exception $e) {
            $this->error('php artisan optimize:clear failed: ' . $e->getMessage());
        }
        $this->info('Post-generation commands completed.');
    }
}
