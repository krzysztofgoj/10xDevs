<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Response\ErrorResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionListener
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Tylko dla żądań API
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $error = 'internal_server_error';
        $message = 'An error occurred';

        if ($exception instanceof AccessDeniedException) {
            // Check if user is authenticated
            $token = $this->tokenStorage->getToken();
            $isAuthenticated = $token !== null && $token->getUser() !== null;
            
            if ($isAuthenticated) {
                // User is authenticated but doesn't have permission
                $statusCode = Response::HTTP_FORBIDDEN;
                $error = 'access_denied';
                $message = 'Access denied';
            } else {
                // User is not authenticated
                $statusCode = Response::HTTP_UNAUTHORIZED;
                $error = 'unauthorized';
                $message = 'Authentication required';
            }
        } elseif ($exception instanceof AuthenticationException) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
            $error = 'unauthorized';
            $message = 'Authentication required';
        } elseif ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: Response::$statusTexts[$statusCode] ?? 'An error occurred';
            $error = match ($statusCode) {
                400 => 'bad_request',
                401 => 'unauthorized',
                403 => 'forbidden',
                404 => 'not_found',
                422 => 'validation_failed',
                default => 'http_error',
            };
        } elseif ($exception instanceof ValidationFailedException) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            $error = 'validation_failed';
            $message = 'Validation failed';
            $violations = [];
            foreach ($exception->getViolations() as $violation) {
                $violations[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
            $response = new ErrorResponse($error, $message, ['violations' => $violations]);
        } else {
            // W środowisku dev, dodaj szczegóły błędu
            $details = null;
            if ($_ENV['APP_ENV'] ?? 'prod' === 'dev') {
                $details = [
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ];
            }
            $response = new ErrorResponse($error, $message, $details);
        }

        if (!isset($response)) {
            $response = new ErrorResponse($error, $message);
        }

        $json = $this->serializer->serialize($response, 'json', ['groups' => ['api']]);

        $event->setResponse(new JsonResponse($json, $statusCode, [], true));
    }
}

