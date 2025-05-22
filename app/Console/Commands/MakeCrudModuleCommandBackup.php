<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MakeCrudModuleCommandBackup extends Command
{
    protected $signature = 'make:crud-module-backup {name : The name of the module (e.g., Post, ProductCategory)}
                                             {--table= : The name of the database table (optional)}
                                             {--dry-run : Generate files in a "dryrun/{ModuleName}" directory at the project root}
                                             {--no-views : Do not create view files}
                                             {--no-api : Do not create API controller and resource}
                                             {--service : Generate a service class for business logic}
                                             {--policy : Generate a policy class for authorization}
                                             {--events : Generate basic model event classes (Created, Updated, Deleted)}
                                             {--observer : Generate a model observer for audit logging}
                                             {--force : Overwrite existing files without asking}';

    protected $description = 'Create an advanced CRUD module with enhanced relationship detection, field stubs, DataTables, audit logs, and soft deletes.';

    // Properties
    protected $moduleName;
    protected $modelName;
    protected $tableName;
    protected $isDryRun = false;
    protected $createViews = true;
    protected $createApi = true;
    protected $generateService = false;
    protected $generatePolicy = false;
    protected $generateEvents = false;
    protected $generateObserver = false;
    protected $forceOverwrite = false;

    protected $basePath;
    protected $stubsPath;
    protected $actualModulesPath;

    public function handle()
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
        $this->stubsPath = base_path('stubs/crud-module');

        if ($this->isDryRun) {
            $this->basePath = base_path('dryrun/' . $this->moduleName);
            $this->warn("Dry run mode: Files will be generated in: {$this->basePath}");
            if (!File::isDirectory(base_path('dryrun'))) {
                File::makeDirectory(base_path('dryrun'), 0755, true, true);
            }
            if (File::isDirectory($this->basePath) && !$this->forceOverwrite && $this->confirm("Dry-run directory [{$this->basePath}] exists. Clear it?")) {
                File::deleteDirectory($this->basePath);
            }
        } else {
            $this->basePath = $this->actualModulesPath;
            if (!File::isDirectory(base_path('Modules'))) {
                File::makeDirectory(base_path('Modules'), 0755, true, true);
            }
        }

        $this->info("Starting CRUD module: {$this->moduleName} (Model: {$this->modelName}, Table: {$this->tableName}) -> {$this->basePath}");
        if (!File::isDirectory($this->stubsPath)) {
            $this->error("Stubs directory not found: {$this->stubsPath}");
            return 1;
        }
        if (!File::isDirectory($this->stubsPath . '/fields')) {
            $this->warn("Field stubs directory not found: {$this->stubsPath}/fields. Form generation will be basic.");
        }

        $this->generateModuleStructure();
        $this->generateModel(); // Enhanced relationship detection here
        $this->generateRequest();

        if ($this->generateService) {
            $this->generateService();
        }
        if ($this->generatePolicy) {
            $this->generatePolicy();
        }
        if ($this->generateEvents) {
            $this->generateEvents();
        }
        if ($this->generateObserver) {
            $this->generateObserver();
        }

        $this->generateWebController();
        if ($this->createViews) {
            $this->generateViews();
        }
        if ($this->createApi) {
            $this->generateApiController();
            $this->generateResource();
        }
        $this->generateRoutes();
        $this->generateServiceProvider();

        $this->info("CRUD module '{$this->moduleName}' generated successfully in {$this->basePath}.");
        $this->outputAutoloadingAndRegistrationInstructions();
        return 0;
    }

    protected function outputAutoloadingAndRegistrationInstructions()
    {
        $this->newLine(2);
        $this->line("<fg=blue>======================================================================</>");
        $this->line("<fg=blue> MODULE GENERATION SUMMARY & NEXT STEPS FOR '{$this->moduleName}' </>");
        $this->line("<fg=blue>======================================================================</>");
        $this->newLine();

        $dryRunPathInfo = base_path('dryrun/' . $this->moduleName);
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
            $this->newLine();
            $this->line("<fg=gray>(Dry Run would generate to: {$dryRunPathInfo} with namespace {$dryRunNamespaceInfo})</>");
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
            $this->line("<fg=cyan>Then, whether moved or generated directly, follow these setup steps:</>");
            $this->newLine();
        }

        $this->line("<fg=yellow>Step A: Configure PSR-4 Autoloading (if 'Modules/' isn't already)</>");
        $this->line("   In `composer.json`, under `\"autoload\"` -> `\"psr-4\"`, ensure:");
        $this->comment('     "Modules\\\\": "Modules/",');
        $this->comment('     // If you were to use dry-run files directly (not typical for production):');
        $this->comment('     // "DryRun\\\\": "dryrun/",');
        $this->newLine();

        $this->line("<fg=yellow>Step B: Update Composer's Autoloader</>");
        $this->comment("     composer dump-autoload");
        $this->newLine();

        $this->line("<fg=yellow>Step C: Register Service Provider</>");
        $this->line("   In `config/app.php` -> `providers` array, add for the actual module:");
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
            $this->line("   Customize the observer's logging logic as needed (e.g., use a dedicated audit table/package).");
            $this->newLine();
        }
        $this->line("<fg=yellow>Step F: Run Migrations (if new tables were created for this module)</>");
        $this->comment("     php artisan migrate");
        $this->newLine();
        $this->warn("======================================================================");
        $this->newLine();
    }

    protected function generateModuleStructure()
    {
        $paths = [
            $this->basePath . '/Http/Controllers/Web', $this->basePath . '/Http/Controllers/Api',
            $this->basePath . '/Http/Requests', $this->basePath . '/Http/Resources',
            $this->basePath . '/Models', $this->basePath . '/Providers', $this->basePath . '/routes',
             // $this->basePath . '/views/' . Str::kebab(Str::plural($this->modelName)),
            $this->basePath . '/views/',
            $this->basePath . '/database/migrations',
        ];
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
            File::makeDirectory($path, 0755, true, true);
            $this->info("Created directory: {$path}");
        } else {
            $this->comment("Directory already exists: {$path}");
        }
    }

    protected function getStubContent($stubName)
    {
        $stubPath = $this->stubsPath . '/' . $stubName . '.stub';
        if (!File::exists($stubPath)) {
            if (str_starts_with($stubName, 'fields/')) {
                $this->warn("Field stub not found: {$stubPath}. Falling back to basic text input for this field.");
                return File::exists($this->stubsPath . '/fields/text.stub') ? File::get($this->stubsPath . '/fields/text.stub') : "<input type=\"text\" name=\"{{fieldName}}\" value=\"{{fieldValue}}\">";
            }
            if (Str::endsWith($stubName, '.blade')) {
                $stubPath = $this->stubsPath . '/' . $stubName . '.stub';
                if (File::exists($stubPath)) {
                    return File::get($stubPath);
                }
            }
            $this->error("Stub file not found: {$stubPath} (tried for {$stubName})");
            throw new \Exception("Stub not found: {$stubPath}");
        }
        return File::get($stubPath);
    }

    protected function writeFile($path, $content)
    {
        if (File::exists($path) && !$this->forceOverwrite) {
            if (!$this->confirm("The file [{$path}] already exists. Do you want to overwrite it?")) {
                $this->comment("Skipped file: {$path}");
                return;
            }
        }
        File::put($path, $content);
        $this->info("Created file: {$path}");
    }

    protected function getReplacements()
    {
        $modelNameSingularLowerCase = Str::lower($this->modelName);
        $modelNamePluralLowerCase = Str::lower(Str::plural($this->modelName));
        $moduleNameKebab = Str::kebab($this->moduleName);

        if ($this->isDryRun) {
            $rootModuleNamespaceForGeneration = 'DryRun\\' . $this->moduleName;
        } else {
            $rootModuleNamespaceForGeneration = 'Modules\\' . $this->moduleName;
        }
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
            '{{viewPath}}' => $moduleNameKebab . '::' . Str::kebab(Str::plural($this->modelName)),
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
        ];
    }

    protected function populateStub($stubName, $replacements)
    {
        $stub = $this->getStubContent($stubName);
        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function getTableColumns()
    {
        try {
            if (!Schema::hasTable($this->tableName)) {
                $this->warn("Table '{$this->tableName}' does not exist. Cannot infer columns.");
                return [];
            }
            return Schema::getColumnListing($this->tableName);
        } catch (\Exception $e) {
            $this->warn("Could not get columns for table '{$this->tableName}': {$e->getMessage()}");
        }
        return [];
    }

    protected function getColumnDetails($columnName)
    {
        if (!Schema::hasTable($this->tableName)) {
            return null;
        }
        try {
            $type = Schema::getColumnType($this->tableName, $columnName);
            return (object) [
                'type' => $type,
                'nullable' => true,
                'length' => null,
            ];
        } catch (\Exception $e) {
            $this->warn("Could not get details for column '{$columnName}': {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get foreign key constraints for a table.
     * This implementation is for MySQL/MariaDB.
     *
     * @param string $tableName
     * @return array
     */
    protected function getForeignKeyConstraints(string $tableName): array
    {
        $foreignKeys = [];
        $dbDriver = DB::connection()->getDriverName();

        if ($dbDriver === 'mysql') { // Also covers MariaDB
            try {
                $results = DB::select("
                    SELECT
                        CONSTRAINT_NAME,
                        COLUMN_NAME,
                        REFERENCED_TABLE_SCHEMA,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [DB::getDatabaseName(), $tableName]);

                foreach ($results as $result) {
                    $foreignKeys[$result->COLUMN_NAME] = $result;
                }
            } catch (\Exception $e) {
                $this->warn("Could not retrieve foreign key constraints for table '{$tableName}' (MySQL/MariaDB): " . $e->getMessage());
            }
        } elseif ($dbDriver === 'sqlite') {
            try {
                $results = DB::select("PRAGMA foreign_key_list('{$tableName}')");
                foreach ($results as $result) {
                    // SQLite PRAGMA has different column names
                    $foreignKeys[$result->from] = (object) [
                        'CONSTRAINT_NAME' => null, // SQLite doesn't name FKs in this output
                        'COLUMN_NAME' => $result->from,
                        'REFERENCED_TABLE_SCHEMA' => null, // SQLite is file-based
                        'REFERENCED_TABLE_NAME' => $result->table,
                        'REFERENCED_COLUMN_NAME' => $result->to,
                    ];
                }
            } catch (\Exception $e) {
                $this->warn("Could not retrieve foreign key constraints for table '{$tableName}' (SQLite): " . $e->getMessage());
            }
        } else {
            $this->comment("Foreign key constraint detection for '{$dbDriver}' is not explicitly implemented. Falling back to conventions.");
        }
        return $foreignKeys;
    }


    protected function generateModel()
    {
        $columns = $this->getTableColumns();
        $foreignKeyData = $this->getForeignKeyConstraints($this->tableName);

        $fillable = [];
        $casts = [];
        // SoftDeletes trait adds 'deleted_at' to $dates automatically
        $dates = ["'deleted_at'"];
        $relationships = [];
        $uses = ["use Illuminate\\Database\\Eloquent\\SoftDeletes;"];
        // $uses[] = "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;";

        $replacementsGlobal = $this->getReplacements();
        // Namespace where the current model is being generated (e.g., DryRun\Product\Models or Modules\Product\Models)
        $currentGeneratingModelModuleNamespace = $replacementsGlobal['{{namespace}}'];


        if (!empty($columns)) {
            foreach ($columns as $columnName) {
                if (!in_array($columnName, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    $fillable[] = "'{$columnName}'";
                }
                $columnDetail = $this->getColumnDetails($columnName);
                if ($columnDetail) {
                    $columnType = $columnDetail->type;
                    if (in_array($columnType, ['date', 'datetime', 'timestamp'])) {
                        $casts[] = "'{$columnName}' => 'datetime'";
                    } elseif ($columnType === 'json') {
                        $casts[] = "'{$columnName}' => 'array'";
                    } elseif (in_array($columnType, ['boolean', 'tinyint'])) {
                        if ($columnType === 'tinyint') {
                            $this->comment("Column '{$columnName}' is tinyint, assuming boolean for casting. Adjust if needed.");
                        }
                        $casts[] = "'{$columnName}' => 'boolean'";
                    }
                }

                // BelongsTo relationship detection
                if (Str::endsWith($columnName, '_id') && $columnName !== 'id') {
                    $relatedModelName = null;
                    $relationName = Str::camel(Str::beforeLast($columnName, '_id')); // e.g. unitId -> unit

                    if (isset($foreignKeyData[$columnName])) {
                        $fkInfo = $foreignKeyData[$columnName];
                        $referencedTable = $fkInfo->REFERENCED_TABLE_NAME;
                        $relatedModelName = Str::studly(Str::singular($referencedTable));
                        // You might prefer the relation name from the column if it's more descriptive
                        // $relationName = Str::camel(Str::beforeLast($columnName, '_id'));
                    } else {
                        // Fallback to convention if no explicit FK constraint found or readable
                        $relatedModelName = Str::studly(Str::singular(str_replace('_id', '', $columnName)));
                    }

                    if ($relatedModelName) {
                        $relatedModelFQN = $this->findRelatedModelFQN($relatedModelName, $currentGeneratingModelModuleNamespace);
                        $classForUse = $relatedModelName; // Default to simple class name
                        $useStatementComment = "// TODO: Create `{$relatedModelName}` model or ensure it's discoverable.";
                        $relationshipTargetClass = "\\{$currentGeneratingModelModuleNamespace}\\Models\\{$relatedModelName}::class; // {$useStatementComment}";

                        if ($relatedModelFQN) { // Model exists
                            $classForUse = $this->getClassForUse($relatedModelFQN, $currentGeneratingModelModuleNamespace . '\\Models', $uses);
                            $relationshipTargetClass = "{$classForUse}::class";
                        } else { // Model does not exist, generate placeholder
                            // Attempt to guess the most likely namespace for the placeholder
                            $potentialAppModel = "App\\Models\\{$relatedModelName}";
                            $potentialCurrentModuleModel = $currentGeneratingModelModuleNamespace . "\\Models\\{$relatedModelName}";

                            // Prefer App\Models if it's a common model, otherwise current module
                            // This is a heuristic.
                            if (in_array($relatedModelName, ['User', 'Team', 'Category', 'Tag', 'Role', 'Permission'])) { // Common models
                                $uses[] = "// use {$potentialAppModel}; {$useStatementComment}";
                                $relationshipTargetClass = "\\{$potentialAppModel}::class; // {$useStatementComment}";
                            } else {
                                $uses[] = "// use {$potentialCurrentModuleModel}; {$useStatementComment}";
                                $relationshipTargetClass = "\\{$potentialCurrentModuleModel}::class; // {$useStatementComment}";
                            }
                            $this->warn("Model '{$relatedModelName}' for column '{$columnName}' not found. Generated a placeholder relationship.");
                        }
                        $relationships[] = "public function {$relationName}()\n    {\n        return \$this->belongsTo({$relationshipTargetClass}, '{$columnName}');\n    }";
                    }
                }
            }
        }

        $this->detectOtherRelationshipsSimplified($relationships, $uses, $currentGeneratingModelModuleNamespace);

        $dispatchesEvents = '';
        if ($this->generateEvents) {
            $eventReplacements = $this->getReplacements();
            $dispatchesEventsLines = [
                "'created' => ".$eventReplacements['{{modelCreatedEvent}}']."::class",
                "'updated' => ".$eventReplacements['{{modelUpdatedEvent}}']."::class",
                "'deleted' => ".$eventReplacements['{{modelDeletedEvent}}']."::class",
            ];
            $dispatchesEvents = "protected \$dispatchesEvents = [\n        " . implode(",\n        ", $dispatchesEventsLines) . "\n    ];";
            $uses[] = "use {$eventReplacements['{{eventNamespace}}']}\\{$eventReplacements['{{modelCreatedEvent}}']};";
            $uses[] = "use {$eventReplacements['{{eventNamespace}}']}\\{$eventReplacements['{{modelUpdatedEvent}}']};";
            $uses[] = "use {$eventReplacements['{{eventNamespace}}']}\\{$eventReplacements['{{modelDeletedEvent}}']};";
        }

        $replacements = $this->getReplacements() + [
            '{{uses}}' => implode("\n", array_unique($uses)),
            '{{fillableProperties}}' => implode(",\n        ", array_unique($fillable)),
            '{{casts}}' => implode(",\n        ", array_unique($casts)),
            '{{dates}}' => !empty($dates) ? "protected \$dates = [\n        " . implode(",\n        ", array_unique($dates)) . "\n    ];" : '',
            '{{relationships}}' => implode("\n\n    ", $relationships),
            '{{dispatchesEvents}}' => $dispatchesEvents,
        ];
        $content = $this->populateStub('model', $replacements);
        $this->writeFile($this->basePath . '/Models/' . $this->modelName . '.php', $content);
    }

    protected function findRelatedModelFQN($relatedModelName, $currentModelModuleNamespaceRoot)
    {
        $currentModuleModelsNamespace = $currentModelModuleNamespaceRoot . '\\Models';

        if (class_exists("{$currentModuleModelsNamespace}\\{$relatedModelName}")) {
            return "{$currentModuleModelsNamespace}\\{$relatedModelName}";
        }
        if (class_exists("App\\Models\\{$relatedModelName}")) {
            return "App\\Models\\{$relatedModelName}";
        }
        $allActualModules = File::glob(base_path('Modules/*'), GLOB_ONLYDIR);
        foreach ($allActualModules as $modulePath) {
            $module = basename($modulePath);
            if ($module === $this->moduleName) {
                continue;
            }
            if (class_exists("Modules\\{$module}\\Models\\{$relatedModelName}")) {
                return "Modules\\{$module}\\Models\\{$relatedModelName}";
            }
        }
        return null;
    }

    protected function getClassForUse($fqcn, $currentNamespace, &$usesArray)
    {
        if (str_starts_with($fqcn, $currentNamespace . '\\')) {
            return Str::afterLast($fqcn, '\\');
        }
        $usesArray[] = "use {$fqcn};";
        return Str::afterLast($fqcn, '\\');
    }

    protected function detectOtherRelationshipsSimplified(&$relationships, &$uses, $currentGeneratingModelModuleNamespaceRoot)
    {
        $allTableNames = [];
        try {
            $dbDriver = DB::connection()->getDriverName();
            if ($dbDriver === 'mysql') {
                $tables = DB::select('SHOW TABLES');
                $dbNameKey = 'Tables_in_' . DB::getDatabaseName();
                foreach ($tables as $table) {
                    $allTableNames[] = $table->$dbNameKey;
                }
            } elseif ($dbDriver === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';");
                foreach ($tables as $table) {
                    $allTableNames[] = $table->name;
                }
            } else {
                $this->comment("Automatic detection of hasMany/belongsToMany relationships for '{$dbDriver}' is limited. Please review and add manually if needed.");
                return;
            }
        } catch (\Exception $e) {
            $this->warn("Could not list all tables for advanced relationship detection: " . $e->getMessage());
            return;
        }

        $currentModelForeignKey = Str::snake($this->modelName) . '_id';

        foreach ($allTableNames as $otherTableName) {
            if ($otherTableName === $this->tableName) {
                continue;
            }

            // HasMany / HasOne
            if (Schema::hasColumn($otherTableName, $currentModelForeignKey)) {
                $relatedModelName = Str::studly(Str::singular($otherTableName));
                $relationBaseName = Str::camel(Str::singular($otherTableName));
                $useStatementComment = "// TODO: Create `{$relatedModelName}` model or ensure it's discoverable.";
                $relationshipTargetClass = "\\{$currentGeneratingModelModuleNamespaceRoot}\\Models\\{$relatedModelName}::class; // {$useStatementComment}";

                $relatedModelFQN = $this->findRelatedModelFQN($relatedModelName, $currentGeneratingModelModuleNamespaceRoot);
                if ($relatedModelFQN) {
                    $classForUse = $this->getClassForUse($relatedModelFQN, $currentGeneratingModelModuleNamespaceRoot . '\\Models', $uses);
                    $relationshipTargetClass = "{$classForUse}::class";
                } else {
                    $this->warn("Model '{$relatedModelName}' for table '{$otherTableName}' not found. Generated a placeholder relationship.");
                    // Add a placeholder use statement
                    $potentialAppModel = "App\\Models\\{$relatedModelName}";
                    $potentialCurrentModuleModel = $currentGeneratingModelModuleNamespaceRoot . "\\Models\\{$relatedModelName}";
                    if (in_array($relatedModelName, ['User', 'Team', 'Category', 'Tag', 'Role', 'Permission'])) {
                        $uses[] = "// use {$potentialAppModel}; {$useStatementComment}";
                        $relationshipTargetClass = "\\{$potentialAppModel}::class; // {$useStatementComment}";
                    } else {
                        $uses[] = "// use {$potentialCurrentModuleModel}; {$useStatementComment}";
                        $relationshipTargetClass = "\\{$potentialCurrentModuleModel}::class; // {$useStatementComment}";
                    }
                }

                if (Str::singular($otherTableName) === $otherTableName) { // Heuristic for HasOne
                    $relationships[] = "public function {$relationBaseName}()\n    {\n        return \$this->hasOne({$relationshipTargetClass}, '{$currentModelForeignKey}');\n    }";
                } else { // HasMany
                    $relationships[] = "public function ". Str::plural($relationBaseName) ."()\n    {\n        return \$this->hasMany({$relationshipTargetClass}, '{$currentModelForeignKey}');\n    }";
                }
            }

            // BelongsToMany
            $parts = explode('_', $otherTableName);
            if (count($parts) >= 2) { // Allow for longer pivot table names like 'moduleA_moduleB_pivot'
                // Convention: table1_table2 or singular1_singular2
                $table1 = Str::singular($parts[0]);
                $table2 = Str::singular($parts[1]);
                $currentModelSingularSnake = Str::singular(Str::snake($this->modelName));

                if ($table1 === $currentModelSingularSnake || $table2 === $currentModelSingularSnake) {
                    $otherModelSingularSnake = ($table1 === $currentModelSingularSnake) ? $table2 : $table1;
                    $relatedModelName = Str::studly($otherModelSingularSnake);

                    $fk1 = Str::snake($currentModelSingularSnake) . '_id';
                    $fk2 = Str::snake($otherModelSingularSnake) . '_id';

                    if (Schema::hasColumn($otherTableName, $fk1) && Schema::hasColumn($otherTableName, $fk2)) {
                        $relationName = Str::plural(Str::camel($otherModelSingularSnake));
                        $useStatementComment = "// TODO: Create `{$relatedModelName}` model or ensure it's discoverable.";
                        $relationshipTargetClass = "\\{$currentGeneratingModelModuleNamespaceRoot}\\Models\\{$relatedModelName}::class; // {$useStatementComment}";

                        $relatedModelFQN = $this->findRelatedModelFQN($relatedModelName, $currentGeneratingModelModuleNamespaceRoot);
                        if ($relatedModelFQN) {
                            $classForUse = $this->getClassForUse($relatedModelFQN, $currentGeneratingModelModuleNamespaceRoot . '\\Models', $uses);
                            $relationshipTargetClass = "{$classForUse}::class";
                        } else {
                            $this->warn("Model '{$relatedModelName}' for pivot table '{$otherTableName}' not found. Generated a placeholder relationship.");
                            $potentialAppModel = "App\\Models\\{$relatedModelName}";
                            $potentialCurrentModuleModel = $currentGeneratingModelModuleNamespaceRoot . "\\Models\\{$relatedModelName}";
                            if (in_array($relatedModelName, ['User', 'Team', 'Category', 'Tag', 'Role', 'Permission'])) {
                                $uses[] = "// use {$potentialAppModel}; {$useStatementComment}";
                                $relationshipTargetClass = "\\{$potentialAppModel}::class; // {$useStatementComment}";
                            } else {
                                $uses[] = "// use {$potentialCurrentModuleModel}; {$useStatementComment}";
                                $relationshipTargetClass = "\\{$potentialCurrentModuleModel}::class; // {$useStatementComment}";
                            }
                        }
                        $relationships[] = "public function {$relationName}()\n    {\n        return \$this->belongsToMany({$relationshipTargetClass}, '{$otherTableName}', '{$fk1}', '{$fk2}');\n    }";
                    }
                }
            }
        }
    }

    protected function generateRequest()
    {
        $columns = $this->getTableColumns();
        $rulesArray = [];
        if (!empty($columns)) {
            $excludedColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
            foreach ($columns as $column) {
                if (in_array($column, $excludedColumns)) {
                    continue;
                }
                $columnRules = [];
                $columnDetail = $this->getColumnDetails($column);
                $type = $columnDetail ? $columnDetail->type : 'string';
                // Default assumption
                $isNullable = $columnDetail ? $columnDetail->nullable : true;

                $columnRules[] = $isNullable ? 'nullable' : 'required';
                switch ($type) {
                    case 'string': case 'text': case 'char': case 'varchar': case 'mediumtext': case 'longtext':
                        $columnRules[] = 'string';
                        if ($type !== 'text' && $type !== 'mediumtext' && $type !== 'longtext') { // Avoid adding max to text types
                            $columnRules[] = 'max:255'; // Default max
                        }
                        break;
                    case 'integer': case 'bigint': case 'smallint': case 'mediumint': case 'tinyint':
                        $columnRules[] = 'integer';
                        break;
                    case 'decimal': case 'float': case 'double': case 'numeric':
                        $columnRules[] = 'numeric';
                        break;
                    case 'boolean': $columnRules[] = 'boolean';
                        break;
                    case 'date': $columnRules[] = 'date';
                        break;
                    case 'datetime': case 'timestamp': $columnRules[] = 'date';
                        break; // Consider 'date_format:Y-m-d H:i:s'
                    case 'json': $columnRules[] = 'json';
                        break;
                    default: $columnRules[] = 'string';
                        break;
                }
                if ($column === 'email' || $column === 'slug' || $column === 'username') {
                    $columnRules[] = "'unique:{$this->tableName},{$column},' . (\$this->route('{$this->getModelVarName()}') ? \$this->route('{$this->getModelVarName()}')->id : 'NULL') . ',id'";
                }
                $rulesArray[] = "'{$column}' => '" . implode('|', $columnRules) . "'";
            }
        } else {
            $rulesArray = ["// 'title' => 'required|string|max:255',"];
        }
        $replacements = $this->getReplacements() + ['{{validationRules}}' => implode(",\n            ", $rulesArray)];
        $content = $this->populateStub('request', $replacements);
        $this->writeFile($this->basePath . '/Http/Requests/' . $this->modelName . 'Request.php', $content);
    }

    protected function generateService()
    {
        $replacements = $this->getReplacements();
        $this->writeFile($this->basePath . '/Contracts/' . $this->modelName . 'ServiceInterface.php', $this->populateStub('service.interface', $replacements));
        $this->writeFile($this->basePath . '/Services/' . $this->modelName . 'Service.php', $this->populateStub('service', $replacements));
    }

    protected function generatePolicy()
    {
        $replacements = $this->getReplacements();
        $this->writeFile($this->basePath . '/Policies/' . $this->modelName . 'Policy.php', $this->populateStub('policy', $replacements));
    }

    protected function generateEvents()
    {
        $eventTypes = ['model_created', 'model_updated', 'model_deleted'];
        foreach ($eventTypes as $eventType) {
            $replacements = $this->getReplacements();
            $actualEventNameForFile = $this->modelName . Str::studly(str_replace('model_', '', $eventType));
            $currentEventPlaceholder = '{{' . Str::studly(str_replace('_', '', $eventType)) . 'Event}}';
            $replacements[$currentEventPlaceholder] = $actualEventNameForFile;

            $content = $this->populateStub('event.' . $eventType, $replacements);
            $this->writeFile($this->basePath . '/Events/' . $actualEventNameForFile . '.php', $content);
        }
    }

    protected function generateObserver()
    {
        $replacements = $this->getReplacements();
        $content = $this->populateStub('observer', $replacements);
        $filePath = $this->basePath . '/Observers/' . $this->modelName . 'Observer.php';
        $this->writeFile($filePath, $content);
    }

    protected function generateWebController()
    {
        $replacementsGlobal = $this->getReplacements();
        $modelFQN = $replacementsGlobal['{{modelFullName}}'];

        $replacements = $replacementsGlobal + [
            '{{useService}}' => $this->generateService ? "use {$replacementsGlobal['{{namespace}}']}\\Contracts\\".$this->modelName."ServiceInterface;" : '',
            '{{serviceVariable}}' => $this->generateService ? "protected ".$this->modelName."ServiceInterface \$".$this->getServiceVarName().";" : '',
            '{{serviceInjection}}' => $this->generateService ? "\\{$replacementsGlobal['{{namespace}}']}\\Contracts\\".$this->modelName."ServiceInterface \$".$this->getServiceVarName() : '',
            '{{serviceAssignment}}' => $this->generateService ? "\$this->".$this->getServiceVarName()." = \$".$this->getServiceVarName().";" : '',
            '{{serviceCallGetAll}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->getAll(\$request->all())" : "\\{$modelFQN}::latest()->paginate(10)",
            '{{serviceCallGetById}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->getById(\${$this->getModelVarName()}->id)" : "\${$this->getModelVarName()}",
            '{{serviceCallCreate}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->create(\$request->validated())" : "\\{$modelFQN}::create(\$request->validated())",
            '{{serviceCallUpdate}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->update(\${$this->getModelVarName()}->id, \$request->validated())" : "\${$this->getModelVarName()}->update(\$request->validated())",
            '{{serviceCallDelete}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->delete(\${$this->getModelVarName()}->id)" : "\${$this->getModelVarName()}->delete()",
            '{{serviceCallRestore}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->restore(\$id)" : "\\{$modelFQN}::withTrashed()->find(\$id)?->restore()",
            '{{serviceCallForceDelete}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->forceDelete(\$id)" : "\\{$modelFQN}::withTrashed()->find(\$id)?->forceDelete()",
            '{{serviceCallGetDataForDataTable}}' => $this->generateService
                ? "\$this->".$this->getServiceVarName()."->getDataForDataTable(\$dataTableParams)"
                : "['data'=>[], 'total'=>0, 'filtered'=>0]; // Service not generated, using {$modelFQN}",
        ];
        $content = $this->populateStub('controller.web', $replacements);
        $this->writeFile($this->basePath . '/Http/Controllers/Web/' . $this->modelName . 'Controller.php', $content);
    }

    protected function generateApiController()
    {
        $replacementsGlobal = $this->getReplacements();
        $modelFQN = $replacementsGlobal['{{modelFullName}}'];

        $replacements = $replacementsGlobal + [
            '{{useService}}' => $this->generateService ? "use {$replacementsGlobal['{{namespace}}']}\\Contracts\\".$this->modelName."ServiceInterface;" : '',
            '{{serviceVariable}}' => $this->generateService ? "protected ".$this->modelName."ServiceInterface \$".$this->getServiceVarName().";" : '',
            '{{serviceInjection}}' => $this->generateService ? "\\{$replacementsGlobal['{{namespace}}']}\\Contracts\\".$this->modelName."ServiceInterface \$".$this->getServiceVarName() : '',
            '{{serviceAssignment}}' => $this->generateService ? "\$this->".$this->getServiceVarName()." = \$".$this->getServiceVarName().";" : '',
            '{{serviceCallGetAllApi}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->getAllPaginated(\$request->all(), \$request->input('per_page', 15))" : "\\{$modelFQN}::latest()->paginate(\$request->input('per_page', 15))",
            '{{serviceCallGetByIdApi}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->getById(\${$this->getModelVarName()}->id)" : "\${$this->getModelVarName()}",
            '{{serviceCallCreateApi}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->create(\$request->validated())" : "\\{$modelFQN}::create(\$request->validated())",
            '{{serviceCallUpdateApi}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->update(\${$this->getModelVarName()}->id, \$request->validated())" : "\${$this->getModelVarName()}->update(\$request->validated())",
            '{{serviceCallDeleteApi}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->delete(\${$this->getModelVarName()}->id)" : "\${$this->getModelVarName()}->delete()",
        ];
        $content = $this->populateStub('controller.api', $replacements);
        $this->writeFile($this->basePath . '/Http/Controllers/Api/' . $this->modelName . 'Controller.php', $content);
    }

    protected function generateResource()
    {
        $columns = $this->getTableColumns();
        $resourceFields = [];
        $excludedColumns = ['password', 'remember_token'];
        if (!empty($columns)) {
            foreach ($columns as $column) {
                if (!in_array($column, $excludedColumns)) {
                    if (Str::endsWith($column, '_at') || $column === 'deleted_at') {
                        $resourceFields[] = "'{$column}' => \$this->whenNotNull(\$this->{$column} ? \$this->{$column}->toIso8601String() : null)";
                    } else {
                        $resourceFields[] = "'{$column}' => \$this->whenNotNull(\$this->{$column})";
                    }
                }
            }
        } else {
            $resourceFields[] = "// 'id' => \$this->id,";
        }
        $idFieldPresent = false;
        foreach ($resourceFields as $fieldLine) {
            if (str_starts_with(trim($fieldLine), "'id'")) {
                $idFieldPresent = true;
                break;
            }
        }
        if (!$idFieldPresent && !in_array('id', $excludedColumns)) {
            array_unshift($resourceFields, "'id' => \$this->id");
        }

        $replacements = $this->getReplacements() + ['{{resourceFields}}' => implode(",\n            ", $resourceFields)];
        $content = $this->populateStub('resource', $replacements);
        $this->writeFile($this->basePath . '/Http/Resources/' . $this->modelName . 'Resource.php', $content);
    }

    protected function generateViews()
    {
        $viewDirName = Str::kebab(Str::plural($this->modelName));
        //$viewPathBase = $this->basePath . '/views/' . $viewDirName; // Corrected path
        $viewPathBase = $this->basePath . '/views/';
        // Ensure this specific directory is created
        $this->makeDirectory($viewPathBase);

        $columns = $this->getTableColumns();
        $excludedColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];
        $viewFieldsForShow = [];
        $formFieldsString = '';
        $tableHeaders = "<th>ID</th>\n                    ";

        if (!empty($columns)) {
            if (!in_array('id', $excludedColumns)) {
                $viewFieldsForShow[] = 'id';
            }
            foreach ($columns as $columnName) {
                if (in_array($columnName, $excludedColumns)) {
                    continue;
                }
                $label = Str::title(str_replace('_', ' ', $columnName));
                $viewFieldsForShow[] = $columnName;
                $tableHeaders .= "<th>{$label}</th>\n                    ";
                $fieldType = $this->determineFieldType($columnName);
                $fieldStubContent = $this->getStubContent("fields/{$fieldType}");
                $fieldReplacements = [
                    '{{fieldName}}' => $columnName,
                    '{{fieldLabel}}' => $label,
                    '{{fieldValue}}' => "{{ old('{$columnName}', \${$this->getModelVarName()}->{$columnName} ?? '') }}",
                    '{{modelVarName}}' => $this->getModelVarName(),
                    '{{options}}' => $this->getOptionsForSelect($columnName),
                ];
                $formFieldsString .= str_replace(array_keys($fieldReplacements), array_values($fieldReplacements), $fieldStubContent) . "\n";
            }
        } else {
            $formFieldsString = "<p class='text-muted'>No table schema. Add form fields manually.</p>";
            $tableHeaders = "<th>Example Header</th>";
        }

        $commonReplacements = $this->getReplacements();
        $actionColumnContent = $this->populateStub('views/actions.blade', $commonReplacements + ['itemVar' => '$item']);

        $viewReplacements = $commonReplacements + [
            '{{formFields}}' => rtrim($formFieldsString),
            '{{tableHeaders}}' => rtrim($tableHeaders),
            '{{viewFields}}' => empty($viewFieldsForShow) ? "[]" : "['" . implode("', '", $viewFieldsForShow) . "']",
            '{{modelVarName}}' => $this->getModelVarName(),
            '{{modelPluralVarName}}' => Str::plural($this->getModelVarName()),
            '{{actionColumnStub}}' => $actionColumnContent,
        ];

        $formOnlyReplacements = $this->getReplacements() + ['{{formFields}}' => rtrim($formFieldsString), '{{modelVarName}}' => $this->getModelVarName()];
        $this->writeFile($viewPathBase . '/_form.blade.php', $this->populateStub('views/_form.blade', $formOnlyReplacements));

        foreach (['index', 'create', 'edit', 'show', 'trashed'] as $view) {
            $stubPath = 'views/' . $view . '.blade';
            if (File::exists($this->stubsPath . '/' . $stubPath . '.stub')) {
                $this->writeFile($viewPathBase . '/' . $view . '.blade.php', $this->populateStub($stubPath, $viewReplacements));
            } else {
                $this->warn("View stub not found: {$this->stubsPath}/{$stubPath}.stub. Skipping {$view}.blade.php.");
            }
        }
    }

    protected function determineFieldType($columnName)
    {
        if (Str::endsWith($columnName, '_id') && $columnName !== 'id') {
            return 'select';
        }
        if (Str::contains($columnName, ['description', 'notes', 'content', 'details', 'message', 'bio', 'summary'])) {
            return 'textarea';
        }
        $dbType = null;
        if (Schema::hasTable($this->tableName) && Schema::hasColumn($this->tableName, $columnName)) {
            try {
                $dbType = Schema::getColumnType($this->tableName, $columnName);
            } catch (\Exception $e) {
                $this->comment("Could not determine DB type for {$columnName}, defaulting to text. Error: " . $e->getMessage());
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
            if (in_array($dbType, ['boolean', 'tinyint'])) {
                return 'checkbox';
                // tinyint(1) often boolean
            }
        }
        if (Str::contains($columnName, ['_at', '_date', 'dated_'])) {
            return 'datetime';
        }
        if (Str::contains($columnName, ['image', 'avatar', 'logo', 'file', 'document', 'attachment', 'photo'])) {
            return 'file';
        }
        if (Str::startsWith($columnName, 'is_') || Str::startsWith($columnName, 'has_') || Str::endsWith($columnName, '_flag')) {
            return 'checkbox';
        }
        if (Str::contains($columnName, 'email')) {
            return 'email';
        }
        if (Str::contains($columnName, 'password')) {
            return 'password';
        }
        if (Str::contains($columnName, ['url', 'link'])) {
            return 'url';
        }
        if (Str::contains($columnName, ['phone', 'mobile', 'tel'])) {
            return 'tel';
        }
        if (Str::contains($columnName, ['color', 'hex'])) {
            return 'color';
        }
        return 'text';
    }

    protected function getOptionsForSelect($columnName)
    {
        if (Str::endsWith($columnName, '_id')) {
            $relatedModelName = Str::studly(Str::singular(str_replace('_id', '', $columnName)));
            $relatedPluralVar = Str::plural(Str::camel($relatedModelName));
            $displayName = 'name'; // Default
            if (in_array($relatedModelName, ['User', 'Team', 'Category', 'Type', 'Status'])) {
                $displayName = 'name';
            }

            return "<option value=\"\">-- Select {$relatedModelName} --</option>\n                    @foreach(\${$relatedPluralVar} ?? [] as \$relatedItem)\n                        <option value=\"{{\$relatedItem->id}}\" {{ (old('{$columnName}', \${$this->getModelVarName()}->{$columnName} ?? null) == \$relatedItem->id) ? 'selected' : '' }}>{{\$relatedItem->{$displayName} ?? \$relatedItem->id}}</option>\n                    @endforeach";
        }
        return '';
    }

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
        if ($this->generatePolicy) {
            $policiesArray[] = "\\{$replacementsGlobal['{{actualModelFullName}}']}::class => \\{$generatedNamespaceRoot}\\Policies\\".$this->modelName."Policy::class,";
        }
        if ($this->generateService) {
            $bindingsArray[] = "\$this->app->bind(\\{$generatedNamespaceRoot}\\Contracts\\".$this->modelName."ServiceInterface::class, \\{$generatedNamespaceRoot}\\Services\\".$this->modelName."Service::class);";
        }
        if ($this->generateObserver) {
            $observersArray[] = "\\{$replacementsGlobal['{{actualModelFullName}}']}::class => \\{$generatedNamespaceRoot}\\Observers\\".$this->modelName."Observer::class);";
        }

        $replacements = $replacementsGlobal + [
            '{{hasApiRoutes}}' => $this->createApi ? 'true' : 'false', '{{hasWebRoutes}}' => 'true',
            '{{policies}}' => empty($policiesArray) ? "// Model Policies" : implode("\n        ", $policiesArray),
            '{{bindings}}' => empty($bindingsArray) ? "// Service Bindings" : implode("\n        ", $bindingsArray),
            '{{observers}}' => empty($observersArray) ? "// Model Observers" : implode("\n        ", $observersArray),
            '{{viewDirectoryName}}' => Str::kebab(Str::plural($this->modelName)),
        ];
        $this->writeFile($this->basePath . '/Providers/' . $this->moduleName . 'ServiceProvider.php', $this->populateStub('provider', $replacements));
    }

    protected function getModelVarName()
    {
        return Str::camel($this->modelName);
    }
    protected function getServiceVarName()
    {
        return Str::camel($this->modelName . 'Service');
    }
}
