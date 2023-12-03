<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminDomitoryStoreFormType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use Knp\Component\Pager\PaginatorInterface;

use App\Serializer\FormErrorSerializer;

use App\Service\FileUploader;

class AdminController extends AbstractController
{
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $query = $entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = true')
            ->setParameter('role', '"ROLE_DORMITORY"')
            ->orderBy('u.created_at', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/yurt/{slug}", name="admin_dormitory_create", methods={"GET"})
     */
    public function dormitoryCreate($slug = null): Response
    {
        $dormitory = null;

        if ($slug) {
            $entityManager = $this->getDoctrine()->getManager();

            $dormitory = $entityManager->getRepository(User::class)->createQueryBuilder('u')
                ->andWhere('u.slug = :slug')
                ->setParameter('slug', $slug)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $this->render('admin/dormitory.html.twig', [
            "dormitory" => $dormitory
        ]);
    }

    /**
     * @Route("/yurt/kaydet", name="admin_dormitory_store", methods={"POST"})
     */
    public function dormitoryStore(Request $request, FormErrorSerializer $formErrorSerializer, FileUploader $fileUploader, UserPasswordEncoderInterface $passwordEncoder, UserRepository $userRepository): Response
    {
        $data = [
            'id' => $request->get('id') ?? null,
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => $request->get('password'),
            'phone' => $request->get('phone'),
            'address' => $request->get('address'),
            'photo' => $request->files->get('photo')
        ];

        if ($data['id']) {
            $entityManager = $this->getDoctrine()->getManager();
            $user = $entityManager->getRepository(User::class)->find($data['id']);

            if (!$user) {
                $data['id'] = null;

                $user = new User();
            }
        } else {
            $user = new User();
        }

        $form = $this->createForm(AdminDomitoryStoreFormType::class);

        $form->submit($data);

        if (!$form->isValid()) {
            $errors = $formErrorSerializer->normalize($form);

            return $this->json([
                "status" => "warning",
                "message" => "Yurt kaydedilemedi.",
                "errors" => $errors
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user->setRoles(["ROLE_DORMITORY"]);
        $user->setName($data["name"]);
        $user->setEmail($data["email"]);

        if (($data["id"] && $data["password"]) || !$data["id"]) {
            $user->setPassword($passwordEncoder->encodePassword($user, $data["password"]));
        }

        $user->setPhone($data["phone"]);

        if ($data["photo"]) {
            if ($data["id"]) {
                $fileUploader->remove($user->getPhoto());
            }

            $user->setPhoto($fileUploader->upload($data['photo']));
        }

        $userRepository->add($user, true);

        return $this->json([
            "status" => "success",
            "message" => "Yurt kaydedildi.",
            "slug" => $data["id"] ? $user->getSlug() : null
        ], JsonResponse::HTTP_CREATED);
    }
}


