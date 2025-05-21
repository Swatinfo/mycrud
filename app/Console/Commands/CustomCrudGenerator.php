<?php

namespace App\Console\Commands;

use Ibex\CrudGenerator\Commands\CrudGenerator;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Session;

/**
 * Class CustomCrudGenerator.
 *
 * Extends the CrudGenerator to add custom functionality
 */
class CustomCrudGenerator extends CrudGenerator
{
    protected Filesystem $files;

    protected $signature = 'make:custom-crud
                            {name : Table name}
                            {stack : The development stack that should be installed (bootstrap,tailwind,livewire,api)}
                            {--route= : Custom route name}
                            {--module== : Custom Module name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Laravel CRUD operations with custom functionality';


    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
    }

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    public function handle()
    {
        // $this->info('Running Crud Generator ...');

        $this->table = $this->argument('name');
        $this->options['module'] = $this->option('module');

        $this->info('Running Crud Generator ...');

        $this->table = $this->getNameInput();

        // If table not exist in DB return
        if (! $this->tableExists()) {
            $this->error("`$this->table` table not exist");

            return false;
        }

        // Build the class name from table name
        $this->name = $this->_buildClassName();

        parent::buildOptions();
        // Generate the crud
        $this->buildModel();
        $this->buildApiController();

        parent::buildController()
            ->buildViews()
            ->writeRoute();


        $this->info('Created Successfully.');
        // $generatorCommand = new CrudGenerator(new Filesystem());

        // $result = $generatorCommand->handle();
        $this->name = Str::studly(Str::singular($this->table));

        $name = Str::kebab($this->name);
        $generatedFiles["{$this->table}"] = array(
            'apiController' => "'".$this->_getApiControllerPath($this->name)."'",
            'apiControllerName' => "'".basename($this->_getApiControllerPath($this->name))."'",
            'controller' =>  "'".$this->_getControllerPath($this->name)."'",
            'controllerName' =>  "'".basename($this->_getControllerPath($this->name))."'",
            'request' =>  "'".$this->_getRequestPath($this->name)."'",
            'requestName' =>  "'".basename($this->_getRequestPath($this->name))."'",
            'resources' =>  "'".$this->_getResourcePath($this->name)."'",
            'resourcesName' =>  "'".basename($this->_getResourcePath($this->name))."'",
            'model' =>  "'".$this->_getModelPath($this->name)."'",
            'modelName' =>  "'".basename($this->_getModelPath($this->name))."'",
            'views' =>  "'".resource_path("views/$this->name")."'",
        );


        Session::put('generatedFiles', $generatedFiles);
    }




    /**
     * Build the API Controller Class and save in app/Http/Controllers.
     *
     * @return $this
     * @throws FileNotFoundException
     */
    protected function buildApiController(): static
    {
        if ($this->options['stack'] == 'livewire') {
            $this->buildLivewire();

            return $this;
        }

        $controllerPath = $this->_getApiControllerPath($this->name);

        if ($this->files->exists($controllerPath) && $this->ask('Already exist API Controller '.$this->name.'. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Controller ...');

        $replace = $this->buildReplacements();

        $stubFolder = 'api/';

        $controllerTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub($stubFolder.'Controller')
        );

        $this->write($controllerPath, $controllerTemplate);


        $resourcePath = $this->_getResourcePath($this->name);

        $resourceTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub($stubFolder.'Resource')
        );

        $this->write($resourcePath, $resourceTemplate);


        return $this;
    }

    /**
     * @return $this
     * @throws FileNotFoundException
     *
     */
    protected function buildModel(): static
    {
        $modelPath = $this->_getModelPath($this->name);

        if ($this->files->exists($modelPath) && $this->ask('Already exist Model. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Model ...');

        // Make the models attributes and replacement
        $replace = array_merge($this->buildReplacements(), $this->modelReplacements(), array('{{tableName}}' => $this->table));

        $modelStub = $this->getStub('Model');

        $modelTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $modelStub
        );

        $this->write($modelPath, $modelTemplate);

        // Make Request Class
        $requestPath = $this->_getRequestPath($this->name);

        $this->info('Creating Request Class ...');

        $requestTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('Request')
        );

        $this->write($requestPath, $requestTemplate);

        return $this;
    }

    /**
     * Make the class name from table name.
     *
     * @return string
     */
    private function _buildClassName(): string
    {
        return Str::studly(Str::singular($this->table));
    }


    /**
     * @param  string  $name
     *
     * @return string
     */
    protected function _getControllerPath(string $name): string
    {
        // echo "DryRun\\". $this->options['module']."\\app\\".$this->_getNamespacePath($this->controllerNamespace)."{$name}Controller.php";
        // echo base_path("DryRun\\". $this->options['module']."\\app\\".$this->_getNamespacePath($this->controllerNamespace)."{$name}Controller.php");
        // exit;
        return base_path("DryRun\\". $this->options['module']."\\app\\".$this->_getNamespacePath($this->controllerNamespace)."{$name}Controller.php");
    }

    /**
     * @param  string  $name
     *
     * @return string
     */
    protected function _getApiControllerPath(string $name): string
    {
        return base_path("DryRun\\". $this->options['module']."\\app\\".$this->_getNamespacePath($this->apiControllerNamespace)."{$name}Controller.php");
    }

    /**
     * @param  string  $name
     *
     * @return string
     */
    protected function _getResourcePath(string $name): string
    {
        return base_path("DryRun\\". $this->options['module']."\\app\\".$this->_getNamespacePath($this->resourceNamespace)."{$name}Resource.php");
    }

    /**
     * @param  string  $name
     *
     * @return string
     */
    protected function _getLivewirePath(string $name): string
    {
        return base_path("DryRun\\".$this->_getNamespacePath($this->livewireNamespace)."{$name}.php");
    }

    /**
     * @param  string  $name
     *
     * @return string
     */
    protected function _getRequestPath(string $name): string
    {
        return base_path("DryRun\\". $this->options['module']."\\app\\".$this->_getNamespacePath($this->requestNamespace)."{$name}Request.php");
    }

    /**
     * @param  string  $name
     *
     * @return string
     */
    protected function _getModelPath(string $name): string
    {
        return $this->makeDirectory(base_path("DryRun\\". $this->options['module']."\\app\\".$this->_getNamespacePath($this->modelNamespace)."$name.php"));
    }

    /**
     * Get the path from namespace.
     *
     * @param  string  $namespace
     *
     * @return string
     */
    private function _getNamespacePath(string $namespace): string
    {
        echo "Name Space...".$str = Str::start(Str::finish(Str::after($namespace, ''), '\\'), '\\');

        return str_replace('\\', '/', $str);
    }



    /**
    * Get the default layout path.
    *
    * @return string
    */
    private function _getLayoutPath(): string
    {
        return $this->makeDirectory(resource_path("/views/layouts/app.blade.php"));
    }

    /**
     * @param  string  $view
     *
     * @return string
     */
    protected function _getViewPath(string $view): string
    {
        $name = Str::kebab($this->name);
        $path = match ($this->options['stack']) {
            'livewire' => "/views/livewire/$name/$view.blade.php",
            default => "/views/$name/$view.blade.php"
        };

        return $this->makeDirectory(base_path("DryRun\\".$this->options['module'].$path));
    }


    /**
    * Build the replacement.
    *
    * @return array
    */
    protected function buildReplacements(): array
    {
        return [
            '{{layout}}' => $this->layout,
            '{{modelName}}' => $this->name,
            '{{modelTitle}}' => Str::title(Str::snake($this->name, ' ')),
            '{{modelTitlePlural}}' => Str::title(Str::snake(Str::plural($this->name), ' ')),
            '{{modelNamespace}}' => "DryRun\\". $this->options['module']."\\".$this->modelNamespace,
            '{{controllerNamespace}}' => "DryRun\\". $this->options['module']."\\".$this->controllerNamespace,
            '{{apiControllerNamespace}}' => "DryRun\\". $this->options['module']."\\".$this->apiControllerNamespace,
            '{{resourceNamespace}}' => "DryRun\\". $this->options['module']."\\".$this->resourceNamespace,
            '{{requestNamespace}}' => "DryRun\\". $this->options['module']."\\".$this->requestNamespace,
            '{{livewireNamespace}}' => "DryRun\\". $this->options['module']."\\".$this->livewireNamespace,
            '{{modelNamePluralLowerCase}}' => Str::camel(Str::plural($this->name)),
            '{{modelNamePluralUpperCase}}' => ucfirst(Str::plural($this->name)),
            '{{modelNameLowerCase}}' => Str::camel($this->name),
            '{{modelRoute}}' => $this->_getRoute(),
            '{{modelView}}' => Str::kebab($this->name),
        ];
    }

    protected function _getRoute(): string
    {
        return $this->options['route'] ?? Str::lower(Str::studly(Str::singular($this->name)));
    }


}
