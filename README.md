<div align="center">
    <h1>Core</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/devanoxltd/core"><img src="https://img.shields.io/packagist/v/devanoxltd/core.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/devanoxltd/core"><img src="https://img.shields.io/packagist/php-v/devanoxltd/core.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/devanoxltd/core"><img src="https://badge.laravel.cloud/badge/devanoxltd/core?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/devanoxltd/core/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/devanoxltd/core/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/devanoxltd/core"><img src="https://img.shields.io/packagist/dt/devanoxltd/core.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Core package for Laravel projects providing module support, helpers, and license verification. This package is developed for use in Devanox Private Limited projects.

## Installation

You can install the package via Composer:

```bash
composer require devanoxltd/core
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="core"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="core-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="core-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="core-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="core-lang"
```

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="core-assets"
```

## Usage

<!-- Add a basic usage example here. -->

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Core! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Mr Chetan](https://github.com/devanoxltd)
- [All Contributors](../../contributors)

## License

Core is open-sourced software licensed under the [MIT license](LICENSE.md).
