# Testing Guide

Przewodnik po wymaganiach dotyczących testów w projekcie 10xDevs.

## Spis treści

1. [Przegląd](#przegląd)
2. [Wymagania testów](#wymagania-testów)
3. [CI/CD - GitHub Actions](#cicd---github-actions)
4. [Pisanie testów](#pisanie-testów)
5. [Best Practices](#best-practices)
6. [Troubleshooting](#troubleshooting)

## Przegląd

Projekt powinien zawierać kompleksową suite testów.

### Wymagania dotyczące testów

- **Testy funkcjonalne**: Powinny testować pełny przepływ HTTP przez API
- **Testy jednostkowe**: Powinny testować poszczególne klasy w izolacji
- **Łączny coverage**: Powinien wynosić > 80%

### Technologie

- PHPUnit 10.5
- Symfony WebTestCase
- Doctrine Fixtures
- Mock Objects

## Wymagania testów

### 1. Przygotowanie środowiska

Projekt powinien wymagać:
- Zainstalowanych zależności Composer
- Wygenerowanych kluczy JWT
- Utworzonej bazy testowej
- Uruchomionych migracji

### 2. Uruchamianie testów

Projekt powinien zawierać skrypt `run-tests.sh` który:
- Sprawdza czy vendor/ istnieje
- Generuje klucze JWT jeśli nie istnieją
- Uruchamia wszystkie testy

### Rodzaje testów

#### 1. Testy funkcjonalne (Functional/)
Powinny testować pełny przepływ HTTP przez API:
- **AuthControllerTest** - rejestracja, logowanie, autoryzacja JWT
- **FlashcardControllerTest** - pełny CRUD fiszek, bezpieczeństwo, izolacja użytkowników

#### 2. Testy jednostkowe (Unit/)
Powinny testować poszczególne klasy w izolacji:
- **AuthServiceTest** - logika autoryzacji z mockami

## CI/CD - GitHub Actions

### Wymagania konfiguracji

Workflow powinien znajdować się w `.github/workflows/tests.yml`.

#### Workflow powinien uruchamiać się przy:
- Push do `main` lub `develop`
- Pull Request do `main` lub `develop`

#### Co powinien robić workflow:
1. ✅ Setup PHP 8.3 z rozszerzeniami
2. ✅ Uruchamia PostgreSQL 15 jako service (lub SQLite in-memory)
3. ✅ Instaluje zależności Composer
4. ✅ Generuje klucze JWT
5. ✅ Tworzy bazę testową i uruchamia migracje
6. ✅ Uruchamia wszystkie testy z coverage
7. ✅ Uploaduje coverage do Codecov
8. ✅ Archiwizuje logi i rezultaty

### Zmienne środowiskowe w CI

Workflow powinien automatycznie ustawiać:
```yaml
APP_ENV=test
DATABASE_URL=postgresql://testuser:testpass@localhost:5432/testdb_test
JWT_PASSPHRASE=testpassphrase
OPENAI_API_KEY=sk-test-mock-key
```

### Secrets w GitHub

Nie powinny być potrzebne żadne secrets dla testów, ponieważ:
- Baza danych jest tymczasowa (service container lub SQLite)
- JWT używa testowych kluczy generowanych on-the-fly
- OpenAI używa mock service

## Pisanie testów

### Testy funkcjonalne

Powinny używać `BaseWebTestCase` jako klasy bazowej:

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

Powinny używać PHPUnit TestCase i mockować zależności:

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

Powinny zawierać:
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
- `BaseWebTestCase` powinien automatycznie czyścić bazę w `setUp()`
- Nie polegać na kolejności wykonania testów
- Nie używać globalnego stanu

### 5. Używać fixtures tylko gdy potrzeba

```php
// ✅ Dobrze - twórz dane w teście
$user = $this->createUser();

// ❌ Źle - nie ładuj fixtures w testach funkcjonalnych
$this->loadFixtures([UserFixtures::class]);
```

### 6. Mockować zewnętrzne serwisy

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
- Sprawdź czy PostgreSQL działa
- Uruchom jeśli nie działa
- Utwórz bazę testową

### Problem: JWT token errors

**Objawy:**
```
Unable to load key from "config/jwt/private.pem"
```

**Rozwiązanie:**
- Wygeneruj klucze
- Upewnij się że JWT_PASSPHRASE jest ustawione

### Problem: Testy passują lokalnie ale failują w CI

**Możliwe przyczyny:**
1. Różne zmienne środowiskowe
2. Różna wersja PHP/PostgreSQL
3. Brakujące migracje
4. Race conditions

**Rozwiązanie:**
- Uruchom z tymi samymi zmiennymi co CI
- Sprawdź migracje
- Uruchom testy verbose

## Metryki jakości

### Docelowe wartości

- **Test Coverage**: > 80%
- **Czas wykonania**: < 2 minuty
- **Flaky tests**: 0%
- **Test to code ratio**: 1:1 (linie testów : linie kodu)

## Zasoby

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [Doctrine Fixtures](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html)
- [GitHub Actions for PHP](https://github.com/shivammathur/setup-php)
