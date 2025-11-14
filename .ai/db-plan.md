# Plan schematu bazy danych - 10x-cards

## Wprowadzenie

Ten dokument opisuje kompleksowy schemat bazy danych dla aplikacji 10x-cards. Schemat powinien być zaprojektowany na podstawie wymagań z PRD oraz sesji planistycznej, z uwzględnieniem najlepszych praktyk projektowania baz danych PostgreSQL i integracji z Symfony/Doctrine.

## Architektura

- **System zarządzania bazą danych**: PostgreSQL 15
- **ORM**: Doctrine ORM (Symfony)
- **Konwencja nazewnictwa**: snake_case dla tabel i kolumn
- **Typy ID**: Integer auto-increment (możliwość zmiany na UUID w przyszłości)

## Tabele

### 1. user

Tabela przechowująca informacje o użytkownikach systemu. Powinna integrować się z Symfony Security Component.

**Kolumny:**
- `id` (INTEGER, PRIMARY KEY, AUTO_INCREMENT) - unikalny identyfikator użytkownika
- `email` (VARCHAR(180), UNIQUE, NOT NULL, INDEXED) - adres email użytkownika (używany do logowania)
- `password` (VARCHAR(255), NOT NULL) - zahashowane hasło (używając Symfony PasswordHasher)
- `roles` (JSON, NOT NULL, DEFAULT '[]') - tablica ról użytkownika (np. ["ROLE_USER"])
- `created_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP) - data utworzenia konta
- `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP) - data ostatniej aktualizacji

**Indeksy:**
- PRIMARY KEY na `id`
- UNIQUE INDEX na `email`
- INDEX na `email` (dla szybkiego wyszukiwania przy logowaniu)

**Uwagi:**
- Tabela powinna być mapowana na encję Doctrine implementującą `UserInterface` z Symfony Security
- Hasła powinny być hashowane przy użyciu Symfony PasswordHasher (domyślnie bcrypt/argon2)
- Soft delete nie jest wymagany - zgodnie z PRD użytkownik może całkowicie usunąć konto

---

### 2. flashcard

Tabela przechowująca fiszki edukacyjne użytkowników. Fiszki mogą być tworzone ręcznie przez użytkownika lub generowane przez AI.

**Kolumny:**
- `id` (INTEGER, PRIMARY KEY, AUTO_INCREMENT) - unikalny identyfikator fiszki
- `user_id` (INTEGER, NOT NULL, FOREIGN KEY) - właściciel fiszki (relacja do user.id)
- `question` (TEXT, NOT NULL) - treść pytania (strona przednia karty)
- `answer` (TEXT, NOT NULL) - treść odpowiedzi (strona tylna karty)
- `source` (ENUM('ai', 'manual'), NOT NULL, DEFAULT 'manual') - źródło utworzenia fiszki
  - `'ai'` - wygenerowana przez AI
  - `'manual'` - utworzona ręcznie przez użytkownika
- `generation_id` (INTEGER, NULLABLE, FOREIGN KEY) - opcjonalna relacja do sesji generowania (jeśli source='ai')
- `created_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP) - data utworzenia
- `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP) - data ostatniej modyfikacji

**Indeksy:**
- PRIMARY KEY na `id`
- INDEX na `user_id` (dla szybkiego pobierania fiszek użytkownika)
- INDEX na `created_at` (dla sortowania chronologicznego)
- INDEX na `generation_id` (dla analityki generowania)
- FOREIGN KEY `fk_flashcard_user` na `user_id` REFERENCES `user(id)` ON DELETE CASCADE
- FOREIGN KEY `fk_flashcard_generation` na `generation_id` REFERENCES `flashcard_generation(id)` ON DELETE SET NULL

**Uwagi:**
- ON DELETE CASCADE zapewnia, że usunięcie użytkownika usuwa wszystkie jego fiszki (zgodność z RODO)
- ON DELETE SET NULL dla generation_id - jeśli sesja generowania zostanie usunięta, fiszka pozostaje
- Walidacja długości tekstu (1000-10000 znaków dla generowania AI) odbywa się w warstwie aplikacji
- Brak limitu długości w bazie danych - TEXT pozwala na długie treści

---

### 3. flashcard_generation

Tabela przechowująca historię sesji generowania fiszek przez AI. Używana do analityki i śledzenia wskaźnika akceptacji.

**Kolumny:**
- `id` (INTEGER, PRIMARY KEY, AUTO_INCREMENT) - unikalny identyfikator sesji generowania
- `user_id` (INTEGER, NOT NULL, FOREIGN KEY) - użytkownik, który zainicjował generowanie
- `source_text` (TEXT, NOT NULL) - oryginalny tekst źródłowy przesłany do API LLM
- `status` (ENUM('pending', 'completed', 'failed'), NOT NULL, DEFAULT 'pending') - status generowania
  - `'pending'` - generowanie w toku
  - `'completed'` - zakończone pomyślnie
  - `'failed'` - zakończone błędem
- `generated_at` (TIMESTAMP, NULLABLE) - data i czas zakończenia generowania
- `created_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP) - data utworzenia sesji

**Indeksy:**
- PRIMARY KEY na `id`
- INDEX na `user_id` (dla historii generowań użytkownika)
- INDEX na `status` (dla filtrowania)
- INDEX na `created_at` (dla sortowania chronologicznego)
- FOREIGN KEY `fk_generation_user` na `user_id` REFERENCES `user(id)` ON DELETE CASCADE

**Uwagi:**
- Przechowywanie source_text pozwala na analizę jakości generowania
- Status pozwala śledzić nieudane próby generowania
- ON DELETE CASCADE - usunięcie użytkownika usuwa jego historię generowań
- Propozycje fiszek przed akceptacją nie są przechowywane w bazie - istnieją tylko w sesji użytkownika

---

### 4. repetition_record

Tabela przechowująca metadane dla algorytmu spaced repetition. Każda fiszka ma jeden rekord powtórek, który jest automatycznie tworzony przy dodaniu fiszki do systemu nauki.

**Kolumny:**
- `id` (INTEGER, PRIMARY KEY, AUTO_INCREMENT) - unikalny identyfikator rekordu
- `flashcard_id` (INTEGER, NOT NULL, UNIQUE, FOREIGN KEY) - fiszka powiązana z tym rekordem
- `last_reviewed_at` (TIMESTAMP, NULLABLE) - data i czas ostatniej powtórki
- `next_review_at` (TIMESTAMP, NULLABLE) - data i czas następnej zaplanowanej powtórki (obliczona przez algorytm)
- `ease_factor` (DECIMAL(5,2), NOT NULL, DEFAULT 2.50) - współczynnik łatwości (używany przez algorytm SM-2 lub podobny)
- `interval_days` (INTEGER, NOT NULL, DEFAULT 1) - aktualny interwał powtórek w dniach
- `repetition_count` (INTEGER, NOT NULL, DEFAULT 0) - liczba wykonanych powtórek
- `created_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP) - data utworzenia rekordu
- `updated_at` (TIMESTAMP, NOT NULL, DEFAULT CURRENT_TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP) - data ostatniej aktualizacji

**Indeksy:**
- PRIMARY KEY na `id`
- UNIQUE INDEX na `flashcard_id` (gwarantuje 1:1 relację)
- INDEX na `next_review_at` (krytyczny dla pobierania fiszek do powtórki)
- INDEX na `last_reviewed_at` (dla analityki)
- FOREIGN KEY `fk_repetition_flashcard` na `flashcard_id` REFERENCES `flashcard(id)` ON DELETE CASCADE

**Uwagi:**
- Relacja 1:1 z flashcard - każda fiszka ma dokładnie jeden rekord powtórek
- Algorytm spaced repetition (np. SM-2) będzie aktualizował te wartości po każdej powtórce
- next_review_at jest kluczowy dla przygotowania sesji nauki - fiszki z next_review_at <= CURRENT_TIMESTAMP są gotowe do powtórki
- ON DELETE CASCADE - usunięcie fiszki usuwa jej rekord powtórek
- Wartości domyślne są zgodne z typowym algorytmem SM-2

---

## Relacje między tabelami

```
user (1) ──< (N) flashcard
user (1) ──< (N) flashcard_generation
flashcard_generation (1) ──< (N) flashcard [opcjonalna]
flashcard (1) ──< (1) repetition_record
```

### Szczegóły relacji:

1. **User → Flashcard** (OneToMany)
   - Jeden użytkownik może mieć wiele fiszek
   - Usunięcie użytkownika usuwa wszystkie jego fiszki (CASCADE)

2. **User → FlashcardGeneration** (OneToMany)
   - Jeden użytkownik może mieć wiele sesji generowania
   - Usunięcie użytkownika usuwa jego historię generowań (CASCADE)

3. **FlashcardGeneration → Flashcard** (OneToMany, opcjonalna)
   - Jedna sesja generowania może wyprodukować wiele fiszek
   - Usunięcie sesji nie usuwa fiszek (SET NULL)

4. **Flashcard → RepetitionRecord** (OneToOne)
   - Każda fiszka ma dokładnie jeden rekord powtórek
   - Usunięcie fiszki usuwa jej rekord powtórek (CASCADE)

## Bezpieczeństwo

### Zasady bezpieczeństwa na poziomie wierszy (RLS)

W kontekście Symfony/Doctrine, bezpieczeństwo powinno być zapewniane przez:
- **Autoryzację w warstwie aplikacji** - wszystkie zapytania filtrowane przez user_id
- **Repository pattern** - metody w repozytoriach zawsze filtrują po zalogowanym użytkowniku
- **Symfony Security** - kontrola dostępu na poziomie kontrolerów

**Uwaga**: W przypadku użycia Supabase, można by zastosować Row Level Security (RLS), ale w standardowym Symfony/Doctrine bezpieczeństwo jest zapewniane w warstwie aplikacji.

### Hashowanie haseł

- Użycie Symfony PasswordHasher (domyślnie bcrypt lub argon2)
- Hasła nigdy nie powinny być przechowywane w formie plaintext
- Minimalna długość hasła: 8 znaków (walidacja w formularzu)

## Wydajność i optymalizacja

### Krytyczne indeksy:

1. **user.email** - UNIQUE INDEX
   - Używany przy każdym logowaniu
   - Zapewnia szybkie wyszukiwanie użytkownika

2. **flashcard.user_id** - INDEX
   - Używany przy pobieraniu kolekcji fiszek użytkownika
   - Najczęstsze zapytanie w aplikacji

3. **flashcard.created_at** - INDEX
   - Używany do sortowania chronologicznego
   - Pomaga w paginacji

4. **repetition_record.next_review_at** - INDEX
   - Krytyczny dla przygotowania sesji nauki
   - Używany w zapytaniu: WHERE next_review_at <= NOW()

5. **repetition_record.flashcard_id** - UNIQUE INDEX
   - Zapewnia relację 1:1
   - Szybkie wyszukiwanie rekordu dla fiszki

### Zapytania do optymalizacji w przyszłości:

- Paginacja dla kolekcji fiszek (LIMIT/OFFSET lub cursor-based)
- Eager loading relacji w Doctrine (unikanie N+1 problem)
- Cache dla często używanych zapytań

## Zgodność z RODO

### Wymagania:

1. **Prawo do wglądu w dane** (Art. 15 RODO)
   - Użytkownik powinien móc wyeksportować wszystkie swoje dane
   - Endpoint API zwracający JSON z wszystkimi fiszkami i metadanymi

2. **Prawo do usunięcia danych** (Art. 17 RODO)
   - Usunięcie konta powinno usuwać wszystkie powiązane dane (CASCADE)
   - Hard delete - dane są trwale usuwane z bazy

3. **Minimalizacja danych**
   - Powinny być przechowywane tylko niezbędne dane
   - Brak zbędnych metadanych w MVP

## Migracje

Wszystkie zmiany schematu bazy danych powinny być wykonywane poprzez migracje Doctrine:
- Pliki migracji w katalogu `migrations/`
- Konwencja nazewnictwa: `VersionYYYYMMDDHHMMSS.php`
- Migracje są wersjonowane i mogą być rollbackowane

## Przyszłe rozszerzenia (poza MVP)

Następujące funkcjonalności mogą wymagać dodatkowych tabel w przyszłości:
- Tagi/kategorie fiszek (tabela `tag`, relacja ManyToMany)
- Historia zmian fiszek (audit log)
- Statystyki użytkownika (tabela `user_statistics`)
- Powiadomienia (tabela `notification`)
- Współdzielenie fiszek (tabela `shared_flashcard`)

## Podsumowanie

Schemat bazy danych powinien być zaprojektowany z myślą o:
- **Skalowalności** - indeksy na krytycznych kolumnach
- **Bezpieczeństwie** - foreign keys z CASCADE, hashowanie haseł
- **Wydajności** - optymalne indeksy dla najczęstszych zapytań
- **Zgodności z RODO** - możliwość eksportu i usunięcia danych
- **Prostocie** - minimalna złożoność dla MVP

Schemat powinien być gotowy do implementacji poprzez migracje Doctrine.
