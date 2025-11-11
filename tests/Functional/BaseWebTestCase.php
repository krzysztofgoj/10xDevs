<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class BaseWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient(['environment' => 'test', 'debug' => true]);
        $this->client->disableReboot(); // Prevent kernel reboot between requests
        
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Clean database before each test
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up
        $this->entityManager->close();
        unset($this->entityManager);
        unset($this->client);
    }

    /**
     * Clean database by removing all records
     */
    protected function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform()->getName();
        
        if ($platform === 'sqlite') {
            // For SQLite: drop and recreate schema
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
            $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
            
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        } else {
            // For PostgreSQL: truncate tables
            // Disable foreign key checks
            $connection->executeStatement('SET CONSTRAINTS ALL DEFERRED');
            
            // Truncate tables
            $tables = ['flashcard_generation', 'repetition_record', 'flashcard', 'users'];
            foreach ($tables as $table) {
                $connection->executeStatement(sprintf('TRUNCATE TABLE %s CASCADE', $table));
            }
            
            // Reset sequences
            foreach ($tables as $table) {
                $connection->executeStatement(sprintf('ALTER SEQUENCE %s_id_seq RESTART WITH 1', $table));
            }
        }
    }

    /**
     * Create a test user
     */
    protected function createUser(
        string $email = 'test@example.com',
        string $password = 'password123',
        array $roles = ['ROLE_USER']
    ): User {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    /**
     * Get JWT token for user
     */
    protected function getAuthToken(User $user): string
    {
        $authService = static::getContainer()->get(AuthService::class);
        return $authService->createTokenForUser($user);
    }

    /**
     * Make authenticated JSON request
     */
    protected function makeAuthenticatedRequest(
        string $method,
        string $uri,
        User $user,
        array $data = [],
        array $headers = []
    ): void {
        $token = $this->getAuthToken($user);
        
        $defaultHeaders = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            $allHeaders,
            empty($data) ? null : json_encode($data)
        );
    }

    /**
     * Make JSON request without authentication
     */
    protected function makeJsonRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = []
    ): void {
        $defaultHeaders = [
            'CONTENT_TYPE' => 'application/json',
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            $allHeaders,
            empty($data) ? null : json_encode($data)
        );
    }

    /**
     * Get response data as array
     */
    protected function getResponseData(): array
    {
        $content = $this->client->getResponse()->getContent();
        return json_decode($content, true) ?? [];
    }

    /**
     * Assert response is JSON
     */
    protected function assertJsonResponse(int $expectedStatusCode = 200): void
    {
        $response = $this->client->getResponse();
        
        $this->assertResponseStatusCodeSame($expectedStatusCode);
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            'Response is not JSON'
        );
    }

    /**
     * Assert response has error
     */
    protected function assertResponseHasError(string $expectedError = null): void
    {
        $data = $this->getResponseData();
        
        $this->assertArrayHasKey('error', $data, 'Response does not contain error key');
        
        if ($expectedError !== null) {
            $this->assertStringContainsString(
                $expectedError,
                $data['error'],
                'Error message does not match'
            );
        }
    }
}

