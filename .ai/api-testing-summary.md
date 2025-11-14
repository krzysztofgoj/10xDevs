# Wymagania dotyczące testowania endpointów autoryzacji

## Endpointy do testowania

### Endpointy
- `POST /api/register` - Rejestracja użytkownika
- `POST /api/login` - Logowanie użytkownika

## Wymagania przed testowaniem

1. ✅ Zainstalowane zależności (`composer install`)
2. ✅ Wygenerowane klucze JWT
3. ✅ Uruchomione migracje (`php bin/console doctrine:migrations:migrate`)
4. ✅ Wyczyszczony cache (`php bin/console cache:clear`)

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

## Następne kroki

Po pomyślnym przetestowaniu endpointów autoryzacji:
1. Utworzenie FlashcardService
2. Utworzenie FlashcardController z endpointami CRUD
3. Implementacja endpointów generowania fiszek przez AI
