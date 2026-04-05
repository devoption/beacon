<?php

declare(strict_types=1);

namespace DevOption\Beacon;

use DevOption\Beacon\Commands\InstallCommand;
use Illuminate\Support\ServiceProvider;

class BeaconServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
