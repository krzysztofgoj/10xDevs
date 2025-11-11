<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Flashcard;
use App\Repository\FlashcardRepository;
use App\Request\BulkCreateFlashcardsRequest;
use App\Request\GenerateFlashcardsRequest;
use App\Response\FlashcardResponse;
use App\Service\FlashcardGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api/flashcards', name: 'api_flashcards_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class FlashcardController extends AbstractController
{
    public function __construct(
        private readonly FlashcardGeneratorInterface $flashcardGenerator,
        private readonly FlashcardRepository $flashcardRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/generate', name: 'generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            GenerateFlashcardsRequest::class,
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
            $generatedFlashcards = $this->flashcardGenerator->generate($dto->sourceText);
            
            return new JsonResponse(
                $this->serializer->serialize($generatedFlashcards, 'json', ['groups' => ['api']]),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Failed to generate flashcards: ' . $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/bulk', name: 'bulk_create', methods: ['POST'])]
    public function bulkCreate(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            BulkCreateFlashcardsRequest::class,
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
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $createdFlashcards = [];

            foreach ($dto->flashcards as $flashcardData) {
                $flashcard = new Flashcard();
                $flashcard->setUser($user);
                $flashcard->setQuestion($flashcardData['question']);
                $flashcard->setAnswer($flashcardData['answer']);
                $flashcard->setSource($flashcardData['source'] ?? 'ai');

                $this->entityManager->persist($flashcard);
                $createdFlashcards[] = $flashcard;
            }

            $this->entityManager->flush();

            // Convert to response DTOs
            $responseData = array_map(function (Flashcard $flashcard) {
                return new FlashcardResponse(
                    $flashcard->getId(),
                    $flashcard->getQuestion(),
                    $flashcard->getAnswer(),
                    $flashcard->getSource(),
                    $flashcard->getCreatedAt(),
                    $flashcard->getUpdatedAt()
                );
            }, $createdFlashcards);

            return new JsonResponse(
                $this->serializer->serialize($responseData, 'json', ['groups' => ['api']]),
                JsonResponse::HTTP_CREATED,
                [],
                true
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Failed to create flashcards: ' . $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $flashcards = $this->flashcardRepository->findByUser($user->getId(), $limit, $offset);
        $total = $this->flashcardRepository->countByUser($user->getId());

        $responseData = array_map(function (Flashcard $flashcard) {
            return new FlashcardResponse(
                $flashcard->getId(),
                $flashcard->getQuestion(),
                $flashcard->getAnswer(),
                $flashcard->getSource(),
                $flashcard->getCreatedAt(),
                $flashcard->getUpdatedAt()
            );
        }, $flashcards);

        return new JsonResponse([
            'data' => json_decode($this->serializer->serialize($responseData, 'json', ['groups' => ['api']]), true),
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil($total / $limit),
            ],
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $flashcard = $this->flashcardRepository->find($id);

        if (!$flashcard) {
            return new JsonResponse(
                ['error' => 'Flashcard not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        if ($flashcard->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(
                ['error' => 'Access denied'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $response = new FlashcardResponse(
            $flashcard->getId(),
            $flashcard->getQuestion(),
            $flashcard->getAnswer(),
            $flashcard->getSource(),
            $flashcard->getCreatedAt(),
            $flashcard->getUpdatedAt()
        );

        return new JsonResponse(
            $this->serializer->serialize($response, 'json', ['groups' => ['api']]),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $flashcard = $this->flashcardRepository->find($id);

        if (!$flashcard) {
            return new JsonResponse(
                ['error' => 'Flashcard not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        if ($flashcard->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(
                ['error' => 'Access denied'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['question'])) {
            if (empty(trim($data['question']))) {
                return new JsonResponse(
                    ['error' => 'Question cannot be empty'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }
            $flashcard->setQuestion($data['question']);
        }

        if (isset($data['answer'])) {
            if (empty(trim($data['answer']))) {
                return new JsonResponse(
                    ['error' => 'Answer cannot be empty'],
                    JsonResponse::HTTP_BAD_REQUEST
                );
            }
            $flashcard->setAnswer($data['answer']);
        }

        try {
            $this->entityManager->flush();

            $response = new FlashcardResponse(
                $flashcard->getId(),
                $flashcard->getQuestion(),
                $flashcard->getAnswer(),
                $flashcard->getSource(),
                $flashcard->getCreatedAt(),
                $flashcard->getUpdatedAt()
            );

            return new JsonResponse(
                $this->serializer->serialize($response, 'json', ['groups' => ['api']]),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Failed to update flashcard: ' . $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $flashcard = $this->flashcardRepository->find($id);

        if (!$flashcard) {
            return new JsonResponse(
                ['error' => 'Flashcard not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        if ($flashcard->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(
                ['error' => 'Access denied'],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        try {
            $this->entityManager->remove($flashcard);
            $this->entityManager->flush();

            return new JsonResponse(
                ['message' => 'Flashcard deleted successfully'],
                JsonResponse::HTTP_OK
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Failed to delete flashcard: ' . $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}

