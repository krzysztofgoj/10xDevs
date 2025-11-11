# ✅ Testy gotowe do użycia!

## 🎉 Status: WSZYSTKIE 34 TESTY PRZECHODZĄ!

```
Tests: 34, Assertions: 144, Deprecations: 1
✅ 100% success rate!
```

## 🚀 Jak uruchomić testy?

### Opcja 1: Skrypt automatyczny (ZALECANE)

```bash
# Z głównego katalogu projektu
./run-tests.sh
```

Skrypt automatycznie:
- ✅ Sprawdzi czy vendor/ istnieje (jeśli nie, uruchomi `composer install`)
- ✅ Wygeneruje klucze JWT jeśli nie istnieją (z passphrase: `testpassphrase`)
- ✅ Uruchomi wszystkie testy

### Opcja 2: Ręcznie w kontenerze

```bash
# Wygeneruj klucze JWT (jednorazowo)
docker exec php-app bash -c "mkdir -p config/jwt && \
  openssl genpkey -out config/jwt/private.pem -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -aes256 -pass pass:testpassphrase && \
  openssl pkey -in config/jwt/private.pem -passin pass:testpassphrase -out config/jwt/public.pem -pubout && \
  chmod 644 config/jwt/*.pem"

# Uruchom testy
docker exec php-app php vendor/bin/phpunit
```

## 📊 Rodzaje testów

### ✅ Auth Controller (11 testów)
- Rejestracja z walidacją (6 testów)
- Logowanie z obsługą błędów (5 testów)

### ✅ Flashcard Controller (17 testów)
- CRUD operations (Create, Read, Update, Delete)
- Bezpieczeństwo (izolacja użytkowników, autoryzacja)
- Walidacja danych

### ✅ Auth Service Unit (6 testów)
- Testy jednostkowe z mockami
- Logika autoryzacji

## 🔑 Ważne informacje o JWT

### Klucze testowe
- **Lokalizacja**: `config/jwt/private.pem` i `config/jwt/public.pem`
- **Passphrase**: `testpassphrase`
- **Algorytm**: RSA 4096-bit

### Konfiguracja w `config/packages/test/lexik_jwt_authentication.yaml`
```yaml
lexik_jwt_authentication:
    secret_key: '/var/www/html/config/jwt/private.pem'
    public_key: '/var/www/html/config/jwt/public.pem'
    pass_phrase: 'testpassphrase'
```

⚠️ **UWAGA**: Klucze JWT są ignorowane przez `.gitignore` i muszą być wygenerowane lokalnie!

## 💾 Baza danych w testach

**SQLite in-memory** - skonfigurowane w `config/packages/test/doctrine.yaml`

Zalety:
- ✅ Zero setupu (brak potrzeby tworzenia bazy)
- ✅ Szybkie (baza w RAM)
- ✅ Izolacja (każdy test ma czystą bazę)
- ✅ Brak problemów z PostgreSQL collation

## 🤖 Mock OpenAI

W testach używamy `MockFlashcardGenerator` zamiast prawdziwego API:
- Skonfigurowane w: `config/packages/test/services.yaml` i `config/services.yaml` (when@test)
- ✅ Zero kosztów API
- ✅ Deterministyczne wyniki
- ✅ Szybkie wykonanie

## 🎯 Przykładowe komendy

```bash
# Wszystkie testy
./run-tests.sh

# Tylko funkcjonalne
./run-tests.sh --functional

# Tylko jednostkowe
./run-tests.sh --unit

# Z pokryciem kodu
./run-tests.sh --coverage

# Czytelny format
./run-tests.sh --testdox

# Konkretna klasa
docker exec php-app php vendor/bin/phpunit tests/Functional/AuthControllerTest.php

# Stop przy pierwszym błędzie
docker exec php-app php vendor/bin/phpunit --stop-on-failure
```

## 🚢 GitHub Actions CI/CD

Workflow znajduje się w `.github/workflows/tests.yml`

### Co robi:
1. ✅ Setup PHP 8.3 z rozszerzeniami (pdo_sqlite, intl, mbstring, etc.)
2. ✅ Instaluje zależności Composer
3. ✅ Generuje klucze JWT
4. ✅ Uruchamia wszystkie testy
5. ✅ Generuje raport coverage
6. ✅ Uploaduje coverage do Codecov

### Uruchamia się automatycznie przy:
- Push do `main` lub `develop`
- Pull Request do `main` lub `develop`

## 🐛 Troubleshooting

### Problem: JWT encode error

**Objaw:**
```
JWTEncodeFailureException: An error occurred while trying to encode the JWT token
```

**Rozwiązanie:**
```bash
# Usuń stare klucze i wygeneruj nowe
rm -f config/jwt/*.pem
./run-tests.sh
```

### Problem: Brak vendor/

**Rozwiązanie:**
```bash
docker exec php-app composer install
```

### Problem: SQLite not found

**Rozwiązanie:**
```bash
# Przebuduj kontener z pdo_sqlite
docker-compose down
docker-compose up -d --build
```

## 📚 Dokumentacja

- **tests/README.md** - szczegółowa dokumentacja testów
- **docs/TESTING.md** - przewodnik testowania
- **SQLITE_TESTS.md** - info o SQLite w testach
- **TEST_COMMANDS.md** - cheat sheet komend

## ✨ Co zostało zaimplementowane?

### Testy
- ✅ 11 testów Auth API (register, login)
- ✅ 17 testów Flashcard CRUD API
- ✅ 6 testów Auth Service (unit)
- ✅ BaseWebTestCase z helperami
- ✅ Fixtures dla danych testowych

### Infrastruktura
- ✅ SQLite in-memory dla testów
- ✅ Mock OpenAI service
- ✅ Automatyczne czyszczenie bazy
- ✅ Konfiguracja JWT dla testów

### CI/CD
- ✅ GitHub Actions workflow
- ✅ Automatyczne uruchamianie testów
- ✅ Coverage reporting
- ✅ Code quality checks

### Naprawione bugi
- ✅ Walidacja password_confirm w RegisterRequest
- ✅ Proper 401/403 error handling w ApiExceptionListener
- ✅ AccessDeniedException → 401 (no token) lub 403 (no permission)

## 🎊 Gotowe!

Testy są w pełni funkcjonalne i gotowe do użycia. Po prostu uruchom:

```bash
./run-tests.sh
```

I wszystko powinno działać! 🚀

---

**Pytania?** Sprawdź dokumentację w `tests/README.md` lub `docs/TESTING.md`

