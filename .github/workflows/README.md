# GitHub Actions Workflows

## 📋 Dostępne Workflows

### `tests.yml` - Testy i Coverage

**Kiedy uruchamia się:**
- Push do `main` lub `develop`
- Pull Request do `main` lub `develop`

**Co robi:**

#### Job 1: `tests`
1. ✅ Setup PHP 8.3 z rozszerzeniami (pdo_sqlite, intl, mbstring, xml, zip)
2. ✅ Cache Composer dependencies (szybsze buildy) - `actions/cache@v4`
3. ✅ Instaluje dependencies (`composer install`)
4. ✅ Generuje klucze JWT (RSA 4096-bit, passphrase: `testpassphrase`)
5. ✅ Tworzy schemat bazy SQLite in-memory
6. ✅ Uruchamia wszystkie testy (34 testy, 144 assertions)
7. ✅ Generuje coverage report (XML + text)
8. ✅ Uploaduje coverage do Codecov - `codecov-action@v4` (jeśli `CODECOV_TOKEN` jest ustawiony)
9. ✅ Archiwizuje logi i coverage jako artifacts - `upload-artifact@v4`

#### Job 2: `code-quality`
1. ✅ Setup PHP 8.3
2. ✅ Instaluje dependencies
3. ✅ Sprawdza syntax PHP (all `*.php` files)
4. ✅ Waliduje schemat Doctrine

**Czas wykonania:** ~2-3 minuty

**Wymagania:**
- Brak (wszystko działa out-of-the-box)

**Opcjonalne:**
- `CODECOV_TOKEN` secret - dla uploadowania coverage do Codecov

---

## 🎯 Jak zobaczyć wyniki?

### W GitHub UI
1. Idź do zakładki **Actions** w repo
2. Kliknij na workflow run
3. Zobacz logi dla każdego job

### Badge w README
Dodaj do README.md:
```markdown
![Tests](https://github.com/YOUR_USERNAME/10xDevs/workflows/Tests/badge.svg)
```

---

## ⚙️ Konfiguracja

### Zmiana branchy
Edytuj `tests.yml`:
```yaml
on:
  push:
    branches: [ main, develop, staging ]  # Dodaj więcej
```

### Dodaj więcej wersji PHP
```yaml
strategy:
  matrix:
    php-version: ['8.2', '8.3']
```

### Wyłącz coverage (szybsze)
Usuń `--coverage-*` flags z `vendor/bin/phpunit`

---

## 📊 Secrets

### Wymagane
- **Brak** - workflow działa bez żadnych secrets!

### Opcjonalne
- `CODECOV_TOKEN` - token z https://codecov.io/ dla uploadowania coverage

#### Jak dodać secret:
1. GitHub repo → **Settings** → **Secrets and variables** → **Actions**
2. **New repository secret**
3. Name: `CODECOV_TOKEN`, Value: (token z Codecov)

---

## 🔍 Troubleshooting

### Workflow nie uruchamia się
- Sprawdź czy Actions są enabled w Settings → Actions
- Upewnij się że pushujesz na `main` lub `develop`

### Testy failują
- Sprawdź logi w Actions tab
- Uruchom lokalnie: `./run-tests.sh`

### Codecov upload fails
- Normalnie jeśli nie masz `CODECOV_TOKEN` secret
- Ma `continue-on-error: true` więc workflow i tak przejdzie

---

## 📚 Więcej informacji

- **Setup guide**: [GITHUB_CICD_SETUP.md](../../GITHUB_CICD_SETUP.md)
- **Dokumentacja testów**: [tests/README.md](../../tests/README.md)
- **Quick start**: [FINAL_SETUP.md](../../FINAL_SETUP.md)

