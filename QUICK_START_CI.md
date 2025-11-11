# 🚀 CI/CD - Quick Start

## ✅ ZERO KONFIGURACJI POTRZEBNE!

Workflow GitHub Actions działa **automatycznie** po `git push`!

```bash
git add .
git commit -m "Add tests"
git push origin main
```

**To wszystko!** 🎉

---

## 📊 Co się dzieje automatycznie?

Po każdym push do `main` lub `develop`:

1. ✅ GitHub uruchamia workflow
2. ✅ Instaluje PHP 8.3 + dependencies
3. ✅ Generuje klucze JWT
4. ✅ Uruchamia **34 testy** (11 Auth + 17 Flashcard + 6 Unit)
5. ✅ Sprawdza jakość kodu
6. ✅ Generuje coverage report

**Czas:** ~2-3 minuty

**Wynik:** ✅ lub ❌ (widoczny w GitHub Actions tab)

---

## 🔍 Gdzie zobaczyć wyniki?

### GitHub UI
1. Idź do repo na GitHub
2. Kliknij zakładkę **Actions**
3. Zobacz najnowszy workflow run
4. Kliknij żeby zobaczyć szczegółowe logi

### Pull Requests
Status testów pojawi się automatycznie na każdym PR! ✨

---

## 🎯 OPCJONALNIE - Codecov (coverage online)

Jeśli chcesz piękne raporty coverage na Codecov:

### 3 kroki:
1. **Załóż konto**: https://codecov.io/ (login przez GitHub)
2. **Dodaj repo**: znajdź `10xDevs` i skopiuj token
3. **Dodaj secret**: 
   - GitHub → Settings → Secrets and variables → Actions
   - New secret: `CODECOV_TOKEN` = (token z Codecov)

**Gotowe!** Przy następnym push coverage uploaduje się automatycznie.

---

## 🐛 Problem? Sprawdź:

### Workflow nie uruchamia się?
```bash
# Upewnij się że pushowałeś na main/develop
git branch

# Sprawdź czy .github/workflows/tests.yml istnieje
ls -la .github/workflows/
```

### Testy failują na CI ale działają lokalnie?
```bash
# Uruchom testy dokładnie tak jak CI
./run-tests.sh
```

---

## 📚 Więcej informacji

- **Pełny przewodnik**: [GITHUB_CICD_SETUP.md](GITHUB_CICD_SETUP.md)
- **Dokumentacja workflow**: [.github/workflows/README.md](.github/workflows/README.md)
- **Setup testów**: [FINAL_SETUP.md](FINAL_SETUP.md)

---

## 🎊 Podsumowanie

### Musisz:
```bash
git push  # To wszystko!
```

### Możesz opcjonalnie:
- 🎯 Dodać Codecov (coverage online)
- 🏷️ Dodać badges do README

### Nie musisz:
- ❌ Konfigurować niczego w GitHub
- ❌ Instalować niczego na serwerze
- ❌ Ustawiać secrets (oprócz Codecov jeśli chcesz)

**Workflow jest gotowy! Zrób push i zobacz magię! ✨**

