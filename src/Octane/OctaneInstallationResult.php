<?php

declare(strict_types=1);

namespace DevOption\Beacon\Octane;

final readonly class OctaneInstallationResult
{
    public function __construct(
        public OctaneInstallationStatus $status,
    ) {
    }

    public function summary(): string
    {
        return match ($this->status) {
            OctaneInstallationStatus::AlreadyInstalled => 'Already available',
            OctaneInstallationStatus::Installed => 'Installed now',
        };
    }
}
