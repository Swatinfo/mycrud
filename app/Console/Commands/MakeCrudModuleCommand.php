<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Still used for DB connection, not Doctrine specific methods
use Illuminate\Support\Str;

class MakeCrudModuleCommand extends Command
{
    protected $signature = 'make:crud-module {name : The name of the module (e.g., Post, ProductCategory)}
                                             {--table= : The name of the database table (optional)}
                                             {--dry-run : Generate files in a "dryrun/{ModuleName}" directory at the project root}
                                             {--no-views : Do not create view files}
                                             {--no-api : Do not create API controller and resource}
                                             {--service : Generate a service class for business logic}
                                             {--policy : Generate a policy class for authorization}
                                             {--events : Generate basic model event classes (Created, Updated, Deleted)}
                                             {--observer : Generate a model observer for audit logging}
                                             {--force : Overwrite existing files without asking}';

    protected $description = 'Create an advanced CRUD module (without Doctrine/DBAL dependency) with field stubs, relationships, DataTables, audit logs, and soft deletes.';

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

    protected $basePath; // This will be the dry-run path if --dry-run is used, otherwise the actual module path
    protected $stubsPath;
    protected $actualModulesPath; // Always points to Modules/ModuleName

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

        // Define actualModulesPath regardless of dry-run for informational output
        $this->actualModulesPath = base_path('Modules/' . $this->moduleName);
        $this->stubsPath = base_path('stubs/crud-module');

        if ($this->isDryRun) {
            $this->basePath = base_path('dryrun/' . $this->moduleName); // basePath is for current operation
            $this->warn("Dry run mode: Files will be generated in: {$this->basePath}");
            if (!File::isDirectory(base_path('dryrun'))) {
                File::makeDirectory(base_path('dryrun'), 0755, true, true);
            }
            if (File::isDirectory($this->basePath) && !$this->forceOverwrite && $this->confirm("Dry-run directory [{$this->basePath}] exists. Clear it?")) {
                File::deleteDirectory($this->basePath);
            }
        } else {
            $this->basePath = $this->actualModulesPath; // basePath is for current operation
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
        $this->generateModel();
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

        // Paths and Namespaces Information
        $dryRunPathInfo = base_path('dryrun/' . $this->moduleName);
        $dryRunNamespaceInfo = 'DryRun\\' . $this->moduleName;
        $actualModulePathInfo = $this->actualModulesPath; // Already defined in handle()
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
            $this->line("  <options=bold>Generated Files Location:</> {$this->basePath}"); // Same as actualModulePathInfo
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

        $stubPath = str_replace('.stub.stub', ".stub", $stubPath);

        if (!File::exists($stubPath)) {
            if (str_starts_with($stubName, 'fields/')) {
                $this->warn("Field stub not found: {$stubPath}. Falling back to basic text input for this field.");
                return File::exists($this->stubsPath . '/fields/text.stub') ? File::get($this->stubsPath . '/fields/text.stub') : "<input type=\"text\" name=\"{{fieldName}}\" value=\"{{fieldValue}}\">";
            }
            // Handle cases where stubName might already contain .blade (e.g. for views/actions.blade)
            if (Str::endsWith($stubName, '.blade')) {
                $stubPath = $this->stubsPath . '/' . $stubName . '.stub'; // e.g. views/actions.blade.stub
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

        // Conditionally define the root namespace for code generation
        if ($this->isDryRun) {
            $rootModuleNamespaceForGeneration = 'DryRun\\' . $this->moduleName;
        } else {
            $rootModuleNamespaceForGeneration = 'Modules\\' . $this->moduleName;
        }
        // Namespace for referencing the actual model, even in dry run (e.g. for Policy)
        $actualModelNamespace = 'Modules\\' . $this->moduleName . '\\Models\\' . $this->modelName;


        return [
            '{{namespace}}' => $rootModuleNamespaceForGeneration, // Used for the 'namespace' line in generated files
            '{{moduleName}}' => $this->moduleName,
            '{{modelName}}' => $this->modelName,
            '{{modelFullName}}' => $rootModuleNamespaceForGeneration . '\\Models\\' . $this->modelName, // Namespace of the model being generated
            '{{actualModelFullName}}' => $actualModelNamespace, // Always Modules\...\Models for policy target etc.
            '{{modelNamePlural}}' => Str::plural($this->modelName),
            '{{modelNameSingularLowerCase}}' => $modelNameSingularLowerCase,
            '{{modelNamePluralLowerCase}}' => $modelNamePluralLowerCase,
            '{{tableName}}' => $this->tableName,
            // viewPath should refer to the alias that will be used by the application to load views.
            // This alias is typically registered by the module's service provider.
            // For dry-run, the files are in dryrun/..., but they'd be moved to Modules/... for actual use.
            // So, the view path in code should reflect the final 'Modules' structure.
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
            // Native Schema doesn't easily provide nullable, length, etc. in a cross-DB way without Doctrine.
            // We'll have to make assumptions or keep it simple.
            return (object) [
                'type' => $type,
                'nullable' => true, // Assume nullable by default, or require user to adjust validation
                'length' => null,   // Cannot reliably get length without Doctrine
            ];
        } catch (\Exception $e) {
            $this->warn("Could not get details for column '{$columnName}': {$e->getMessage()}");
            return null;
        }
    }


    protected function generateModel()
    {
        $columns = $this->getTableColumns();
        $fillable = [];
        $casts = [];
        $dates = ["'deleted_at'"]; // SoftDeletes trait adds 'deleted_at' to $dates automatically if not present
        $relationships = [];
        $uses = ["use Illuminate\\Database\\Eloquent\\SoftDeletes;"]; // Default
        // $uses[] = "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;"; // Default

        $replacementsGlobal = $this->getReplacements();
        $currentGeneratingModelNamespace = $replacementsGlobal['{{namespace}}'] . '\\Models';


        $fillable[] = "'deleted_at' => 'datetime'"; // This is not standard for $fillable, more for $casts if needed.
        // $fillable is usually just column names.
        // SoftDeletes handles deleted_at automatically.
        // Let's remove this and rely on SoftDeletes trait.
        $fillable = []; // Resetting, as deleted_at is handled by SoftDeletes.

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
                        // Check if tinyint(1) convention is used for boolean
                        if ($columnType === 'tinyint') {
                            // This is a heuristic. True boolean detection needs more info or Doctrine.
                            $this->comment("Column '{$columnName}' is tinyint, assuming boolean for casting. Adjust if needed.");
                        }
                        $casts[] = "'{$columnName}' => 'boolean'";
                    }
                }

                if (Str::endsWith($columnName, '_id') && $columnName !== 'id') {
                    $relatedModel = Str::studly(Str::singular(str_replace('_id', '', $columnName)));
                    $relationName = Str::camel(Str::singular(str_replace('_id', '', $columnName)));
                    // Pass the namespace where the *current model being generated* will reside
                    $relatedFQN = $this->findRelatedModelFQN($relatedModel, $currentGeneratingModelNamespace);
                    if ($relatedFQN) {
                        $classForUse = $this->getClassForUse($relatedFQN, $currentGeneratingModelNamespace, $uses);
                        $relationships[] = "public function {$relationName}()\n    {\n        return \$this->belongsTo({$classForUse}::class, '{$columnName}');\n    }";
                    }
                }
            }
        }

        $this->detectOtherRelationshipsSimplified($relationships, $uses, $currentGeneratingModelNamespace);

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
            '{{dates}}' => implode(",\n        ", array_unique($dates)), // SoftDeletes handles 'deleted_at' for $dates.
            '{{relationships}}' => implode("\n\n    ", $relationships),
            '{{dispatchesEvents}}' => $dispatchesEvents,
        ];
        $content = $this->populateStub('model', $replacements);
        $this->writeFile($this->basePath . '/Models/' . $this->modelName . '.php', $content);
    }

    protected function findRelatedModelFQN($relatedModelName, $currentModelModuleNamespaceRoot)
    {
        // Namespace of the module where the *current model is being generated*
        // e.g., DryRun\MyModule\Models or Modules\MyModule\Models
        $currentModuleModelsNamespace = $currentModelModuleNamespaceRoot . '\\Models';


        // 1. Check within the same module's Models directory (e.g., DryRun\MyModule\Models\RelatedModel)
        if (class_exists("{$currentModuleModelsNamespace}\\{$relatedModelName}")) {
            return "{$currentModuleModelsNamespace}\\{$relatedModelName}";
        }

        // 2. Check App\Models (standard Laravel app models)
        if (class_exists("App\\Models\\{$relatedModelName}")) {
            return "App\\Models\\{$relatedModelName}";
        }

        // 3. Check other *actual* Modules (Modules\OtherModule\Models\RelatedModel)
        // This part should always look in the 'Modules' directory, not 'DryRun' for inter-module relations.
        $allActualModules = File::glob(base_path('Modules/*'), GLOB_ONLYDIR);
        foreach ($allActualModules as $modulePath) {
            $module = basename($modulePath);
            // Skip if it's the current module we are generating (already checked by $currentModuleModelsNamespace)
            // Note: $this->moduleName is the simple name like 'Product'
            if ($module === $this->moduleName) {
                continue;
            }
            if (class_exists("Modules\\{$module}\\Models\\{$relatedModelName}")) {
                return "Modules\\{$module}\\Models\\{$relatedModelName}";
            }
        }
        $this->warn("Could not find FQN for related model: {$relatedModelName}. Relationship may be incomplete.");
        return null; // Fallback, or you could return a placeholder.
    }
    protected function getClassForUse($fqcn, $currentNamespace, &$usesArray)
    {
        // $currentNamespace here is the namespace of the file being written,
        // e.g., DryRun\MyModule\Models or Modules\MyModule\Models
        if (str_starts_with($fqcn, $currentNamespace . '\\')) {
            // It's in the same namespace, no 'use' needed, just the class name.
            return Str::afterLast($fqcn, '\\');
        }
        // It's in a different namespace, add 'use' statement and return class name.
        $usesArray[] = "use {$fqcn};";
        return Str::afterLast($fqcn, '\\');
    }

    protected function detectOtherRelationshipsSimplified(&$relationships, &$uses, $currentGeneratingModelNamespaceRoot)
    {
        $allTableNames = [];
        try {
            $tables = DB::select('SHOW TABLES');
            $dbNameKey = 'Tables_in_' . DB::getDatabaseName(); // Adjust if your DB key is different
            foreach ($tables as $table) {
                $allTableNames[] = $table->$dbNameKey;
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

            if (Schema::hasColumn($otherTableName, $currentModelForeignKey)) {
                $relatedModelName = Str::studly(Str::singular($otherTableName));
                // For finding FQN, always search based on actual potential locations (App\Models, Modules\OtherModule\Models)
                // The $currentGeneratingModelNamespaceRoot is for the 'use' statement context.
                $relatedModelFQN = $this->findRelatedModelFQN($relatedModelName, $currentGeneratingModelNamespaceRoot);

                if (!$relatedModelFQN) {
                    continue;
                }

                $classForUse = $this->getClassForUse($relatedModelFQN, $currentGeneratingModelNamespaceRoot . '\\Models', $uses);
                $relationBaseName = Str::camel(Str::singular($otherTableName)); // e.g. userProfile

                if (Str::singular($otherTableName) === $otherTableName) { // Heuristic for HasOne
                    $relationName = $relationBaseName;
                    $relationships[] = "public function {$relationName}()\n    {\n        return \$this->hasOne({$classForUse}::class, '{$currentModelForeignKey}');\n    }";
                } else { // HasMany
                    $relationName = Str::plural($relationBaseName); // e.g. comments
                    $relationships[] = "public function {$relationName}()\n    {\n        return \$this->hasMany({$classForUse}::class, '{$currentModelForeignKey}');\n    }";
                }
            }

            $parts = explode('_', $otherTableName);
            if (count($parts) === 2) {
                $model1SingularSnake = Str::singular($parts[0]);
                $model2SingularSnake = Str::singular($parts[1]);
                $currentModelSingularSnake = Str::singular(Str::snake($this->modelName));

                if (($model1SingularSnake === $currentModelSingularSnake || $model2SingularSnake === $currentModelSingularSnake)) {
                    $otherModelSingularSnake = ($model1SingularSnake === $currentModelSingularSnake) ? $model2SingularSnake : $model1SingularSnake;
                    $relatedModelName = Str::studly($otherModelSingularSnake);
                    $relatedModelFQN = $this->findRelatedModelFQN($relatedModelName, $currentGeneratingModelNamespaceRoot);

                    if ($relatedModelFQN) {
                        $fk1 = Str::snake($currentModelSingularSnake) . '_id';
                        $fk2 = Str::snake($otherModelSingularSnake) . '_id';

                        if (Schema::hasColumn($otherTableName, $fk1) && Schema::hasColumn($otherTableName, $fk2)) {
                            $classForUse = $this->getClassForUse($relatedModelFQN, $currentGeneratingModelNamespaceRoot . '\\Models', $uses);
                            $relationName = Str::plural(Str::camel($otherModelSingularSnake));
                            $relationships[] = "public function {$relationName}()\n    {\n        return \$this->belongsToMany({$classForUse}::class, '{$otherTableName}', '{$fk1}', '{$fk2}');\n    }";
                        }
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
                $isNullable = $columnDetail ? $columnDetail->nullable : true;

                $columnRules[] = $isNullable ? 'nullable' : 'required';
                switch ($type) {
                    case 'string': case 'text': case 'char': case 'varchar':
                        $columnRules[] = 'string';
                        $columnRules[] = 'max:255';
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
                        break;
                    case 'json': $columnRules[] = 'json';
                        break;
                    default: $columnRules[] = 'string';
                        break;
                }
                // Unique rule (example, adjust as needed)
                if ($column === 'email' || $column === 'slug' || $column === 'username') { // Add other common unique fields
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
            // Construct the event name based on the model name and event type
            $actualEventNameForFile = $this->modelName . Str::studly(str_replace('model_', '', $eventType));
            // Update the specific event placeholder for this iteration
            $currentEventPlaceholder = '{{' . Str::studly(str_replace('_', '', $eventType)) . 'Event}}'; // e.g. {{ModelCreatedEvent}}
            $replacements[$currentEventPlaceholder] = $actualEventNameForFile; // Override for this specific event

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
        $modelFQN = $replacementsGlobal['{{modelFullName}}']; // This will be DryRun\Mod\Models\Mod or Modules\Mod\Models\Mod

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
        $excludedColumns = ['password', 'remember_token']; // 'deleted_at' is usually not included unless specifically needed
        if (!empty($columns)) {
            foreach ($columns as $column) {
                if (!in_array($column, $excludedColumns)) {
                    // For date fields, ensure they are formatted if not null
                    if (Str::endsWith($column, '_at') || $column === 'deleted_at') { // Include deleted_at if you want it in API response
                        $resourceFields[] = "'{$column}' => \$this->whenNotNull(\$this->{$column} ? \$this->{$column}->toIso8601String() : null)";
                    } else {
                        $resourceFields[] = "'{$column}' => \$this->whenNotNull(\$this->{$column})";
                    }
                }
            }
        } else {
            $resourceFields[] = "// 'id' => \$this->id,";
        }
        // Ensure 'id' is present if not excluded
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
        $createdAtPresent = false;
        $updatedAtPresent = false;
        foreach ($resourceFields as $fieldLine) {
            if (str_contains($fieldLine, "'created_at'")) {
                $createdAtPresent = true;
            } if (str_contains($fieldLine, "'updated_at'")) {
                $updatedAtPresent = true;
            }
        }
        if (!$createdAtPresent && !in_array('created_at', $excludedColumns)) {
            $resourceFields[] = "'created_at' => \$this->whenNotNull(\$this->created_at?->toIso8601String())";
        }
        if (!$updatedAtPresent && !in_array('updated_at', $excludedColumns)) {
            $resourceFields[] = "'updated_at' => \$this->whenNotNull(\$this->updated_at?->toIso8601String())";
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
        $this->makeDirectory($viewPathBase); // Ensure this specific directory is created

        $columns = $this->getTableColumns();
        $excludedColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];
        $viewFieldsForShow = [];
        $formFieldsString = '';
        $tableHeaders = "<th>ID</th>\n                    "; // Start with ID

        if (!empty($columns)) {
            // Add ID to show fields if not excluded (it's usually not)
            if (!in_array('id', $excludedColumns)) {
                $viewFieldsForShow[] = 'id';
            }

            foreach ($columns as $columnName) {
                if (in_array($columnName, $excludedColumns)) {
                    continue;
                }
                $label = Str::title(str_replace('_', ' ', $columnName));
                $viewFieldsForShow[] = $columnName; // Add to show view
                $tableHeaders .= "<th>{$label}</th>\n                    ";

                $fieldType = $this->determineFieldType($columnName);
                $fieldStubContent = $this->getStubContent("fields/{$fieldType}"); // No .stub needed here

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
            $formFieldsString = "<p class='text-muted'>No table schema found. Please add form fields manually or define them in field stubs.</p>";
            $tableHeaders = "<th>Example Header</th>";
        }

        $commonReplacements = $this->getReplacements();
        // Pass 'views/actions.blade' to getStubContent, which will append '.stub'
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
            $stubPath = 'views/' . $view . '.blade'; // e.g., 'views/index.blade'

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
            if ($dbType === 'text' || $dbType === 'mediumtext' || $dbType === 'longtext') { // Common text types
                return 'textarea';
            }
            if ($dbType === 'date') {
                return 'date';
            }
            if ($dbType === 'datetime' || $dbType === 'timestamp') {
                return 'datetime';
            }
            if ($dbType === 'boolean' || ($dbType === 'tinyint')) { // Often tinyint(1) is boolean
                return 'checkbox';
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
        if (Str::contains($columnName, 'url') || Str::contains($columnName, 'link')) {
            return 'url'; // Assuming you might have a url.stub or fallback to text
        }
        if (Str::contains($columnName, 'phone') || Str::contains($columnName, 'mobile') || Str::contains($columnName, 'tel')) {
            return 'tel'; // Assuming you might have a tel.stub or fallback to text
        }
        if (Str::contains($columnName, 'color') || Str::contains($columnName, 'hex')) {
            return 'color'; // Assuming you might have a color.stub or fallback to text
        }
        return 'text';
    }
    protected function getOptionsForSelect($columnName)
    {
        if (Str::endsWith($columnName, '_id')) {
            $relatedModelName = Str::studly(Str::singular(str_replace('_id', '', $columnName)));
            $relatedPluralVar = Str::plural(Str::camel($relatedModelName));
            $displayName = 'name'; // Default display name
            // Heuristic for common display names
            if ($relatedModelName === 'User') {
                $displayName = 'name';
            } // or 'email'
            elseif (Str::contains($relatedModelName, 'Category')) {
                $displayName = 'name';
            } elseif (Str::contains($relatedModelName, 'Type')) {
                $displayName = 'name';
            } elseif (Str::contains($relatedModelName, 'Status')) {
                $displayName = 'name';
            }


            return "<option value=\"\">-- Select {$relatedModelName} --</option>\n                    @foreach(\${$relatedPluralVar} ?? [] as \$relatedItem)\n                        <option value=\"{{\$relatedItem->id}}\" {{ (old('{$columnName}', \${$this->getModelVarName()}->{$columnName} ?? null) == \$relatedItem->id) ? 'selected' : '' }}>{{\$relatedItem->{$displayName} ?? \$relatedItem->id}}</option>\n                    @endforeach";
        }
        // For boolean-like fields, you might want a Yes/No select instead of a checkbox sometimes
        // if (Str::startsWith($columnName, 'is_') || Str::startsWith($columnName, 'has_')) {
        //     return "<option value=\"1\" {{ (old('{$columnName}', \${$this->getModelVarName()}->{$columnName} ?? 0) == 1) ? 'selected' : '' }}>Yes</option>\n                    <option value=\"0\" {{ (old('{$columnName}', \${$this->getModelVarName()}->{$columnName} ?? 0) == 0) ? 'selected' : '' }}>No</option>";
        // }
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
        $generatedNamespaceRoot = $replacementsGlobal['{{namespace}}']; // This will be DryRun\Mod or Modules\Mod

        $policiesArray = [];
        $bindingsArray = [];
        $observersArray = [];
        if ($this->generatePolicy) {
            // Policy maps the *actual* model (Modules\...) to the generated policy (DryRun\...\P or Modules\...\P)
            $policiesArray[] = "\\{$replacementsGlobal['{{actualModelFullName}}']}::class => \\{$generatedNamespaceRoot}\\Policies\\".$this->modelName."Policy::class,";
        }
        if ($this->generateService) {
            $bindingsArray[] = "\$this->app->bind(\\{$generatedNamespaceRoot}\\Contracts\\".$this->modelName."ServiceInterface::class, \\{$generatedNamespaceRoot}\\Services\\".$this->modelName."Service::class);";
        }
        if ($this->generateObserver) {
            // Observer observes the *actual* model (Modules\...) using the generated observer (DryRun\...\O or Modules\...\O)
            $observersArray[] = "\\{$replacementsGlobal['{{actualModelFullName}}']}::class => \\{$generatedNamespaceRoot}\\Observers\\".$this->modelName."Observer::class);";
        }

        $replacements = $replacementsGlobal + [
            '{{hasApiRoutes}}' => $this->createApi ? 'true' : 'false', '{{hasWebRoutes}}' => 'true',
            '{{policies}}' => empty($policiesArray) ? "// Model Policies" : implode("\n        ", $policiesArray),
            '{{bindings}}' => empty($bindingsArray) ? "// Service Bindings" : implode("\n        ", $bindingsArray),
            '{{observers}}' => empty($observersArray) ? "// Model Observers" : implode("\n        ", $observersArray),
            '{{viewDirectoryName}}' => Str::kebab(Str::plural($this->modelName)), // for loadViewsFrom
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
