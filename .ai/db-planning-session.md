# Sesja planistyczna bazy danych - 10x-cards

## Analiza wymagań z PRD

Na podstawie analizy PRD i User Stories, należy zidentyfikować następujące główne encje:

1. **User** - użytkownicy systemu
2. **Flashcard** - fiszki edukacyjne
3. **FlashcardGeneration** - historia generowania fiszek przez AI (dla analityki)
4. **RepetitionSession** - sesje nauki z algorytmem spaced repetition
5. **RepetitionRecord** - pojedyncze rekordy powtórek (dla algorytmu)

## Pytania i decyzje projektowe

### 1. Tabela User

**Pytania:**
- Czy używamy Symfony Security (UserInterface) czy własnej implementacji?
- Jakie pola są wymagane: email, password, created_at, updated_at?
- Czy potrzebujemy soft delete dla zgodności z RODO?
- Czy email powinien być unikalny i zindeksowany?

**Rekomendacje:**
- Użycie Symfony Security Component z UserInterface
- Pola: id (UUID lub auto-increment), email (unique, indexed), password (hashed), roles (JSON array), created_at, updated_at
- Soft delete nie jest konieczny w MVP - hard delete wystarczy (zgodnie z PRD: "całkowite usunięcie konta")
- Email musi być unikalny i zindeksowany dla szybkiego logowania

### 2. Tabela Flashcard

**Pytania:**
- Jak rozróżnić fiszki wygenerowane przez AI od ręcznych?
- Czy potrzebujemy pola "status" (draft, active, archived)?
- Jakie są limity długości pytania i odpowiedzi?
- Czy potrzebujemy timestamps dla śledzenia zmian?
- Czy fiszka powinna mieć relację do sesji generowania?

**Rekomendacje:**
- Pole `source` (enum: 'ai', 'manual') do rozróżnienia źródła
- W MVP nie potrzebujemy statusu - fiszka jest aktywna lub usunięta
- Pytanie i odpowiedź jako TEXT (bez limitów w bazie, walidacja w aplikacji)
- Timestamps: created_at, updated_at
- Relacja ManyToOne do User (właściciel)
- Opcjonalna relacja ManyToOne do FlashcardGeneration (jeśli wygenerowana przez AI)

### 3. Tabela FlashcardGeneration

**Pytania:**
- Co przechowywać z sesji generowania?
- Czy przechowywać oryginalny tekst źródłowy?
- Jak śledzić akceptację/odrzucenie fiszek?
- Czy potrzebujemy osobnej tabeli dla propozycji przed akceptacją?

**Rekomendacje:**
- Przechowywać: id, user_id, source_text (TEXT), generated_at, status (enum: 'pending', 'completed', 'failed')
- Nie przechowywać propozycji przed akceptacją - są tylko w sesji użytkownika
- Statystyki akceptacji liczymy przez porównanie liczby wygenerowanych vs zapisanych fiszek
- Relacja OneToMany do Flashcard (fiszki z source='ai')

### 4. System powtórek (Spaced Repetition)

**Pytania:**
- Którą bibliotekę open-source użyjemy? (np. php-spaced-repetition)
- Jakie dane musi przechowywać algorytm?
- Czy potrzebujemy osobnej tabeli dla metadanych powtórek?
- Jak często będą sesje nauki?

**Rekomendacje:**
- Użycie biblioteki wymagającej: flashcard_id, last_reviewed_at, next_review_at, ease_factor, interval_days, repetition_count
- Tabela `RepetitionRecord` z polami wymaganymi przez algorytm
- Relacja OneToOne z Flashcard (każda fiszka ma jeden rekord powtórek)
- Timestamps dla śledzenia historii

### 5. Bezpieczeństwo i wydajność

**Pytania:**
- Jakie indeksy są krytyczne?
- Czy potrzebujemy foreign key constraints?
- Jak obsłużyć cascade delete (usunięcie użytkownika = usunięcie fiszek)?

**Rekomendacje:**
- Indeksy: user.email (unique), flashcard.user_id, flashcard.created_at, repetition_record.next_review_at
- Foreign keys z ON DELETE CASCADE dla integralności danych
- Indeksy na kolumnach używanych w WHERE i JOIN

### 6. Zgodność z RODO

**Pytania:**
- Jak zapewnić możliwość eksportu danych użytkownika?
- Czy potrzebujemy logowania operacji na danych osobowych?

**Rekomendacje:**
- W MVP: możliwość eksportu przez API/endpoint (wszystkie fiszki użytkownika)
- Logowanie operacji nie jest wymagane w MVP (zgodnie z PRD)

## Podsumowanie decyzji

### Tabele do utworzenia:

1. **user**
   - id (UUID lub integer auto-increment)
   - email (VARCHAR, UNIQUE, INDEXED)
   - password (VARCHAR - hashed)
   - roles (JSON)
   - created_at (TIMESTAMP)
   - updated_at (TIMESTAMP)

2. **flashcard**
   - id (UUID lub integer auto-increment)
   - user_id (FK to user, ON DELETE CASCADE)
   - question (TEXT)
   - answer (TEXT)
   - source (ENUM: 'ai', 'manual')
   - generation_id (FK to flashcard_generation, nullable, ON DELETE SET NULL)
   - created_at (TIMESTAMP)
   - updated_at (TIMESTAMP)

3. **flashcard_generation**
   - id (UUID lub integer auto-increment)
   - user_id (FK to user, ON DELETE CASCADE)
   - source_text (TEXT)
   - status (ENUM: 'pending', 'completed', 'failed')
   - generated_at (TIMESTAMP)
   - created_at (TIMESTAMP)

4. **repetition_record**
   - id (UUID lub integer auto-increment)
   - flashcard_id (FK to flashcard, UNIQUE, ON DELETE CASCADE)
   - last_reviewed_at (TIMESTAMP, nullable)
   - next_review_at (TIMESTAMP, nullable)
   - ease_factor (DECIMAL)
   - interval_days (INTEGER)
   - repetition_count (INTEGER, default 0)
   - created_at (TIMESTAMP)
   - updated_at (TIMESTAMP)

### Indeksy:
- user.email (UNIQUE)
- flashcard.user_id
- flashcard.created_at
- flashcard.generation_id
- repetition_record.next_review_at
- repetition_record.flashcard_id (UNIQUE)

### Relacje:
- User 1:N Flashcard
- User 1:N FlashcardGeneration
- FlashcardGeneration 1:N Flashcard (opcjonalna)
- Flashcard 1:1 RepetitionRecord
