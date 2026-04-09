<?php

declare(strict_types=1);

namespace DevOption\Beacon\Deploy;

final class DeploymentEnvironmentProfileRepository
{
    public function discover(string $chartAbsolutePath): DeploymentEnvironmentProfiles
    {
        $environmentNames = [];

        foreach (glob(rtrim($chartAbsolutePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'values.*.yaml') ?: [] as $path) {
            $filename = basename($path);

            if (preg_match('/^values\.([a-z0-9-]+)\.yaml$/', $filename, $matches) === 1) {
                $environmentNames[] = $matches[1];
            }
        }

        $environmentNames = array_values(array_unique($environmentNames));

        $orderedNames = [];

        foreach (DeploymentEnvironmentProfiles::defaults() as $defaultEnvironment) {
            if (in_array($defaultEnvironment, $environmentNames, true)) {
                $orderedNames[] = $defaultEnvironment;
            }
        }

        $customNames = array_values(array_diff($environmentNames, $orderedNames));
        sort($customNames);

        return new DeploymentEnvironmentProfiles(
            $environmentNames !== [] ? array_merge($orderedNames, $customNames) : DeploymentEnvironmentProfiles::defaults(),
        );
    }
}
