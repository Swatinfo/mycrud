composer require nwidart/laravel-modules
composer require ibex/crud-generator --dev
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"
php artisan vendor:publish --tag=crud



add following code into composer.json file
"extra": {
    "merge-plugin": {
        "include": [
            "Modules/*/composer.json"
        ]
    }
},

{
  "autoload": {
    "psr-4": {      
      "Modules\\": "Modules/"
    }
  }
}


composer dump-autoload -o


php artisan make:command GenerateModuleCrud
