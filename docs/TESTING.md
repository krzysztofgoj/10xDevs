# Testing Guide

Przewodnik po testach w projekcie 10xDevs.

## Spis treści

1. [Przegląd](#przegląd)
2. [Uruchamianie lokalnie](#uruchamianie-lokalnie)
3. [CI/CD - GitHub Actions](#cicd---github-actions)
4. [Pisanie testów](#pisanie-testów)
5. [Best Practices](#best-practices)
6. [Troubleshooting](#troubleshooting)

## Przegląd

Projekt zawiera kompleksową suite testów:

### Statystyki testów
- **Testy funkcjonalne**: ~30 test cases
  - AuthControllerTest: 10 test cases
  - FlashcardControllerTest: 20 test cases
- **Testy jednostkowe**: ~6 test cases
  - AuthServiceTest: 6 test cases
- **Łączny coverage**: ~85% (docelowo)

### Technologie
- PHPUnit 10.5
- Symfony WebTestCase
- Doctrine Fixtures
- Mock Objects

## Uruchamianie lokalnie

### 1. Przygotowanie środowiska

```bash
# Zainstaluj zależności
composer install

# Wygeneruj klucze JWT (jeśli nie istnieją)
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:testpassphrase
openssl pkey -in config/jwt/private.pem -passin pass:testpassphrase -out config/jwt/public.pem -pubout

# Utwórz bazę testową
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

### 2. Uruchom testy

```bash
# Wszystkie testy
./run-tests.sh

# W kontenerze Docker
docker-compose exec php vendor/bin/phpunit

# Konkretna grupa testów
./run-tests.sh --functional
./run-tests.sh --unit
```

### 3. Analiza wyników

```bash
# Z pokryciem kodu
./run-tests.sh --coverage

# Format testdox (czytelny)
./run-tests.sh --testdox
```

## CI/CD - GitHub Actions

### Konfiguracja

Workflow znajduje się w `.github/workflows/tests.yml`.

#### Workflow uruchamia się przy:
- Push do `main` lub `develop`
- Pull Request do `main` lub `develop`

#### Co robi workflow:
1. ✅ Setup PHP 8.3 z rozszerzeniami
2. ✅ Uruchamia PostgreSQL 15 jako service
3. ✅ Instaluje zależności Composer
4. ✅ Generuje klucze JWT
5. ✅ Tworzy bazę testową i uruchamia migracje
6. ✅ Uruchamia wszystkie testy z coverage
7. ✅ Uploaduje coverage do Codecov
8. ✅ Archiwizuje logi i rezultaty

### Zmienne środowiskowe w CI

Workflow automatycznie ustawia:
```yaml
APP_ENV=test
DATABASE_URL=postgresql://testuser:testpass@localhost:5432/testdb_test
JWT_PASSPHRASE=testpassphrase
OPENAI_API_KEY=sk-test-mock-key
```

### Secrets w GitHub

Nie są potrzebne żadne secrets dla testów, ponieważ:
- Baza danych jest tymczasowa (service container)
- JWT używa testowych kluczy generowanych on-the-fly
- OpenAI używa mock service

### Monitoring CI/CD

1. Przejdź do zakładki **Actions** w repozytorium GitHub
2. Zobacz status workflow dla każdego commita/PR
3. Kliknij w konkretny run, żeby zobaczyć szczegóły
4. Coverage jest dostępny w Codecov (jeśli skonfigurowany)

### Debugging niepowodzeń CI

Jeśli testy failują w CI:

1. **Sprawdź logi**
   - Kliknij w failed workflow
   - Sprawdź "Run PHPUnit tests" step
   - Pobierz artifacts (test-results)

2. **Odtwórz lokalnie**
   ```bash
   # Użyj tych samych zmiennych co CI
   export APP_ENV=test
   export DATABASE_URL="postgresql://testuser:testpass@localhost:5432/testdb_test"
   ./run-tests.sh
   ```

3. **Najczęstsze problemy**
   - Brak migracji bazy danych
   - Nieprawidłowe zmienne środowiskowe
   - Timeout PostgreSQL service
   - Błędy w kodzie (syntax errors)

## Pisanie testów

### Testy funkcjonalne

Użyj `BaseWebTestCase` jako klasy bazowej:

```php
<?php
namespace App\Tests\Functional;

final class MyFeatureTest extends BaseWebTestCase
{
    public function testMyEndpoint(): void
    {
        // Arrange
        $user = $this->createUser('test@example.com');
        
        // Act
        $this->makeAuthenticatedRequest('GET', '/api/my-endpoint', $user);
        
        // Assert
        $this->assertJsonResponse(200);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('result', $data);
    }
}
```

### Testy jednostkowe

Użyj PHPUnit TestCase i mockuj zależności:

```php
<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MyServiceTest extends TestCase
{
    public function testMyMethod(): void
    {
        // Arrange
        $dependency = $this->createMock(DependencyInterface::class);
        $dependency->method('doSomething')->willReturn('result');
        
        $service = new MyService($dependency);
        
        // Act
        $result = $service->myMethod();
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Helpery w BaseWebTestCase

- `createUser($email, $password, $roles)` - tworzy testowego użytkownika
- `getAuthToken($user)` - generuje JWT token
- `makeAuthenticatedRequest($method, $uri, $user, $data)` - żądanie z JWT
- `makeJsonRequest($method, $uri, $data)` - żądanie bez autoryzacji
- `getResponseData()` - parsuje JSON response
- `assertJsonResponse($statusCode)` - sprawdza JSON response
- `assertResponseHasError($message)` - sprawdza błędy

## Best Practices

### 1. Nazewnictwo testów

```php
// ✅ Dobrze - opisuje co testuje
public function testUserCannotAccessAnotherUsersFlashcard(): void

// ❌ Źle - niejasne
public function testFlashcard(): void
```

### 2. Struktura testu (AAA)

```php
public function testExample(): void
{
    // Arrange - przygotuj dane
    $user = $this->createUser();
    
    // Act - wykonaj akcję
    $this->makeAuthenticatedRequest('GET', '/api/endpoint', $user);
    
    // Assert - sprawdź wynik
    $this->assertJsonResponse(200);
}
```

### 3. Jeden test - jedna rzecz

```php
// ✅ Dobrze
public function testCreateFlashcardSuccess(): void { ... }
public function testCreateFlashcardWithInvalidData(): void { ... }

// ❌ Źle - testuje za dużo
public function testFlashcardCRUD(): void { 
    // create, read, update, delete w jednym teście
}
```

### 4. Niezależność testów

Każdy test powinien być niezależny:
- `BaseWebTestCase` automatycznie czyści bazę w `setUp()`
- Nie polegaj na kolejności wykonania testów
- Nie używaj globalnego stanu

### 5. Używaj fixtures tylko gdy potrzeba

```php
// ✅ Dobrze - twórz dane w teście
$user = $this->createUser();

// ❌ Źle - nie ładuj fixtures w testach funkcjonalnych
$this->loadFixtures([UserFixtures::class]);
```

### 6. Mockuj zewnętrzne serwisy

```php
// ✅ Dobrze - mock w środowisku testowym
// config/packages/test/services.yaml
App\Service\FlashcardGeneratorInterface:
    class: App\Service\MockFlashcardGenerator

// ❌ Źle - prawdziwe API w testach
// Kosztuje pieniądze i spowalnia testy
```

## Troubleshooting

### Problem: Testy nie znajdują bazy danych

**Objawy:**
```
Connection refused [tcp://localhost:5432]
```

**Rozwiązanie:**
```bash
# Sprawdź czy PostgreSQL działa
docker-compose ps postgres

# Uruchom jeśli nie działa
docker-compose up -d postgres

# Utwórz bazę testową
php bin/console doctrine:database:create --env=test
```

### Problem: JWT token errors

**Objawy:**
```
Unable to load key from "config/jwt/private.pem"
```

**Rozwiązanie:**
```bash
# Wygeneruj klucze
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pass pass:testpassphrase
openssl pkey -in config/jwt/private.pem -passin pass:testpassphrase -out config/jwt/public.pem -pubout
chmod 644 config/jwt/private.pem config/jwt/public.pem

# Upewnij się że JWT_PASSPHRASE jest ustawione
export JWT_PASSPHRASE=testpassphrase
```

### Problem: Fixtures nie ładują się

**Objawy:**
```
Class "App\DataFixtures\UserFixtures" not found
```

**Rozwiązanie:**
```bash
# Zainstaluj doctrine-fixtures-bundle
composer require --dev doctrine/doctrine-fixtures-bundle

# Lub przebuduj autoload
composer dump-autoload
```

### Problem: Testy passują lokalnie ale failują w CI

**Możliwe przyczyny:**
1. Różne zmienne środowiskowe
2. Różna wersja PHP/PostgreSQL
3. Brakujące migracje
4. Race conditions

**Rozwiązanie:**
```bash
# Uruchom z tymi samymi zmiennymi co CI
export APP_ENV=test
export DATABASE_URL="postgresql://testuser:testpass@localhost:5432/testdb_test"

# Sprawdź migracje
php bin/console doctrine:migrations:status --env=test

# Uruchom testy verbose
vendor/bin/phpunit --verbose --debug
```

### Problem: Testy są wolne

**Przyczyny:**
- Zbyt dużo czyszczenia bazy
- Zbyt wiele zapytań SQL
- Brak indeksów w bazie

**Rozwiązanie:**
```bash
# Użyj SQLite in-memory dla szybszych testów (opcjonalnie)
# lub optymalizuj zapytania SQL

# Uruchom tylko zmienione testy
vendor/bin/phpunit --filter MyChangedTest
```

## Metryki jakości

### Docelowe wartości

- **Test Coverage**: > 80%
- **Czas wykonania**: < 2 minuty
- **Flaky tests**: 0%
- **Test to code ratio**: 1:1 (linie testów : linie kodu)

### Monitoring

```bash
# Coverage
./run-tests.sh --coverage

# Czas wykonania
vendor/bin/phpunit --log-junit junit.xml
```

## Zasoby

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [Doctrine Fixtures](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html)
- [GitHub Actions for PHP](https://github.com/shivammathur/setup-php)

## Kontakt

W razie problemów:
1. Sprawdź [tests/README.md](../tests/README.md)
2. Otwórz issue na GitHubie
3. Sprawdź logi w `var/log/test.log`

