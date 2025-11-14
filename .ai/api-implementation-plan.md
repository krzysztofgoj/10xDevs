# Plan implementacji REST API - 10x-cards

## Decyzje architektoniczne

Projekt powinien używać:
- ✅ **Kontrolery**: Zwykłe kontrolery Symfony z atrybutami `#[Route]`
- ✅ **Serializacja**: Symfony Serializer Component
- ✅ **Autoryzacja**: JWT tokens (lexik/jwt-authentication-bundle)
- ✅ **DTOs**: Struktura `src/Request/` i `src/Response/`
- ✅ **Format**: Standardowy JSON
- ✅ **Obsługa błędów**: Symfony Exception Listener
- ✅ **Prefix API**: `/api/`

## Struktura projektu

```
src/
  Controller/
    Api/
      AuthController.php          # POST /api/login, POST /api/register
      FlashcardController.php     # CRUD dla fiszek
      FlashcardGenerationController.php  # Generowanie fiszek przez AI
  Entity/
    User.php
    Flashcard.php
    FlashcardGeneration.php
    RepetitionRecord.php
  Repository/
    UserRepository.php
    FlashcardRepository.php
    FlashcardGenerationRepository.php
    RepetitionRecordRepository.php
  Request/
    LoginRequest.php
    RegisterRequest.php
    CreateFlashcardRequest.php
    UpdateFlashcardRequest.php
    BulkCreateFlashcardsRequest.php
    GenerateFlashcardsRequest.php
  Response/
    AuthResponse.php
    FlashcardResponse.php
    FlashcardGenerationResponse.php
    ErrorResponse.php
    PaginatedResponse.php
  Service/
    AuthService.php
    FlashcardService.php
    FlashcardGenerationService.php
    LLMServiceInterface.php
    LLMService.php (implementacja)
  EventListener/
    ApiExceptionListener.php
```

## Endpointy API

### Autoryzacja
- `POST /api/register` - Rejestracja użytkownika
- `POST /api/login` - Logowanie (zwraca JWT token)

### Fiszki
- `GET /api/flashcards` - Lista fiszek użytkownika (z paginacją)
- `POST /api/flashcards` - Utworzenie pojedynczej lub wielu fiszek
- `GET /api/flashcards/{id}` - Szczegóły fiszki
- `PUT /api/flashcards/{id}` - Aktualizacja fiszki
- `DELETE /api/flashcards/{id}` - Usunięcie fiszki

### Generowanie przez AI
- `POST /api/generations` - Rozpoczęcie generowania fiszek z tekstu
- `GET /api/generations/{id}` - Status generowania

## Workflow implementacji

### Krok 1: Dodanie zależności i konfiguracja podstawowa
- [ ] Dodać `lexik/jwt-authentication-bundle` do composer.json
- [ ] Sprawdzić czy Symfony Serializer jest dostępny (część framework-bundle)
- [ ] Skonfigurować JWT w `config/packages/lexik_jwt_authentication.yaml`
- [ ] Zaktualizować `security.yaml` z firewall dla API (JWT)

### Krok 2: Utworzenie struktury katalogów i podstawowych klas
- [ ] Utworzyć katalogi: `src/Controller/Api/`, `src/Request/`, `src/Response/`, `src/Service/`
- [ ] Utworzyć podstawowy `ErrorResponse` DTO
- [ ] Utworzyć `ApiExceptionListener` dla obsługi błędów
- [ ] Skonfigurować Exception Listener w `services.yaml`

### Krok 3: Utworzenie encji Doctrine
- [ ] Utworzyć encję `User` (implementującą UserInterface)
- [ ] Utworzyć encję `Flashcard`
- [ ] Utworzyć encję `FlashcardGeneration`
- [ ] Utworzyć encję `RepetitionRecord`
- [ ] Utworzyć repozytoria dla wszystkich encji

## Następne kroki

### Krok 4: Endpointy autoryzacji
- [ ] Utworzyć `LoginRequest` i `RegisterRequest` DTOs
- [ ] Utworzyć `AuthResponse` DTO
- [ ] Utworzyć `AuthService` z logiką rejestracji i logowania
- [ ] Utworzyć `AuthController` z endpointami POST /api/login i POST /api/register

### Krok 5: Podstawowe DTOs dla fiszek
- [ ] Utworzyć `CreateFlashcardRequest` z walidacją
- [ ] Utworzyć `UpdateFlashcardRequest` z walidacją
- [ ] Utworzyć `BulkCreateFlashcardsRequest` z walidacją
- [ ] Utworzyć `FlashcardResponse` DTO
- [ ] Utworzyć `PaginatedResponse` DTO

### Krok 6: Endpointy CRUD dla fiszek
- [ ] Utworzyć `FlashcardService` z logiką biznesową
- [ ] Utworzyć `FlashcardController` z endpointami CRUD
- [ ] Dodać paginację do GET /api/flashcards
- [ ] Dodać autoryzację (tylko własne fiszki)

## Szczegóły implementacji

### JWT Configuration

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600
```

### Security Configuration (API firewall)

```yaml
# config/packages/security.yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
        main:
            # ... existing config
```

### Exception Listener

```php
// src/EventListener/ApiExceptionListener.php
// Automatyczna konwersja wyjątków na JSON responses
```

### Request DTOs z walidacją

Wszystkie Request DTOs powinny używać atrybutów Symfony Validator:
- `#[Assert\NotBlank]`
- `#[Assert\Length]`
- `#[Assert\Email]`
- Custom constraints jeśli potrzeba

### Response DTOs z serializacją

Wszystkie Response DTOs powinny używać grup serializacji:
- `#[Groups(['api'])]` dla pól do serializacji
- Symfony Serializer do konwersji na JSON

## Testowanie

### Automatyczne testy
- PHPUnit functional tests dla każdego endpointa
- Testy walidacji
- Testy autoryzacji (JWT)
- Testy izolacji danych (użytkownik widzi tylko swoje dane)

### Manualne testy
- curl commands dla każdego endpointa
- Dokumentacja w README.md

## Dokumentacja

Po implementacji należy utworzyć:
- `docs/API.md` - Dokumentacja wszystkich endpointów
- Przykłady curl dla każdego endpointa
- Opis formatów request/response
