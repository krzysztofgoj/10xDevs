# 🎴 Flashcards API Documentation

## Generowanie fiszek za pomocą AI

### Generowanie propozycji fiszek

**Endpoint:** `POST /api/flashcards/generate`

**Wymagania:** Token JWT (zalogowany użytkownik)

**Opis:** Generuje 3-10 propozycji fiszek na podstawie podanego tekstu. Fiszki nie są zapisywane w bazie danych.

#### Request Body

```json
{
  "sourceText": "Photosynthesis is a process used by plants and other organisms to convert light energy into chemical energy..."
}
```

**Walidacja:**
- `sourceText` - wymagane, minimum 5 słów, maksimum 1000 słów

#### Response (200 OK)

```json
[
  {
    "question": "What is the main topic of this text?",
    "answer": "Głównym tematem tekstu jest Photosynthesis is a process..."
  },
  {
    "question": "Co oznacza termin opisany w tekście?",
    "answer": "Termin opisany w tekście odnosi się do kluczowych koncepcji przedstawionych w materiale."
  },
  {
    "question": "What are the key concepts mentioned?",
    "answer": "Kluczowe koncepcje to: Photosynthesis, process, organisms"
  }
]
```

#### Response Errors

**400 Bad Request** - Nieprawidłowe dane wejściowe
```json
{
  "errors": "sourceText: Source text must contain at least 5 words"
}
```

**401 Unauthorized** - Brak tokenu JWT
```json
{
  "message": "JWT Token not found"
}
```

**500 Internal Server Error** - Błąd podczas generowania
```json
{
  "error": "Failed to generate flashcards: ..."
}
```

### Przykład użycia z cURL

```bash
curl -X POST http://localhost:8000/api/flashcards/generate \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "sourceText": "Photosynthesis is a process used by plants and other organisms to convert light energy into chemical energy that can later be released to fuel the organisms activities. This chemical energy is stored in carbohydrate molecules."
  }'
```

---

## Zapisywanie wybranych fiszek

**Endpoint:** `POST /api/flashcards/bulk`

**Wymagania:** Token JWT (zalogowany użytkownik)

**Opis:** Zapisuje wybrane przez użytkownika fiszki w bazie danych. Fiszki są przypisane do zalogowanego użytkownika.

#### Request Body

```json
{
  "flashcards": [
    {
      "question": "What is photosynthesis?",
      "answer": "Fotosynteza to proces, w którym rośliny przekształcają energię świetlną w energię chemiczną.",
      "source": "ai"
    },
    {
      "question": "Where is chemical energy stored?",
      "answer": "Chemical energy is stored in carbohydrate molecules.",
      "source": "ai"
    }
  ]
}
```

**Walidacja:**
- `flashcards` - wymagane, tablica z minimum 1, maksimum 100 fiszkami
- `question` - wymagane, 1-10000 znaków
- `answer` - wymagane, 1-10000 znaków
- `source` - opcjonalne, wartości: "ai" lub "manual" (domyślnie: "ai")

#### Response (201 Created)

```json
[
  {
    "id": 1,
    "question": "What is photosynthesis?",
    "answer": "Fotosynteza to proces, w którym rośliny przekształcają energię świetlną w energię chemiczną.",
    "source": "ai",
    "createdAt": "2025-11-12T10:30:00+00:00",
    "updatedAt": "2025-11-12T10:30:00+00:00"
  },
  {
    "id": 2,
    "question": "Where is chemical energy stored?",
    "answer": "Chemical energy is stored in carbohydrate molecules.",
    "source": "ai",
    "createdAt": "2025-11-12T10:30:00+00:00",
    "updatedAt": "2025-11-12T10:30:00+00:00"
  }
]
```

#### Response Errors

**400 Bad Request** - Nieprawidłowe dane wejściowe
```json
{
  "errors": "flashcards: At least one flashcard is required"
}
```

**401 Unauthorized** - Brak tokenu JWT
```json
{
  "message": "JWT Token not found"
}
```

**500 Internal Server Error** - Błąd podczas zapisywania
```json
{
  "error": "Failed to create flashcards: ..."
}
```

### Przykład użycia z cURL

```bash
curl -X POST http://localhost:8000/api/flashcards/bulk \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "flashcards": [
      {
        "question": "What is photosynthesis?",
        "answer": "Fotosynteza to proces, w którym rośliny przekształcają energię świetlną w energię chemiczną.",
        "source": "ai"
      }
    ]
  }'
```

---

## Flow użycia

1. **Użytkownik wpisuje tekst** (minimum 5 słów, maksimum 1000 słów)
2. **Kliknięcie przycisku "Generuj fiszki"**
   - Wywołanie `POST /api/flashcards/generate`
   - System generuje 3-10 propozycji fiszek
   - Fiszki NIE są zapisane w bazie
3. **Użytkownik wybiera fiszki** z listy propozycji
4. **Kliknięcie przycisku "Dodaj fiszki"**
   - Wywołanie `POST /api/flashcards/bulk` z wybranymi fiszkami
   - Fiszki są zapisane w bazie danych przypisane do użytkownika

---

## Mock Generator vs OpenAI

### Aktualna implementacja (Mock)

Obecnie system używa `MockFlashcardGenerator`, który generuje przykładowe fiszki bez połączenia z API OpenAI.

### Przyszła implementacja (OpenAI)

Aby podłączyć prawdziwe API OpenAI:

1. **Zainstaluj bibliotekę OpenAI:**
```bash
composer require openai-php/client
```

2. **Utwórz `OpenAIFlashcardGenerator.php`:**

```php
<?php

namespace App\Service;

use App\Response\GeneratedFlashcardResponse;
use OpenAI\Client;

final class OpenAIFlashcardGenerator implements FlashcardGeneratorInterface
{
    public function __construct(
        private readonly Client $openAIClient,
        private readonly string $apiKey
    ) {
    }

    public function generate(string $sourceText): array
    {
        $prompt = "Generate 3-10 flashcards in Polish and English based on this text: " . $sourceText;
        
        // Wywołanie OpenAI API
        $response = $this->openAIClient->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that generates educational flashcards.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);
        
        // Parse odpowiedzi i zwróć fiszki
        // ...
    }
}
```

3. **Zaktualizuj `config/services.yaml`:**

```yaml
App\Service\FlashcardGeneratorInterface:
    class: App\Service\OpenAIFlashcardGenerator
    arguments:
        $apiKey: '%env(OPENAI_API_KEY)%'
```

4. **Dodaj klucz API do `.env`:**
```
OPENAI_API_KEY=sk-...
```

---

## Bezpieczeństwo

- ✅ Wszystkie endpointy wymagają autentykacji JWT
- ✅ Fiszki są przypisane do zalogowanego użytkownika
- ✅ Walidacja długości tekstu (5-1000 słów)
- ✅ Limit liczby generowanych fiszek (3-10)
- ✅ Limit liczby zapisywanych fiszek na raz (1-100)

---

## Testowanie

### Test generowania fiszek
```bash
# 1. Zaloguj się i pobierz token
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}' \
  | jq -r '.token')

# 2. Generuj fiszki
curl -X POST http://localhost:8000/api/flashcards/generate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "sourceText": "Artificial intelligence is intelligence demonstrated by machines. It is the simulation of human intelligence processes by machines."
  }' | jq
```

### Test zapisywania fiszek
```bash
# 3. Zapisz wybrane fiszki
curl -X POST http://localhost:8000/api/flashcards/bulk \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "flashcards": [
      {
        "question": "What is artificial intelligence?",
        "answer": "Sztuczna inteligencja to inteligencja wykazywana przez maszyny.",
        "source": "ai"
      }
    ]
  }' | jq
```



