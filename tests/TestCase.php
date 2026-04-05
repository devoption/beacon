<?php

declare(strict_types=1);

namespace DevOption\Beacon\Tests;

use DevOption\Beacon\BeaconServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BeaconServiceProvider::class,
        ];
    }
}
