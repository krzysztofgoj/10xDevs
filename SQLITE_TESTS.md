# ✅ Rozwiązanie problemu z PostgreSQL - SQLite dla testów!

## Problem

Napotkałeś błąd PostgreSQL collation:
```
SQLSTATE[XX000]: Internal error: 7 ERROR: template database "template1" has a collation version
```

## Rozwiązanie - SQLite in-memory dla testów

Testy powinny używać **SQLite in-memory** zamiast PostgreSQL. To jest **lepsze rozwiązanie** ponieważ:

✅ **Brak konfiguracji** - nie trzeba setupować bazy danych  
✅ **Szybsze** - baza w pamięci RAM jest znacznie szybsza  
✅ **Izolacja** - każdy test ma swoją własną bazę  
✅ **Proste** - działa od razu bez problemów z collation  
✅ **CI-friendly** - nie wymaga service containers w GitHub Actions  

## Co powinno być zmienione?

### 1. Dockerfile - dodano pdo_sqlite

```dockerfile
pdo_sqlite  # Rozszerzenie dla SQLite
```

### 2. Konfiguracja test/doctrine.yaml

```yaml
# config/packages/test/doctrine.yaml
doctrine:
    dbal:
        url: 'sqlite:///:memory:'
        driver: 'pdo_sqlite'
```

### 3. BaseWebTestCase - wspiera SQLite

Powinien automatycznie wykrywać SQLite i tworzyć schemat zamiast truncate.

### 4. GitHub Actions - uproszczony

Nie powinien potrzebować PostgreSQL service container!

## Jak uruchomić testy?

### Krok 1: Przebuduj kontener Docker (ważne!)

```bash
# Wyjdź z kontenera jeśli jesteś w środku
exit

# Przebuduj kontener z nowym pdo_sqlite
docker-compose down
docker-compose up -d --build

# Poczekaj aż kontener się uruchomi
docker-compose logs -f php
```

### Krok 2: Zainstaluj zależności (jeśli jeszcze nie)

```bash
docker-compose exec php composer install
```

### Krok 3: Uruchom testy!

```bash
# Skrypt automatyczny
./run-tests.sh

# Lub bezpośrednio w kontenerze
docker-compose exec php vendor/bin/phpunit

# Lub wejdź do kontenera i uruchom
docker exec -it php-app bash
vendor/bin/phpunit
```

## Weryfikacja

Sprawdź czy SQLite działa:

```bash
docker-compose exec php php -m | grep -i sqlite
# Powinno pokazać: pdo_sqlite, sqlite3
```

## Czy mogę nadal używać PostgreSQL dla aplikacji?

**TAK!** To ustawienie dotyczy TYLKO testów (środowisko `APP_ENV=test`).

- **Rozwój** (`dev`): nadal używa PostgreSQL z docker-compose
- **Produkcja** (`prod`): nadal używa PostgreSQL
- **Testy** (`test`): powinien używać SQLite in-memory

Konfiguracja jest w `config/packages/test/doctrine.yaml` - wpływa tylko na testy.

## Porównanie

| Feature | PostgreSQL | SQLite |
|---------|-----------|--------|
| Setup | ❌ Wymaga konfiguracji | ✅ Zero setup |
| Szybkość | ⚠️ Wolniejsze (sieć) | ✅ Bardzo szybkie (RAM) |
| CI/CD | ❌ Service container | ✅ Nie potrzebuje |
| Izolacja | ⚠️ Trzeba czyścić | ✅ Automatyczna |
| Collation | ❌ Może być problem | ✅ Brak problemów |

## Testy w CI/CD

GitHub Actions workflow powinien być zaktualizowany - teraz:

- ✅ Nie wymaga PostgreSQL service
- ✅ Szybsze (brak czekania na database ready)
- ✅ Prostsze (mniej kroków setup)
- ✅ Tańsze (mniej zasobów)

## Migracja istniejących testów

Jeśli masz własne testy, nic nie musisz zmieniać:

```php
// Działa automatycznie z SQLite i PostgreSQL
public function testSomething(): void
{
    $user = $this->createUser();
    // ... reszta testu
}
```

`BaseWebTestCase` powinien automatycznie wykrywać bazę danych i dostosowywać się.

## FAQ

**Q: Czy testy z SQLite są wiarygodne?**  
A: TAK! Większość różnic między PostgreSQL a SQLite nie wpływa na logikę aplikacji. Testujemy API i logikę biznesową, nie specyficzne funkcje bazy danych.

**Q: A co z funkcjami specyficznymi dla PostgreSQL?**  
A: Jeśli używasz specyficznych funkcji PostgreSQL (np. JSONB, array aggregates), możesz stworzyć osobną grupę testów która używa PostgreSQL.

**Q: Czy mogę przełączyć się z powrotem na PostgreSQL?**  
A: TAK! Usuń `config/packages/test/doctrine.yaml` i przywróć standardowe `DATABASE_URL` dla środowiska test.

**Q: Czy to wpłynie na produkcję?**  
A: NIE! To dotyczy tylko środowiska testowego (`APP_ENV=test`).

## Sukces!

Jeśli widzisz:

```bash
PHPUnit 10.5.x by Sebastian Bergmann and contributors.

Runtime:       PHP 8.3.x
Configuration: /var/www/html/phpunit.dist.xml

.....................                                   21 / 21 (100%)

Time: 00:02.123, Memory: 24.00 MB

OK (36 tests, 150 assertions)
```

**Gratulacje! Testy działają z SQLite! 🎉**

## Następne kroki

1. ✅ Uruchom testy lokalnie: `./run-tests.sh`
2. ✅ Push do GitHub - CI/CD powinien uruchomić testy automatycznie
3. ✅ Wszystko powinno działać bez problemów!

---

**Teraz możesz spokojnie rozwijać projekt bez problemów z PostgreSQL collation!** 🚀
