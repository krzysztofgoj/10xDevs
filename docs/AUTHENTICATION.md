# 🔐 System Autoryzacji w 10x Cards

Projekt używa **dwóch różnych systemów autoryzacji** w zależności od typu żądania:

---

## 📌 Podsumowanie

| Typ | Endpoint | Metoda | Token/Sesja | Stateless |
|-----|----------|--------|-------------|-----------|
| **API** | `/api/*` | JWT | Authorization Header | ✅ Tak |
| **Web** | `/`, `/login`, `/profile` | Session | Cookie (PHPSESSID) | ❌ Nie |

---

## 1️⃣ API - JWT Authentication (Stateless)

### Konfiguracja

```yaml
# config/packages/security.yaml
firewalls:
    api:
        pattern: ^/api
        stateless: true        # Każde żądanie niezależne
        jwt: ~                 # Używa LexikJWTAuthenticationBundle

access_control:
    - { path: ^/api/login, roles: PUBLIC_ACCESS }
    - { path: ^/api/register, roles: PUBLIC_ACCESS }
    - { path: ^/api, roles: ROLE_USER }              # Wymaga JWT!
```

### Flow JWT

```
1. POST /api/register lub /api/login
   ↓
2. Otrzymujesz JWT token w response:
   {
     "token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
     "userId": 5,
     "email": "user@example.com"
   }
   ↓
3. Używasz tokena w każdym żądaniu:
   GET /api/flashcards
   Header: Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGci...
   ↓
4. Backend weryfikuje token (podpis RSA)
   ↓
5. Dostęp do zasobu ✅
```

### Przykład użycia

```bash
# Rejestracja/Logowanie
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Response
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "userId": 5,
  "email": "user@example.com"
}

# Użycie tokena
curl -X GET http://localhost/api/flashcards \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGci..."
```

### Gdzie jest token?

**❌ Nie ma go w cookies!**  
**✅ Musisz go przechować:**
- LocalStorage (proste)
- SessionStorage (bezpieczniejsze)
- Pamięć aplikacji (najbezpieczniejsze)

**Klient jest odpowiedzialny za:**
- Przechowywanie tokena
- Dodawanie go do każdego żądania API
- Usunięcie przy wylogowaniu

---

## 2️⃣ Web Views - Session Authentication (Stateful)

### Konfiguracja

```yaml
# config/packages/security.yaml
firewalls:
    main:
        lazy: true
        provider: app_user_provider
        form_login:
            login_path: app_login              # /login
            check_path: app_login              # POST /login
            default_target_path: app_profile   # Redirect po logowaniu
            enable_csrf: true                  # Ochrona CSRF
        logout:
            path: app_logout                   # /logout
            target: app_login                  # Redirect po wylogowaniu
        remember_me:
            secret: '%kernel.secret%'
            lifetime: 604800                   # 7 dni

access_control:
    - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/register, roles: PUBLIC_ACCESS }
    - { path: ^/, roles: ROLE_USER }              # Wymaga logowania!
```

### Flow Sesji

```
1. Użytkownik otwiera /profile BEZ logowania
   ↓
2. access_control wykrywa brak autoryzacji
   HTTP 302 Redirect → /login
   Set-Cookie: PHPSESSID=abc123...
   ↓
3. Formularz logowania (z CSRF tokenem)
   POST /login
   {
     _username: "user@example.com",
     _password: "password123",
     _csrf_token: "1b20543.xnwHuvIUl..."
   }
   ↓
4. Symfony weryfikuje dane
   - Sprawdza CSRF token
   - Weryfikuje hasło (bcrypt)
   - Tworzy sesję użytkownika
   ↓
5. HTTP 302 Redirect → /profile
   Set-Cookie: PHPSESSID=xyz789... (nowa sesja)
   ↓
6. Dostęp do /profile ✅
   Browser automatycznie wysyła:
   Cookie: PHPSESSID=xyz789...
```

### Gdzie jest "token"?

**✅ W COOKIE:**
```
Set-Cookie: PHPSESSID=a2e76079c531166c3eb3909be01e9798; 
            path=/; 
            httponly;      ← JS nie może odczytać
            samesite=lax   ← Ochrona CSRF
```

**Browser automatycznie:**
- Przechowuje cookie
- Wysyła go w każdym żądaniu do tej samej domeny
- Usuwa go po wygaśnięciu/zamknięciu przeglądarki

### Jak działa access_control?

```yaml
access_control:
    - { path: ^/login, roles: PUBLIC_ACCESS }     # Każdy może
    - { path: ^/register, roles: PUBLIC_ACCESS }  # Każdy może
    - { path: ^/, roles: ROLE_USER }              # TYLKO zalogowani!
```

**Symfony sprawdza kolejno:**
1. Czy URL pasuje do wzorca?
2. Czy użytkownik ma wymaganą rolę?
3. Jeśli NIE → redirect do `/login`

### Testowanie w przeglądarce

1. Otwórz DevTools (F12)
2. Zakładka **Application** → **Cookies**
3. Po zalogowaniu zobaczysz: `PHPSESSID`
4. Zakładka **Network** → sprawdź nagłówki:
   ```
   Request Headers:
     Cookie: PHPSESSID=...
   
   Response Headers:
     Set-Cookie: PHPSESSID=...
   ```

---

## 🔒 Bezpieczeństwo

### JWT (API)
- ✅ Stateless - skalowalność
- ✅ Token przechowuje dane (email, role)
- ❌ Nie można "unieważnić" tokena przed wygaśnięciem
- ❌ Klient musi zabezpieczyć token (XSS)

### Session (Web)
- ✅ HttpOnly cookies - ochrona przed XSS
- ✅ CSRF protection włączona
- ✅ Można natychmiastowo unieważnić sesję
- ❌ Stateful - wymaga storage sesji na serwerze

---

## 🧪 Testowanie

### Test API (JWT)

```bash
# 1. Zarejestruj/Zaloguj
TOKEN=$(curl -s -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}' \
  | grep -oP '"token":"\K[^"]*')

# 2. Użyj tokena
curl -X GET http://localhost/api/flashcards \
  -H "Authorization: Bearer $TOKEN"
```

### Test Web (Session)

```bash
# 1. Pobierz stronę logowania (zapisz cookies)
curl -s -c cookies.txt http://localhost:8080/login > login.html

# 2. Wyciągnij CSRF token
CSRF=$(grep -oP 'name="_csrf_token" value="\K[^"]*' login.html)

# 3. Zaloguj się (używając cookies)
curl -s -b cookies.txt -c cookies.txt \
  -X POST http://localhost:8080/login \
  -d "_username=test@example.com" \
  -d "_password=password123" \
  -d "_csrf_token=$CSRF" \
  -L -i

# 4. Dostęp do chronionego zasobu
curl -s -b cookies.txt http://localhost:8080/profile
```

---

## 📚 Pliki konfiguracyjne

- **Security**: `config/packages/security.yaml`
- **JWT**: `config/packages/lexik_jwt_authentication.yaml`
- **Klucze JWT**: `config/jwt/private.pem`, `config/jwt/public.pem`
- **Kontroler API**: `src/Controller/Api/AuthController.php`
- **Kontroler Web**: `src/Controller/SecurityController.php`
- **Encja User**: `src/Entity/User.php`
- **Service**: `src/Service/AuthService.php`

---

## 🎯 Podsumowanie

**Używaj JWT gdy:**
- Budujesz API
- Potrzebujesz stateless authentication
- Klient to aplikacja mobilna/SPA

**Używaj Sesji gdy:**
- Budujesz tradycyjną aplikację webową
- Renderujesz HTML na serwerze (Twig)
- Potrzebujesz natychmiastowej kontroli nad sesjami

**W tym projekcie masz OBA!** 🎉
- API dla frontendu/mobilek: JWT
- Widoki admin/management: Sesje

