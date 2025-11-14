# 🚀 GitHub CI/CD - Wymagania Setup

## Wymagania automatycznego działania

Workflow GitHub Actions powinien być gotowy i działać automatycznie po push.

### Co powinno się dziać automatycznie?

1. ✅ GitHub powinien uruchomić workflow z `.github/workflows/tests.yml`
2. ✅ Zainstalować PHP 8.3 z rozszerzeniami
3. ✅ Zainstalować Composer dependencies
4. ✅ Wygenerować klucze JWT (testpassphrase)
5. ✅ Uruchomić wszystkie testy
6. ✅ Sprawdzić jakość kodu
7. ✅ Wygenerować coverage report

### Gdzie zobaczyć wyniki?

- W GitHub: zakładka **Actions**
- Każdy push/PR powinien pokazać status ✅ lub ❌

---

## 🔧 OPCJONALNIE - Codecov (raport coverage online)

Jeśli chcesz mieć raporty coverage na Codecov:

### Krok 1: Załóż konto Codecov

1. Idź na https://codecov.io/
2. Kliknij **Sign up with GitHub**
3. Zaloguj się przez GitHub
4. Zaakceptuj permissions

### Krok 2: Dodaj repo

1. W Codecov kliknij **Add new repository**
2. Znajdź `10xDevs` na liście
3. Kliknij **Setup repo**
4. Skopiuj **Codecov Upload Token** (wyświetli się na ekranie)

### Krok 3: Dodaj secret w GitHub

1. W GitHub repo: **Settings** → **Secrets and variables** → **Actions**
2. Kliknij **New repository secret**
3. Wpisz:
   - **Name**: `CODECOV_TOKEN`
   - **Value**: (wklej token z Codecov)
4. Kliknij **Add secret**

### Krok 4: Gotowe! 🎉

Przy następnym push workflow powinien automatycznie uploadować coverage do Codecov.

#### Gdzie zobaczyć raport?

- Codecov dashboard: https://codecov.io/gh/YOUR_USERNAME/10xDevs
- Badge w README (opcjonalnie)

---

## 📊 Badge w README (opcjonalnie)

### Status testów

Dodaj do README.md:
```markdown
![Tests](https://github.com/YOUR_USERNAME/10xDevs/workflows/Tests/badge.svg)
```

### Coverage z Codecov

Dodaj do README.md (jeśli skonfigurowałeś Codecov):
```markdown
[![codecov](https://codecov.io/gh/YOUR_USERNAME/10xDevs/branch/main/graph/badge.svg)](https://codecov.io/gh/YOUR_USERNAME/10xDevs)
```

Zastąp `YOUR_USERNAME` swoim GitHub username.

---

## 🔍 Jak sprawdzić czy workflow działa?

### Metoda 1: Zrób pusty commit

```bash
git commit --allow-empty -m "Test CI/CD"
git push
```

### Metoda 2: Zobacz Actions

1. W GitHub repo kliknij zakładkę **Actions**
2. Zobaczysz listę workflow runs
3. Kliknij na najnowszy run żeby zobaczyć logi

### Metoda 3: Pull Request

Każdy PR powinien automatycznie uruchomić testy i pokazać status.

---

## ⚙️ Konfiguracja workflow (zaawansowane)

### Zmiana gałęzi dla CI/CD

Domyślnie workflow powinien uruchamiać się na `main` i `develop`.

Żeby zmienić, edytuj `.github/workflows/tests.yml`:
```yaml
on:
  push:
    branches: [ main, develop, feature/* ]  # Dodaj więcej branchy
  pull_request:
    branches: [ main, develop ]
```

### Dodaj więcej wersji PHP

Domyślnie testujemy tylko PHP 8.3. Żeby dodać więcej:
```yaml
strategy:
  matrix:
    php-version: ['8.2', '8.3']  # Dodaj więcej wersji
```

### Wyłącz coverage (szybsze testy)

W `.github/workflows/tests.yml` zamień:
```yaml
- name: Run PHPUnit tests
  run: vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml
```
na:
```yaml
- name: Run PHPUnit tests
  run: vendor/bin/phpunit
```

---

## 🐛 Troubleshooting

### Problem: Workflow nie uruchamia się

**Rozwiązanie:**
- Upewnij się że pushowałeś na branch `main` lub `develop`
- Sprawdź czy `.github/workflows/tests.yml` istnieje w repo
- W Settings → Actions sprawdź czy Actions są enabled

### Problem: Testy failują na CI ale działają lokalnie

**Rozwiązanie:**
```bash
# Sprawdź czy wszystkie zmiany są w git
git status

# Uruchom testy lokalnie dokładnie tak jak CI
./run-tests.sh
```

### Problem: Codecov upload fails

**Rozwiązanie:**
- To normalne jeśli nie skonfigurowałeś `CODECOV_TOKEN`
- Workflow powinien mieć `continue-on-error: true` więc testy i tak przejdą
- Jeśli chcesz Codecov - dodaj secret (patrz wyżej)

---

## 📝 Podsumowanie

### Musisz zrobić:
- ✅ `git push` - **to wszystko!**

### Możesz opcjonalnie:
- 🎯 Skonfigurować Codecov (coverage online)
- 🏷️ Dodać badges do README
- ⚙️ Dostosować workflow (inne branche, więcej PHP versions)

### Nie musisz:
- ❌ Instalować niczego na serwerze GitHub
- ❌ Konfigurować secrets (oprócz Codecov jeśli chcesz)
- ❌ Ustawiać niczego w Settings (jeśli Actions są enabled)

---

## 🎉 Gotowe!

Workflow powinien być **w pełni gotowy** i działać automatycznie przy każdym push/PR.

Wystarczy zrobić:
```bash
git push
```

I zobaczyć magię! ✨

**Sprawdź**: GitHub → zakładka **Actions**
