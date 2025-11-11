<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public const USER_REFERENCE = 'user_';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create test users
        $users = [
            [
                'email' => 'test@example.com',
                'password' => 'password123',
                'roles' => ['ROLE_USER'],
            ],
            [
                'email' => 'test2@example.com',
                'password' => 'password123',
                'roles' => ['ROLE_USER'],
            ],
            [
                'email' => 'admin@example.com',
                'password' => 'adminpass123',
                'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
            ],
        ];

        foreach ($users as $index => $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $userData['password'])
            );

            $manager->persist($user);
            
            // Add reference for use in other fixtures
            $this->addReference(self::USER_REFERENCE . $index, $user);
        }

        $manager->flush();
    }
}

