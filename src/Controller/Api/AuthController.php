<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Request\LoginRequest;
use App\Request\RegisterRequest;
use App\Response\AuthResponse;
use App\Repository\UserRepository;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api', name: 'api_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly UserRepository $userRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/debug-db', name: 'debug_db', methods: ['GET'])]
    public function debugDb(): JsonResponse
    {
        try {
            $connection = $this->userRepository->createQueryBuilder('u')
                ->getEntityManager()
                ->getConnection();
            
            $sql = 'SELECT column_name, data_type FROM information_schema.columns WHERE table_name = \'users\' ORDER BY ordinal_position';
            $result = $connection->executeQuery($sql)->fetchAllAssociative();
            
            // Test findOneBy
            $testResult = [];
            try {
                $user = $this->userRepository->findOneBy(['email' => 'nonexistent@test.com']);
                $testResult['findOneBy'] = 'success - no user found: ' . ($user === null ? 'null' : 'found');
            } catch (\Exception $e) {
                $testResult['findOneBy_error'] = $e->getMessage();
            }
            
            // Test QueryBuilder
            try {
                $qb = $this->userRepository->createQueryBuilder('u')
                    ->where('u.email = :email')
                    ->setParameter('email', 'nonexistent@test.com')
                    ->getQuery();
                $testResult['sql_query'] = $qb->getSQL();
                $user2 = $qb->getOneOrNullResult();
                $testResult['queryBuilder'] = 'success - no user found: ' . ($user2 === null ? 'null' : 'found');
            } catch (\Exception $e) {
                $testResult['queryBuilder_error'] = $e->getMessage();
                // Get SQL even on error
                try {
                    $qb = $this->userRepository->createQueryBuilder('u')
                        ->where('u.email = :email')
                        ->setParameter('email', 'nonexistent@test.com')
                        ->getQuery();
                    $testResult['sql_query'] = $qb->getSQL();
                } catch (\Exception $e2) {
                    // ignore
                }
            }
            
            return new JsonResponse([
                'status' => 'ok',
                'columns' => $result,
                'database_url' => $_ENV['DATABASE_URL'] ?? 'not set',
                'tests' => $testResult
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            RegisterRequest::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(
                ['errors' => (string) $errors],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            $user = $this->authService->register($dto);
            
            // Utwórz LoginRequest dla automatycznego logowania po rejestracji
            $loginRequest = new LoginRequest();
            $loginRequest->email = $user->getEmail();
            $loginRequest->password = $dto->password;
            
            $token = $this->authService->login($loginRequest);

            $response = new AuthResponse(
                $token,
                $user->getId(),
                $user->getEmail()
            );

            return new JsonResponse(
                $this->serializer->serialize($response, 'json', ['groups' => ['api']]),
                JsonResponse::HTTP_CREATED,
                [],
                true
            );
        } catch (\RuntimeException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_CONFLICT
            );
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            LoginRequest::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(
                ['errors' => (string) $errors],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        try {
            $token = $this->authService->login($dto);
            
            // Pobierz użytkownika po emailu do odpowiedzi
            $user = $this->userRepository->findOneBy(['email' => $dto->email]);
            
            if ($user === null) {
                return new JsonResponse(
                    ['error' => 'Invalid credentials'],
                    JsonResponse::HTTP_UNAUTHORIZED
                );
            }

            $response = new AuthResponse(
                $token,
                $user->getId(),
                $user->getEmail()
            );

            return new JsonResponse(
                $this->serializer->serialize($response, 'json', ['groups' => ['api']]),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Symfony\Component\Security\Core\Exception\AuthenticationException $e) {
            return new JsonResponse(
                ['error' => 'Invalid credentials'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }
    }
}

