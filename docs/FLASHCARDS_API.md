# 🎴 Flashcards API Documentation

## Generowanie fiszek za pomocą AI

### Generowanie propozycji fiszek

**Endpoint:** `POST /api/flashcards/generate`

**Wymagania:** Token JWT (zalogowany użytkownik)

**Opis:** Powinien generować 3-10 propozycji fiszek na podstawie podanego tekstu. Fiszki nie powinny być zapisywane w bazie danych.

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

---

## Zapisywanie wybranych fiszek

**Endpoint:** `POST /api/flashcards/bulk`

**Wymagania:** Token JWT (zalogowany użytkownik)

**Opis:** Powinien zapisywać wybrane przez użytkownika fiszki w bazie danych. Fiszki powinny być przypisane do zalogowanego użytkownika.

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

### Wymagania implementacji

System powinien wspierać dwa tryby generowania:

1. **Mock Generator** - dla testów i developmentu
   - Generuje przykładowe fiszki bez połączenia z API OpenAI
   - Nie generuje kosztów
   - Szybkie i deterministyczne

2. **OpenAI Generator** - dla produkcji
   - Używa prawdziwego API OpenAI
   - Wymaga klucza API
   - Generuje koszty

### Wymagania konfiguracji

System powinien umożliwiać przełączanie między trybami przez konfigurację w `config/services.yaml`:

```yaml
App\Service\FlashcardGeneratorInterface:
    class: App\Service\OpenAIFlashcardGenerator  # lub MockFlashcardGenerator
    arguments:
        $apiKey: '%env(OPENAI_API_KEY)%'
```

---

## Bezpieczeństwo

- ✅ Wszystkie endpointy powinny wymagać autentykacji JWT
- ✅ Fiszki powinny być przypisane do zalogowanego użytkownika
- ✅ Walidacja długości tekstu (5-1000 słów)
- ✅ Limit liczby generowanych fiszek (3-10)
- ✅ Limit liczby zapisywanych fiszek na raz (1-100)
