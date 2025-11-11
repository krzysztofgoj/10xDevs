# 🚀 Instrukcja konfiguracji OpenAI API

## Krok 1: Uzyskaj klucz API OpenAI

1. **Przejdź do:** https://platform.openai.com/api-keys
2. **Zaloguj się** lub utwórz konto OpenAI
3. **Kliknij:** "Create new secret key"
4. **Nadaj nazwę:** np. "10x Cards Production"
5. **Skopiuj klucz** (zaczyna się od `sk-proj-...` lub `sk-...`)
   - ⚠️ **WAŻNE:** Klucz pokazuje się tylko raz! Zapisz go bezpiecznie

## Krok 2: Dodaj klucz do aplikacji

### Opcja A: Lokalnie (development)

Utwórz plik `.env.local` (jeśli nie istnieje):

```bash
# W głównym katalogu projektu
touch .env.local
```

Dodaj do `.env.local`:

```env
###> OpenAI Configuration ###
OPENAI_API_KEY=sk-proj-TWÓJ-KLUCZ-TUTAJ
###< OpenAI Configuration ###
```

### Opcja B: Docker/Produkcja

Dodaj zmienną środowiskową w `docker-compose.yml`:

```yaml
services:
  php:
    environment:
      OPENAI_API_KEY: ${OPENAI_API_KEY}
```

Następnie utwórz `.env` w głównym katalogu:

```env
OPENAI_API_KEY=sk-proj-TWÓJ-KLUCZ-TUTAJ
```

## Krok 3: Ustaw limity kosztów w OpenAI (WAŻNE!)

1. **Przejdź do:** https://platform.openai.com/account/billing/limits
2. **Ustaw "Hard limit"** (np. $10 miesięcznie)
3. **Ustaw "Soft limit"** (np. $5 miesięcznie)
4. **Włącz email notifications** przy zbliżaniu się do limitu

## Krok 4: Dodaj kartę płatniczą (wymagane)

OpenAI wymaga karty nawet dla małych użyć:

1. **Przejdź do:** https://platform.openai.com/account/billing/payment-methods
2. **Dodaj kartę** (Visa/Mastercard/AMEX)
3. **Zweryfikuj** poprzez mały test charge ($1)

## Krok 5: Sprawdź czy działa

### A. Wyczyść cache Symfony:

```bash
docker exec be66879af885 php bin/console cache:clear
```

### B. Przetestuj generowanie:

1. Zaloguj się do aplikacji: http://localhost:8080/login
2. Przejdź do: **Menu → Dodaj → Generuj z AI**
3. Wklej tekst (minimum 5 słów)
4. Kliknij **"Generuj fiszki"**

Jeśli działa - zobaczysz 3-10 fiszek! 🎉

### C. Sprawdź logi:

```bash
docker exec be66879af885 tail -f var/log/dev.log | grep OpenAI
```

Powinny pojawić się linie typu:
```
[info] OpenAI: Generating flashcards
[info] OpenAI: Flashcards generated successfully
```

## Krok 6: Monitoruj koszty

### W aplikacji:

Logi pokażą szacowane koszty:
```
tokens_used: 287
estimated_cost_usd: 0.0001234
```

### W dashboard OpenAI:

https://platform.openai.com/usage

Sprawdzaj codziennie/tygodniowo ile wydajesz.

---

## 🔒 Zabezpieczenia (już wbudowane!)

Aplikacja ma **5 poziomów** ochrony przed wysokimi kosztami:

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

---

## 🧪 Testowanie bez kosztów

Jeśli chcesz testować bez wydawania pieniędzy, przełącz na Mock:

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

Potem:
```bash
docker exec be66879af885 php bin/console cache:clear
```

---

## 🚨 Troubleshooting

### Problem: "Invalid API key"

**Rozwiązanie:**
```bash
# Sprawdź .env.local
cat .env.local | grep OPENAI

# Sprawdź czy klucz jest poprawny (zaczyna się od sk-)
# Sprawdź czy nie ma spacji na początku/końcu
# Wyczyść cache
docker exec be66879af885 php bin/console cache:clear
```

### Problem: "Rate limit exceeded"

**Rozwiązanie:**
- To limit **aplikacji** (10/h na użytkownika)
- Poczekaj godzinę
- Lub zwiększ limit w `config/packages/rate_limiter.yaml`

### Problem: "Daily cost limit reached"

**Rozwiązanie:**
- Limit aplikacji osiągnięty ($5/dzień)
- Poczekaj do następnego dnia
- Lub zwiększ limit w `src/Service/OpenAICostTracker.php`

### Problem: Wolne generowanie

**To normalne!** OpenAI API zajmuje 2-5 sekund.

---

## ✅ Checklist przed uruchomieniem

- [ ] Mam klucz API OpenAI
- [ ] Dodałem klucz do `.env.local`
- [ ] Ustawiłem hard limit w OpenAI dashboard
- [ ] Dodałem kartę płatniczą w OpenAI
- [ ] Wyczyszczono cache
- [ ] Przetestowano generowanie
- [ ] Sprawdzono logi
- [ ] `.env.local` jest w `.gitignore` ⚠️

---

## 📚 Dodatkowe zasoby

- **OpenAI Dashboard:** https://platform.openai.com
- **Usage & Billing:** https://platform.openai.com/usage
- **API Keys:** https://platform.openai.com/api-keys
- **Dokumentacja:** https://platform.openai.com/docs
- **Pricing:** https://openai.com/api/pricing

---

## 💡 Wskazówki

1. **Zacznij od małych limitów** ($5-10) i zwiększaj w miarę potrzeb
2. **Monitoruj usage** pierwszego tygodnia codziennie
3. **Nie udostępniaj klucza API** nikomu
4. **Rotuj klucze** co kilka miesięcy dla bezpieczeństwa
5. **Backup klucza** w bezpiecznym miejscu (password manager)

---

## 🎉 Gotowe!

Po wykonaniu tych kroków Twoja aplikacja będzie generować fiszki używając prawdziwego AI!

Jeśli masz pytania - sprawdź dokumentację w `docs/OPENAI_SECURITY.md`



