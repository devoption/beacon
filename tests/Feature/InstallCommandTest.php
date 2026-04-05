<?php

declare(strict_types=1);

use DevOption\Beacon\BeaconServiceProvider;

it('boots the package service provider in testbench', function (): void {
    expect($this->app->getProvider(BeaconServiceProvider::class))->not->toBeNull();
});

it('registers the beacon install command', function (): void {
    $this->artisan('beacon:install')
        ->expectsOutputToContain('Beacon installation workflow will be implemented in a follow-up issue.')
        ->assertSuccessful();
});
