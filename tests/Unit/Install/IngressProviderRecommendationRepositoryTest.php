<?php

declare(strict_types=1);

use DevOption\Beacon\Install\IngressProviderRecommendationRepository;
use DevOption\Beacon\Tests\TestCase;
use Illuminate\Support\Facades\Process;

uses(TestCase::class);

it('recommends traefik for rancher desktop contexts', function (): void {
    Process::fake([
        '*' => Process::result("rancher-desktop\n", '', 0),
    ]);

    $repository = new IngressProviderRecommendationRepository;

    $recommendation = $repository->recommend('/tmp/app');

    expect($recommendation)->not->toBeNull()
        ->and($recommendation?->provider)->toBe('traefik')
        ->and($recommendation?->clusterContext)->toBe('rancher-desktop');
});

it('recommends ingress nginx for nginx-oriented contexts', function (): void {
    Process::fake([
        '*' => Process::result("kind-nginx\n", '', 0),
    ]);

    $repository = new IngressProviderRecommendationRepository;

    $recommendation = $repository->recommend('/tmp/app');

    expect($recommendation)->not->toBeNull()
        ->and($recommendation?->provider)->toBe('nginx');
});

it('returns no recommendation when kubectl is unavailable or the context is unknown', function (): void {
    Process::fake([
        '*' => Process::result('', 'kubectl not available', 1),
    ]);

    $repository = new IngressProviderRecommendationRepository;

    expect($repository->recommend('/tmp/app'))->toBeNull();

    Process::fake([
        '*' => Process::result("production-cluster\n", '', 0),
    ]);

    expect($repository->recommend('/tmp/app'))->toBeNull();
});
