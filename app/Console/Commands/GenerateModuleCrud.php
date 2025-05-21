<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class GenerateModuleCrud extends Command
{
    protected $generatedFiles = [];
    protected $signature = 'make:module-crud
                            {module : Module name}
                            {name : Database table name}
                            {stack : The development stack that should be installed (bootstrap,tailwind,livewire,api)}
                            {--route= : Custom route name}
                            {--force : Overwrite existing files}';

    protected $description = 'Generate a module with CRUD operations using ibex/crud-generator';

    public function handle()
    {
        $moduleName = $this->argument('module');
        $tableName = $this->argument('name');
        $stack = $this->argument('stack');
        $route = $this->option('route');


        // 1. Create Module
        $this->createModule($tableName, $moduleName);
        // return;



        // 2. Generate CRUD
        $this->generatedFiles = $this->generateCrud($tableName, $stack, $route, $moduleName);
        echo "<pre>".print_r($this->generatedFiles, true)."</pre>";
        // return;

        // 3. Generate Model
        $this->generateModel($tableName, $moduleName);


        // 4. Move Files to Module
        $this->moveFilesToModule($moduleName, $tableName);

        // 5. Cleanup
        // $this->cleanup($tableName);

        $this->info("Module {$moduleName} with {$tableName} CRUD created successfully!");
    }

    private function createModule($tableName, $moduleName)
    {
        Artisan::call('module:make', [
           'name' => [$moduleName],
           '--force' => $this->option('force')
        ]);

        $modelName = Str::studly(Str::singular($tableName));

        // echo "Model name {$modelName} !\n";
        // exit;

        Artisan::call("module:make-model ".Str::studly(Str::singular($modelName))." ".$moduleName." --all");
        /*Artisan::call('module:make-model ', [
            'model' => [$tableName)],
            'module' => [$moduleName],
            // '--force' => $this->option('force'),
            "--all" => "",

        ]);*/
        $this->info("Module {$moduleName} created!");
    }



    private function generateCrud($tableName, $stack, $route, $moduleName)
    {
        $command = "make:custom-crud {$tableName} {$stack}";
        if ($route) {
            $command .= " --route=\"{$route}\"";
        }
        if ($moduleName) {
            $command .= " --module=\"{$moduleName}\"";
        }

        if ($this->option('force')) {
            // $command .= " --force";
        }


        $this->info("Command: {$command}");


        Artisan::call($command, [], $this->getOutput());
        // echo "Output...".$output = Artisan::output();
        // $output = Artisan::call($command);

        $this->info("CRUD for {$tableName} generated successfully!");
        // return json_decode($output);

        return Session::get('generatedFiles');

    }
    private function generateModel($tableName, $moduleName)
    {
        $modelPath = "DryRun\\". $moduleName."\\app\\Models";

        config(['models.path' => $modelPath]);

        $command = "code:custom-models --table={$tableName}";


        $this->info("Command: {$command}");


        Artisan::call($command, [], $this->getOutput());
        // echo "Output...".$output = Artisan::output();
        // $output = Artisan::call($command);

        $this->info("Model for {$tableName} generated successfully!");


    }

    private function moveFilesToModule($moduleName, $tableName)
    {
        $this->moveModel($moduleName, $tableName);
        // $this->moveController($moduleName, $tableName);
        // $this->moveViews($moduleName, $tableName);
        // $this->moveRequests($moduleName, $tableName);
        // $this->moveResource($moduleName, $tableName);
        // $this->moveMigration($moduleName, $tableName);
    }

    private function moveModel($moduleName, $tableName)
    {
        $source = app_path("Models/{$tableName}.php");
        $source = str_replace("'", "", $this->generatedFiles[$tableName]['model']);

        $location = str_replace("'", "", $this->generatedFiles[$tableName]['modelName']);

        $destination = module_path($moduleName, "app/Models/".$location);

        $source =  str_replace('\\', '/', $source);
        $source =  str_replace('//', '/', $source);
        $destination =  str_replace('\\', '/', $destination);
        $destination =  str_replace('//', '/', $destination);


        echo "Source {$source} Destination {$destination} \n";

        // $basePath = base_path()."/";
        // $basePath = str_replace('\\', '/', $basePath);
        // $basePath = str_replace('//', '/', $basePath);

        // $source =  str_replace($basePath, '', $source);
        // $destination =  str_replace($basePath, '', $destination);

        echo "\n Source {$source} Destination {$destination} \n";

        echo "Moving model from {$source} to {$destination} =".File::exists($source)."-- =".File::exists($destination)."--  \n";

        if (!File::exists($source)) {
            echo "Model not exists {$source} \n";

            return;
        }
        File::move($source, $destination);
        $this->updateFileNamespace($destination, "Modules\\{$moduleName}\\Models");




    }

    private function moveController($moduleName, $tableName)
    {


        $source = app_path("Http/Controllers/{$tableName}Controller.php");
        $source = $this->generatedFiles[$tableName]['controller'];

        $destination = module_path($moduleName, "app/Http/Controllers/{$tableName}Controller.php");



        $source =  str_replace('\\', '/', $source);
        $source =  str_replace('//', '/', $source);
        $destination =  str_replace('\\', '/', $destination);
        $destination =  str_replace('//', '/', $destination);

        if (File::exists($source)) {
            echo "File exists {$source} \n";
        } else {
            $source =  str_replace('/', '\\', $source);
            $destination =  str_replace('/', '\\', $destination);
            if (File::exists($source)) {
                echo "File exists {$source} \n";
            }
        }
        echo "Moving controller from {$source} to {$destination}\n";
        return;

        if (File::exists($source)) {
            File::move($source, $destination);
            $this->updateFileNamespace($destination, "Modules\\{$moduleName}\\Http\\Controllers");
            // $this->updateParentController($destination, "Modules\\{$moduleName}\\Http\\Controllers\\Controller");
            $this->updateParentController($destination, "Controller");
        } else {
            echo "Controller not exists\n";
            // return;
        }
    }

    private function moveViews($moduleName, $tableName)
    {
        $source = resource_path("views/{$tableName}");
        $source = $this->generatedFiles[$tableName]['views'];
        // $destination = module_path($moduleName, "Resources/views/{$tableName}");
        $destination = module_path($moduleName, "Resources/views");

        $source =  str_replace('\\', '/', $source);
        $source =  str_replace('//', '/', $source);
        $destination =  str_replace('\\', '/', $destination);
        $destination =  str_replace('//', '/', $destination);


        echo "Moving views from {$source} to {$destination}\n";
        return;

        if (File::exists($source)) {
            File::moveDirectory($source, $destination);
        } else {
            echo "Views not exists\n";
            // return;
        }
    }

    private function moveRequests($moduleName, $tableName)
    {
        $source = app_path("Http/Requests/{$tableName}Request.php");
        $source = $this->generatedFiles[$tableName]['request'];

        $destination = module_path($moduleName, "app/Http/Requests/{$tableName}Request.php");

        $source =  str_replace('\\', '/', $source);
        $source =  str_replace('//', '/', $source);
        $destination =  str_replace('\\', '/', $destination);
        $destination =  str_replace('//', '/', $destination);

        if (File::exists($source)) {
            echo "File exists {$source} \n";
        }
        echo "Moving request from {$source} to {$destination}\n";
        return;

        if (File::exists($source)) {
            File::move($source, $destination);
            $this->updateFileNamespace($destination, "Modules\\{$moduleName}\\Http\\Requests");
        } else {
            echo "Request not exists\n";
            // return;
        }
    }
    private function moveResource($moduleName, $tableName)
    {
        $source = app_path("Http/Requests/{$tableName}Resource.php");
        $source = $this->generatedFiles[$tableName]['resources'];

        $destination = module_path($moduleName, "app/Http/Resources/{$tableName}Resource.php");

        $source =  str_replace('\\', '/', $source);
        $source =  str_replace('//', '/', $source);
        $destination =  str_replace('\\', '/', $destination);
        $destination =  str_replace('//', '/', $destination);

        if (File::exists($source)) {
            echo "File exists {$source} \n";
        }
        echo "Moving resource from {$source} to {$destination}\n";
        return;

        if (File::exists($source)) {
            File::move($source, $destination);
            $this->updateFileNamespace($destination, "Modules\\{$moduleName}\\Http\\Resource");
        } else {
            echo "Resource not exists\n";
            // return;
        }
    }

    private function moveMigration($moduleName, $tableName)
    {
        return;
        $migrations = File::glob(database_path("migrations/*create_{$tableName}_table.php"));

        //echo "Moving migrations from " . implode(", ", $migrations) . " to module\n";

        foreach ($migrations as $migration) {
            $filename = basename($migration);
            $destination = module_path($moduleName, "Database/Migrations/{$filename}");
            File::move($migration, $destination);
        }
    }

    private function cleanup($tableName)
    {
        // echo "Cleaning up files...\n";
        // return;

        File::deleteDirectory(resource_path("views/{$tableName}"));
        File::delete(app_path("Http/Controllers/{$tableName}Controller.php"));
        File::delete(app_path("Models/{$tableName}.php"));
        File::delete(app_path("Http/Requests/{$tableName}Request.php"));
    }

    private function updateFileNamespace($filePath, $newNamespace)
    {
        $content = File::get($filePath);
        $content = preg_replace('/namespace App\\\\(Models|Http\\\Requests|Http\\\Controllers);/', "namespace {$newNamespace};", $content);
        $content = preg_replace('/use App\\\\(Models|Http\\\Requests|Http\\\Controllers);/', "use {$newNamespace};", $content);
        $content = str_replace('use App\\Http\\Requests\\', "use {$newNamespace}", $content);
        File::put($filePath, $content);
    }

    private function updateParentController($filePath, $newParent)
    {
        $content = File::get($filePath);
        $content = str_replace('use Illuminate\Http\Request;', "use Illuminate\Http\Request; \n use App\Http\Controllers\Controller;", $content);
        File::put($filePath, $content);
    }


}
