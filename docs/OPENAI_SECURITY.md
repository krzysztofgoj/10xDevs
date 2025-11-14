# 🔒 Zabezpieczenia OpenAI API

## Wielowarstwowa ochrona przed kosztami

System powinien zawierać **5 poziomów** zabezpieczeń przed przekroczeniem budżetu:

### 1️⃣ Rate Limiting (poziom użytkownika)

**Limit:** 10 zapytań na godzinę na użytkownika

```yaml
# config/packages/rate_limiter.yaml
flashcard_generator:
    policy: 'sliding_window'
    limit: 10
    interval: '1 hour'
```

**Jak powinno działać:**
- Każdy użytkownik może wygenerować maksymalnie 10 razy na godzinę
- Sliding window = płynnie przez godzinę, nie reset o pełnej godzinie
- Po przekroczeniu: HTTP 429 Too Many Requests

### 2️⃣ Cost Tracking (poziom aplikacji)

**Limity:**
- **Dzienny:** $5.00 USD
- **Miesięczny:** $50.00 USD

```php
// src/Service/OpenAICostTracker.php
private const DAILY_LIMIT_USD = 5.00;
private const MONTHLY_LIMIT_USD = 50.00;
```

**Jak powinno działać:**
- Każde wywołanie API powinno być śledzone
- Koszty powinny być szacowane na podstawie użytych tokenów
- Po przekroczeniu limitu: HTTP 503 Service Unavailable
- Powinno resetować się automatycznie każdego dnia/miesiąca

### 3️⃣ Model Selection (oszczędność)

**Powinien używać:** `gpt-4o-mini` zamiast `gpt-4`

```php
private const MODEL = 'gpt-4o-mini';
```

**Różnica w kosztach:**
- `gpt-4o-mini`: ~$0.15-0.60 / 1M tokenów
- `gpt-4`: ~$30-60 / 1M tokenów

**Oszczędność:** ~99% kosztów przy podobnej jakości

### 4️⃣ Token Limiting (maksymalna długość)

**Limit:** 2000 tokenów na odpowiedź

```php
private const MAX_TOKENS = 2000;
```

**Jak powinno działać:**
- Ogranicza maksymalną długość odpowiedzi od OpenAI
- Zapobiega długim (i drogim) odpowiedziom
- ~3-10 fiszek mieści się w tym limicie

### 5️⃣ Logging & Monitoring (widoczność)

**Co powinno być logowane:**
- ✅ Każde wywołanie API (start + koniec)
- ✅ Użyte tokeny
- ✅ Szacowany koszt w USD
- ✅ Czas trwania zapytania
- ✅ Błędy i problemy

**Gdzie sprawdzić logi:**
```bash
# W kontenerze
tail -f var/log/dev.log | grep OpenAI

# Lub lokalnie
docker exec <container> tail -f var/log/dev.log | grep OpenAI
```

---

## 📊 Monitoring kosztów

### Sprawdź aktual użycie

System powinien umożliwiać sprawdzenie użycia przez endpoint administracyjny (opcjonalnie):

```php
#[Route('/admin/openai-stats', name: 'admin_openai_stats')]
#[IsGranted('ROLE_ADMIN')]
public function openAiStats(OpenAICostTracker $costTracker): JsonResponse
{
    return new JsonResponse($costTracker->getUsageStats());
}
```

Odpowiedź:
```json
{
  "daily": {
    "used_usd": 0.0234,
    "limit_usd": 5.00,
    "remaining_usd": 4.9766,
    "percentage_used": 0.47
  },
  "monthly": {
    "used_usd": 1.2345,
    "limit_usd": 50.00,
    "remaining_usd": 48.7655,
    "percentage_used": 2.47
  }
}
```

### Dashboard OpenAI

System powinien wymagać sprawdzania rzeczywistych kosztów w dashboard OpenAI:
- https://platform.openai.com/usage

**Ustawianie limitów w OpenAI:**
1. Idź do: https://platform.openai.com/account/billing/limits
2. Ustaw "Hard limit" na np. $10
3. OpenAI **zatrzyma** API po przekroczeniu

---

## ⚙️ Konfiguracja limitów

### Zmiana limitów aplikacji

System powinien umożliwiać zmianę limitów w `src/Service/OpenAICostTracker.php`:

```php
private const DAILY_LIMIT_USD = 5.00;    // ← Zmień tutaj
private const MONTHLY_LIMIT_USD = 50.00; // ← Zmień tutaj
```

### Zmiana rate limitów

System powinien umożliwiać zmianę limitów w `config/packages/rate_limiter.yaml`:

```yaml
flashcard_generator:
    limit: 10        # ← Zmień liczbę zapytań
    interval: '1 hour' # ← Zmień okres
```

### Zmiana modelu AI

System powinien umożliwiać zmianę modelu w `src/Service/OpenAIFlashcardGenerator.php`:

```php
private const MODEL = 'gpt-4o-mini'; // ← Zmień na:
// 'gpt-4o-mini'     - najtańszy, dobra jakość ✅
// 'gpt-4o'          - droższy, lepsza jakość
// 'gpt-4-turbo'     - jeszcze droższy
// 'gpt-4'           - najdroższy
```

---

## 🧪 Testowanie bez kosztów

### Przełącz na Mock Generator

System powinien umożliwiać przełączenie na Mock w `config/services.yaml`:

```yaml
# Zakomentuj OpenAI
# App\Service\FlashcardGeneratorInterface:
#     class: App\Service\OpenAIFlashcardGenerator

# Odkomentuj Mock
App\Service\FlashcardGeneratorInterface:
    class: App\Service\MockFlashcardGenerator
```

### Wyczyść cache

```bash
docker exec <container> php bin/console cache:clear
```

---

## 📈 Szacowanie kosztów

### Przykładowe koszty (gpt-4o-mini)

| Akcja | Tokeny | Koszt USD |
|-------|--------|-----------|
| 1 generowanie (100 słów tekstu) | ~300 | $0.0001 |
| 10 generowań | ~3000 | $0.001 |
| 100 generowań | ~30000 | $0.01 |
| 1000 generowań | ~300000 | $0.10 |
| 10000 generowań | ~3M | $1.00 |

**Z limitami:**
- 10 zapytań/godzinę × 24h = 240 zapytań/dzień
- 240 × $0.0001 = ~$0.024/dzień
- ~$0.72/miesiąc

**Wniosek:** Przy normalnym użyciu koszty powinny być minimalne.

---

## 🚨 Alarmy i powiadomienia

### Email przy wysokim użyciu (opcjonalnie)

System może zawierać powiadomienia przy wysokim użyciu:

```php
public function recordUsage(float $costUsd, int $tokensUsed): void
{
    // ... existing code ...
    
    // Alert przy 80% limitu dziennego
    if ($dailyUsage / self::DAILY_LIMIT_USD >= 0.8) {
        $this->notifier->send(new Notification(
            'OpenAI: Osiągnięto 80% dziennego limitu!',
            ['email']
        ));
    }
}
```

---

## 🔧 Troubleshooting

### Problem: "Daily cost limit reached"

**Rozwiązanie:**
1. Sprawdź logi: `var/log/dev.log`
2. Sprawdź usage: `$costTracker->getUsageStats()`
3. Zwiększ limit lub poczekaj do następnego dnia

### Problem: "Too many requests"

**Rozwiązanie:**
- Użytkownik przekroczył 10 zapytań/h
- Poczekaj godzinę lub zwiększ limit w `rate_limiter.yaml`

### Problem: "Invalid API key"

**Rozwiązanie:**
1. Sprawdź czy `OPENAI_API_KEY` w `.env` jest prawidłowy
2. Sprawdź czy key nie wygasł w dashboard OpenAI
3. Wyczyść cache: `php bin/console cache:clear`

---

## ✅ Checklist przed produkcją

- [ ] Ustaw hard limit w OpenAI dashboard ($10-20)
- [ ] Sprawdź czy `.env` nie jest w git (powinien być w .gitignore)
- [ ] Przetestuj rate limiting (spróbuj 11 razy w godzinę)
- [ ] Sprawdź logi czy działają
- [ ] Ustal monitoring kosztów (email/slack)
- [ ] Dokumentuj limitów dla użytkowników
- [ ] Backup klucza API (w bezpiecznym miejscu)

---

## 📚 Dodatkowe zasoby

- [OpenAI Pricing](https://openai.com/api/pricing/)
- [OpenAI Usage Dashboard](https://platform.openai.com/usage)
- [OpenAI Billing Settings](https://platform.openai.com/account/billing/limits)
- [Rate Limiting Best Practices](https://platform.openai.com/docs/guides/rate-limits)
