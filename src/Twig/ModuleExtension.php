<?php
namespace App\Twig;

use App\Service\ModuleService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ModuleExtension extends AbstractExtension
{
    private $moduleService;

    public function __construct(ModuleService $moduleService)
    {
        $this->moduleService = $moduleService;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('get_modules', [$this, 'getModules']),
        ];
    }

    public function getModules($role)
    {
        return $this->moduleService->getModules($role);
    }
}