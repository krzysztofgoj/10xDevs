# 🤖 Integracja z OpenAI

## Wymagania integracji

System powinien być zintegrowany z OpenAI API i zawierać:
- Profesjonalny prompt dla generowania fiszek
- Rate limiting (10 zapytań/godzinę na użytkownika)
- Monitoring kosztów ($5/dzień, $50/miesiąc)
- Logowanie wszystkich wywołań API
- Automatyczne szacowanie kosztów
- Możliwość przełączenia na Mock do testów

## Wymagania konfiguracji

### Krok 1: Klucz API OpenAI

System powinien wymagać klucza API OpenAI:
- Klucz powinien być pobierany z https://platform.openai.com/api-keys
- Klucz powinien zaczynać się od `sk-`
- Klucz powinien być przechowywany w zmiennych środowiskowych

### Krok 2: Zmienne środowiskowe

System powinien używać następujących zmiennych środowiskowych:

```env
OPENAI_API_KEY=sk-proj-TWÓJ-KLUCZ-TUTAJ
OPENAI_MODEL=gpt-4o-mini
```

⚠️ **WAŻNE:** 
- Klucz API nie powinien być commitowany do repozytorium
- `.env.local` powinien być w `.gitignore`
- W produkcji powinny być używane zmienne środowiskowe serwera

### Krok 3: Konfiguracja serwisu

System powinien umożliwiać konfigurację generatora fiszek w `config/services.yaml`:

```yaml
services:
    # OpenAI Client
    OpenAI\Client:
        factory: ['OpenAI', 'client']
        arguments:
            - '%env(OPENAI_API_KEY)%'

    # Flashcard generator - OpenAI implementation
    App\Service\FlashcardGeneratorInterface:
        class: App\Service\OpenAIFlashcardGenerator
        arguments:
            $model: '%env(default:gpt-4o-mini:OPENAI_MODEL)%'
```

## Wymagania implementacji

### OpenAIFlashcardGenerator

System powinien zawierać klasę `OpenAIFlashcardGenerator` która:
- Implementuje interfejs `FlashcardGeneratorInterface`
- Używa OpenAI Client do generowania fiszek
- Generuje 3-10 fiszek na podstawie tekstu źródłowego
- Zwraca fiszki w formacie `GeneratedFlashcardResponse`

### Prompt engineering

System powinien używać profesjonalnego promptu który:
- Instruuje model do generowania fiszek edukacyjnych
- Wymaga mieszania języków (polski i angielski)
- Skupia się na kluczowych koncepcjach i faktach
- Zwraca dane w formacie JSON

### Przełączanie między Mock a OpenAI

System powinien umożliwiać łatwe przełączanie między trybami:

**Mock (dla testów):**
```yaml
App\Service\FlashcardGeneratorInterface:
    class: App\Service\MockFlashcardGenerator
```

**OpenAI (produkcja):**
```yaml
App\Service\FlashcardGeneratorInterface:
    class: App\Service\OpenAIFlashcardGenerator
    arguments:
        $model: '%env(default:gpt-4o-mini:OPENAI_MODEL)%'
```

## Koszty API

### Wymagania dotyczące modelu

System powinien używać modelu `gpt-4o-mini` zamiast `gpt-4` ze względu na:
- Znacznie niższe koszty (~99% oszczędności)
- Podobną jakość dla tego przypadku użycia
- Szybsze odpowiedzi

### Szacowanie kosztów

Przy użyciu GPT-4o-mini:
- **Input**: ~$0.15-0.60 / 1M tokenów
- **Output**: ~$0.15-0.60 / 1M tokenów

Przykładowa kalkulacja:
- Tekst źródłowy: 500 słów ≈ 667 tokenów
- Wygenerowane fiszki: ≈ 300 tokenów
- Koszt na zapytanie: ~$0.0001-0.0004

**Zalecenia:**
- Dla środowiska dev powinien być używany Mock generator
- Dla produkcji powinny być ustawione limity rate limiting
- Powinien być monitoring kosztów w dashboard OpenAI

## Rate Limiting

System powinien zawierać rate limiting który:
- Ogranicza liczbę zapytań na użytkownika (10 zapytań/godzinę)
- Używa sliding window policy
- Zwraca HTTP 429 Too Many Requests po przekroczeniu limitu

### Konfiguracja

```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        flashcard_generator:
            policy: 'sliding_window'
            limit: 10
            interval: '1 hour'
```

## Monitorowanie i logi

System powinien logować:
- Każde wywołanie API (start + koniec)
- Użyte tokeny
- Szacowany koszt w USD
- Czas trwania zapytania
- Błędy i problemy

Logi powinny być dostępne w `var/log/dev.log` z tagiem `OpenAI`.

## Wsparcie

W razie problemów:
1. Sprawdź logi Symfony: `var/log/dev.log`
2. Sprawdź dashboard OpenAI: https://platform.openai.com/usage
3. Sprawdź dokumentację: https://platform.openai.com/docs
