# Pytania przed implementacją REST API - 10x-cards

## Kontekst projektu

Projekt wykorzystuje:
- **Backend**: Symfony 6.4 + PHP 8.3
- **Baza danych**: PostgreSQL 15 + Doctrine ORM
- **Autoryzacja**: Symfony Security Component (form_login)
- **Status**: Migracja bazy danych gotowa, encje Doctrine jeszcze nie utworzone

## Pytania architektoniczne

### 1. Struktura API

**Pytanie**: Czy chcemy używać:
- **A)** Zwykłych kontrolerów Symfony z atrybutami `#[Route]` i ręczną serializacją JSON?
- **B)** API Platform (bundle Symfony do automatycznego generowania REST API)?
- **C)** Innego rozwiązania?

**Rekomendacja**: Dla MVP polecam opcję **A** - zwykłe kontrolery, ponieważ:
- Daje pełną kontrolę nad formatem odpowiedzi
- Nie wymaga dodatkowych zależności
- Łatwiejsze w debugowaniu i utrzymaniu
- Zgodne z zasadą "thin controllers" - logika biznesowa w serwisach

**Odpowiedź użytkownika**: [A] Zwykłe kontrolery Symfony

---

### 2. Serializacja danych

**Pytanie**: Jak chcemy serializować dane do JSON?
- **A)** Symfony Serializer Component (wbudowany w Symfony)?
- **B)** Ręczna serializacja przez `json_encode()` z array/objects?
- **C)** Inne rozwiązanie (np. JMS Serializer)?

**Rekomendacja**: **A** - Symfony Serializer Component, ponieważ:
- Wbudowany w Symfony (nie wymaga dodatkowych pakietów)
- Obsługuje normalizację encji Doctrine
- Możliwość użycia atrybutów do kontroli serializacji
- Obsługa grup serializacji (np. `normalizationContext: ['groups' => ['api']]`)

**Odpowiedź użytkownika**: [A] Symfony Serializer Component

---

### 3. Autoryzacja API

**Pytanie**: Jak chcemy autoryzować żądania API?
- **A)** Session-based (form_login) - ta sama sesja co dla UI?
- **B)** JWT tokens (JSON Web Tokens)?
- **C)** API tokens (statyczne tokeny w bazie danych)?
- **D)** OAuth2?

**Rekomendacja**: Dla MVP **A** - session-based, ponieważ:
- Już skonfigurowane w projekcie
- Proste w implementacji
- Wystarczające dla aplikacji webowej (nie ma osobnej aplikacji mobilnej)
- Można rozszerzyć o JWT w przyszłości

**Uwaga**: Jeśli planujemy osobne API dla aplikacji mobilnej w przyszłości, lepiej od razu JWT.

**Odpowiedź użytkownika**: [B] JWT tokens

---

### 4. Struktura odpowiedzi API

**Pytanie**: Jaki format odpowiedzi API preferujemy?
- **A)** Standardowy JSON z encjami (np. `{"id": 1, "question": "...", "answer": "..."}`)
- **B)** Wrapper z metadanymi (np. `{"data": {...}, "meta": {...}}`)
- **C)** Zgodny z JSON:API spec?
- **D)** Inny format?

**Rekomendacja**: **A** - standardowy JSON dla MVP, ponieważ:
- Prosty i czytelny
- Łatwy w konsumpcji przez frontend
- Można dodać wrapper w przyszłości jeśli potrzeba

**Odpowiedź użytkownika**: [A] Standardowy JSON

---

### 5. Obsługa błędów

**Pytanie**: Jak chcemy formatować błędy API?
- **A)** Standardowe odpowiedzi HTTP z JSON body (np. `{"error": "message"}`)
- **B)** Symfony Exception Listener z custom formatowaniem
- **C)** RFC 7807 Problem Details for HTTP APIs?
- **D)** Inny format?

**Rekomendacja**: **B** - Symfony Exception Listener, ponieważ:
- Wbudowany mechanizm Symfony
- Możliwość customizacji formatu błędów
- Obsługa różnych typów wyjątków
- Możliwość dodania Problem Details w przyszłości

**Odpowiedź użytkownika**: [B] Symfony Exception Listener

---

### 6. DTOs i Command Models

**Pytanie**: Gdzie i jak chcemy przechowywać DTOs (Data Transfer Objects) i Command Models?
- **A)** `src/DTO/` - osobny katalog dla DTOs
- **B)** `src/Request/` i `src/Response/` - podział na request/response
- **C)** `src/Api/Request/` i `src/Api/Response/` - w namespace API
- **D)** Inna struktura?

**Rekomendacja**: **B** - `src/Request/` i `src/Response/`, ponieważ:
- Jasny podział odpowiedzialności
- Łatwe do znalezienia
- Możliwość użycia z Symfony Forms (jeśli potrzeba)
- Zgodne z konwencją Symfony

**Przykładowa struktura**:
```
src/
  Request/
    CreateFlashcardRequest.php
    GenerateFlashcardsRequest.php
  Response/
    FlashcardResponse.php
    FlashcardGenerationResponse.php
```

**Odpowiedź użytkownika**: [B] Tak - src/Request/ i src/Response/

---

### 7. Walidacja danych wejściowych

**Pytanie**: Jak chcemy walidować dane wejściowe?
- **A)** Symfony Validator Component z atrybutami (Assert\*) na DTOs
- **B)** Symfony Forms z walidacją
- **C)** Ręczna walidacja w kontrolerach/serwisach
- **D)** Kombinacja powyższych?

**Rekomendacja**: **A** - Symfony Validator z atrybutami, ponieważ:
- Wbudowany w Symfony
- Deklaratywny (atrybuty na klasach)
- Łatwy w użyciu i testowaniu
- Możliwość custom constraints

**Odpowiedź użytkownika**: [ ]

---

### 8. Prefix routingu API

**Pytanie**: Czy chcemy prefiksować wszystkie endpointy API?
- **A)** Tak, `/api/` (np. `/api/flashcards`, `/api/generations`)
- **B)** Tak, `/api/v1/` (dla wersjonowania)
- **C)** Nie, bez prefiksu (np. `/flashcards`, `/generations`)
- **D)** Inny prefiks?

**Rekomendacja**: **A** - `/api/` dla MVP, ponieważ:
- Jasne rozróżnienie między API a UI routes
- Łatwe dodanie wersjonowania w przyszłości (`/api/v2/`)
- Standardowa praktyka

**Odpowiedź użytkownika**: [ ]

---

### 9. Dokumentacja API

**Pytanie**: Jak chcemy dokumentować API?
- **A)** OpenAPI/Swagger (np. NelmioApiDocBundle)
- **B)** README z przykładami curl
- **C)** Tylko komentarze w kodzie
- **D)** Inne rozwiązanie?

**Rekomendacja**: **B** - README z przykładami dla MVP, ponieważ:
- Proste i wystarczające na start
- Można dodać OpenAPI w przyszłości
- Nie wymaga dodatkowych zależności

**Odpowiedź użytkownika**: [ ]

---

### 10. Testowanie API

**Pytanie**: Jak chcemy testować endpointy API?
- **A)** PHPUnit functional tests z Symfony Test Client
- **B)** Tylko manualne testy (curl/Postman)
- **C)** Kombinacja automatycznych i manualnych
- **D)** Inne narzędzie?

**Rekomendacja**: **C** - kombinacja, ponieważ:
- Automatyczne testy dla krytycznych ścieżek (happy path, walidacja)
- Manualne testy dla edge cases i integracji
- Symfony Test Client jest wbudowany i łatwy w użyciu

**Odpowiedź użytkownika**: [ ]

---

## Pytania dotyczące konkretnych endpointów

### 11. Endpoint generowania fiszek

**Pytanie**: Jak chcemy obsługiwać asynchroniczne generowanie fiszek przez AI?
- **A)** Synchronicznie - endpoint czeka na odpowiedź z API LLM (może być długo)
- **B)** Asynchronicznie - endpoint zwraca job ID, status sprawdzany przez polling
- **C)** WebSockets/SSE dla real-time updates
- **D)** Inne rozwiązanie?

**Rekomendacja**: **A** - synchronicznie dla MVP, ponieważ:
- Prostsze w implementacji
- Nie wymaga systemu kolejkowania (Symfony Messenger)
- Wystarczające dla MVP
- Można zmienić na asynchroniczne w przyszłości

**Uwaga**: Jeśli API LLM może zwracać odpowiedź >30s, lepiej od razu asynchronicznie.

**Odpowiedź użytkownika**: [ ]

---

### 12. Endpoint zapisu fiszek

**Pytanie**: Czy endpoint zapisu fiszek powinien:
- **A)** Przyjmować pojedynczą fiszkę (POST `/api/flashcards`)
- **B)** Przyjmować wiele fiszek naraz (POST `/api/flashcards` z array)
- **C)** Oba warianty (pojedyncza i bulk)
- **D)** Inne rozwiązanie?

**Rekomendacja**: **C** - oba warianty, ponieważ:
- Pojedyncza fiszka dla ręcznego dodawania
- Bulk dla zapisu wielu wygenerowanych fiszek
- Można użyć tego samego endpointa z różnymi formatami request body

**Odpowiedź użytkownika**: [ ]

---

### 13. Paginacja i filtrowanie

**Pytanie**: Czy chcemy paginację i filtrowanie dla listy fiszek?
- **A)** Tak, od razu w MVP (page, limit, sort)
- **B)** Nie, zwracamy wszystkie fiszki użytkownika
- **C)** Tylko podstawowa paginacja (bez filtrowania)
- **D)** Inne rozwiązanie?

**Rekomendacja**: **C** - podstawowa paginacja dla MVP, ponieważ:
- Proste w implementacji
- Wystarczające dla początkowego użycia
- Można dodać filtrowanie w przyszłości

**Odpowiedź użytkownika**: [ ]

---

## Pytania techniczne

### 14. Integracja z API LLM

**Pytanie**: Jakie API LLM będziemy używać i jak chcemy je integrować?
- **A)** OpenAI API
- **B)** Anthropic Claude API
- **C)** Lokalny model (Ollama, etc.)
- **D)** Inne API?
- **E)** Abstrakcja pozwalająca na łatwą zmianę providera?

**Rekomendacja**: **E** - abstrakcja, ponieważ:
- Elastyczność w zmianie providera
- Łatwiejsze testowanie (mockowanie)
- Zgodne z zasadą Dependency Inversion

**Odpowiedź użytkownika**: [ ]

---

### 15. Environment variables

**Pytanie**: Czy mamy już skonfigurowane zmienne środowiskowe dla:
- API LLM (URL, API key)?
- Innych zewnętrznych serwisów?

**Odpowiedź użytkownika**: [ ]

---

## Podsumowanie

Po odpowiedzi na powyższe pytania będziemy mogli:
1. ✅ Utworzyć odpowiednią strukturę katalogów
2. ✅ Skonfigurować serializację i walidację
3. ✅ Zdefiniować format odpowiedzi i błędów
4. ✅ Zaplanować szczegółową implementację endpointów
5. ✅ Przygotować dokumentację i testy

**Następny krok**: Po zebraniu odpowiedzi przygotujemy szczegółowy plan implementacji API zgodny z wybranymi decyzjami.

