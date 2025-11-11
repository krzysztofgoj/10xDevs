# 10x-cards

Platforma do tworzenia i nauki z wykorzystaniem fiszek edukacyjnych. System wykorzystuje sztuczną inteligencję (modele językowe dostępne przez API) do automatycznego tworzenia propozycji pytań i odpowiedzi na podstawie dostarczonych przez użytkownika materiałów tekstowych.

## 📋 Opis projektu

10x-cards to aplikacja webowa umożliwiająca efektywną naukę poprzez technikę spaced repetition. Główną zaletą platformy jest możliwość automatycznego generowania fiszek z dowolnego tekstu przy użyciu AI, co znacząco redukuje czas potrzebny na przygotowanie materiału do nauki.

### Główne funkcjonalności

- 🤖 **Generowanie fiszek wspomagane AI** - automatyczne tworzenie pytań i odpowiedzi z tekstu źródłowego
- ✏️ **Ręczne tworzenie i edycja fiszek** - pełna kontrola nad treścią kart
- 📚 **Zarządzanie kolekcją** - organizacja i przeglądanie wszystkich fiszek
- 🔄 **System powtórek** - integracja z algorytmem spaced repetition
- 👤 **System kont użytkowników** - rejestracja, logowanie, zarządzanie kontem
- 📊 **Analityka** - śledzenie statystyk generowania i akceptacji fiszek

## 🛠️ Stos technologiczny

### Backend
- **PHP 8.3** - główny język programowania
- **Symfony 6.4** - framework webowy
- **Twig 3.8** - silnik szablonów

### Baza danych
- **PostgreSQL 15** - relacyjna baza danych

### Infrastruktura
- **Docker** - konteneryzacja aplikacji
- **Docker Compose** - orkiestracja kontenerów
- **Apache** - serwer HTTP z mod_rewrite

### Narzędzia deweloperskie
- **PHPUnit 10.5** - framework do testów
- **Composer** - menedżer pakietów PHP

### Rozszerzenia PHP
- pdo / pdo_pgsql - obsługa PostgreSQL
- intl - internacjonalizacja
- mbstring - obsługa wielobajtowych stringów
- xml - parsowanie XML
- zip - obsługa archiwów ZIP

## 📁 Struktura projektu

```
.
├── src/              # Kod źródłowy (PSR-4, namespace: App\)
│   ├── Controller/   # Kontrolery Symfony
│   ├── Entity/       # Encje Doctrine
│   ├── Service/      # Logika biznesowa
│   ├── Repository/   # Repozytoria Doctrine
│   └── Form/         # Typy formularzy Symfony
├── templates/        # Szablony Twig
├── tests/           # Testy PHPUnit
├── config/          # Pliki konfiguracyjne Symfony
├── public/          # Katalog publiczny (punkt wejścia)
├── docker-compose.yml
├── Dockerfile
└── composer.json
```

## 🚀 Instalacja i uruchomienie

### Wymagania wstępne

- Docker i Docker Compose
- Git

### Kroki instalacji

1. **Sklonuj repozytorium:**
   ```bash
   git clone <repository-url>
   cd 10xDevs
   ```

2. **Skonfiguruj zmienne środowiskowe:**
   
   Utwórz plik `.env` w katalogu głównym projektu (jeśli nie istnieje) i ustaw odpowiednie wartości:
   ```env
   DATABASE_URL=postgresql://testuser:testpass@postgres:5432/testdb
   APP_ENV=dev
   APP_SECRET=your-secret-key-here
   ```

3. **Zbuduj i uruchom kontenery:**
   ```bash
   docker-compose up -d --build
   ```

4. **Zainstaluj zależności Composer:**
   ```bash
   docker-compose exec php composer install
   ```

5. **Wygeneruj klucze JWT:**
   ```bash
   # Utwórz katalog dla kluczy JWT
   mkdir -p config/jwt
   
   # Wygeneruj klucz prywatny (użyj passphrase z pliku .env)
   openssl genpkey -algorithm RSA -out config/jwt/private.pem -aes256 -pass pass:9f094eace947ed0eb1ca2dbfc37deaa1a578bb957d7a52d2db53b0274981fd67 -pkeyopt rsa_keygen_bits:4096
   
   # Wygeneruj klucz publiczny z klucza prywatnego
   openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:9f094eace947ed0eb1ca2dbfc37deaa1a578bb957d7a52d2db53b0274981fd67
   ```
   
   ⚠️ **Uwaga**: W środowisku produkcyjnym użyj bezpiecznego, losowego passphrase i przechowuj go w zmiennych środowiskowych!

6. **Uruchom migracje bazy danych:**
   ```bash
   docker-compose exec php php bin/console doctrine:migrations:migrate
   ```

7. **Aplikacja będzie dostępna pod adresem:**
   ```
   http://localhost:8080
   ```

### Porty

- **Aplikacja**: `http://localhost:8080` (mapowany na port 80 w kontenerze)
- **PostgreSQL**: `localhost:5433` (mapowany na port 5432 w kontenerze)

## 🧪 Testy

Projekt zawiera kompleksową suite testów automatycznych (funkcjonalnych i jednostkowych) gotowych do użycia w CI/CD.

### Szybki start

```bash
# Uruchom wszystkie testy
./run-tests.sh

# Lub w kontenerze Docker
docker-compose exec php vendor/bin/phpunit
```

### Rodzaje testów

#### 1. Testy funkcjonalne (Functional/)
Testują pełny przepływ HTTP przez API:
- ✅ **AuthControllerTest** - rejestracja, logowanie, autoryzacja JWT
- ✅ **FlashcardControllerTest** - pełny CRUD fiszek, bezpieczeństwo, izolacja użytkowników

#### 2. Testy jednostkowe (Unit/)
Testują poszczególne klasy w izolacji:
- ✅ **AuthServiceTest** - logika autoryzacji z mockami

### Uruchamianie testów

```bash
# Wszystkie testy
./run-tests.sh

# Tylko testy funkcjonalne
./run-tests.sh --functional

# Tylko testy jednostkowe
./run-tests.sh --unit

# Z pokryciem kodu (coverage)
./run-tests.sh --coverage

# Format testdox (czytelny output)
./run-tests.sh --testdox

# Konkretna klasa
./run-tests.sh tests/Functional/AuthControllerTest.php
```

### Coverage raport

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage/
# Raport w: coverage/index.html
```

### CI/CD - GitHub Actions

Projekt zawiera workflow `.github/workflows/tests.yml`, który automatycznie:
- ✅ Uruchamia testy na PHP 8.3
- ✅ Używa SQLite in-memory (szybkie, zero setupu)
- ✅ Generuje klucze JWT
- ✅ Testuje API autoryzacji i CRUD fiszek
- ✅ Generuje raport coverage i uploaduje do Codecov
- ✅ Sprawdza jakość kodu

Workflow uruchamia się przy push/PR do `main` i `develop`.

**Status**: ✅ **34/34 testy przechodzą** (11 Auth + 17 Flashcard + 6 AuthService)

**Setup CI/CD**: Workflow działa automatycznie po `git push`! Opcjonalna konfiguracja Codecov: **[GITHUB_CICD_SETUP.md](GITHUB_CICD_SETUP.md)**

### Więcej informacji

Szczegółowa dokumentacja: **[tests/README.md](tests/README.md)**

### Ręczne testowanie API

Użyj skryptów testowych do sprawdzenia endpointów:

```bash
# Testowanie API autoryzacji
./test-auth.sh

# Testowanie API fiszek
./test-flashcards.sh [email] [password]
```

Lub ręcznie używając cURL (przykłady w `docs/FLASHCARDS_API.md`)

## 📝 Konfiguracja

### Baza danych

Domyślna konfiguracja bazy danych w `docker-compose.yml`:
- **Host**: `postgres`
- **Database**: `testdb`
- **User**: `testuser`
- **Password**: `testpass`

⚠️ **Uwaga**: W środowisku produkcyjnym zmień domyślne hasła i użyj zmiennych środowiskowych.

### Symfony

Konfiguracja Symfony znajduje się w katalogu `config/`. Główne pliki:
- `services.yaml` - definicje serwisów
- `routes.yaml` - routing (lub użyj atrybutów w kontrolerach)
- `packages/doctrine.yaml` - konfiguracja Doctrine

### JWT Authentication

Projekt używa LexikJWTAuthenticationBundle do autoryzacji API. Konfiguracja JWT:
- **Klucze**: RSA 4096-bit, przechowywane w `config/jwt/` (ignorowane w .gitignore)
- **TTL tokenu**: 3600 sekund (1 godzina)
- **User ID claim**: email (używany jako identyfikator użytkownika w tokenie)
- **Zmienne środowiskowe**:
  - `JWT_SECRET_KEY` - ścieżka do klucza prywatnego
  - `JWT_PUBLIC_KEY` - ścieżka do klucza publicznego  
  - `JWT_PASSPHRASE` - hasło do klucza prywatnego

Dokumentacja: [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle)

## 🔧 Rozwój

### Konwencje kodowania

- Projekt korzysta z **PSR-12** coding standard
- Wszystkie pliki PHP powinny zawierać `declare(strict_types=1);`
- Używaj type hints dla wszystkich parametrów i zwracanych wartości
- Kontrolery powinny być cienkie - logika biznesowa w serwisach
- Używaj dependency injection dla wszystkich zależności

### Reguły dla AI

Projekt zawiera reguły dla AI w katalogu `.cursor/rules/`:
- `shared.mdc` - ogólne reguły projektu
- `backend.mdc` - reguły dla PHP/Symfony
- `twig.mdc` - reguły dla szablonów Twig

## 📚 Dokumentacja

### Dokumentacja projektu

- **[API Autoryzacji](docs/AUTHENTICATION.md)** - Rejestracja, logowanie i autoryzacja JWT
- **[API Fiszek](docs/FLASHCARDS_API.md)** - Generowanie i zapisywanie fiszek z AI
- **[Architektura JWT](docs/JWT_ARCHITECTURE.md)** - Jak frontend używa JWT tokenów
- **[🚀 Setup OpenAI](SETUP_OPENAI.md)** - Instrukcja konfiguracji OpenAI API (START TUTAJ!)
- **[Integracja OpenAI](docs/OPENAI_INTEGRATION.md)** - Szczegóły techniczne
- **[Zabezpieczenia OpenAI](docs/OPENAI_SECURITY.md)** - Ochrona przed kosztami

### Dokumentacja zewnętrzna

- [Symfony Documentation](https://symfony.com/doc/6.4/index.html)
- [Twig Documentation](https://twig.symfony.com/doc/3.x/)
- [Doctrine Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/index.html)

## 🎯 User Stories

Główne funkcjonalności zdefiniowane jako User Stories:

- **US-001**: Utworzenie konta użytkownika
- **US-002**: Logowanie
- **US-003**: Generowanie fiszek przez AI
- **US-004**: Przeglądanie i selekcja wygenerowanych fiszek
- **US-005**: Modyfikacja istniejących fiszek
- **US-006**: Usuwanie fiszek
- **US-007**: Ręczne dodawanie fiszek
- **US-008**: Nauka z wykorzystaniem algorytmu powtórek
- **US-009**: Izolacja danych użytkownika

Szczegóły dostępne w pliku `.ai/prd.md`.

## 🔒 Bezpieczeństwo

- Hasła są hashowane przy użyciu komponentu PasswordHasher Symfony
- Wszystkie dane wejściowe są walidowane i sanitizowane
- Używane są tokeny CSRF dla formularzy
- Dane osobowe przetwarzane zgodnie z RODO
- Użytkownicy mają prawo do wglądu i usunięcia swoich danych

## 📊 Wskaźniki sukcesu

- **Jakość generowania AI**: Docelowo 75% wygenerowanych fiszek powinno być akceptowanych przez użytkowników
- **Aktywność użytkowników**: Minimum 75% wszystkich nowo dodanych fiszek powinno pochodzić z generowania AI

## 🚧 Funkcjonalności wyłączone z MVP

Następujące funkcje nie są planowane w pierwszej wersji:
- Własna implementacja algorytmu powtórek (używamy gotowej biblioteki)
- Elementy grywalizacji
- Aplikacje mobilne (tylko wersja przeglądarkowa)
- Import plików (PDF, DOCX, etc.)
- Publiczne API
- Funkcje społecznościowe
- Zaawansowane powiadomienia
- Wyszukiwarka z filtrowaniem

## 📄 Licencja

[Określ licencję projektu]

## 👥 Autorzy

[Informacje o autorach]

## 🤝 Wsparcie

[Informacje o wsparciu i kontakcie]
