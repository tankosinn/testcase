<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\ModuleService;

class StudentController extends AbstractController
{
    public function index($user, $modules): Response
    {
        return $this->render('student/index.html.twig');
    }
}
