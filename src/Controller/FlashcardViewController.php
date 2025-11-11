<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\LearnService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/flashcards', name: 'flashcards_')]
#[IsGranted('ROLE_USER')]
final class FlashcardViewController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly LearnService $learnService
    ) {
    }

    #[Route('/generate', name: 'generate', methods: ['GET'])]
    public function generate(): Response
    {
        $user = $this->getUser();
        $jwtToken = $this->authService->createTokenForUser($user);

        return $this->render('flashcards/generate.html.twig', [
            'jwt_token' => $jwtToken,
        ]);
    }

    #[Route('/my-flashcards', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        $user = $this->getUser();
        $jwtToken = $this->authService->createTokenForUser($user);

        return $this->render('flashcards/list.html.twig', [
            'jwt_token' => $jwtToken,
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET'])]
    public function add(): Response
    {
        $user = $this->getUser();
        $jwtToken = $this->authService->createTokenForUser($user);

        return $this->render('flashcards/add.html.twig', [
            'jwt_token' => $jwtToken,
        ]);
    }

    #[Route('/learn', name: 'learn_start', methods: ['GET', 'POST'])]
    public function learnStart(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $mode = $request->request->get('mode', 'question_to_answer');
            
            // Walidacja trybu
            $validModes = ['question_to_answer', 'answer_to_question', 'random'];
            if (!in_array($mode, $validModes, true)) {
                $mode = 'question_to_answer';
            }

            // Inicjalizacja sesji nauki
            $user = $this->getUser();
            $session = $this->learnService->initializeSession($user, $mode);
            
            // Sprawdzamy czy są fiszki
            if (empty($session['flashcards'])) {
                $this->addFlash('warning', 'Nie masz jeszcze żadnych fiszek do nauki! Dodaj najpierw fiszki.');
                return $this->redirectToRoute('app_dashboard');
            }

            // Zapisujemy sesję
            $request->getSession()->set('learn_session', $session);
            
            return $this->redirectToRoute('flashcards_learn_session');
        }

        return $this->render('flashcards/learn_start.html.twig');
    }

    #[Route('/learn/session', name: 'learn_session', methods: ['GET', 'POST'])]
    public function learnSession(Request $request): Response
    {
        $session = $request->getSession()->get('learn_session');
        
        if (!$session) {
            $this->addFlash('warning', 'Sesja nauki wygasła. Rozpocznij nową sesję.');
            return $this->redirectToRoute('flashcards_learn_start');
        }

        // Sprawdzamy czy sesja jest zakończona
        if ($this->learnService->isSessionFinished($session)) {
            return $this->redirectToRoute('flashcards_learn_summary');
        }

        // Pobieramy aktualną fiszkę
        $flashcard = $this->learnService->getCurrentFlashcard($session);
        
        if (!$flashcard) {
            return $this->redirectToRoute('flashcards_learn_summary');
        }

        // Określamy kierunek (jeśli jeszcze nie został określony dla tej fiszki)
        $flashcardId = $flashcard->getId();
        $sessionKey = "direction_{$flashcardId}";
        
        $direction = $request->getSession()->get($sessionKey);
        if (!$direction) {
            $direction = $this->learnService->getDirectionForCurrentFlashcard($session);
            $request->getSession()->set($sessionKey, $direction);
        }

        // Określamy pytanie i odpowiedź w zależności od kierunku
        if ($direction === 'question_to_answer') {
            $prompt = $flashcard->getQuestion();
            $expectedAnswer = $flashcard->getAnswer();
        } else {
            $prompt = $flashcard->getAnswer();
            $expectedAnswer = $flashcard->getQuestion();
        }

        // Pobieramy informacje o poprzedniej próbie (jeśli była)
        $attemptNumber = 1;
        $previousResult = null;
        $flashcardIdStr = (string)$flashcardId;
        
        if (isset($session['results'][$flashcardIdStr])) {
            $attemptNumber = $session['results'][$flashcardIdStr]['attempts'] + 1;
            $previousResult = $session['results'][$flashcardIdStr];
        }

        return $this->render('flashcards/learn.html.twig', [
            'flashcard' => $flashcard,
            'direction' => $direction,
            'prompt' => $prompt,
            'expected_answer' => $expectedAnswer,
            'current_index' => $session['current_index'] + 1,
            'total' => $session['total'],
            'attempt_number' => $attemptNumber,
            'previous_result' => $previousResult,
        ]);
    }

    #[Route('/learn/check', name: 'learn_check', methods: ['POST'])]
    public function learnCheck(Request $request): Response
    {
        $session = $request->getSession()->get('learn_session');
        
        if (!$session) {
            $this->addFlash('warning', 'Sesja nauki wygasła.');
            return $this->redirectToRoute('flashcards_learn_start');
        }

        $flashcard = $this->learnService->getCurrentFlashcard($session);
        
        if (!$flashcard) {
            return $this->redirectToRoute('flashcards_learn_summary');
        }

        $flashcardId = $flashcard->getId();
        $userAnswer = $request->request->get('answer', '');
        $direction = $request->getSession()->get("direction_{$flashcardId}");

        // Określamy poprawną odpowiedź
        $correctAnswer = $direction === 'question_to_answer' 
            ? $flashcard->getAnswer() 
            : $flashcard->getQuestion();

        // Sprawdzamy odpowiedź
        $isCorrect = $this->learnService->checkAnswer($userAnswer, $correctAnswer);

        // Pobieramy numer próby
        $flashcardIdStr = (string)$flashcardId;
        $attemptNumber = isset($session['results'][$flashcardIdStr]) 
            ? $session['results'][$flashcardIdStr]['attempts'] + 1 
            : 1;

        // Zapisujemy wynik
        $this->learnService->recordAttempt($session, $flashcardId, $isCorrect, $userAnswer, $attemptNumber);

        // Jeśli poprawna odpowiedź lub druga próba - przechodzimy dalej i aktualizujemy RepetitionRecord
        if ($isCorrect || $attemptNumber >= 2) {
            // Aktualizujemy RepetitionRecord tylko raz (po pierwszej poprawnej lub po drugiej próbie)
            if ($attemptNumber === 1 || ($attemptNumber === 2 && $isCorrect)) {
                $this->learnService->updateRepetitionRecord($flashcard, $isCorrect);
            } elseif ($attemptNumber === 2 && !$isCorrect) {
                // Druga próba niepoprawna
                $this->learnService->updateRepetitionRecord($flashcard, false);
            }
            
            $this->learnService->moveToNextFlashcard($session);
            
            // Czyścimy kierunek dla tej fiszki
            $request->getSession()->remove("direction_{$flashcardId}");
        }

        // Zapisujemy zaktualizowaną sesję
        $request->getSession()->set('learn_session', $session);

        return $this->redirectToRoute('flashcards_learn_session');
    }

    #[Route('/learn/summary', name: 'learn_summary', methods: ['GET'])]
    public function learnSummary(Request $request): Response
    {
        $session = $request->getSession()->get('learn_session');
        
        if (!$session) {
            $this->addFlash('warning', 'Brak sesji nauki.');
            return $this->redirectToRoute('flashcards_learn_start');
        }

        $summary = $this->learnService->generateSummary($session);

        // Czyścimy sesję nauki
        $request->getSession()->remove('learn_session');

        return $this->render('flashcards/learn_summary.html.twig', [
            'summary' => $summary,
        ]);
    }

    #[Route('/learn/quit', name: 'learn_quit', methods: ['POST'])]
    public function learnQuit(Request $request): Response
    {
        // Czyścimy sesję nauki
        $request->getSession()->remove('learn_session');
        
        $this->addFlash('info', 'Sesja nauki została przerwana.');
        
        return $this->redirectToRoute('flashcards_list');
    }

}

