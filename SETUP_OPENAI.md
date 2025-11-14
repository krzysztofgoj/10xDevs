# 🚀 Wymagania konfiguracji OpenAI API

## Krok 1: Klucz API OpenAI

System powinien wymagać klucza API OpenAI:
- Klucz powinien być pobierany z https://platform.openai.com/api-keys
- Klucz powinien zaczynać się od `sk-proj-...` lub `sk-...`
- ⚠️ **WAŻNE:** Klucz pokazuje się tylko raz! Powinien być zapisany bezpiecznie

## Krok 2: Konfiguracja zmiennych środowiskowych

### Opcja A: Lokalnie (development)

System powinien używać pliku `.env.local` (jeśli nie istnieje) z następującą zawartością:

```env
###> OpenAI Configuration ###
OPENAI_API_KEY=sk-proj-TWÓJ-KLUCZ-TUTAJ
###< OpenAI Configuration ###
```

### Opcja B: Docker/Produkcja

System powinien używać zmiennej środowiskowej w `docker-compose.yml`:

```yaml
services:
  php:
    environment:
      OPENAI_API_KEY: ${OPENAI_API_KEY}
```

Następnie plik `.env` w głównym katalogu powinien zawierać:

```env
OPENAI_API_KEY=sk-proj-TWÓJ-KLUCZ-TUTAJ
```

## Krok 3: Limity kosztów w OpenAI (WAŻNE!)

System powinien wymagać ustawienia limitów w OpenAI:
- **Hard limit** (np. $10 miesięcznie)
- **Soft limit** (np. $5 miesięcznie)
- **Email notifications** przy zbliżaniu się do limitu

Limity powinny być ustawiane w: https://platform.openai.com/account/billing/limits

## Krok 4: Karta płatnicza (wymagane)

OpenAI wymaga karty nawet dla małych użyć:
- Karta powinna być dodana w: https://platform.openai.com/account/billing/payment-methods
- Karta powinna być zweryfikowana poprzez mały test charge ($1)

## Krok 5: Weryfikacja działania

System powinien umożliwiać sprawdzenie czy integracja działa:
- Po konfiguracji powinno być możliwe wygenerowanie fiszek
- Logi powinny pokazywać wywołania API
- Dashboard OpenAI powinien pokazywać użycie

## 🔒 Zabezpieczenia (wbudowane w system)

Aplikacja powinna mieć **5 poziomów** ochrony przed wysokimi kosztami:

1. ✅ **Rate Limiting:** 10 zapytań/godzinę na użytkownika
2. ✅ **Daily Limit:** $5/dzień (w kodzie)
3. ✅ **Monthly Limit:** $50/miesiąc (w kodzie)
4. ✅ **Tani model:** gpt-4o-mini (~99% taniej niż gpt-4)
5. ✅ **Token Limit:** max 2000 tokenów na odpowiedź

**Szacowane koszty przy normalnym użyciu:**
- 1 generowanie: ~$0.0001 (0.01 centa)
- 100 generowań: ~$0.01 (1 cent)
- 1000 generowań: ~$0.10 (10 centów)

**Z limitami** (10/h): maksymalnie ~$0.72/miesiąc 💰

## 🧪 Testowanie bez kosztów

Jeśli chcesz testować bez wydawania pieniędzy, system powinien umożliwiać przełączenie na Mock:

### W pliku `config/services.yaml`:

```yaml
# Zakomentuj OpenAI:
# App\Service\FlashcardGeneratorInterface:
#     class: App\Service\OpenAIFlashcardGenerator
#     arguments:
#         $maxDailyRequests: 100

# Odkomentuj Mock:
App\Service\FlashcardGeneratorInterface:
    class: App\Service\MockFlashcardGenerator
```

Potem powinno być wyczyszczone cache.

## 🚨 Troubleshooting

### Problem: "Invalid API key"

**Rozwiązanie:**
- Sprawdź `.env.local`
- Sprawdź czy klucz jest poprawny (zaczyna się od sk-)
- Sprawdź czy nie ma spacji na początku/końcu
- Wyczyść cache

### Problem: "Rate limit exceeded"

**Rozwiązanie:**
- To limit aplikacji (10/h na użytkownika)
- Poczekaj godzinę
- Lub zwiększ limit w `config/packages/rate_limiter.yaml`

### Problem: "Daily cost limit reached"

**Rozwiązanie:**
- Limit aplikacji osiągnięty ($5/dzień)
- Poczekaj do następnego dnia
- Lub zwiększ limit w `src/Service/OpenAICostTracker.php`

### Problem: Wolne generowanie

**To normalne!** OpenAI API zajmuje 2-5 sekund.

## ✅ Checklist przed uruchomieniem

- [ ] Mam klucz API OpenAI
- [ ] Dodałem klucz do `.env.local`
- [ ] Ustawiłem hard limit w OpenAI dashboard
- [ ] Dodałem kartę płatniczą w OpenAI
- [ ] Wyczyszczono cache
- [ ] Przetestowano generowanie
- [ ] Sprawdzono logi
- [ ] `.env.local` jest w `.gitignore` ⚠️

## 📚 Dodatkowe zasoby

- **OpenAI Dashboard:** https://platform.openai.com
- **Usage & Billing:** https://platform.openai.com/usage
- **API Keys:** https://platform.openai.com/api-keys
- **Dokumentacja:** https://platform.openai.com/docs
- **Pricing:** https://openai.com/api/pricing

## 💡 Wskazówki

1. **Zacznij od małych limitów** ($5-10) i zwiększaj w miarę potrzeb
2. **Monitoruj usage** pierwszego tygodnia codziennie
3. **Nie udostępniaj klucza API** nikomu
4. **Rotuj klucze** co kilka miesięcy dla bezpieczeństwa
5. **Backup klucza** w bezpiecznym miejscu (password manager)

## 🎉 Gotowe!

Po wykonaniu tych kroków aplikacja powinna być gotowa do generowania fiszek używając prawdziwego AI!

Jeśli masz pytania - sprawdź dokumentację w `docs/OPENAI_SECURITY.md`
