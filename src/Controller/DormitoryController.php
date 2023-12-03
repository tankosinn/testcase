<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\Room;
use App\Entity\User;
use App\Form\DormitoryItemStoreFormType;
use App\Form\DormitoryRoomStoreFormType;
use App\Form\DormitoryStudentStoreFormType;
use App\Repository\ItemRepository;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use Knp\Component\Pager\PaginatorInterface;

use App\Serializer\FormErrorSerializer;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class DormitoryController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('dormitory/index.html.twig');
    }

    /**
     * @Route("/odalar", name="dormitory_rooms", methods={"GET"})
     */
    public function rooms(Request $request, PaginatorInterface $paginator): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $query = $entityManager->getRepository(Room::class)->createQueryBuilder('r')
            ->addSelect('COUNT(u.id) AS resident')
            ->leftJoin('App\Entity\User', 'u', 'WITH', 'u.roomId = r.id')
            ->groupBy('r.id')
            ->orderBy('r.name', 'ASC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('dormitory/rooms/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/oda/{slug}", name="dormitory_room_create", methods={"GET"})
     */
    public function roomCreate($slug = null): Response
    {
        $room = null;

        if ($slug) {
            $entityManager = $this->getDoctrine()->getManager();

            $room = $entityManager->getRepository(Room::class)->createQueryBuilder('r')
                ->andWhere('r.slug = :slug')
                ->setParameter('slug', $slug)
                ->andWhere('r.dormitoryId = :dormitoryId')
                ->setParameter('dormitoryId', $this->getUser()->getId())
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $this->render('dormitory/rooms/create.html.twig', [
            "room" => $room
        ]);
    }

    /**
     * @Route("/oda/kaydet", name="dormitory_room_store", methods={"POST"})
     */
    public function roomStore(Request $request, FormErrorSerializer $formErrorSerializer, RoomRepository $roomRepository): Response
    {
        $user = $this->getUser();

        $data = [
            'id' => $request->get('id') ?? null,
            'name' => $request->get('name'),
            'capacity' => $request->get('capacity'),
        ];

        if ($data['id']) {
            $entityManager = $this->getDoctrine()->getManager();
            $room = $entityManager->getRepository(Room::class)->find($data['id']);

            if (!$room) {
                $data['id'] = null;

                $room = new Room();
            }
        } else {
            $room = new Room();
        }

        $form = $this->createForm(DormitoryRoomStoreFormType::class);

        $form->submit($data);

        if (!$form->isValid()) {
            $errors = $formErrorSerializer->normalize($form);

            return $this->json([
                "status" => "warning",
                "message" => "Oda kaydedilemedi.",
                "errors" => $errors
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $room->setDormitoryId($user->getId());
        $room->setName($data["name"]);
        $room->setCapacity($data["capacity"]);

        $roomRepository->add($room, true);

        return $this->json([
            "status" => "success",
            "message" => "Oda kaydedildi.",
            "slug" => $data["id"] ? $room->getSlug() : null
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/oda/{slug}/detay", name="dormitory_room_detail", methods={"GET"})
     */
    public function roomDetail($slug = null): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $room = $entityManager->getRepository(Room::class)->createQueryBuilder('r')
            ->addSelect('COUNT(u.id) AS resident')
            ->leftJoin('App\Entity\User', 'u', 'WITH', 'u.roomId = r.id')
            ->groupBy('r.id')
            ->orderBy('r.name', 'ASC')
            ->andWhere('r.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();

        $students = $entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->andWhere('u.roomId = :roomId')
            ->setParameter('roomId', $room[0]->getId())
            ->getQuery()
            ->getResult();

        return $this->render('dormitory/rooms/view.html.twig', [
            "room" => $room,
            "students" => $students,
            "inventory" => null
        ]);
    }

    /**
     * @Route("/oda/{slug}/envanter/{inventory}", name="dormitory_room_inventory", methods={"GET"})
     */
    public function roomInventory($slug, $inventory = null): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $room = $entityManager->getRepository(Room::class)->findOneBy(["slug" => $slug]);

        $inventory = null;

        if ($inventory) {
            $inventory = $entityManager->getRepository(Inventory::class)->find($inventory);
        }

        $items = $entityManager->getRepository(Item::class)->findBy(["dormitoryId" => $this->getUser()->getId()]);

        $users = $entityManager->getRepository(User::class)->findBy(["roomId" => $room->getId()]);

        return $this->render('dormitory/rooms/inventory.html.twig', [
            "room" => $room,
            "inventory" => $inventory,
            "items" => $items,
            "users" => $users
        ]);
    }



    /**
     * @Route("/konuklar", name="dormitory_students", methods={"GET"})
     */
    public function students(Request $request, PaginatorInterface $paginator): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $query = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('partial u.{id, email, roles, password, dormitoryId, roomId, name, slug, phone, address, photo, gender, checkInDate, departureDate, created_at, updated_at}', 'r.name as roomName') // Burada sadece gerekli User alanlarını ve Room'un name alanını seçiyoruz
            ->leftJoin('App\Entity\Room', 'r', 'WITH', 'u.roomId = r.id')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = true')
            ->setParameter('role', '"ROLE_STUDENT"')
            ->andWhere('u.dormitoryId = :dormitoryId')
            ->setParameter('dormitoryId', $this->getUser()->getId())
            ->orderBy('u.created_at', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('dormitory/students/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/konuk/{slug}", name="dormitory_student_create", methods={"GET"})
     */
    public function studentCreate($slug = null): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $student = null;

        $rooms = $entityManager->getRepository(Room::class)->createQueryBuilder('r')
            ->andWhere('r.dormitoryId = :dormitoryId')
            ->setParameter('dormitoryId', $this->getUser()->getId())
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();

        if ($slug) {
            $student = $entityManager->getRepository(User::class)->createQueryBuilder('u')
                ->andWhere('u.slug = :slug')
                ->setParameter('slug', $slug)
                ->andWhere('u.dormitoryId = :dormitoryId')
                ->setParameter('dormitoryId', $this->getUser()->getId())
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $this->render('dormitory/students/create.html.twig', [
            "student" => $student,
            "rooms" => $rooms
        ]);
    }

    /**
     * @Route("/konuk/kaydet", name="dormitory_student_store", methods={"POST"})
     */
    public function studentStore(Request $request, FormErrorSerializer $formErrorSerializer, FileUploader $fileUploader, UserPasswordEncoderInterface $passwordEncoder, UserRepository $userRepository): Response
    {
        $data = [
            'id' => $request->get('id') ?? null,
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => $request->get('password'),
            'phone' => $request->get('phone'),
            'photo' => $request->files->get('photo'),
            'room' => $request->get('room'),
            'gender' => $request->get('gender'),
            'checkInDate' => $request->get('checkInDate'),
            'departureDate' => $request->get('departureDate'),
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

        $form = $this->createForm(DormitoryStudentStoreFormType::class);

        $form->submit($data);

        if (!$form->isValid()) {
            $errors = $formErrorSerializer->normalize($form);

            return $this->json([
                "status" => "warning",
                "message" => "Konuk kaydedilemedi.",
                "errors" => $errors
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user->setRoles(["ROLE_STUDENT"]);
        $user->setDormitoryId($this->getUser()->getId());
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

        $user->setRoomId($data["room"]);
        $user->setGender(!!$data["gender"]);
        $user->setCheckInDate(new DateTimeImmutable($data["checkInDate"]));
        $user->setDepartureDate(new DateTimeImmutable($data["departureDate"]));

        $userRepository->add($user, true);

        return $this->json([
            "status" => "success",
            "message" => "Konuk kaydedildi.",
            "slug" => $data["id"] ? $user->getSlug() : null
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * @Route("/envanter", name="dormitory_inventory", methods={"GET"})
     */
    public function inventory(Request $request, PaginatorInterface $paginator): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $query = $entityManager->getRepository(Item::class)->createQueryBuilder('i')->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('dormitory/inventory/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/envanter/esya/{slug}", name="dormitory_item_create", methods={"GET"})
     */
    public function inventoryCreate($slug = null): Response
    {
        $item = null;

        if ($slug) {
            $entityManager = $this->getDoctrine()->getManager();

            $item = $entityManager->getRepository(Item::class)->createQueryBuilder('i')
                ->andWhere('i.slug = :slug')
                ->setParameter('slug', $slug)
                ->andWhere('i.dormitoryId = :dormitoryId')
                ->setParameter('dormitoryId', $this->getUser()->getId())
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $this->render('dormitory/inventory/create.html.twig', [
            "item" => $item
        ]);
    }

    /**
     * @Route("/envanter/kaydet", name="dormitory_item_store", methods={"POST"})
     */
    public function inventoryStore(Request $request, FormErrorSerializer $formErrorSerializer, ItemRepository $itemRepository): Response
    {
        $user = $this->getUser();

        $data = [
            'id' => $request->get('id') ?? null,
            'name' => $request->get('name'),
            'quantity' => $request->get('quantity'),
        ];

        if ($data['id']) {
            $entityManager = $this->getDoctrine()->getManager();
            $item = $entityManager->getRepository(Item::class)->find($data['id']);

            if (!$item) {
                $data['id'] = null;

                $room = new Item();
            }
        } else {
            $item = new Item();
        }

        $form = $this->createForm(DormitoryItemStoreFormType::class);

        $form->submit($data);

        if (!$form->isValid()) {
            $errors = $formErrorSerializer->normalize($form);

            return $this->json([
                "status" => "warning",
                "message" => "Eşya/Ürün kaydedilemedi.",
                "errors" => $errors
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $item->setDormitoryId($user->getId());
        $item->setName($data["name"]);
        $item->setQuantity($data["quantity"]);

        $itemRepository->add($item, true);

        return $this->json([
            "status" => "success",
            "message" => "Eşya/Ürün kaydedildi.",
            "slug" => $data["id"] ? $room->getSlug() : null
        ], JsonResponse::HTTP_CREATED);
    }
}
