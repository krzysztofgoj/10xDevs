# Podsumowanie implementacji REST API - 10x-cards

## Wykonane kroki (Workflow 3x3 - Iteracja 1)

### ✅ Krok 1: Konfiguracja podstawowa
- Dodano `lexik/jwt-authentication-bundle` do `composer.json`
- Dodano `symfony/serializer` do `composer.json`
- Włączono serializer w `config/packages/framework.yaml`
- Dodano LexikJWTAuthenticationBundle do `config/bundles.php`

### ✅ Krok 2: Konfiguracja JWT i Security
- Utworzono `config/packages/lexik_jwt_authentication.yaml` z konfiguracją JWT
- Zaktualizowano `config/packages/security.yaml` z firewall dla API (JWT)
- Skonfigurowano access_control dla endpointów API

### ✅ Krok 3: Struktura katalogów i podstawowe klasy
- Utworzono katalogi: `src/Controller/Api/`, `src/Request/`, `src/Response/`, `src/Service/`, `src/EventListener/`
- Utworzono `ErrorResponse` DTO dla błędów API
- Utworzono `ApiExceptionListener` dla automatycznej obsługi błędów API
- Skonfigurowano Exception Listener w `services.yaml`

### ✅ Krok 4: Encje Doctrine
- Utworzono encję `User` (implementującą `UserInterface` i `PasswordAuthenticatedUserInterface`)
- Utworzono encję `Flashcard` z relacjami do User i FlashcardGeneration
- Utworzono encję `FlashcardGeneration` z relacjami do User i Flashcard
- Utworzono encję `RepetitionRecord` z relacją 1:1 do Flashcard
- Utworzono repozytoria dla wszystkich encji:
  - `UserRepository`
  - `FlashcardRepository` (z metodą `findByUser` dla paginacji)
  - `FlashcardGenerationRepository`
  - `RepetitionRecordRepository`

### ✅ Krok 5: DTOs dla autoryzacji
- Utworzono `LoginRequest` z walidacją (email, password)
- Utworzono `RegisterRequest` z walidacją (email, password min 8 znaków)
- Utworzono `AuthResponse` DTO (token, userId, email)

## Struktura projektu

```
src/
  Controller/
    Api/              # (gotowe do użycia)
  Entity/
    User.php          ✅
    Flashcard.php     ✅
    FlashcardGeneration.php  ✅
    RepetitionRecord.php     ✅
  Repository/
    UserRepository.php              ✅
    FlashcardRepository.php         ✅
    FlashcardGenerationRepository.php  ✅
    RepetitionRecordRepository.php     ✅
  Request/
    LoginRequest.php      ✅
    RegisterRequest.php   ✅
  Response/
    ErrorResponse.php     ✅
    AuthResponse.php      ✅
  Service/               # (gotowe do użycia)
  EventListener/
    ApiExceptionListener.php  ✅
```

## Konfiguracja

### JWT Configuration
- Plik: `config/packages/lexik_jwt_authentication.yaml`
- Wymaga zmiennych środowiskowych: `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE`
- Token TTL: 3600 sekund (1 godzina)

### Security Configuration
- Firewall API: `/api` z JWT authentication (stateless)
- Firewall main: form_login dla UI
- Access control: `/api/login` i `/api/register` są publiczne

### Exception Handling
- `ApiExceptionListener` automatycznie konwertuje wyjątki na JSON responses
- Obsługuje `HttpExceptionInterface` i `ValidationFailedException`
- Działa tylko dla żądań zaczynających się od `/api`

## Następne kroki (Iteracja 2)

### Krok 6: Endpointy autoryzacji
- Utworzyć `AuthService` z logiką rejestracji i logowania
- Utworzyć `AuthController` z endpointami:
  - `POST /api/register` - rejestracja użytkownika
  - `POST /api/login` - logowanie (zwraca JWT token)

### Krok 7: DTOs dla fiszek
- Utworzyć `CreateFlashcardRequest` z walidacją
- Utworzyć `UpdateFlashcardRequest` z walidacją
- Utworzyć `BulkCreateFlashcardsRequest` z walidacją
- Utworzyć `FlashcardResponse` DTO
- Utworzyć `PaginatedResponse` DTO

### Krok 8: Endpointy CRUD dla fiszek
- Utworzyć `FlashcardService` z logiką biznesową
- Utworzyć `FlashcardController` z endpointami:
  - `GET /api/flashcards` - lista fiszek (z paginacją)
  - `POST /api/flashcards` - utworzenie pojedynczej lub wielu fiszek
  - `GET /api/flashcards/{id}` - szczegóły fiszki
  - `PUT /api/flashcards/{id}` - aktualizacja fiszki
  - `DELETE /api/flashcards/{id}` - usunięcie fiszki

## Wymagane działania przed kontynuacją

1. **Zainstalować zależności:**
   ```bash
   composer install
   ```

2. **Wygenerować klucze JWT:**
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```
   To utworzy pliki `config/jwt/private.pem` i `config/jwt/public.pem`

3. **Dodać zmienne środowiskowe do `.env`:**
   ```env
   JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
   JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
   JWT_PASSPHRASE=your_passphrase_here
   ```

4. **Uruchomić migracje (jeśli jeszcze nie):**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

## Uwagi

- Błędy lintera są spodziewane - znikną po `composer install`
- Wszystkie encje używają lifecycle callbacks dla automatycznego ustawiania `created_at` i `updated_at`
- Repozytoria mają metody `save()` i `remove()` dla wygody
- `FlashcardRepository` ma metodę `findByUser()` z obsługą paginacji

