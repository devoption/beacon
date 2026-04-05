<?php

declare(strict_types=1);

namespace DevOption\Beacon\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'beacon:install';

    protected $description = 'Install Beacon into the current Laravel application';

    public function handle(): int
    {
        $this->components->info('Beacon installation workflow will be implemented in a follow-up issue.');

        return self::SUCCESS;
    }
}
