# Wymagania implementacji REST API - 10x-cards

## Struktura projektu

Projekt powinien zawierać następującą strukturę:

```
src/
  Controller/
    Api/              # Kontrolery API
  Entity/
    User.php          # Encja użytkownika
    Flashcard.php     # Encja fiszki
    FlashcardGeneration.php  # Encja sesji generowania
    RepetitionRecord.php     # Encja rekordu powtórki
  Repository/
    UserRepository.php              # Repozytorium użytkowników
    FlashcardRepository.php         # Repozytorium fiszek
    FlashcardGenerationRepository.php  # Repozytorium generowań
    RepetitionRecordRepository.php     # Repozytorium rekordów powtórek
  Request/
    LoginRequest.php      # DTO dla logowania
    RegisterRequest.php   # DTO dla rejestracji
  Response/
    ErrorResponse.php     # DTO dla błędów
    AuthResponse.php      # DTO dla odpowiedzi autoryzacji
  Service/               # Serwisy z logiką biznesową
  EventListener/
    ApiExceptionListener.php  # Listener obsługi błędów API
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
- `ApiExceptionListener` powinien automatycznie konwertować wyjątki na JSON responses
- Powinien obsługiwać `HttpExceptionInterface` i `ValidationFailedException`
- Powinien działać tylko dla żądań zaczynających się od `/api`

## Wymagane działania przed kontynuacją

1. **Zainstalować zależności:**
   ```bash
   composer install
   ```

2. **Wygenerować klucze JWT:**
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```
   To powinno utworzyć pliki `config/jwt/private.pem` i `config/jwt/public.pem`

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

- Wszystkie encje powinny używać lifecycle callbacks dla automatycznego ustawiania `created_at` i `updated_at`
- Repozytoria powinny mieć metody `save()` i `remove()` dla wygody
- `FlashcardRepository` powinien mieć metodę `findByUser()` z obsługą paginacji
