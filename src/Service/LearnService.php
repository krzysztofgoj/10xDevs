<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Flashcard;
use App\Entity\RepetitionRecord;
use App\Entity\User;
use App\Repository\FlashcardRepository;
use App\Repository\RepetitionRecordRepository;
use Doctrine\ORM\EntityManagerInterface;

final class LearnService
{
    public function __construct(
        private readonly FlashcardRepository $flashcardRepository,
        private readonly RepetitionRecordRepository $repetitionRecordRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Inicjalizuje sesję nauki dla użytkownika
     * 
     * @param User $user
     * @param string $mode 'question_to_answer', 'answer_to_question', 'random'
     * @return array Struktura sesji
     */
    public function initializeSession(User $user, string $mode): array
    {
        $flashcards = $this->flashcardRepository->findBy(['user' => $user]);
        
        if (empty($flashcards)) {
            return [
                'mode' => $mode,
                'flashcards' => [],
                'current_index' => 0,
                'results' => [],
                'total' => 0
            ];
        }

        // Losowa kolejność
        shuffle($flashcards);
        
        // Wyciągamy tylko ID
        $flashcardIds = array_map(fn(Flashcard $f) => $f->getId(), $flashcards);

        return [
            'mode' => $mode,
            'flashcards' => $flashcardIds,
            'current_index' => 0,
            'results' => [],
            'total' => count($flashcardIds)
        ];
    }

    /**
     * Pobiera aktualną fiszkę w sesji
     */
    public function getCurrentFlashcard(array $session): ?Flashcard
    {
        if (empty($session['flashcards']) || $session['current_index'] >= count($session['flashcards'])) {
            return null;
        }

        $flashcardId = $session['flashcards'][$session['current_index']];
        return $this->flashcardRepository->find($flashcardId);
    }

    /**
     * Sprawdza czy sesja jest zakończona
     */
    public function isSessionFinished(array $session): bool
    {
        return $session['current_index'] >= count($session['flashcards']);
    }

    /**
     * Określa kierunek dla aktualnej fiszki
     * 
     * @return string 'question_to_answer' lub 'answer_to_question'
     */
    public function getDirectionForCurrentFlashcard(array $session): string
    {
        if ($session['mode'] === 'random') {
            return random_int(0, 1) === 0 ? 'question_to_answer' : 'answer_to_question';
        }

        return $session['mode'];
    }

    /**
     * Sprawdza odpowiedź użytkownika
     * 
     * @param string $userAnswer
     * @param string $correctAnswer
     * @return bool
     */
    public function checkAnswer(string $userAnswer, string $correctAnswer): bool
    {
        $normalized1 = $this->normalizeAnswer($userAnswer);
        $normalized2 = $this->normalizeAnswer($correctAnswer);

        return $normalized1 === $normalized2;
    }

    /**
     * Normalizuje odpowiedź (trim + lowercase)
     */
    private function normalizeAnswer(string $answer): string
    {
        return mb_strtolower(trim($answer));
    }

    /**
     * Zapisuje wynik próby w sesji
     */
    public function recordAttempt(array &$session, int $flashcardId, bool $isCorrect, string $userAnswer, int $attemptNumber): void
    {
        $flashcardIdStr = (string)$flashcardId;
        
        if (!isset($session['results'][$flashcardIdStr])) {
            $session['results'][$flashcardIdStr] = [
                'correct' => false,
                'attempts' => 0,
                'user_answers' => []
            ];
        }

        $session['results'][$flashcardIdStr]['attempts'] = $attemptNumber;
        $session['results'][$flashcardIdStr]['user_answers'][] = $userAnswer;
        
        if ($isCorrect) {
            $session['results'][$flashcardIdStr]['correct'] = true;
        }
    }

    /**
     * Przechodzi do następnej fiszki
     */
    public function moveToNextFlashcard(array &$session): void
    {
        $session['current_index']++;
    }

    /**
     * Aktualizuje RepetitionRecord po odpowiedzi
     */
    public function updateRepetitionRecord(Flashcard $flashcard, bool $isCorrect): void
    {
        $repetitionRecord = $flashcard->getRepetitionRecord();
        
        if (!$repetitionRecord) {
            $repetitionRecord = new RepetitionRecord();
            $repetitionRecord->setFlashcard($flashcard);
            $this->entityManager->persist($repetitionRecord);
        }

        $now = new \DateTimeImmutable();
        $repetitionRecord->setLastReviewedAt($now);
        $repetitionRecord->setRepetitionCount($repetitionRecord->getRepetitionCount() + 1);

        if ($isCorrect) {
            // Algorytm SM-2 uproszczony
            $currentEaseFactor = (float)$repetitionRecord->getEaseFactor();
            $newEaseFactor = max(1.3, $currentEaseFactor + 0.1);
            $repetitionRecord->setEaseFactor((string)$newEaseFactor);

            $currentInterval = $repetitionRecord->getIntervalDays();
            $newInterval = (int)ceil($currentInterval * $newEaseFactor);
            $repetitionRecord->setIntervalDays($newInterval);

            $nextReview = $now->modify("+{$newInterval} days");
            $repetitionRecord->setNextReviewAt($nextReview);
        } else {
            // Niepoprawna odpowiedź - resetujemy
            $currentEaseFactor = (float)$repetitionRecord->getEaseFactor();
            $newEaseFactor = max(1.3, $currentEaseFactor - 0.2);
            $repetitionRecord->setEaseFactor((string)$newEaseFactor);
            
            $repetitionRecord->setIntervalDays(1);
            $nextReview = $now->modify('+1 day');
            $repetitionRecord->setNextReviewAt($nextReview);
        }

        $this->entityManager->flush();
    }

    /**
     * Generuje podsumowanie sesji
     */
    public function generateSummary(array $session): array
    {
        $total = count($session['flashcards']);
        $correct = 0;
        $firstAttemptCorrect = 0;
        $secondAttemptCorrect = 0;
        $incorrect = 0;

        foreach ($session['results'] as $result) {
            if ($result['correct']) {
                $correct++;
                if ($result['attempts'] === 1) {
                    $firstAttemptCorrect++;
                } else {
                    $secondAttemptCorrect++;
                }
            } else {
                $incorrect++;
            }
        }

        return [
            'total' => $total,
            'correct' => $correct,
            'incorrect' => $incorrect,
            'first_attempt_correct' => $firstAttemptCorrect,
            'second_attempt_correct' => $secondAttemptCorrect,
            'percentage' => $total > 0 ? round(($correct / $total) * 100, 1) : 0
        ];
    }
}

