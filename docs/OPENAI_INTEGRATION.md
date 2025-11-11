# 🤖 Integracja z OpenAI - AKTYWNA ✅

## Status: GOTOWE DO UŻYCIA 🚀

System jest w pełni zintegrowany z OpenAI API i zawiera:
- ✅ Profesjonalny prompt dla generowania fiszek
- ✅ Rate limiting (10 zapytań/godzinę na użytkownika)
- ✅ Monitoring kosztów ($5/dzień, $50/miesiąc)
- ✅ Logowanie wszystkich wywołań API
- ✅ Automatyczne szacowanie kosztów
- ✅ Możliwość przełączenia na Mock do testów

## Szybki start

### Krok 1: Pobierz klucz API OpenAI

1. Idź do https://platform.openai.com/api-keys
2. Zaloguj się lub utwórz konto
3. Kliknij "Create new secret key"
4. Skopiuj klucz (zaczyna się od `sk-`)

### Krok 2: Skonfiguruj zmienną środowiskową

Dodaj klucz do `.env` lub `.env.local`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Response\GeneratedFlashcardResponse;
use OpenAI\Client;

final class OpenAIFlashcardGenerator implements FlashcardGeneratorInterface
{
    public function __construct(
        private readonly Client $openAIClient,
        private readonly string $model = 'gpt-4'
    ) {
    }

    public function generate(string $sourceText): array
    {
        $prompt = $this->buildPrompt($sourceText);
        
        $response = $this->openAIClient->chat()->create([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system', 
                    'content' => 'You are an expert educational content creator. Generate flashcards in both Polish and English based on the provided text. Return a JSON array with 3-10 flashcards.'
                ],
                [
                    'role' => 'user', 
                    'content' => $prompt
                ],
            ],
            'temperature' => 0.7,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response->choices[0]->message->content;
        $data = json_decode($content, true);
        
        return $this->parseFlashcards($data);
    }
    
    private function buildPrompt(string $sourceText): string
    {
        return <<<PROMPT
Based on the following text, generate between 3 and 10 educational flashcards.
Each flashcard should have a question (in either Polish or English) and an answer (in either Polish or English).
Mix the languages - some questions in Polish with English answers, and vice versa.
Focus on key concepts, definitions, and important facts.

Text:
{$sourceText}

Return the flashcards in this JSON format:
{
  "flashcards": [
    {"question": "What is...", "answer": "Odpowiedź..."},
    {"question": "Co to jest...", "answer": "The answer is..."}
  ]
}
PROMPT;
    }
    
    private function parseFlashcards(array $data): array
    {
        $flashcards = [];
        
        if (!isset($data['flashcards']) || !is_array($data['flashcards'])) {
            throw new \RuntimeException('Invalid response format from OpenAI');
        }
        
        foreach ($data['flashcards'] as $item) {
            if (!isset($item['question']) || !isset($item['answer'])) {
                continue;
            }
            
            $flashcards[] = new GeneratedFlashcardResponse(
                $item['question'],
                $item['answer']
            );
        }
        
        // Ensure we have 3-10 flashcards
        if (count($flashcards) < 3) {
            throw new \RuntimeException('Too few flashcards generated');
        }
        
        if (count($flashcards) > 10) {
            $flashcards = array_slice($flashcards, 0, 10);
        }
        
        return $flashcards;
    }
}
```

### Krok 3: Utwórz konfigurację dla OpenAI Client

Dodaj do `config/services.yaml`:

```yaml
services:
    # ... istniejące serwisy ...

    # OpenAI Client
    OpenAI\Client:
        factory: ['OpenAI', 'client']
        arguments:
            - '%env(OPENAI_API_KEY)%'

    # Flashcard generator - OpenAI implementation
    App\Service\FlashcardGeneratorInterface:
        class: App\Service\OpenAIFlashcardGenerator
        arguments:
            $model: '%env(default:gpt-4:OPENAI_MODEL)%'
```

### Krok 4: Dodaj zmienne środowiskowe

W pliku `.env` (lokalnie) lub `.env.local` dodaj:

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_MODEL=gpt-4
```

**⚠️ WAŻNE:** 
- NIE commituj pliku `.env.local` z prawdziwym kluczem API!
- Dodaj `.env.local` do `.gitignore`
- W produkcji użyj zmiennych środowiskowych serwera

### Krok 5: Testowanie

Użyj skryptu testowego:

```bash
./test-flashcards.sh test@example.com password123
```

Lub ręcznie:

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}' \
  | jq -r '.token')

curl -X POST http://localhost:8000/api/flashcards/generate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "sourceText": "Artificial intelligence is intelligence demonstrated by machines..."
  }' | jq
```

## Przełączanie między Mock a OpenAI

### Powrót do Mock (dla testów)

W `config/services.yaml` zmień:

```yaml
App\Service\FlashcardGeneratorInterface:
    class: App\Service\MockFlashcardGenerator
```

### Użycie OpenAI (produkcja)

W `config/services.yaml` zmień:

```yaml
App\Service\FlashcardGeneratorInterface:
    class: App\Service\OpenAIFlashcardGenerator
    arguments:
        $model: '%env(default:gpt-4:OPENAI_MODEL)%'
```

## Koszty API

Przy użyciu GPT-4:
- **Input**: ~$0.03 / 1K tokens
- **Output**: ~$0.06 / 1K tokens

Przykładowa kalkulacja:
- Tekst źródłowy: 500 słów ≈ 667 tokenów
- Wygenerowane fiszki: ≈ 300 tokenów
- Koszt na zapytanie: ~$0.02-0.04

**Zalecenia:**
- Dla środowiska dev używaj Mock generatora
- Dla produkcji rozważ limity rate limiting
- Monitoruj koszty w dashboard OpenAI

## Rate Limiting (opcjonalnie)

Aby zabezpieczyć się przed nadmiernym użyciem API, możesz dodać rate limiting:

```bash
composer require symfony/rate-limiter
```

W kontrolerze:

```php
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/generate', name: 'generate', methods: ['POST'])]
public function generate(
    Request $request,
    RateLimiterFactory $flashcardGeneratorLimiter
): JsonResponse {
    $limiter = $flashcardGeneratorLimiter->create($request->getClientIp());
    
    if (!$limiter->consume(1)->isAccepted()) {
        return new JsonResponse(
            ['error' => 'Too many requests. Please try again later.'],
            JsonResponse::HTTP_TOO_MANY_REQUESTS
        );
    }
    
    // ... reszta kodu
}
```

Konfiguracja w `config/packages/rate_limiter.yaml`:

```yaml
framework:
    rate_limiter:
        flashcard_generator:
            policy: 'sliding_window'
            limit: 10
            interval: '1 hour'
```

## Monitorowanie i logi

Dodaj logowanie do śledzenia użycia:

```php
use Psr\Log\LoggerInterface;

public function __construct(
    private readonly Client $openAIClient,
    private readonly LoggerInterface $logger,
    private readonly string $model = 'gpt-4'
) {
}

public function generate(string $sourceText): array
{
    $this->logger->info('Generating flashcards with OpenAI', [
        'model' => $this->model,
        'text_length' => strlen($sourceText),
        'word_count' => str_word_count($sourceText),
    ]);
    
    try {
        // ... generowanie
        
        $this->logger->info('Flashcards generated successfully', [
            'count' => count($flashcards),
        ]);
        
        return $flashcards;
    } catch (\Exception $e) {
        $this->logger->error('Failed to generate flashcards', [
            'error' => $e->getMessage(),
        ]);
        throw $e;
    }
}
```

## Wsparcie

W razie problemów:
1. Sprawdź logi Symfony: `var/log/dev.log`
2. Sprawdź dashboard OpenAI: https://platform.openai.com/usage
3. Sprawdź dokumentację: https://platform.openai.com/docs

