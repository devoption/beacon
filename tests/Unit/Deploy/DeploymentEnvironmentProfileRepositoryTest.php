<?php

declare(strict_types=1);

use DevOption\Beacon\Deploy\DeploymentEnvironmentProfileRepository;

it('discovers generated deployment environment profiles from chart values overlays', function (): void {
    $directory = beaconTestTempDirectory();
    $chartPath = $directory.'/charts/beacon-demo';

    mkdir($chartPath, 0755, true);
    file_put_contents($chartPath.'/values.local.yaml', "replicaCount: 1\n");
    file_put_contents($chartPath.'/values.production.yaml', "replicaCount: 3\n");
    file_put_contents($chartPath.'/values.staging.yaml', "replicaCount: 2\n");

    try {
        $profiles = (new DeploymentEnvironmentProfileRepository())->discover($chartPath);

        expect($profiles->names)->toBe(['local', 'staging', 'production'])
            ->and($profiles->default())->toBe('local')
            ->and($profiles->overlayRelativePath('staging'))->toBe('values.staging.yaml');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('falls back to the default deployment environment profile set when no overlays exist', function (): void {
    $directory = beaconTestTempDirectory();
    $chartPath = $directory.'/charts/beacon-demo';

    mkdir($chartPath, 0755, true);

    try {
        $profiles = (new DeploymentEnvironmentProfileRepository())->discover($chartPath);

        expect($profiles->names)->toBe(['local', 'staging', 'production'])
            ->and($profiles->default())->toBe('local');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});
