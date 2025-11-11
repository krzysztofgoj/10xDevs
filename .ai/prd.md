# Specyfikacja wymagań produktowych – 10x-cards

## Wprowadzenie

10x-cards to platforma do tworzenia i nauki z wykorzystaniem fiszek edukacyjnych. System wykorzystuje sztuczną inteligencję (modele językowe dostępne przez API) do automatycznego tworzenia propozycji pytań i odpowiedzi na podstawie dostarczonych przez użytkownika materiałów tekstowych.

## Kontekst biznesowy i potrzeby użytkowników

Tworzenie wartościowych fiszek metodą tradycyjną jest czasochłonne i wymaga znacznego zaangażowania. To często zniechęca do korzystania z techniki spaced repetition, która jest jedną z najbardziej efektywnych metod zapamiętywania. Nasze rozwiązanie redukuje czas potrzebny na przygotowanie materiału do nauki i ułatwia zarządzanie własną bazą wiedzy.

## Zakres funkcjonalności

### Generowanie fiszek wspomagane AI

System oferuje możliwość automatycznego tworzenia fiszek z dowolnego tekstu:
- Użytkownik wprowadza tekst źródłowy (np. fragment książki, notatki)
- Tekst jest przetwarzany przez zewnętrzne API modelu językowego
- Model zwraca propozycje par pytanie-odpowiedź
- Wygenerowane fiszki prezentowane są użytkownikowi z opcjami: zaakceptuj, zmodyfikuj lub odrzuć

### Tworzenie i modyfikacja fiszek przez użytkownika

Użytkownicy mogą samodzielnie tworzyć i zarządzać swoimi fiszkami:
- Interfejs do ręcznego wprowadzania treści (strona przednia i tylna karty)
- Możliwość modyfikacji istniejących wpisów
- Wszystkie fiszki wyświetlane są w sekcji "Moja kolekcja"

### System kont i bezpieczeństwo

Podstawowe funkcje autoryzacji:
- Proces rejestracji nowych użytkowników
- Logowanie do systemu
- Opcja całkowitego usunięcia konta wraz z wszystkimi danymi

### System powtórek

Integracja z zewnętrznym algorytmem do zarządzania harmonogramem powtórek:
- Automatyczne przypisanie fiszek do systemu powtórek (użycie istniejącej biblioteki open-source)
- W wersji MVP nie przewidujemy dodatkowych metadanych ani zaawansowanych powiadomień

### Infrastruktura danych

- Bezpieczne przechowywanie informacji o użytkownikach i fiszkach
- Architektura umożliwiająca skalowanie systemu

### Analityka

- Śledzenie liczby wygenerowanych przez AI fiszek
- Pomiar wskaźnika akceptacji wygenerowanych propozycji

### Zgodność z przepisami

- Przetwarzanie danych osobowych zgodnie z wymogami RODO
- Użytkownicy mają prawo do wglądu w swoje dane oraz ich usunięcia (wraz z kontem i wszystkimi fiszkami)

## Funkcjonalności wyłączone z MVP

Następujące funkcje nie są planowane w pierwszej wersji produktu:
- Własna implementacja algorytmu powtórek (używamy gotowej biblioteki)
- Elementy grywalizacji
- Aplikacje na urządzenia mobilne (tylko wersja przeglądarkowa)
- Import plików w różnych formatach (PDF, DOCX, etc.)
- Publiczne API dla zewnętrznych integracji
- Funkcje społecznościowe i udostępnianie fiszek
- Zaawansowane powiadomienia
- Wyszukiwarka z filtrowaniem po tagach i słowach kluczowych

## User Stories

### US-001: Utworzenie konta użytkownika

**Jako** nowy użytkownik  
**Chcę** zarejestrować się w systemie  
**Aby** uzyskać dostęp do funkcji generowania fiszek przez AI i zarządzania własną kolekcją

**Warunki akceptacji:**
- Formularz rejestracyjny wymaga podania e-maila i hasła
- Po weryfikacji poprawności danych konto zostaje utworzone
- Użytkownik automatycznie loguje się po pomyślnej rejestracji i widzi komunikat potwierdzający

---

### US-002: Logowanie

**Jako** użytkownik z kontem  
**Chcę** zalogować się do aplikacji  
**Aby** uzyskać dostęp do mojej kolekcji fiszek i historii generowania

**Warunki akceptacji:**
- Po wprowadzeniu poprawnych danych logowania następuje przekierowanie do panelu generowania
- Nieprawidłowe dane wyświetlają odpowiedni komunikat błędu
- Dane logowania są przechowywane w sposób bezpieczny (hashowanie haseł)

---

### US-003: Generowanie fiszek przez AI

**Jako** zalogowany użytkownik  
**Chcę** wprowadzić tekst i wygenerować propozycje fiszek  
**Aby** szybko stworzyć materiał do nauki bez ręcznego formułowania pytań

**Warunki akceptacji:**
- Panel generowania zawiera obszar tekstowy do wklejenia treści
- Minimalna długość tekstu: 1000 znaków, maksymalna: 10 000 znaków
- Po kliknięciu "Generuj" system komunikuje się z API LLM i prezentuje listę propozycji
- W razie błędu API lub braku odpowiedzi wyświetlany jest komunikat informujący użytkownika

---

### US-004: Przeglądanie i selekcja wygenerowanych fiszek

**Jako** zalogowany użytkownik  
**Chcę** przejrzeć propozycje fiszek i wybrać te, które chcę zachować  
**Aby** mieć kontrolę nad jakością i przydatnością materiału do nauki

**Warunki akceptacji:**
- Wygenerowane fiszki wyświetlane są poniżej formularza generowania
- Każda fiszka ma przyciski: "Zapisz", "Edytuj", "Odrzuć"
- Po wybraniu fiszek do zapisania użytkownik klika "Zapisz wybrane" i są one dodawane do bazy

---

### US-005: Modyfikacja istniejących fiszek

**Jako** zalogowany użytkownik  
**Chcę** edytować zapisane fiszki  
**Aby** poprawić błędy lub dostosować treść do moich potrzeb

**Warunki akceptacji:**
- W sekcji "Moja kolekcja" widoczna jest lista wszystkich fiszek (ręcznych i wygenerowanych)
- Kliknięcie na fiszkę umożliwia przejście do trybu edycji
- Po zapisaniu zmian są one natychmiast aktualizowane w bazie danych

---

### US-006: Usuwanie fiszek

**Jako** zalogowany użytkownik  
**Chcę** usuwać niepotrzebne fiszki  
**Aby** utrzymać porządek w mojej kolekcji

**Warunki akceptacji:**
- W widoku "Moja kolekcja" każda fiszka ma opcję usunięcia
- Przed usunięciem wymagane jest potwierdzenie operacji
- Po potwierdzeniu fiszka jest trwale usuwana z systemu

---

### US-007: Ręczne dodawanie fiszek

**Jako** zalogowany użytkownik  
**Chcę** stworzyć fiszkę samodzielnie  
**Aby** dodać materiał, który nie został wygenerowany automatycznie

**Warunki akceptacji:**
- W sekcji "Moja kolekcja" dostępny jest przycisk "Dodaj nową fiszkę"
- Kliknięcie otwiera formularz z polami: "Pytanie" (przód) i "Odpowiedź" (tył)
- Po zapisaniu nowa fiszka pojawia się na liście

---

### US-008: Nauka z wykorzystaniem algorytmu powtórek

**Jako** zalogowany użytkownik  
**Chcę** uczyć się z moich fiszek w trybie sesji nauki  
**Aby** efektywnie przyswajać wiedzę dzięki metodzie spaced repetition

**Warunki akceptacji:**
- Sekcja "Sesja nauki" wykorzystuje zewnętrzny algorytm do przygotowania sesji
- Na początku wyświetlana jest strona przednia fiszki
- Użytkownik może odkryć odpowiedź i ocenić poziom znajomości zgodnie z wymaganiami algorytmu
- Po ocenie algorytm przechodzi do następnej fiszki w sesji

---

### US-009: Izolacja danych użytkownika

**Jako** zalogowany użytkownik  
**Chcę** mieć gwarancję, że moje fiszki są prywatne  
**Aby** zachować poufność moich materiałów edukacyjnych

**Warunki akceptacji:**
- Dostęp do fiszek ma wyłącznie ich właściciel (zalogowany użytkownik)
- Brak możliwości przeglądania lub współdzielenia fiszek innych użytkowników
- Wszystkie operacje wymagają autoryzacji

## Wskaźniki sukcesu

### Jakość generowania AI
- Docelowo 75% wygenerowanych przez AI fiszek powinno być akceptowanych przez użytkowników
- Minimum 75% wszystkich nowo dodanych fiszek powinno pochodzić z generowania AI (względem całkowitej liczby)

### Aktywność użytkowników
- Monitoring liczby wygenerowanych fiszek w stosunku do liczby zaakceptowanych
- Analiza wskaźnika wykorzystania funkcji generowania w stosunku do ręcznego tworzenia
