<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\ComponentAttributeBag;

final class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/layouts', 'layouts');

        ComponentAttributeBag::macro('sanitize', function (): ComponentAttributeBag {
            /** @var ComponentAttributeBag $this */
            /** @var array<string> $sanitizeAttributes */
            $sanitizeAttributes = config('view.sanitize_attributes', []);

            if (empty($sanitizeAttributes)) {
                return $this;
            }

            $attributes = $this->getAttributes();

            foreach ($attributes as $key => $value) {
                if ($value === true) {
                    foreach ($sanitizeAttributes as $sanitizeAttribute) {
                        if (str($key)->startsWith($sanitizeAttribute) || $key === $sanitizeAttribute) {
                            $attributes[$key] = '';
                        }
                    }
                }
            }

            $this->setAttributes($attributes);

            return $this;
        });
    }
}
