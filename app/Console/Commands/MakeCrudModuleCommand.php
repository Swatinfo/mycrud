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
    { /* ... (same as previous version) ... */
        $this->newLine(2);
        $this->warn("======================================================================");
        $this->warn(" IMPORTANT NEXT STEPS TO ACTIVATE MODULE '{$this->moduleName}' ");
        $this->warn("======================================================================");
        $this->newLine();

        if ($this->isDryRun) {
            $this->info("<fg=cyan>You used --dry-run. Module '{$this->moduleName}' is in: {$this->basePath}</>");
            $this->line("<fg=cyan>To use it:</>");
            $this->line("<fg=yellow>  Step 1: Move Module</>");
            $this->line("     Move '{$this->moduleName}' from '{$this->basePath}'");
            $this->line("     to your project's root 'Modules/' directory: " . base_path('Modules/' . $this->moduleName));
            $this->newLine();
            $this->line("<fg=cyan>Then, whether moved or generated directly, follow these setup steps:</>");
            $this->newLine();
        }

        $this->line("<fg=yellow>Step A: Configure PSR-4 Autoloading (if 'Modules/' isn't already)</>");
        $this->line("   In `composer.json`, under `\"autoload\"` -> `\"psr-4\"`, ensure:");
        $this->comment('     "Modules\\\\": "Modules/",');
        $this->newLine();

        $this->line("<fg=yellow>Step B: Update Composer's Autoloader</>");
        $this->comment("     composer dump-autoload");
        $this->newLine();

        $this->line("<fg=yellow>Step C: Register Service Provider</>");
        $this->line("   In `config/app.php` -> `providers` array, add:");
        $this->comment("     Modules\\{$this->moduleName}\\Providers\\{$this->moduleName}ServiceProvider::class,");
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
    { /* ... (same as previous version) ... */
        $paths = [
            $this->basePath . '/Http/Controllers/Web', $this->basePath . '/Http/Controllers/Api',
            $this->basePath . '/Http/Requests', $this->basePath . '/Http/Resources',
            $this->basePath . '/Models', $this->basePath . '/Providers', $this->basePath . '/routes',
            $this->basePath . '/views/' . Str::kebab(Str::plural($this->modelName)),
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
    { /* ... (same as previous version) ... */
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true, true);
            $this->info("Created directory: {$path}");
        } else {
            $this->comment("Directory already exists: {$path}");
        }
    }
    protected function getStubContent($stubName)
    { /* ... (same as previous version) ... */
        $stubPath = $this->stubsPath . '/' . $stubName . '.stub';
        if (!File::exists($stubPath)) {
            if (str_starts_with($stubName, 'fields/')) {
                $this->warn("Field stub not found: {$stubPath}. Falling back to basic text input for this field.");
                return File::exists($this->stubsPath . '/fields/text.stub') ? File::get($this->stubsPath . '/fields/text.stub') : "<input type=\"text\" name=\"{{fieldName}}\" value=\"{{fieldValue}}\">";
            }
            $this->error("Stub file not found: {$stubPath}");
            throw new \Exception("Stub not found: {$stubPath}");
        }
        return File::get($stubPath);
    }
    protected function writeFile($path, $content)
    { /* ... (same as previous version) ... */
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
    { /* ... (same as previous version) ... */
        $modelNameSingularLowerCase = Str::lower($this->modelName);
        $modelNamePluralLowerCase = Str::lower(Str::plural($this->modelName));
        $moduleNameKebab = Str::kebab($this->moduleName);
        $rootModuleNamespace = 'Modules\\' . $this->moduleName;
        return [
            '{{namespace}}' => $rootModuleNamespace,
            '{{moduleName}}' => $this->moduleName,
            '{{modelName}}' => $this->modelName,
            '{{modelNamePlural}}' => Str::plural($this->modelName),
            '{{modelNameSingularLowerCase}}' => $modelNameSingularLowerCase,
            '{{modelNamePluralLowerCase}}' => $modelNamePluralLowerCase,
            '{{tableName}}' => $this->tableName,
            '{{viewPath}}' => $moduleNameKebab . '::' . $modelNamePluralLowerCase,
            '{{routeNamePrefix}}' => $moduleNameKebab . '.' . $modelNamePluralLowerCase,
            '{{apiRouteNamePrefix}}' => 'api.' . $moduleNameKebab . '.' . $modelNamePluralLowerCase,
            '{{webRoutePrefix}}' => $modelNamePluralLowerCase,
            '{{apiRoutePrefix}}' => $modelNamePluralLowerCase,
            '{{moduleNamespaceKebab}}' => $moduleNameKebab,
            '{{serviceName}}' => $this->modelName . 'Service',
            '{{serviceInterfaceName}}' => $this->modelName . 'ServiceInterface',
            '{{policyName}}' => $this->modelName . 'Policy',
            '{{observerName}}' => $this->modelName . 'Observer',
            '{{eventNamespace}}' => $rootModuleNamespace . '\\Events',
            '{{modelCreatedEvent}}' => $this->modelName . 'Created',
            '{{modelUpdatedEvent}}' => $this->modelName . 'Updated',
            '{{modelDeletedEvent}}' => $this->modelName . 'Deleted',
        ];
    }
    protected function populateStub($stubName, $replacements)
    { /* ... (same as previous version) ... */
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
        $dates = ["'deleted_at'"];
        $relationships = [];
        $uses = ["use Illuminate\\Database\\Eloquent\\SoftDeletes;"];
        $dispatchesEvents = '';
        $rootModuleModelsNamespace = 'Modules\\' . $this->moduleName . '\\Models';

        $fillable[] = "'deleted_at' => 'datetime'";

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
                        $casts[] = "'{$columnName}' => 'boolean'";
                    }
                }

                if (Str::endsWith($columnName, '_id')) {
                    $relatedModel = Str::studly(Str::singular(str_replace('_id', '', $columnName)));
                    $relationName = Str::camel(Str::singular(str_replace('_id', '', $columnName)));
                    $relatedFQN = $this->findRelatedModelFQN($relatedModel, $rootModuleModelsNamespace);
                    if ($relatedFQN) {
                        $classForUse = $this->getClassForUse($relatedFQN, $rootModuleModelsNamespace, $uses);
                        $relationships[] = "public function {$relationName}()\n    {\n        return \$this->belongsTo({$classForUse}::class, '{$columnName}');\n    }";
                    }
                }
            }
        }

        $this->detectOtherRelationshipsSimplified($relationships, $uses, $rootModuleModelsNamespace);

        if ($this->generateEvents) { /* ... (same as previous) ... */
            $eventReplacements = $this->getReplacements();
            $dispatchesEventsLines = [
                "'created' => ".$eventReplacements['{{modelCreatedEvent}}']."::class",
                "'updated' => ".$eventReplacements['{{modelUpdatedEvent}}']."::class",
                "'deleted' => ".$eventReplacements['{{modelDeletedEvent}}']."::class",
            ];
            $dispatchesEvents = "protected \$dispatchesEvents = [\n        " . implode(",\n        ", $dispatchesEventsLines) . "\n    ];";
        }

        $replacements = $this->getReplacements() + [
            '{{uses}}' => implode("\n", array_unique($uses)),
            '{{fillableProperties}}' => implode(",\n        ", array_unique($fillable)),
            '{{casts}}' => implode(",\n        ", array_unique($casts)),
            '{{dates}}' => implode(",\n        ", array_unique($dates)),
            '{{relationships}}' => implode("\n\n    ", $relationships),
            '{{dispatchesEvents}}' => $dispatchesEvents,
        ];
        $content = $this->populateStub('model', $replacements);
        $this->writeFile($this->basePath . '/Models/' . $this->modelName . '.php', $content);
    }

    protected function findRelatedModelFQN($relatedModelName, $currentModuleModelsNamespace)
    { /* ... (same as previous version) ... */
        if (class_exists("App\\Models\\{$relatedModelName}")) {
            return "App\\Models\\{$relatedModelName}";
        }
        if (class_exists("{$currentModuleModelsNamespace}\\{$relatedModelName}")) {
            return "{$currentModuleModelsNamespace}\\{$relatedModelName}";
        }
        $allModules = File::glob(base_path('Modules/*'), GLOB_ONLYDIR);
        foreach ($allModules as $modulePath) {
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
    { /* ... (same as previous version) ... */
        if (str_starts_with($fqcn, $currentNamespace . '\\')) {
            return Str::afterLast($fqcn, '\\');
        }
        $usesArray[] = "use {$fqcn};";
        return Str::afterLast($fqcn, '\\');
    }

    protected function detectOtherRelationshipsSimplified(&$relationships, &$uses, $currentModuleModelsNamespace)
    {
        $allTableNames = [];
        try {
            // Get all table names using a raw query - this part can be database specific if Schema::getAllTables() isn't available/suitable
            $tables = DB::select('SHOW TABLES'); // MySQL specific, adjust for other DBs
            $dbNameKey = 'Tables_in_' . DB::getDatabaseName();
            foreach ($tables as $table) {
                $allTableNames[] = $table->$dbNameKey;
            }
        } catch (\Exception $e) {
            $this->warn("Could not list all tables for advanced relationship detection: " . $e->getMessage());
            return; // Exit if cannot list tables
        }

        $currentModelForeignKey = Str::snake($this->modelName) . '_id'; // e.g., post_id

        foreach ($allTableNames as $tableName) {
            if ($tableName === $this->tableName) {
                continue;
            }

            // HasMany / HasOne: Check if $tableName has a column named $currentModelForeignKey
            if (Schema::hasColumn($tableName, $currentModelForeignKey)) {
                $relatedModel = Str::studly(Str::singular($tableName));
                $relatedModelFQN = $this->findRelatedModelFQN($relatedModel, $currentModuleModelsNamespace);
                if (!$relatedModelFQN) {
                    continue;
                }

                $classForUse = $this->getClassForUse($relatedModelFQN, $currentModuleModelsNamespace, $uses);
                $relationName = Str::pluralStudly(Str::singular($tableName)); // e.g. Comments
                if (Str::singular($tableName) === $tableName) { // Heuristic for HasOne
                    $relationName = Str::studly(Str::singular($tableName)); // e.g. UserProfile
                    $relationships[] = "public function {$relationName}()\n    {\n        return \$this->hasOne({$classForUse}::class, '{$currentModelForeignKey}');\n    }";
                } else {
                    $relationships[] = "public function {$relationName}()\n    {\n        return \$this->hasMany({$classForUse}::class, '{$currentModelForeignKey}');\n    }";
                }
            }

            // BelongsToMany (Pivot Table Detection by convention: table1_table2)
            $parts = explode('_', $tableName);
            if (count($parts) === 2) { // e.g., post_tag
                $model1Singular = Str::singular($parts[0]);
                $model2Singular = Str::singular($parts[1]);

                $currentModelSingular = Str::singular(Str::snake($this->modelName));

                if (($model1Singular === $currentModelSingular || $model2Singular === $currentModelSingular)) {
                    $otherModelSingular = ($model1Singular === $currentModelSingular) ? $model2Singular : $model1Singular;
                    $relatedModel = Str::studly($otherModelSingular);
                    $relatedModelFQN = $this->findRelatedModelFQN($relatedModel, $currentModuleModelsNamespace);

                    if ($relatedModelFQN) {
                        $fk1 = Str::snake($currentModelSingular) . '_id'; // e.g., post_id
                        $fk2 = Str::snake($otherModelSingular) . '_id';   // e.g., tag_id

                        // Check if pivot table has these foreign key columns
                        if (Schema::hasColumn($tableName, $fk1) && Schema::hasColumn($tableName, $fk2)) {
                            $classForUse = $this->getClassForUse($relatedModelFQN, $currentModuleModelsNamespace, $uses);
                            $relationName = Str::pluralStudly($otherModelSingular); // e.g., Tags
                            $relationships[] = "public function {$relationName}()\n    {\n        return \$this->belongsToMany({$classForUse}::class, '{$tableName}', '{$fk1}', '{$fk2}');\n    }";
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
                $type = $columnDetail ? $columnDetail->type : 'string'; // Default to string if type unknown
                $isNullable = $columnDetail ? $columnDetail->nullable : true; // Assume nullable if unknown

                $columnRules[] = $isNullable ? 'nullable' : 'required';
                switch ($type) {
                    case 'string': case 'text': case 'char': case 'varchar': // Common string types
                        $columnRules[] = 'string';
                        $columnRules[] = 'max:255'; // Default max length for strings without Doctrine
                        break;
                    case 'integer': case 'bigint': case 'smallint': case 'mediumint': case 'tinyint': // Integer types
                        $columnRules[] = 'integer';
                        break;
                    case 'decimal': case 'float': case 'double': case 'numeric': // Numeric types
                        $columnRules[] = 'numeric';
                        break;
                    case 'boolean': $columnRules[] = 'boolean';
                        break;
                    case 'date': $columnRules[] = 'date';
                        break;
                    case 'datetime': case 'timestamp': $columnRules[] = 'date';
                        break; // Consider 'date_format:Y-m-d H:i:s'
                    case 'json': $columnRules[] = 'json';
                        break; // Or 'array' if you cast it
                    default: $columnRules[] = 'string';
                        break; // Fallback
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
    { /* ... (same as previous version) ... */
        $this->writeFile($this->basePath . '/Contracts/' . $this->modelName . 'ServiceInterface.php', $this->populateStub('service.interface', $this->getReplacements()));
        $this->writeFile($this->basePath . '/Services/' . $this->modelName . 'Service.php', $this->populateStub('service', $this->getReplacements()));
    }
    protected function generatePolicy()
    { /* ... (same as previous version) ... */
        $this->writeFile($this->basePath . '/Policies/' . $this->modelName . 'Policy.php', $this->populateStub('policy', $this->getReplacements()));
    }
    protected function generateEvents()
    { /* ... (same as previous version) ... */
        $eventTypes = ['model_created', 'model_updated', 'model_deleted'];
        foreach ($eventTypes as $eventType) {
            $replacements = $this->getReplacements();
            $eventNamePlaceholder = '{{' . Str::studly(str_replace('_', '', $eventType)) . 'Event}}';
            $actualEventNameForFile = $replacements[$eventNamePlaceholder] ?? $this->modelName . Str::studly(str_replace('model_', '', $eventType));
            $content = $this->populateStub('event.' . $eventType, $replacements);
            $this->writeFile($this->basePath . '/Events/' . $actualEventNameForFile . '.php', $content);
        }
    }
    protected function generateObserver()
    { /* ... (same as previous version) ... */
        $replacements = $this->getReplacements();
        $content = $this->populateStub('observer', $replacements);
        $filePath = $this->basePath . '/Observers/' . $this->modelName . 'Observer.php';
        $this->writeFile($filePath, $content);
    }
    protected function generateWebController()
    { /* ... (same as previous version) ... */
        $rootModuleNamespace = 'Modules\\' . $this->moduleName;
        $replacements = $this->getReplacements() + [
            '{{useService}}' => $this->generateService ? "use {$rootModuleNamespace}\\Contracts\\".$this->modelName."ServiceInterface;" : '',
            '{{serviceVariable}}' => $this->generateService ? "protected ".$this->modelName."ServiceInterface \$".$this->getServiceVarName().";" : '',
            '{{serviceInjection}}' => $this->generateService ? "\\{$rootModuleNamespace}\\Contracts\\".$this->modelName."ServiceInterface \$".$this->getServiceVarName() : '',
            '{{serviceAssignment}}' => $this->generateService ? "\$this->".$this->getServiceVarName()." = \$".$this->getServiceVarName().";" : '',
            '{{serviceCallGetAll}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->getAll(\$request->all())" : "\\{$rootModuleNamespace}\\Models\\".Str::studly($this->modelName)."::latest()->paginate(10)",
            '{{serviceCallGetById}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->getById(\${$this->getModelVarName()}->id)" : "\${$this->getModelVarName()}",
            '{{serviceCallCreate}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->create(\$request->validated())" : "\\{$rootModuleNamespace}\\Models\\".Str::studly($this->modelName)."::create(\$request->validated())",
            '{{serviceCallUpdate}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->update(\${$this->getModelVarName()}->id, \$request->validated())" : "\${$this->getModelVarName()}->update(\$request->validated())",
            '{{serviceCallDelete}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->delete(\${$this->getModelVarName()}->id)" : "\${$this->getModelVarName()}->delete()",
            '{{serviceCallRestore}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->restore(\$id)" : "\\{$rootModuleNamespace}\\Models\\".Str::studly($this->modelName)."::withTrashed()->find(\$id)?->restore()",
            '{{serviceCallForceDelete}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->forceDelete(\$id)" : "\\{$rootModuleNamespace}\\Models\\".Str::studly($this->modelName)."::withTrashed()->find(\$id)?->forceDelete()",
            '{{serviceCallGetDataForDataTable}}' => $this->generateService
                ? "\$this->".$this->getServiceVarName()."->getDataForDataTable(\$dataTableParams)"
                : "['data'=>[], 'total'=>0, 'filtered'=>0]; // Service not generated",
        ];
        $content = $this->populateStub('controller.web', $replacements);
        $this->writeFile($this->basePath . '/Http/Controllers/Web/' . $this->modelName . 'Controller.php', $content);
    }
    protected function generateApiController()
    { /* ... (same as previous version) ... */
        $rootModuleNamespace = 'Modules\\' . $this->moduleName;
        $replacements = $this->getReplacements() + [
            '{{useService}}' => $this->generateService ? "use {$rootModuleNamespace}\\Contracts\\".$this->modelName."ServiceInterface;" : '',
            '{{serviceVariable}}' => $this->generateService ? "protected ".$this->modelName."ServiceInterface \$".$this->getServiceVarName().";" : '',
            '{{serviceInjection}}' => $this->generateService ? "\\{$rootModuleNamespace}\\Contracts\\".$this->modelName."ServiceInterface \$".$this->getServiceVarName() : '',
            '{{serviceAssignment}}' => $this->generateService ? "\$this->".$this->getServiceVarName()." = \$".$this->getServiceVarName().";" : '',
            '{{serviceCallGetAllApi}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->getAllPaginated(\$request->all(), \$request->input('per_page', 15))" : "\\{$rootModuleNamespace}\\Models\\".Str::studly($this->modelName)."::latest()->paginate(\$request->input('per_page', 15))",
            '{{serviceCallGetByIdApi}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->getById(\${$this->getModelVarName()}->id)" : "\${$this->getModelVarName()}",
            '{{serviceCallCreateApi}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->create(\$request->validated())" : "\\{$rootModuleNamespace}\\Models\\".Str::studly($this->modelName)."::create(\$request->validated())",
            '{{serviceCallUpdateApi}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->update(\${$this->getModelVarName()}->id, \$request->validated())" : "\${$this->getModelVarName()}->update(\$request->validated())",
            '{{serviceCallDeleteApi}}' => $this->generateService ? "\$this->".$this->getServiceVarName()."->delete(\${$this->getModelVarName()}->id)" : "\${$this->getModelVarName()}->delete()",
        ];
        $content = $this->populateStub('controller.api', $replacements);
        $this->writeFile($this->basePath . '/Http/Controllers/Api/' . $this->modelName . 'Controller.php', $content);
    }
    protected function generateResource()
    { /* ... (same as previous version) ... */
        $columns = $this->getTableColumns();
        $resourceFields = [];
        $excludedColumns = ['password', 'remember_token'];
        if (!empty($columns)) {
            foreach ($columns as $column) {
                if (!in_array($column, $excludedColumns)) {
                    $resourceFields[] = "'{$column}' => \$this->whenNotNull(\$this->{$column})";
                }
            }
        } else {
            $resourceFields[] = "// 'id' => \$this->id,";
        }
        $idFieldPresent = false;
        foreach ($resourceFields as $fieldLine) {
            if (str_starts_with(trim($fieldLine), "'id'")) {
                $idFieldPresent = true;
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
    { /* ... (same as previous version, uses determineFieldType without Doctrine) ... */
        // $viewPathBase = $this->basePath . '/views/' . Str::kebab(Str::plural($this->modelName));
        $viewPathBase = $this->basePath . '/views/' ;
        $this->makeDirectory($viewPathBase);
        $columns = $this->getTableColumns();
        $excludedColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];
        $viewFieldsForShow = [];
        $formFieldsString = '';
        $tableHeaders = '';

        if (!empty($columns)) {
            foreach ($columns as $columnName) {
                if (in_array($columnName, $excludedColumns)) {
                    continue;
                }
                $label = Str::title(str_replace('_', ' ', $columnName));
                $viewFieldsForShow[] = $columnName;
                $tableHeaders .= "<th>{$label}</th>\n                    ";

                $fieldType = $this->determineFieldType($columnName);
                $fieldStubContent = $this->getStubContent("fields/{$fieldType}.stub");

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
        $actionColumnContent = $this->populateStub('views.actions', $commonReplacements + ['itemVar' => '$item']);

        $viewReplacements = $commonReplacements + [
            '{{formFields}}' => rtrim($formFieldsString),
            '{{tableHeaders}}' => rtrim($tableHeaders),
            '{{viewFields}}' => empty($viewFieldsForShow) ? "[]" : "['" . implode("', '", $viewFieldsForShow) . "']",
            '{{modelVarName}}' => $this->getModelVarName(),
            '{{modelPluralVarName}}' => Str::plural($this->getModelVarName()),
            '{{actionColumnStub}}' => $actionColumnContent,
        ];

        $formOnlyReplacements = $this->getReplacements() + ['{{formFields}}' => rtrim($formFieldsString), '{{modelVarName}}' => $this->getModelVarName()];
        $this->writeFile($viewPathBase . '/_form.blade.php', $this->populateStub('views._form', $formOnlyReplacements));

        foreach (['index', 'create', 'edit', 'show', 'trashed'] as $view) { // Added trashed view
            if (File::exists($this->stubsPath . '/views/' . $view . '.blade.stub')) { // Check if stub exists
                $this->writeFile($viewPathBase . '/' . $view . '.blade.php', $this->populateStub('views.' . $view, $viewReplacements));
            }
        }
    }
    protected function determineFieldType($columnName)
    { /* ... (same as previous version, relies on Schema::getColumnType and conventions) ... */
        if (Str::endsWith($columnName, '_id') && $columnName !== 'id') {
            return 'select';
        }
        if (Str::contains($columnName, ['description', 'notes', 'content', 'details', 'message'])) {
            return 'textarea';
        }

        $dbType = null;
        if (Schema::hasTable($this->tableName) && Schema::hasColumn($this->tableName, $columnName)) { // Check column exists
            try {
                $dbType = Schema::getColumnType($this->tableName, $columnName);
            } catch (\Exception $e) {
            }
        }

        if ($dbType) {
            if ($dbType === 'text') {
                return 'textarea';
            }
            if ($dbType === 'date') {
                return 'date';
            }
            if ($dbType === 'datetime' || $dbType === 'timestamp') {
                return 'datetime';
            } // Use datetime.stub
            if ($dbType === 'boolean' || $dbType === 'tinyint') {
                return 'checkbox';
            }
        }
        // Fallback to naming conventions
        if (Str::contains($columnName, ['_at', 'date'])) {
            return 'datetime';
        } // Or 'date' if preferred
        if (Str::contains($columnName, ['image', 'avatar', 'logo', 'file', 'document'])) {
            return 'file';
        }
        if (Str::startsWith($columnName, 'is_') || Str::startsWith($columnName, 'has_')) {
            return 'checkbox';
        }
        if (Str::contains($columnName, 'email')) {
            return 'email';
        }
        if (Str::contains($columnName, 'password')) {
            return 'password';
        }
        return 'text';
    }
    protected function getOptionsForSelect($columnName)
    { /* ... (same as previous version) ... */
        if (Str::endsWith($columnName, '_id')) {
            $relatedModelName = Str::studly(Str::singular(str_replace('_id', '', $columnName)));
            $relatedPluralVar = Str::plural(Str::camel($relatedModelName));
            return "<option value=\"\">-- Select {$relatedModelName} --</option>\n                    @foreach(\${$relatedPluralVar} ?? [] as \$relatedItem)\n                        <option value=\"{{\$relatedItem->id}}\" {{ (old('{$columnName}', \${$this->getModelVarName()}->{$columnName} ?? null) == \$relatedItem->id) ? 'selected' : '' }}>{{\$relatedItem->name ?? \$relatedItem->id}}</option>\n                    @endforeach";
        }
        if (Str::startsWith($columnName, 'is_') || Str::startsWith($columnName, 'has_')) {
            return "<option value=\"1\" {{ (old('{$columnName}', \${$this->getModelVarName()}->{$columnName} ?? 0) == 1) ? 'selected' : '' }}>Yes</option>\n                    <option value=\"0\" {{ (old('{$columnName}', \${$this->getModelVarName()}->{$columnName} ?? 0) == 0) ? 'selected' : '' }}>No</option>";
        }
        return '';
    }
    protected function generateRoutes()
    { /* ... (same as previous version) ... */
        $this->writeFile($this->basePath . '/routes/web.php', $this->populateStub('routes.web', $this->getReplacements()));
        if ($this->createApi) {
            $this->writeFile($this->basePath . '/routes/api.php', $this->populateStub('routes.api', $this->getReplacements()));
        }
    }
    protected function generateServiceProvider()
    { /* ... (same as previous version) ... */
        $rootModuleNamespace = 'Modules\\' . $this->moduleName;
        $policiesArray = [];
        $bindingsArray = [];
        $observersArray = [];
        if ($this->generatePolicy) {
            $policiesArray[] = "\\{$rootModuleNamespace}\\Models\\".$this->modelName."::class => \\{$rootModuleNamespace}\\Policies\\".$this->modelName."Policy::class,";
        }
        if ($this->generateService) {
            $bindingsArray[] = "\$this->app->bind(\\{$rootModuleNamespace}\\Contracts\\".$this->modelName."ServiceInterface::class, \\{$rootModuleNamespace}\\Services\\".$this->modelName."Service::class);";
        }
        if ($this->generateObserver) {
            $observersArray[] = "\\{$rootModuleNamespace}\\Models\\".$this->modelName."::observe(\\{$rootModuleNamespace}\\Observers\\".$this->modelName."Observer::class);";
        }

        $replacements = $this->getReplacements() + [
            '{{hasApiRoutes}}' => $this->createApi ? 'true' : 'false', '{{hasWebRoutes}}' => 'true',
            '{{policies}}' => empty($policiesArray) ? "// Model Policies" : implode("\n        ", $policiesArray),
            '{{bindings}}' => empty($bindingsArray) ? "// Service Bindings" : implode("\n        ", $bindingsArray),
            '{{observers}}' => empty($observersArray) ? "// Model Observers" : implode("\n        ", $observersArray),
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
