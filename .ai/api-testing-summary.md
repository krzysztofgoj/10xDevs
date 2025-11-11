# Podsumowanie testowania endpointów autoryzacji

## Gotowe do testowania

### Endpointy
- ✅ `POST /api/register` - Rejestracja użytkownika
- ✅ `POST /api/login` - Logowanie użytkownika

### Pliki utworzone
- ✅ `src/Service/AuthService.php` - Logika biznesowa autoryzacji
- ✅ `src/Controller/Api/AuthController.php` - Kontroler endpointów
- ✅ `test-auth.sh` - Skrypt testowy (bash)
- ✅ `.ai/api-testing-guide.md` - Dokumentacja testowa

## Szybki start - testowanie

### Opcja 1: Użyj skryptu testowego
```bash
./test-auth.sh http://localhost:8080
```

### Opcja 2: Testy ręczne z curl

**1. Rejestracja:**
```bash
curl -X POST http://localhost:8080/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**2. Logowanie:**
```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

## Oczekiwane odpowiedzi

### Sukces rejestracji (201 Created):
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "userId": 1,
  "email": "test@example.com"
}
```

### Sukces logowania (200 OK):
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "userId": 1,
  "email": "test@example.com"
}
```

### Błędy:
- `400 Bad Request` - błędy walidacji
- `401 Unauthorized` - nieprawidłowe dane logowania
- `409 Conflict` - użytkownik już istnieje

## Wymagania przed testowaniem

1. ✅ Zainstalowane zależności (`composer install`)
2. ✅ Wygenerowane klucze JWT (`php bin/console lexik:jwt:generate-keypair`)
3. ✅ Uruchomione migracje (`php bin/console doctrine:migrations:migrate`)
4. ✅ Wyczyszczony cache (`php bin/console cache:clear`)

## Następne kroki

Po pomyślnym przetestowaniu endpointów autoryzacji:
1. Utworzenie FlashcardService
2. Utworzenie FlashcardController z endpointami CRUD
3. Implementacja endpointów generowania fiszek przez AI

