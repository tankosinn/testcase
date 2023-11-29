<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Service\FileUploader;

class AdminController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    /**
     * @Route("/yurt/{slug}", name="admin_dormitory", methods={"GET"})
     */
    public function dormitory($slug = null): Response
    {
        return $this->render('admin/dormitory.html.twig');
    }

    /**
     * @Route("/yurt", name="admin_dormitory_registration", methods={"POST"})
     */
    public function dormitoryRegistration(Request $request, FileUploader $fileUploader, UserPasswordEncoderInterface $passwordEncoder, UserRepository $userRepository): Response 
    {
        $user = new User();
        
        $user->setRoles(['ROLE_DORMITORY']);

        $user->setName($request->get('name'));

        $user->setEmail($request->get('email'));

        $password = $request->get('password');

        $user->setPassword($passwordEncoder->encodePassword($user, $password));

        $user->setPhone($request->get('phone'));
        
        $user->setAddress($request->get('address'));

        $file = $request->files->get('photo');

        $fileUploader->upload($file);

        $userRepository->add($user, true);

        return $this->redirectToRoute('admin_dormitory_list');
    }
}   


