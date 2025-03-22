# Core Package

Core package for Laravel projects providing module support, helpers, and license verification. This package is developed for use in Devanox Private Limited projects.

## Installation

To install the package, use composer:

```bash
composer require devanox/core-package
```


## Configuration

Publish the configuration file using the following command:

```bash
php artisan vendor:publish --provider="Devanox\CorePackage\Providers\CorePackageServiceProvider"
```

## Usage
### Middleware
To use the middleware, you can add it to your `bootstrap/app.php` file in the `middleware` array:

```php
$middleware->append(\Devanox\Core\Http\Middleware\InstallApp::class);
```
### License Verification
