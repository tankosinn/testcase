<?php

namespace App\Form;

use App\Entity\Room;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Validator\Constraints\File;

use Symfony\Component\Validator\Constraints\Callback;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DormitoryStudentStoreFormType extends AbstractType
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $this->id = $data['id'] ?? null;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', NumberType::class)
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'E-posta boş bırakılamaz.']),
                    new Assert\Email(['message' => "E-posta geçerli değil."]),
                    new Callback([$this, 'validateUniqueEmail']),
                ]
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Şifre boş bırakılamaz.',
                        'groups' => function (FormInterface $form) {
                            $data = $form->getData();
                            return empty($data->getId()) ? ['Default'] : [];
                        },
                    ]),
                ]
            ])
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Ad soyad boş bırakılamaz.']),
                ]
            ])
            ->add('phone', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Telefon boş bırakılamaz.']),
                ]
            ])
            ->add('photo', FileType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Logo yüklenmesi zorunludur.',
                        'groups' => function (FormInterface $form) {
                            $data = $form->getData();
                            return empty($data->getId()) ? ['Default'] : [];
                        },
                    ]),
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Geçersiz dosya biçimi. JPEG ya da PNG dosya yükleyebilirsiniz.',
                        'maxSizeMessage' => 'Bu dosya çok büyük ({{ size }} {{ suffix }}). Maksimum dosya büyüklüğü: {{ limit }} {{ suffix }}',
                    ]),
                ]
            ])
            ->add('room', NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Oda seçilmesi zorunludur.']),
                    new Callback([$this, 'validateRoomCapacity']),
                ]
            ])
            ->add('gender', NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Cinsiyet seçilmesi zorunludur.']),
                ]
            ])
            ->add('checkInDate', TextType::class, [
                'constraints' => [
                    new Assert\DateTime([
                        'format' => 'd-m-Y',
                        'message' => 'Geçerli bir tarih giriniz.',
                    ]),
                ],
            ])
            ->add('departureDate', TextType::class, [
                'constraints' => [
                    new Assert\DateTime([
                        'format' => 'd-m-Y',
                        'message' => 'Geçerli bir tarih giriniz.',
                    ]),
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false
        ]);
    }

    public function validateUniqueEmail($value, ExecutionContextInterface $context)
    {
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $value]);

        if ($existingUser !== null && $existingUser->getId() != $this->id) {
            $context->buildViolation('Bu e-posta zaten kullanılıyor.')->addViolation();
        }
    }

    public function validateRoomCapacity($value, ExecutionContextInterface $context)
    {
        if ($this->id) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $this->id, 'roomId' => $value]);
        } else {
            $user = null;
        }

        $roomData = $this->entityManager->getRepository(Room::class)->createQueryBuilder('r')
            ->select('r', 'COUNT(u.id) AS residentCount')
            ->leftJoin('App\Entity\User', 'u', 'WITH', 'u.roomId = r.id')
            ->andWhere('r.id = :id')
            ->setParameter('id', $value)
            ->groupBy('r.id')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();

        if ($roomData) {
            $room = $roomData[0];
            $residentCount = $roomData['residentCount'];

            if ($room->getCapacity() - $residentCount + ($user ? 1 : 0) <= 0) {
                $context->buildViolation('Bu oda dolu.')->addViolation();
            }
        }
    }
}
