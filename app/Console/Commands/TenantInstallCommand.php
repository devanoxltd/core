<?php

declare(strict_types=1);

namespace Devanox\Core\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Description('Install the Tenancy package and generate custom models')]
#[Signature('tenancy:install')]
final class TenantInstallCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info(__('core::tenancy.commands.install.installing'));

        // 1. Publish Config and Migrations
        $this->call('vendor:publish', ['--tag' => 'tenancy-config']);
        $this->call('vendor:publish', ['--tag' => 'tenancy-migrations']);

        // 2. Generate the Custom Tenant Model for the app
        $this->createAppModel('Tenant');
        $this->createAppModel('Domain');

        $this->info(__('core::tenancy.commands.install.success'));

        return self::SUCCESS;
    }

    private function createAppModel(string $modelName): void
    {
        $appModelPath = app_path("Models/{$modelName}.php");

        if (! File::exists($appModelPath)) {
            $stub = <<<EOT
<?php

declare(strict_types=1);

namespace App\Models;

use Devanox\Core\Models\\{$modelName} as Base{$modelName};

class {$modelName} extends Base{$modelName}
{
    // Add your custom {$modelName} logic, mutators, and relationships here.
}
EOT;
            // Ensure the Models directory exists
            File::ensureDirectoryExists(app_path('Models'));

            File::put($appModelPath, $stub);
            $this->line(__('core::tenancy.commands.install.created_model', ['model' => $modelName]));
        } else {
            $this->comment(__('core::tenancy.commands.install.model_exists', ['model' => $modelName]));
        }
    }
}
