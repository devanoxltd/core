<?php
declare(strict_types=1);
namespace Devanox\Core\Tests;
use Devanox\Core\Providers\CoreServiceProvider;
use Devanox\Core\Providers\RouteServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            RouteServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }
}

