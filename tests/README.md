# Testy projektu 10xDevs

Ten folder zawiera testy automatyczne dla projektu 10xDevs - aplikacji do nauki z użyciem fiszek.

## Struktura testów

```
tests/
├── Functional/           # Testy funkcjonalne (integracyjne) - testują API przez HTTP
│   ├── BaseWebTestCase.php     # Bazowa klasa z helperami dla testów funkcjonalnych
│   ├── AuthControllerTest.php  # Testy rejestracji i logowania
│   └── FlashcardControllerTest.php  # Testy CRUD fiszek
├── Unit/                 # Testy jednostkowe - testują poszczególne klasy w izolacji
│   └── AuthServiceTest.php     # Testy serwisu autoryzacji
└── bootstrap.php         # Bootstrap dla PHPUnit
```

## Wymagania

- PHP 8.3+
- PostgreSQL 15+
- Composer
- Zainstalowane rozszerzenia PHP: pdo_pgsql, mbstring, intl, xml, zip

## Instalacja zależności testowych

```bash
composer install
```

Zainstalowane zostaną pakiety:
- `phpunit/phpunit` - framework do testów
- `symfony/browser-kit` - do symulacji żądań HTTP
- `symfony/css-selector` - do parsowania HTML w testach
- `doctrine/doctrine-fixtures-bundle` - do ładowania danych testowych

## Konfiguracja środowiska testowego

### 1. Baza danych

Testy używają osobnej bazy danych `testdb_test`. Upewnij się, że masz dostęp do PostgreSQL:

```bash
# W dockerze (jeśli używasz docker-compose)
docker-compose up -d postgres

# Stwórz bazę testową
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

### 2. Zmienne środowiskowe

Musisz ustawić zmienne środowiskowe dla testów. Możesz to zrobić na dwa sposoby:

**Opcja A: Plik .env.test.local (lokalnie)**

Utwórz plik `.env.test.local` w głównym katalogu projektu:

```env
DATABASE_URL="postgresql://testuser:testpass@postgres:5432/testdb_test?serverVersion=15&charset=utf8"
APP_SECRET='$ecretf0rt3st'
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=9f094eace947ed0eb1ca2dbfc37deaa1a578bb957d7a52d2db53b0274981fd67
OPENAI_API_KEY=sk-test-mock-key
```

**Opcja B: Zmienne systemowe**

```bash
export DATABASE_URL="postgresql://testuser:testpass@localhost:5432/testdb_test?serverVersion=15&charset=utf8"
export APP_SECRET='$ecretf0rt3st'
export APP_ENV=test
```

### 3. Klucze JWT

Upewnij się, że klucze JWT są wygenerowane:

```bash
# Jeśli jeszcze nie istnieją
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

## Uruchamianie testów

### Wszystkie testy

```bash
vendor/bin/phpunit
```

### Tylko testy funkcjonalne

```bash
vendor/bin/phpunit tests/Functional
```

### Tylko testy jednostkowe

```bash
vendor/bin/phpunit tests/Unit
```

### Konkretna klasa testowa

```bash
vendor/bin/phpunit tests/Functional/AuthControllerTest.php
```

### Konkretna metoda testowa

```bash
vendor/bin/phpunit --filter testRegisterSuccess tests/Functional/AuthControllerTest.php
```

### Z pokryciem kodu (coverage)

```bash
# Wymaga Xdebug lub PCOV
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage/
```

Raport HTML będzie w katalogu `coverage/`.

## Opis testów

### Testy funkcjonalne (Functional/)

Testują pełny przepływ żądań HTTP przez API:

#### AuthControllerTest
- ✅ Rejestracja nowego użytkownika
- ✅ Rejestracja z istniejącym emailem (konflikt)
- ✅ Rejestracja z nieprawidłowymi danymi
- ✅ Logowanie z poprawnymi danymi
- ✅ Logowanie z nieprawidłowym hasłem
- ✅ Logowanie nieistniejącego użytkownika

#### FlashcardControllerTest
- ✅ Tworzenie fiszek (bulk create)
- ✅ Pobieranie listy fiszek
- ✅ Pobieranie pojedynczej fiszki
- ✅ Aktualizacja fiszki (PUT/PATCH)
- ✅ Usuwanie fiszki
- ✅ Testy bezpieczeństwa (izolacja użytkowników)
- ✅ Testy autoryzacji (wymagane JWT)

### Testy jednostkowe (Unit/)

Testują poszczególne klasy w izolacji z mockami:

#### AuthServiceTest
- ✅ Rejestracja użytkownika
- ✅ Rejestracja z istniejącym emailem
- ✅ Logowanie użytkownika
- ✅ Logowanie z nieprawidłowymi danymi
- ✅ Generowanie tokenu JWT

## Fixtures (dane testowe)

Projekt zawiera fixtures w `src/DataFixtures/`:

- `UserFixtures` - tworzy testowych użytkowników
- `FlashcardFixtures` - tworzy testowe fiszki

Można je załadować ręcznie:

```bash
php bin/console doctrine:fixtures:load --env=test
```

**Uwaga:** Testy funkcjonalne automatycznie czyszczą bazę przed każdym testem, więc fixtures nie są potrzebne w testach.

## Konfiguracja dla środowiska testowego

### config/packages/test/services.yaml

W środowisku testowym używamy `MockFlashcardGenerator` zamiast prawdziwego API OpenAI, żeby:
- Nie generować kosztów API
- Testy były szybkie i deterministyczne
- Nie wymagać prawdziwego klucza API

## CI/CD - GitHub Actions

Projekt zawiera workflow `.github/workflows/tests.yml`, który automatycznie:

1. ✅ Uruchamia testy na PHP 8.3
2. ✅ Używa PostgreSQL 15 jako bazy danych
3. ✅ Generuje klucze JWT
4. ✅ Uruchamia wszystkie testy
5. ✅ Generuje raport coverage
6. ✅ Sprawdza jakość kodu

Workflow uruchamia się automatycznie przy:
- Push na branch `main` lub `develop`
- Pull Request do `main` lub `develop`

## Debugowanie testów

### Logowanie w testach

Logi testów są zapisywane w `var/log/test.log`.

### Verbose output

```bash
vendor/bin/phpunit --verbose
vendor/bin/phpunit --debug
```

### Stop on failure

```bash
vendor/bin/phpunit --stop-on-failure
```

### Testdox - czytelny format

```bash
vendor/bin/phpunit --testdox
```

Przykładowy output:
```
AuthController (App\Tests\Functional\AuthController)
 ✔ Register success
 ✔ Register with existing email
 ✔ Login success
 ✔ Login with invalid password
```

## Problemy i rozwiązania

### Problem: Baza danych nie jest czyszczona między testami

**Rozwiązanie:** `BaseWebTestCase` automatycznie czyści bazę w metodzie `setUp()`. Upewnij się, że Twoja klasa testowa dziedziczy po `BaseWebTestCase`.

### Problem: JWT token jest nieprawidłowy

**Rozwiązanie:** 
1. Sprawdź czy klucze JWT są wygenerowane
2. Sprawdź `JWT_PASSPHRASE` w zmiennych środowiskowych
3. Sprawdź czy klucze mają odpowiednie uprawnienia (644)

### Problem: Connection refused do PostgreSQL

**Rozwiązanie:**
1. Upewnij się, że PostgreSQL działa: `docker-compose ps`
2. Sprawdź `DATABASE_URL` w zmiennych środowiskowych
3. Dla dockera użyj `postgres` jako hosta, dla lokalnego `localhost`

### Problem: Class not found

**Rozwiązanie:**
```bash
composer dump-autoload
```

## Best Practices

1. **Jeden assert na test** - każdy test powinien testować jedną rzecz
2. **Czyszczenie danych** - testy powinny być niezależne od siebie
3. **Nazewnictwo** - nazwy testów powinny opisywać co testują: `testRegisterWithInvalidEmail`
4. **Arrange-Act-Assert** - struktura testu:
   ```php
   // Arrange - przygotowanie danych
   $user = $this->createUser();
   
   // Act - wykonanie akcji
   $this->makeAuthenticatedRequest('GET', '/api/flashcards', $user);
   
   // Assert - sprawdzenie rezultatu
   $this->assertJsonResponse(200);
   ```
5. **Mock zewnętrzne serwisy** - nie wywołuj prawdziwych API (OpenAI, etc.)

## Dodawanie nowych testów

1. Dla testów API - dziedzicz po `BaseWebTestCase`
2. Dla testów jednostkowych - dziedzicz po `PHPUnit\Framework\TestCase`
3. Umieść test w odpowiednim katalogu (`Functional/` lub `Unit/`)
4. Nazwa klasy: `*Test.php`
5. Nazwa metody: `test*` lub użyj adnotacji `@test`

Przykład:

```php
<?php
namespace App\Tests\Functional;

final class MyNewFeatureTest extends BaseWebTestCase
{
    public function testSomething(): void
    {
        // Arrange
        $user = $this->createUser();
        
        // Act
        $this->makeAuthenticatedRequest('GET', '/api/something', $user);
        
        // Assert
        $this->assertJsonResponse(200);
    }
}
```

## Kontakt

Jeśli masz pytania lub problemy z testami, otwórz issue na GitHubie.

