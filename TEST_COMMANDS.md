# Test Commands - Quick Reference

Szybki przewodnik po komendach testowych.

## 🚀 Podstawowe komendy

```bash
# Wszystkie testy
./run-tests.sh
docker-compose exec php vendor/bin/phpunit

# Testy funkcjonalne
./run-tests.sh --functional
vendor/bin/phpunit tests/Functional

# Testy jednostkowe
./run-tests.sh --unit
vendor/bin/phpunit tests/Unit

# Konkretny plik
vendor/bin/phpunit tests/Functional/AuthControllerTest.php

# Konkretna metoda
vendor/bin/phpunit --filter testRegisterSuccess
```

## 📊 Coverage i raporty

```bash
# Coverage HTML
./run-tests.sh --coverage
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage/

# Coverage text (terminal)
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text

# Testdox (czytelny format)
./run-tests.sh --testdox
vendor/bin/phpunit --testdox

# Kolory w terminalu
vendor/bin/phpunit --colors=always
```

## 🐛 Debugging

```bash
# Verbose output
vendor/bin/phpunit --verbose

# Debug output
vendor/bin/phpunit --debug

# Stop przy pierwszym błędzie
vendor/bin/phpunit --stop-on-failure

# Stop przy pierwszym błędzie lub riskach
vendor/bin/phpunit --stop-on-error

# Display PHPUnit version
vendor/bin/phpunit --version
```

## 🗄️ Zarządzanie bazą

```bash
# Utwórz bazę testową
php bin/console doctrine:database:create --env=test

# Usuń bazę testową
php bin/console doctrine:database:drop --env=test --force

# Uruchom migracje
php bin/console doctrine:migrations:migrate --env=test --no-interaction

# Status migracji
php bin/console doctrine:migrations:status --env=test

# Załaduj fixtures
php bin/console doctrine:fixtures:load --env=test
```

## 🐳 Docker

```bash
# Uruchom testy w kontenerze
docker-compose exec php vendor/bin/phpunit

# Bash w kontenerze PHP
docker-compose exec php bash

# Logi kontenera
docker-compose logs php

# Restart kontenera
docker-compose restart php
```

## 📦 Composer

```bash
# Zainstaluj zależności testowe
composer install

# Zaktualizuj zależności
composer update

# Tylko dev dependencies
composer install --dev

# Przebuduj autoload
composer dump-autoload

# Sprawdź outdated packages
composer outdated
```

## 🔑 JWT

```bash
# Wygeneruj klucze JWT
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:testpassphrase
openssl pkey -in config/jwt/private.pem -passin pass:testpassphrase -out config/jwt/public.pem -pubout
chmod 644 config/jwt/*.pem

# Sprawdź czy klucze istnieją
ls -la config/jwt/

# Usuń klucze (jeśli chcesz je wygenerować ponownie)
rm config/jwt/*.pem
```

## 🔍 Wyszukiwanie testów

```bash
# Pokaż wszystkie testy (bez uruchamiania)
vendor/bin/phpunit --list-tests

# Pokaż grupy testów
vendor/bin/phpunit --list-groups

# Uruchom konkretną grupę
vendor/bin/phpunit --group unit
vendor/bin/phpunit --group functional

# Wyklucz grupę
vendor/bin/phpunit --exclude-group slow
```

## 📝 Zmienne środowiskowe

```bash
# Ustaw środowisko testowe
export APP_ENV=test
export DATABASE_URL="postgresql://testuser:testpass@postgres:5432/testdb_test"
export JWT_PASSPHRASE=testpassphrase

# Sprawdź zmienne
env | grep -E "APP_ENV|DATABASE_URL|JWT"

# Wyczyść zmienne
unset APP_ENV DATABASE_URL JWT_PASSPHRASE
```

## 🧹 Czyszczenie

```bash
# Wyczyść cache testów
php bin/console cache:clear --env=test

# Usuń cache PHPUnit
rm -rf .phpunit.cache

# Usuń coverage
rm -rf coverage/

# Usuń wszystkie logi
rm -rf var/log/*

# Kompletne czyszczenie
rm -rf var/cache/* var/log/* .phpunit.cache coverage/
```

## 🎨 Formatowanie output

```bash
# Minimal output
vendor/bin/phpunit --no-output

# Progress dots
vendor/bin/phpunit --progress

# TAP format
vendor/bin/phpunit --log-tap tap.log

# JUnit XML (dla CI)
vendor/bin/phpunit --log-junit junit.xml

# Teamcity format
vendor/bin/phpunit --teamcity
```

## 🔄 CI/CD

```bash
# Symuluj CI lokalnie
export APP_ENV=test
export DATABASE_URL="postgresql://testuser:testpass@localhost:5432/testdb_test"
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
vendor/bin/phpunit --coverage-clover coverage.xml

# Sprawdź workflow GitHub Actions
cat .github/workflows/tests.yml
```

## 💡 Przydatne aliasy

Dodaj do `~/.bashrc` lub `~/.zshrc`:

```bash
# Testy
alias pt='vendor/bin/phpunit'
alias ptf='vendor/bin/phpunit tests/Functional'
alias ptu='vendor/bin/phpunit tests/Unit'
alias ptc='XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage/'
alias ptd='vendor/bin/phpunit --testdox'

# Docker
alias dphp='docker-compose exec php'
alias dphpunit='docker-compose exec php vendor/bin/phpunit'
alias dconsole='docker-compose exec php php bin/console'

# Kombinacje
alias test-all='./run-tests.sh'
alias test-fast='vendor/bin/phpunit --no-coverage'
alias test-watch='watch -n 2 vendor/bin/phpunit'
```

## 📚 Więcej informacji

- Pełna dokumentacja: `tests/README.md`
- Przewodnik testowania: `docs/TESTING.md`
- Setup guide: `TESTS_SETUP.md`
- PHPUnit docs: https://phpunit.de/documentation.html
