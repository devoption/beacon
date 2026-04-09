<?php

declare(strict_types=1);

use DevOption\Beacon\BeaconServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Testing\PendingCommand;
use Illuminate\Support\Facades\Process;

function fakeKubernetesContextDiscovery(string $currentContext = 'rancher-desktop', array $availableContexts = ['rancher-desktop', 'staging']): void
{
    Process::fake(function ($process) use ($currentContext, $availableContexts) {
        if ($process->command === ['kubectl', 'config', 'get-contexts', '-o', 'name']) {
            return Process::result(implode(PHP_EOL, $availableContexts).PHP_EOL, '', 0);
        }

        if ($process->command === ['kubectl', 'config', 'current-context']) {
            return Process::result($currentContext.PHP_EOL, '', 0);
        }

        if (array_slice($process->command, 0, 3) === ['helm', 'upgrade', '--install']) {
            return Process::result('Release deployed.', '', 0);
        }

        return Process::result();
    });
}

function expectBeaconDeployPrompts(
    PendingCommand $command,
    string $context = 'staging',
    string $namespace = 'preview',
): PendingCommand {
    return $command
        ->expectsPromptsIntro('Beacon will help you choose a Kubernetes deployment target.')
        ->expectsChoice(
            'Which Kubernetes context should Beacon deploy to?',
            $context,
            [
                'rancher-desktop' => 'rancher-desktop',
                'staging' => 'staging',
            ],
        )
        ->expectsQuestion('Which namespace should Beacon deploy into?', $namespace)
        ->expectsConfirmation('Continue with this deployment target?', 'yes');
}

function supportsDeployPendingPromptExpectations(): bool
{
    return version_compare(Application::VERSION, '12.0.0', '>=');
}

it('boots the deploy command through the package service provider', function (): void {
    expect($this->app->getProvider(BeaconServiceProvider::class))->not->toBeNull()
        ->and($this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all())->toHaveKey('beacon:deploy');
});

it('deploys to the current Kubernetes context when running non-interactively', function (): void {
    $directory = beaconTestApplicationDirectory();
    $originalBasePath = $this->app->basePath();

    $this->app->setBasePath($directory);
    $this->app['config']->set('app.name', 'Beacon Demo');

    mkdir($directory.'/charts/beacon-demo', 0755, true);
    fakeKubernetesContextDiscovery();

    try {
        $this->artisan('beacon:deploy', ['--no-interaction' => true])
            ->expectsOutputToContain('Deployment target')
            ->expectsOutputToContain('beacon-demo')
            ->expectsOutputToContain('rancher-desktop')
            ->expectsOutputToContain('default')
            ->expectsOutputToContain('Beacon deployment completed.')
            ->assertSuccessful();

        Process::assertRan(fn ($process) => $process->path === $directory
            && $process->command === [
                'helm',
                'upgrade',
                '--install',
                'beacon-demo',
                './charts/beacon-demo',
                '--namespace',
                'default',
                '--create-namespace',
                '--kube-context',
                'rancher-desktop',
            ]);
    } finally {
        $this->app->setBasePath($originalBasePath);
        removeBeaconTestDirectory($directory);
    }
});

it('allows the user to choose a Kubernetes context and namespace interactively', function (): void {
    if (! supportsDeployPendingPromptExpectations()) {
        $this->markTestSkipped('Pending command prompt expectations are not reliable on Laravel 11.');
    }

    $directory = beaconTestApplicationDirectory();
    $originalBasePath = $this->app->basePath();

    $this->app->setBasePath($directory);
    $this->app['config']->set('app.name', 'Beacon Demo');

    mkdir($directory.'/charts/beacon-demo', 0755, true);
    fakeKubernetesContextDiscovery();

    try {
        expectBeaconDeployPrompts($this->artisan('beacon:deploy'))
            ->expectsOutputToContain('Deployment target')
            ->expectsOutputToContain('staging')
            ->expectsOutputToContain('preview')
            ->expectsOutputToContain('Beacon deployment completed.')
            ->assertSuccessful();

        Process::assertRan(fn ($process) => $process->path === $directory
            && $process->command === [
                'helm',
                'upgrade',
                '--install',
                'beacon-demo',
                './charts/beacon-demo',
                '--namespace',
                'preview',
                '--create-namespace',
                '--kube-context',
                'staging',
            ]);
    } finally {
        $this->app->setBasePath($originalBasePath);
        removeBeaconTestDirectory($directory);
    }
});

it('trims the chosen namespace before invoking helm', function (): void {
    if (! supportsDeployPendingPromptExpectations()) {
        $this->markTestSkipped('Pending command prompt expectations are not reliable on Laravel 11.');
    }

    $directory = beaconTestApplicationDirectory();
    $originalBasePath = $this->app->basePath();

    $this->app->setBasePath($directory);
    $this->app['config']->set('app.name', 'Beacon Demo');

    mkdir($directory.'/charts/beacon-demo', 0755, true);
    fakeKubernetesContextDiscovery();

    try {
        expectBeaconDeployPrompts($this->artisan('beacon:deploy'), namespace: '  preview  ')
            ->expectsOutputToContain('preview')
            ->assertSuccessful();

        Process::assertRan(fn ($process) => $process->path === $directory
            && $process->command === [
                'helm',
                'upgrade',
                '--install',
                'beacon-demo',
                './charts/beacon-demo',
                '--namespace',
                'preview',
                '--create-namespace',
                '--kube-context',
                'staging',
            ]);
    } finally {
        $this->app->setBasePath($originalBasePath);
        removeBeaconTestDirectory($directory);
    }
});

it('falls back to the beacon chart slug when the application name normalizes to empty', function (): void {
    $directory = beaconTestApplicationDirectory();
    $originalBasePath = $this->app->basePath();

    $this->app->setBasePath($directory);
    $this->app['config']->set('app.name', '!!!');

    mkdir($directory.'/charts/beacon', 0755, true);
    fakeKubernetesContextDiscovery();

    try {
        $this->artisan('beacon:deploy', ['--no-interaction' => true])->assertSuccessful();

        Process::assertRan(fn ($process) => $process->path === $directory
            && $process->command === [
                'helm',
                'upgrade',
                '--install',
                'beacon',
                './charts/beacon',
                '--namespace',
                'default',
                '--create-namespace',
                '--kube-context',
                'rancher-desktop',
            ]);
    } finally {
        $this->app->setBasePath($originalBasePath);
        removeBeaconTestDirectory($directory);
    }
});

it('fails clearly when kubernetes contexts cannot be discovered', function (): void {
    Process::fake([
        '*' => Process::result('', 'kubectl config is unavailable.', 1),
    ]);

    $directory = beaconTestApplicationDirectory();
    $originalBasePath = $this->app->basePath();

    $this->app->setBasePath($directory);
    mkdir($directory.'/charts/beacon-demo', 0755, true);
    $this->app['config']->set('app.name', 'Beacon Demo');

    try {
        $this->artisan('beacon:deploy', ['--no-interaction' => true])
            ->expectsOutputToContain('Beacon deployment failed: Unable to discover Kubernetes contexts. kubectl config is unavailable.')
            ->assertExitCode(1);
    } finally {
        $this->app->setBasePath($originalBasePath);
        removeBeaconTestDirectory($directory);
    }
});

it('surfaces helm failures without duplicating the beacon deployment prefix', function (): void {
    $directory = beaconTestApplicationDirectory();
    $originalBasePath = $this->app->basePath();

    $this->app->setBasePath($directory);
    $this->app['config']->set('app.name', 'Beacon Demo');

    mkdir($directory.'/charts/beacon-demo', 0755, true);

    Process::fake(function ($process) {
        if ($process->command === ['kubectl', 'config', 'get-contexts', '-o', 'name']) {
            return Process::result("rancher-desktop\n", '', 0);
        }

        if ($process->command === ['kubectl', 'config', 'current-context']) {
            return Process::result("rancher-desktop\n", '', 0);
        }

        if (array_slice($process->command, 0, 3) === ['helm', 'upgrade', '--install']) {
            return Process::result('', 'release failed', 1);
        }

        return Process::result();
    });

    try {
        $this->artisan('beacon:deploy', ['--no-interaction' => true])
            ->expectsOutputToContain('Beacon deployment failed: release failed')
            ->assertExitCode(1);
    } finally {
        $this->app->setBasePath($originalBasePath);
        removeBeaconTestDirectory($directory);
    }
});
