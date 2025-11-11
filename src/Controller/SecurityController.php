<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use App\Service\AuthService;
use App\Repository\FlashcardRepository;

final class SecurityController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AuthService $authService,
        private readonly FlashcardRepository $flashcardRepository
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }
        
        return $this->redirectToRoute('app_login');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $jwtToken = $this->authService->createTokenForUser($user);

        // Pobierz statystyki
        $totalFlashcards = $this->flashcardRepository->countByUser($user->getId());
        $aiGeneratedFlashcards = $this->flashcardRepository->countByUserAndSource($user->getId(), 'ai');
        $manualFlashcards = $this->flashcardRepository->countByUserAndSource($user->getId(), 'manual');

        return $this->render('dashboard/index.html.twig', [
            'jwt_token' => $jwtToken,
            'total_flashcards' => $totalFlashcards,
            'ai_generated' => $aiGeneratedFlashcards,
            'manual_flashcards' => $manualFlashcards,
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            // Walidacja
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Podaj prawidłowy adres email';
            } elseif (!$password || strlen($password) < 6) {
                $error = 'Hasło musi mieć minimum 6 znaków';
            } elseif ($password !== $passwordConfirm) {
                $error = 'Hasła nie są identyczne';
            } elseif ($this->userRepository->findOneBy(['email' => $email])) {
                $error = 'Użytkownik o tym adresie email już istnieje';
            } else {
                // Utwórz użytkownika
                $user = new User();
                $user->setEmail($email);
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
                $user->setRoles(['ROLE_USER']);

                $this->userRepository->save($user, true);

                $this->addFlash('success', 'Konto zostało utworzone! Możesz się teraz zalogować.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $jwtToken = $this->authService->createTokenForUser($user);

        // Pobierz statystyki
        $totalFlashcards = $this->flashcardRepository->countByUser($user->getId());
        $aiGeneratedFlashcards = $this->flashcardRepository->countByUserAndSource($user->getId(), 'ai');

        return $this->render('security/profile.html.twig', [
            'jwt_token' => $jwtToken,
            'total_flashcards' => $totalFlashcards,
            'ai_generated' => $aiGeneratedFlashcards,
            'total_repetitions' => 0, // TODO: implement when repetition system is ready
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Ten endpoint jest obsługiwany przez security system
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

