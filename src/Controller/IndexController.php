<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\ModuleService;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="app_index")
     */
    public function index(ModuleService $moduleService): Response
    {
        $user = $this->getUser();

        $response = $this->forward($moduleService->getController($user->getRoles()[0]));

        return $response;
    }
}
