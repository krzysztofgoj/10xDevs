<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\LoginRequest;
use App\Request\RegisterRequest;
use App\Service\AuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class AuthServiceTest extends TestCase
{
    /** @var UserRepository&\PHPUnit\Framework\MockObject\MockObject */
    private UserRepository $userRepository;
    /** @var UserPasswordHasherInterface&\PHPUnit\Framework\MockObject\MockObject */
    private UserPasswordHasherInterface $passwordHasher;
    /** @var JWTTokenManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private JWTTokenManagerInterface $jwtManager;
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);

        $this->authService = new AuthService(
            $this->userRepository,
            $this->passwordHasher,
            $this->jwtManager
        );
    }

    /**
     * Test successful user registration
     */
    public function testRegisterSuccess(): void
    {
        $request = new RegisterRequest();
        $request->email = 'newuser@example.com';
        $request->password = 'password123';
        $request->password_confirm = 'password123';

        // Mock: User doesn't exist yet
        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'newuser@example.com'])
            ->willReturn(null);

        // Mock: Password hashing
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        // Mock: Save user
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(function (User $user) {
                    return $user->getEmail() === 'newuser@example.com'
                        && $user->getPassword() === 'hashed_password'
                        && in_array('ROLE_USER', $user->getRoles());
                }),
                true
            );

        $user = $this->authService->register($request);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('newuser@example.com', $user->getEmail());
    }

    /**
     * Test registration with existing email
     */
    public function testRegisterWithExistingEmail(): void
    {
        $request = new RegisterRequest();
        $request->email = 'existing@example.com';
        $request->password = 'password123';
        $request->password_confirm = 'password123';

        $existingUser = new User();
        $existingUser->setEmail('existing@example.com');

        // Mock: User already exists
        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($existingUser);

        // Should not call save
        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User with this email already exists');

        $this->authService->register($request);
    }

    /**
     * Test successful login
     */
    public function testLoginSuccess(): void
    {
        $request = new LoginRequest();
        $request->email = 'user@example.com';
        $request->password = 'password123';

        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPassword('hashed_password');

        // Mock: Find user
        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn($user);

        // Mock: Password validation
        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'password123')
            ->willReturn(true);

        // Mock: JWT token creation
        $this->jwtManager
            ->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturn('mocked_jwt_token');

        $token = $this->authService->login($request);

        $this->assertEquals('mocked_jwt_token', $token);
    }

    /**
     * Test login with non-existent user
     */
    public function testLoginWithNonExistentUser(): void
    {
        $request = new LoginRequest();
        $request->email = 'nonexistent@example.com';
        $request->password = 'password123';

        // Mock: User not found
        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'nonexistent@example.com'])
            ->willReturn(null);

        // Should not validate password
        $this->passwordHasher
            ->expects($this->never())
            ->method('isPasswordValid');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login($request);
    }

    /**
     * Test login with invalid password
     */
    public function testLoginWithInvalidPassword(): void
    {
        $request = new LoginRequest();
        $request->email = 'user@example.com';
        $request->password = 'wrongpassword';

        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPassword('hashed_password');

        // Mock: Find user
        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn($user);

        // Mock: Password validation fails
        $this->passwordHasher
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'wrongpassword')
            ->willReturn(false);

        // Should not create JWT token
        $this->jwtManager
            ->expects($this->never())
            ->method('create');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login($request);
    }

    /**
     * Test creating token for user
     */
    public function testCreateTokenForUser(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        // Mock: JWT token creation
        $this->jwtManager
            ->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturn('mocked_jwt_token');

        $token = $this->authService->createTokenForUser($user);

        $this->assertEquals('mocked_jwt_token', $token);
    }
}

