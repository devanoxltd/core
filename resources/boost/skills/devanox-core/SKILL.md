---
name: devanox-core
description: >
  Configure and apply the Core package in Laravel applications.
license: MIT
metadata:
  author: Mr Chetan
---

# Core

Use this skill when a Laravel application needs to integrate the Core package.

## Primary Goal

- apply the `devanoxltd/core` package's public API in the smallest correct way

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project
- inspect the target code paths where the package should be applied

### 2. Apply the package's public API

#### Installation & Configuration
- Ensure `devanoxltd/core` is installed via composer (`composer require devanoxltd/core`).
- Publish configuration and resources if needed using `php artisan vendor:publish --tag="core"` (or specific tags like `core-config`, `core-migrations`, `core-views`, `core-lang`, `core-assets`).
- If tenancy features are required, publish tenancy config and migrations via `--tag="tenancy-config"` and `--tag="tenancy-migrations"`.
- Run migrations: `php artisan migrate`.

#### Commands
The package provides several artisan commands for module and tenancy management:
- **Module Commands:** `module:list`, `module:enable {module?}`, `module:disable {module?}`, `module:migrate`
- **Core Utilities:** `app:clean-up`, `migrate:check`, `devanox:license-check`
- **Tenancy Commands:** `tenancy:install`, `tenant:create-database {id}`, `tenant {artisanCommand}` (available when tenancy is enabled)

#### Helpers & Facades
- **Helpers:** `isAppInstalled()`, `modules()`, `tenancy()`, `tenant()`
- **Facade:** `\Devanox\Core\Facades\Core` maps to `\Devanox\Core\Core`.

#### Middleware & Routing
The package registers the following middleware that can be applied to routes:
- `Devanox\Core\Http\Middleware\InstallApp`
- `Devanox\Core\Http\Middleware\PreventAccessFromCentralDomains` (prepended to priority when tenancy is enabled)

When tenancy is enabled, configure tenant routes in `bootstrap/app.php` using the `PreventAccessFromCentralDomains` middleware to protect them.

#### Events
The package provides the following events when tenancy is enabled:
- `Devanox\Core\Events\Tenant\Created`
- `Devanox\Core\Events\Tenant\DatabaseCreated`

#### Components
- **Blade:** Components are registered under the `core::` namespace.
- **Livewire:** Livewire components are natively registered and can be used directly.

## Rules, References, and Templates

Read before executing:

- check `config/core.php` and `config/tenancy.php` for required configuration variables.
- do not document package internals here; keep the skill focused on adoption in Laravel apps.
- no additional resource files for this skill.

## Examples

- **Publishing specific assets:** `php artisan vendor:publish --tag="core-config"`
- **Enabling a module:** `php artisan module:enable MyModule`
- **Running code in a tenant context:** `tenancy()->run($tenant, fn($tenant) => /* your code */)`

## Anti-patterns

- Do not use internal classes like `Devanox\Core\Support\Module` directly if a helper or command exists for your use case.
- Do not run `tenancy:*` commands if `tenancy.enabled` is false in the configuration.
- Do not ignore the exceptions thrown by module commands when dealing with missing requirements or invalid licenses.
