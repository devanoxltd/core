<?php
declare(strict_types=1);
namespace Devanox\Core\Tests;
use Devanox\Core\CoreServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
        ];
    }
}

