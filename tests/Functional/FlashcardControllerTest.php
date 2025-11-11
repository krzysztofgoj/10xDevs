<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Flashcard;
use Symfony\Component\HttpFoundation\Response;

final class FlashcardControllerTest extends BaseWebTestCase
{
    /**
     * Test creating flashcards in bulk
     */
    public function testBulkCreateFlashcards(): void
    {
        $user = $this->createUser();

        $flashcardsData = [
            'flashcards' => [
                [
                    'question' => 'What is PHP?',
                    'answer' => 'PHP is a server-side scripting language.',
                    'source' => 'manual',
                ],
                [
                    'question' => 'What is Symfony?',
                    'answer' => 'Symfony is a PHP framework.',
                    'source' => 'ai',
                ],
            ],
        ];

        $this->makeAuthenticatedRequest('POST', '/api/flashcards/bulk', $user, $flashcardsData);

        $this->assertJsonResponse(Response::HTTP_CREATED);

        $data = $this->getResponseData();
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertEquals('What is PHP?', $data[0]['question']);
        $this->assertEquals('PHP is a server-side scripting language.', $data[0]['answer']);
        $this->assertEquals('manual', $data[0]['source']);
        
        $this->assertArrayHasKey('id', $data[1]);
        $this->assertEquals('What is Symfony?', $data[1]['question']);
    }

    /**
     * Test creating flashcards without authentication
     */
    public function testBulkCreateFlashcardsUnauthorized(): void
    {
        $flashcardsData = [
            'flashcards' => [
                [
                    'question' => 'Test question',
                    'answer' => 'Test answer',
                    'source' => 'manual',
                ],
            ],
        ];

        $this->makeJsonRequest('POST', '/api/flashcards/bulk', $flashcardsData);

        $this->assertJsonResponse(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test creating flashcards with invalid data
     */
    public function testBulkCreateFlashcardsWithInvalidData(): void
    {
        $user = $this->createUser();

        $flashcardsData = [
            'flashcards' => [
                [
                    'question' => '',  // Empty question
                    'answer' => 'Test answer',
                ],
            ],
        ];

        $this->makeAuthenticatedRequest('POST', '/api/flashcards/bulk', $user, $flashcardsData);

        $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test listing user's flashcards
     */
    public function testListFlashcards(): void
    {
        $user = $this->createUser();
        $this->createFlashcardsForUser($user, 5);

        $this->makeAuthenticatedRequest('GET', '/api/flashcards', $user);

        $this->assertJsonResponse(Response::HTTP_OK);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertCount(5, $data['data']);
        $this->assertEquals(5, $data['pagination']['total']);
    }

    /**
     * Test listing flashcards with pagination
     */
    public function testListFlashcardsWithPagination(): void
    {
        $user = $this->createUser();
        $this->createFlashcardsForUser($user, 25);

        // Test first page
        $this->makeAuthenticatedRequest('GET', '/api/flashcards?page=1&limit=10', $user);

        $this->assertJsonResponse(Response::HTTP_OK);

        $data = $this->getResponseData();
        $this->assertCount(10, $data['data']);
        $this->assertEquals(25, $data['pagination']['total']);
        $this->assertEquals(1, $data['pagination']['page']);
        $this->assertEquals(3, $data['pagination']['pages']); // 25/10 = 3 pages

        // Test second page
        $this->makeAuthenticatedRequest('GET', '/api/flashcards?page=2&limit=10', $user);

        $this->assertJsonResponse(Response::HTTP_OK);

        $data = $this->getResponseData();
        $this->assertCount(10, $data['data']);
        $this->assertEquals(2, $data['pagination']['page']);
    }

    /**
     * Test that user can only see their own flashcards
     */
    public function testListFlashcardsIsolation(): void
    {
        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');
        
        $this->createFlashcardsForUser($user1, 3);
        $this->createFlashcardsForUser($user2, 5);

        // User1 should only see their 3 flashcards
        $this->makeAuthenticatedRequest('GET', '/api/flashcards', $user1);
        $this->assertJsonResponse(Response::HTTP_OK);
        
        $data = $this->getResponseData();
        $this->assertCount(3, $data['data']);
        $this->assertEquals(3, $data['pagination']['total']);
    }

    /**
     * Test listing flashcards without authentication
     */
    public function testListFlashcardsUnauthorized(): void
    {
        $this->makeJsonRequest('GET', '/api/flashcards');

        $this->assertJsonResponse(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test getting a single flashcard
     */
    public function testGetFlashcard(): void
    {
        $user = $this->createUser();
        $flashcards = $this->createFlashcardsForUser($user, 1);
        $flashcard = $flashcards[0];

        $this->makeAuthenticatedRequest('GET', '/api/flashcards/' . $flashcard->getId(), $user);

        $this->assertJsonResponse(Response::HTTP_OK);

        $data = $this->getResponseData();
        $this->assertEquals($flashcard->getId(), $data['id']);
        $this->assertArrayHasKey('question', $data);
        $this->assertArrayHasKey('answer', $data);
    }

    /**
     * Test getting a non-existent flashcard
     */
    public function testGetNonExistentFlashcard(): void
    {
        $user = $this->createUser();

        $this->makeAuthenticatedRequest('GET', '/api/flashcards/99999', $user);

        $this->assertJsonResponse(Response::HTTP_NOT_FOUND);
        $this->assertResponseHasError('Flashcard not found');
    }

    /**
     * Test getting another user's flashcard
     */
    public function testGetAnotherUsersFlashcard(): void
    {
        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');
        
        $flashcards = $this->createFlashcardsForUser($user1, 1);
        $flashcard = $flashcards[0];

        // User2 tries to access User1's flashcard
        $this->makeAuthenticatedRequest('GET', '/api/flashcards/' . $flashcard->getId(), $user2);

        $this->assertJsonResponse(Response::HTTP_FORBIDDEN);
        $this->assertResponseHasError('Access denied');
    }

    /**
     * Test updating a flashcard
     */
    public function testUpdateFlashcard(): void
    {
        $user = $this->createUser();
        $flashcards = $this->createFlashcardsForUser($user, 1);
        $flashcard = $flashcards[0];

        $updateData = [
            'question' => 'Updated question',
            'answer' => 'Updated answer',
        ];

        $this->makeAuthenticatedRequest('PUT', '/api/flashcards/' . $flashcard->getId(), $user, $updateData);

        $this->assertJsonResponse(Response::HTTP_OK);

        $data = $this->getResponseData();
        $this->assertEquals('Updated question', $data['question']);
        $this->assertEquals('Updated answer', $data['answer']);
    }

    /**
     * Test updating flashcard with partial data (PATCH)
     */
    public function testPartialUpdateFlashcard(): void
    {
        $user = $this->createUser();
        $flashcards = $this->createFlashcardsForUser($user, 1);
        $flashcard = $flashcards[0];
        $originalAnswer = $flashcard->getAnswer();

        $updateData = [
            'question' => 'Only question updated',
        ];

        $this->makeAuthenticatedRequest('PATCH', '/api/flashcards/' . $flashcard->getId(), $user, $updateData);

        $this->assertJsonResponse(Response::HTTP_OK);

        $data = $this->getResponseData();
        $this->assertEquals('Only question updated', $data['question']);
        $this->assertEquals($originalAnswer, $data['answer']); // Answer should remain unchanged
    }

    /**
     * Test updating flashcard with empty fields
     */
    public function testUpdateFlashcardWithEmptyFields(): void
    {
        $user = $this->createUser();
        $flashcards = $this->createFlashcardsForUser($user, 1);
        $flashcard = $flashcards[0];

        $updateData = [
            'question' => '',  // Empty question
        ];

        $this->makeAuthenticatedRequest('PUT', '/api/flashcards/' . $flashcard->getId(), $user, $updateData);

        $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $this->assertResponseHasError('Question cannot be empty');
    }

    /**
     * Test updating another user's flashcard
     */
    public function testUpdateAnotherUsersFlashcard(): void
    {
        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');
        
        $flashcards = $this->createFlashcardsForUser($user1, 1);
        $flashcard = $flashcards[0];

        $updateData = [
            'question' => 'Hacked question',
        ];

        // User2 tries to update User1's flashcard
        $this->makeAuthenticatedRequest('PUT', '/api/flashcards/' . $flashcard->getId(), $user2, $updateData);

        $this->assertJsonResponse(Response::HTTP_FORBIDDEN);
        $this->assertResponseHasError('Access denied');
    }

    /**
     * Test deleting a flashcard
     */
    public function testDeleteFlashcard(): void
    {
        $user = $this->createUser();
        $flashcards = $this->createFlashcardsForUser($user, 1);
        $flashcard = $flashcards[0];
        $flashcardId = $flashcard->getId();

        $this->makeAuthenticatedRequest('DELETE', '/api/flashcards/' . $flashcardId, $user);

        $this->assertJsonResponse(Response::HTTP_OK);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('message', $data);

        // Verify flashcard is actually deleted
        $this->entityManager->clear();
        $deletedFlashcard = $this->entityManager->getRepository(Flashcard::class)->find($flashcardId);
        $this->assertNull($deletedFlashcard);
    }

    /**
     * Test deleting a non-existent flashcard
     */
    public function testDeleteNonExistentFlashcard(): void
    {
        $user = $this->createUser();

        $this->makeAuthenticatedRequest('DELETE', '/api/flashcards/99999', $user);

        $this->assertJsonResponse(Response::HTTP_NOT_FOUND);
        $this->assertResponseHasError('Flashcard not found');
    }

    /**
     * Test deleting another user's flashcard
     */
    public function testDeleteAnotherUsersFlashcard(): void
    {
        $user1 = $this->createUser('user1@example.com');
        $user2 = $this->createUser('user2@example.com');
        
        $flashcards = $this->createFlashcardsForUser($user1, 1);
        $flashcard = $flashcards[0];

        // User2 tries to delete User1's flashcard
        $this->makeAuthenticatedRequest('DELETE', '/api/flashcards/' . $flashcard->getId(), $user2);

        $this->assertJsonResponse(Response::HTTP_FORBIDDEN);
        $this->assertResponseHasError('Access denied');

        // Verify flashcard still exists
        $this->entityManager->clear();
        $stillExists = $this->entityManager->getRepository(Flashcard::class)->find($flashcard->getId());
        $this->assertNotNull($stillExists);
    }

    /**
     * Helper method to create flashcards for a user
     * @return Flashcard[]
     */
    private function createFlashcardsForUser($user, int $count): array
    {
        $flashcards = [];
        
        for ($i = 0; $i < $count; $i++) {
            $flashcard = new Flashcard();
            $flashcard->setUser($user);
            $flashcard->setQuestion("Question $i");
            $flashcard->setAnswer("Answer $i");
            $flashcard->setSource($i % 2 === 0 ? 'manual' : 'ai');
            
            $this->entityManager->persist($flashcard);
            $flashcards[] = $flashcard;
        }
        
        $this->entityManager->flush();
        
        return $flashcards;
    }
}

