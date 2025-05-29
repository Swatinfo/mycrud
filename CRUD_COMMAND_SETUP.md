# Laravel Advanced CRUD Module Generator

This Artisan command (`php artisan make:crud-module`) accelerates development by generating a complete CRUD (Create, Read, Update, Delete) module for your Laravel application. Modules are created in a root `Modules/` directory, promoting a modular architecture.

**Features:**

* Generates Controllers (Web & API), Models, Requests, API Resources.
* Optional generation of:
    * Service Layer (Services & Interfaces) for business logic.
    * Policies for authorization.
    * Model Observers for audit logging (basic setup).
    * Model Events.
* Infers database schema for fillable attributes, basic validation rules, and casts.
* Attempts to detect and generate model relationships (BelongsTo, HasOne, HasMany, BelongsToMany).
* Generates Blade views with Bootstrap 5 styling, including:
    * Index view with server-side AJAX DataTables.
    * Create, Edit, Show, and Trashed views.
    * Form partial (`_form.blade.php`) built using field-specific stubs.
    * Customizable action column in DataTables.
* Implements Soft Deletes by default.
* `--dry-run` option to generate files in a separate `dryrun/` directory for review.
* Customizable through stub files.

## Prerequisites

1.  **Laravel Project:** A working Laravel application.
2.  **Database Setup:** Database configured and initial migrations run.
3.  **Composer:** PHP dependency manager.
4.  **Doctrine DBAL (Recommended):** For better schema introspection.
    ```bash
    composer require doctrine/dbal
    ```
5.  **Frontend Scaffolding (Recommended for Views):**
    * Laravel Breeze/Jetstream, or manual Bootstrap 5 & Font Awesome setup.
    * jQuery (required by DataTables, CDN link included in stub).

## Phase 1: Installation & Setup

1.  **Copy the Artisan Command:**
    * Take the `MakeCrudModuleCommand.php` file.
    * Place it in your Laravel project at `app/Console/Commands/MakeCrudModuleCommand.php`.

2.  **Register the Command:**
    * Open `app/Console/Kernel.php`.
    * Add the command to the `$commands` array:
        ```php
        protected $commands = [
            // ... other commands
            \App\Console\Commands\MakeCrudModuleCommand::class,
        ];
        ```

3.  **Create Stub Files:**
    * In your project's **root directory**, create the following structure: `stubs/crud-module/`
    * Inside `stubs/crud-module/`, create a `fields/` subdirectory and a `views/` subdirectory.
    * Populate these directories with all the `.stub` and `.blade.stub` files provided for the generator (model, controller, views, field stubs, etc.). Ensure filenames are exact.
        * `stubs/crud-module/fields/` should contain: `checkbox.stub`, `date.stub`, `datetime.stub`, `email.stub`, `file.stub`, `password.stub`, `radio.stub`, `select.stub`, `text.stub`, `textarea.stub`.
        * `stubs/crud-module/views/` should contain: `actions.blade.stub`, `create.blade.stub`, `edit.blade.stub`, `index.blade.stub`, `show.blade.stub`, `trashed.blade.stub`, `_form.blade.stub`.
        * `stubs/crud-module/` (root) should contain the rest: `controller.api.stub`, `controller.web.stub`, event stubs, `model.stub`, `observer.stub`, `policy.stub`, `provider.stub`, `request.stub`, `resource.stub`, route stubs, service stubs.

## Phase 2: Generating a Module

1.  **Define Database Table:**
    For best results, ensure the database table for your module exists with its schema defined *before* running the command.

2.  **Run the Command:**
    Open your terminal in the project root and use:
    ```bash
    php artisan make:crud-module ModuleName --table=your_table_name [options]
    ```
    * **`ModuleName`**: The StudlyCase name for your module (e.g., `Product`, `BlogCategory`).
    * **`--table=your_table_name`**: (Optional) Specify the database table. If omitted, it's inferred from `ModuleName`.
    * **Common Options:**
        * `--service`: Generate service layer.
        * `--policy`: Generate policy for authorization.
        * `--observer`: Generate observer for audit logs.
        * `--events`: Generate model events.
        * `--dry-run`: Output files to `YourProjectRoot/dryrun/ModuleName/` for review.
        * `--force`: Overwrite existing files without prompting.

    **Example:**
    ```bash
    php artisan make:crud-module BlogPost --table=blog_posts --service --policy --observer
    php artisan make:crud-module Brands --table=brands --dry-run --policy --service --observer --events  
    ```

## Phase 3: Post-Generation Activation

If you used `--dry-run`, first move the generated module folder (e.g., `dryrun/BlogPost`) to the project's root `Modules/` directory (create `Modules/` if it doesn't exist).

Then, for **all** generated modules:

1.  **Configure PSR-4 Autoloading:**
    * Open `composer.json`.
    * Ensure the `autoload.psr-4` section maps the `Modules\` namespace:
        ```json
        "autoload": {
            "psr-4": {
                "App\\": "app/",
                "Modules\\": "Modules/", // <-- Ensure this line exists
                // ... other mappings
            }
        },
        ```

2.  **Update Autoloader:**
    ```bash
    composer dump-autoload
    ```

3.  **Register Service Provider:**
    * Open `config/app.php`.
    * Add the module's service provider to the `providers` array:
        ```php
        'providers' => [
            // ...
            Modules\YourModuleName\Providers\YourModuleNameServiceProvider::class, // Replace YourModuleName
        ],
        ```

4.  **Review Policy & Observer Registration (if generated):**
    * The module's Service Provider attempts to register policies and observers. Verify this in `Modules/YourModuleName/Providers/YourModuleNameServiceProvider.php`.
    * For observers (audit logs), configure the `audit` logging channel in `config/logging.php` or customize the observer's logging logic.

5.  **Run Migrations:**
    If your module requires new database tables (that you haven't created yet), create and run their migrations:
    ```bash
    php artisan make:migration create_your_module_table
    php artisan migrate
    ```

6.  **Storage Link (for file uploads):**
    ```bash
    php artisan storage:link
    ```

7.  **Permissions Setup (Manual):**
    * Define permissions (e.g., `view posts`, `create posts`) and assign them to roles/users using your chosen permission system (e.g., `spatie/laravel-permission`).
    * Update `TODO` comments in the generated Policy files (`Modules/YourModuleName/Policies/`) with your specific permission checks.

## Customization

* **Stubs:** Modify any `.stub` files in `stubs/crud-module/` to change the default generated code structure.
    * `fields/`: Customize individual form field HTML.
    * `views/actions.blade.stub`: Change DataTables action buttons.
* **Generated Code:** Refine relationships in models, validation rules in requests, business logic in services, authorization in policies, and audit details in observers.

This generator provides a strong foundation. Tailor the generated code and stubs to perfectly fit your project's needs and coding standards.
