<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\User;
use App\Repository\UserRepository;


class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';
    protected static $defaultDescription = 'Create a user';

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->urlGenerator = $urlGenerator;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new User();

        $io = new SymfonyStyle($input, $output);

        $io->title('User Creation Wizard');

        $roleChoices = ['ROLE_SYSTEM_ADMIN', 'ROLE_DORMITORY', 'ROLE_STUDENT'];
        $role = $io->choice("Select user role:", $roleChoices);

        $user->setRoles([$role]);

        $name = $io->ask("Enter name");

        $user->setName($name);

        $email = $io->ask("Enter email");

        $user->setEmail($email);

        $password = $io->askHidden("Enter password");

        $encodedPassword = $this->passwordEncoder->encodePassword($user, $password);

        $user->setPassword($encodedPassword);

        $phone = $io->ask("Enter phone");

        $user->setPhone($phone);

        switch ($role) {
            case 'ROLE_DORMITORY':
                $io->text('Entering Information for Dormitory (Business)');

                $address = $io->ask('Enter address');

                $user->setAddress($address);

                $photo = $io->ask('Enter photo (file path)');

                $user->setPhoto($photo);
                break;
            case 'ROLE_STUDENT':
                $io->text('Entering Information for Student');

                $dormitoryId = $io->ask("Enter dormitoryId");
                $user->setDormitoryId($dormitoryId);

                $roomId = $io->ask("Enter roomId");
                $user->setRoomId($roomId);

                $genderChoices = ['Male', 'Female'];
                $gender = $io->choice("Select user role:", $genderChoices);

                $user->setGender(array_search($gender, $genderChoices));

                $checkInDate = $io->ask("Enter check-in date");
                $user->setCheckInDate(new \DateTime($checkInDate));

                $departureDate = $io->ask("Enter departure date");
                $user->setDepartureDate(new \DateTime($departureDate));
                break;
        }

        $this->userRepository->add($user, true);

        $io->success([
            'User created! ID: ' . $user->getId(),
            $this->urlGenerator->generate('app_login')
        ]);

        return Command::SUCCESS;
    }
}
