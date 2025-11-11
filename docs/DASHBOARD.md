# Dashboard - Strona główna po zalogowaniu

## 📱 Przegląd funkcjonalności

Nowy dashboard jest nowoczesnym, przyjaznym dla użytkownika interfejsem, który służy jako główny punkt kontrolny aplikacji 10x Cards.

## 🎨 Design & UI/UX

### Główne elementy:

1. **Powitanie użytkownika**
   - Wyświetla spersonalizowane powitanie z imieniem użytkownika (pierwsza część emaila)
   - Zachęcająca wiadomość motywacyjna

2. **Duże karty akcji** (Main Action Cards)
   - **Rozpocznij naukę** - Prowadzi do wyboru trybu nauki
     - Aktywna gdy użytkownik ma fiszki
     - Wyłączona (greyed out) gdy brak fiszek
     - Animacja hover z podnoszeniem karty
   - **Moje fiszki** - Prowadzi do listy wszystkich fiszek
     - Pokazuje liczbę fiszek jako badge
     - Zawsze aktywna

3. **Statystyki**
   - **Wszystkich fiszek** - Łączna liczba fiszek użytkownika
   - **Wygenerowanych AI** - Fiszki stworzone przez AI
   - **Dodanych ręcznie** - Fiszki dodane manualnie
   - Karty statystyk z efektem hover (scale)

4. **Szybkie akcje** (Quick Actions)
   - Generuj fiszki z AI
   - Dodaj fiszkę ręcznie
   - Przyciski z animacją przesunięcia przy hover

5. **Wskazówki dla użytkownika**
   - Dynamiczne komunikaty zależne od liczby fiszek:
     - 0 fiszek: Wskazówka dla początkujących
     - 1-4 fiszki: Motywacja do dodania więcej
     - 5+ fiszek: Zachęta do nauki

## 🛣️ Routing

- **URL**: `/dashboard`
- **Route name**: `app_dashboard`
- **Kontroler**: `SecurityController::dashboard()`

### Przekierowania:
- Po zalogowaniu → Dashboard
- Strona główna (`/`) gdy użytkownik zalogowany → Dashboard
- Logo aplikacji → Dashboard (dla zalogowanych użytkowników)

## 🎯 Zasady UX

1. **Hierarchia wizualna**
   - Największe i najbardziej widoczne elementy to główne akcje (Nauka, Fiszki)
   - Statystyki w drugiej kolejności
   - Szybkie akcje jako uzupełnienie

2. **Feedback wizualny**
   - Wszystkie interaktywne elementy mają animacje hover
   - Wyraźne wskazówki co jest klikalne
   - Disabled state dla niedostępnych akcji

3. **Responsywność**
   - Layout dostosowuje się do rozmiaru ekranu
   - Karty układają się w kolumny na mniejszych ekranach

4. **Dostępność**
   - Wyraźne ikony z tekstem
   - Odpowiednie kontrasty kolorów
   - Logiczna struktura nawigacji

## 🎨 Kolory i styl

- **Gradient tła**: Fiolet (667eea) → Purpura (764ba2)
- **Karta nauki**: Niebieski akcent
- **Karta fiszek**: Zielony akcent
- **Statystyki**: Białe karty z kolorowymi akcentami
- **Shadow**: Różne poziomy dla głębi wizualnej

## 📊 Dane przekazywane do widoku

```php
[
    'jwt_token' => string,           // Token JWT dla API
    'total_flashcards' => int,       // Łączna liczba fiszek
    'ai_generated' => int,           // Liczba fiszek AI
    'manual_flashcards' => int       // Liczba fiszek manualnych
]
```

## 🔗 Powiązane pliki

- **Kontroler**: `src/Controller/SecurityController.php`
- **Widok**: `templates/dashboard/index.html.twig`
- **Layout**: `templates/base.html.twig`
- **Security config**: `config/packages/security.yaml`

## 💡 Najlepsze praktyki zastosowane

1. ✅ **Progressive disclosure** - Pokazywanie informacji gdy są potrzebne
2. ✅ **Visual hierarchy** - Ważniejsze elementy są większe i bardziej widoczne
3. ✅ **Feedback** - Użytkownik wie co się dzieje (hover, disabled states)
4. ✅ **Consistency** - Spójny design w całej aplikacji
5. ✅ **Error prevention** - Wyłączenie przycisku nauki gdy brak fiszek
6. ✅ **Recognition over recall** - Ikony + tekst, jasne etykiety
7. ✅ **Aesthetic and minimalist design** - Tylko potrzebne informacje
8. ✅ **Flexibility and efficiency** - Szybki dostęp do wszystkich funkcji

## 🚀 Future improvements

Możliwe przyszłe rozszerzenia:
- Wykres postępów w nauce
- Ostatnio dodane fiszki (preview)
- Streak counter (dni nauki z rzędu)
- Rekomendacje co warto powtórzyć
- Cele i achievements

