# Wymagania implementacji bazy danych - 10x-cards

## Proces planowania

Projekt powinien przejść przez kompleksowy proces planowania i wdrożenia bazy danych:

### 1. Sesja planistyczna bazy danych

**Plik:** `.ai/db-planning-session.md`

Należy przeprowadzić analizę wymagań z PRD i zidentyfikować kluczowe encje:
- User (użytkownicy)
- Flashcard (fiszki edukacyjne)
- FlashcardGeneration (historia generowania przez AI)
- RepetitionRecord (metadane dla algorytmu spaced repetition)

**Kluczowe decyzje:**
- Użycie Symfony Security Component z UserInterface
- Hard delete (zgodnie z PRD - całkowite usunięcie konta)
- Enum dla source (ai/manual) i status (pending/completed/failed)
- Relacja 1:1 między Flashcard a RepetitionRecord
- Foreign keys z ON DELETE CASCADE dla integralności danych

### 2. Definiowanie schematu bazy danych

**Plik:** `.ai/db-plan.md`

Należy stworzyć kompleksowy plan schematu zawierający:
- Szczegółowy opis wszystkich 4 tabel
- Definicje kolumn z typami danych
- Indeksy dla optymalizacji wydajności
- Relacje między tabelami z foreign keys
- Uwagi dotyczące bezpieczeństwa i zgodności z RODO
- Planowanie przyszłych rozszerzeń

**Główne tabele:**
1. `user` - użytkownicy systemu (email, password, roles)
2. `flashcard` - fiszki edukacyjne (question, answer, source)
3. `flashcard_generation` - sesje generowania przez AI (source_text, status)
4. `repetition_record` - metadane dla spaced repetition (ease_factor, interval_days, next_review_at)

### 3. Wdrożenie poprzez migracje

**Plik:** `migrations/Version20251111212844.php`

Należy wygenerować migrację Doctrine zawierającą:
- Tworzenie wszystkich 4 tabel z odpowiednimi kolumnami
- Indeksy dla optymalizacji zapytań
- Foreign keys z odpowiednimi akcjami (CASCADE, SET NULL)
- Check constraints dla enum values
- PostgreSQL triggers dla automatycznej aktualizacji `updated_at`
- Funkcja `update_updated_at_column()` dla automatycznego zarządzania timestampami

**Dodatkowe funkcjonalności:**
- Automatyczne aktualizowanie `updated_at` przez trigger PostgreSQL
- Check constraints dla wartości enum (source, status)
- Optymalne indeksy na krytycznych kolumnach

### 4. Konfiguracja projektu

**Plik:** `composer.json`

Projekt powinien zawierać zależności:
- `doctrine/doctrine-bundle` - integracja Doctrine z Symfony
- `doctrine/orm` - ORM Doctrine
- `doctrine/doctrine-migrations-bundle` - zarządzanie migracjami
- `symfony/security-bundle` - komponent bezpieczeństwa
- `symfony/validator` - walidacja danych
- `symfony/form` - komponenty formularzy

## Struktura bazy danych

```
user (1) ──< (N) flashcard
user (1) ──< (N) flashcard_generation
flashcard_generation (1) ──< (N) flashcard [opcjonalna]
flashcard (1) ──< (1) repetition_record
```

## Indeksy i optymalizacja

**Krytyczne indeksy:**
- `user.email` (UNIQUE) - szybkie logowanie
- `flashcard.user_id` - pobieranie kolekcji użytkownika
- `flashcard.created_at` - sortowanie chronologiczne
- `repetition_record.next_review_at` - przygotowanie sesji nauki
- `repetition_record.flashcard_id` (UNIQUE) - relacja 1:1

## Bezpieczeństwo

- Foreign keys z ON DELETE CASCADE dla integralności danych
- Hashowanie haseł przez Symfony PasswordHasher
- Autoryzacja w warstwie aplikacji (filtrowanie po user_id)
- Check constraints dla wartości enum

## Zgodność z RODO

- Hard delete - usunięcie użytkownika usuwa wszystkie powiązane dane (CASCADE)
- Możliwość eksportu danych (do implementacji w API)
- Minimalizacja przechowywanych danych

## Następne kroki

1. **Zainstalowanie zależności:**
   ```bash
   docker-compose exec php composer install
   ```

2. **Konfiguracja Doctrine:**
   - Utworzenie pliku `config/packages/doctrine.yaml`
   - Konfiguracja połączenia z bazą danych PostgreSQL

3. **Uruchomienie migracji:**
   ```bash
   docker-compose exec php php bin/console doctrine:migrations:migrate
   ```

4. **Weryfikacja schematu:**
   - Sprawdzenie utworzonych tabel w bazie danych
   - Weryfikacja indeksów i foreign keys

5. **Tworzenie encji Doctrine:**
   - `App\Entity\User` (implementująca UserInterface)
   - `App\Entity\Flashcard`
   - `App\Entity\FlashcardGeneration`
   - `App\Entity\RepetitionRecord`

## Wybrane rozwiązania techniczne

Projekt powinien wykorzystywać Symfony/Doctrine:

1. **Migracje:**
   - Klasy PHP (`VersionYYYYMMDDHHMMSS.php`) zamiast plików SQL
   - Wersjonowanie i możliwość rollbacku

2. **Autentykacja:**
   - Własna tabela `user` z Symfony Security Component
   - Integracja z UserInterface dla pełnej kontroli

3. **Bezpieczeństwo:**
   - Autoryzacja w warstwie aplikacji (filtrowanie po user_id)
   - Repository pattern zapewniający izolację danych użytkowników

4. **Timestamps:**
   - PostgreSQL triggers dla automatycznej aktualizacji `updated_at`
   - Doctrine lifecycle callbacks jako dodatkowa warstwa

## Podsumowanie

Proces planowania i wdrożenia bazy danych powinien obejmować:
1. ✅ Sesja planistyczna - zebranie pytań i rekomendacji
2. ✅ Definiowanie schematu - szczegółowy plan bazy danych
3. ✅ Wdrożenie - migracja Doctrine gotowa do uruchomienia

Schemat bazy danych powinien być gotowy do użycia i zgodny z wymaganiami z PRD oraz najlepszymi praktykami projektowania baz danych PostgreSQL.
