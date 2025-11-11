<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\LoginRequest;
use App\Request\RegisterRequest;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function register(RegisterRequest $request): User
    {
        // Sprawdź czy użytkownik już istnieje
        $existingUser = $this->userRepository->findOneBy(['email' => $request->email]);
        if ($existingUser !== null) {
            throw new \RuntimeException('User with this email already exists');
        }

        // Utwórz nowego użytkownika
        $user = new User();
        $user->setEmail($request->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $request->password));
        $user->setRoles(['ROLE_USER']);

        $this->userRepository->save($user, true);

        return $user;
    }

    public function login(LoginRequest $request): string
    {
        // Znajdź użytkownika po emailu
        $user = $this->userRepository->findOneBy(['email' => $request->email]);
        if ($user === null) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Sprawdź hasło
        if (!$this->passwordHasher->isPasswordValid($user, $request->password)) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Wygeneruj token JWT
        return $this->jwtManager->create($user);
    }

    public function createTokenForUser(UserInterface $user): string
    {
        return $this->jwtManager->create($user);
    }
}

