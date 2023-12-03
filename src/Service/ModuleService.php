<?php

namespace App\Service;

use Symfony\Component\Yaml\Yaml;

class ModuleService
{
    private $moduleConfig;

    public function __construct(string $moduleConfig)
    {
        $this->moduleConfig = $moduleConfig;
    }

    public function getModules(string $role = null): array
    {
        $modules = Yaml::parseFile($this->moduleConfig)['modules'] ?? [];

        $formattedModules = [];

        foreach ($modules as $key => $roleBasedModules) {
            $formattedModules[$key] = $roleBasedModules['routes'];
        }

        if ($role) {
            return $formattedModules[$role] ?? [];
        }

        return $formattedModules;
    }

    public function getController(string $role): string
    {
        $modules = Yaml::parseFile($this->moduleConfig)['modules'] ?? [];

        return $modules[$role]['controller'];
    }
}