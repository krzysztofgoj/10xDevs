# 🔐 System Autoryzacji w 10x Cards

Projekt powinien używać **dwóch różnych systemów autoryzacji** w zależności od typu żądania:

---

## 📌 Podsumowanie

| Typ | Endpoint | Metoda | Token/Sesja | Stateless |
|-----|----------|--------|-------------|-----------|
| **API** | `/api/*` | JWT | Authorization Header | ✅ Tak |
| **Web** | `/`, `/login`, `/profile` | Session | Cookie (PHPSESSID) | ❌ Nie |

---

## 1️⃣ API - JWT Authentication (Stateless)

### Wymagania konfiguracji

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

### Wymagania użycia

**❌ Nie ma go w cookies!**  
**✅ Musi być przechowany:**
- LocalStorage (proste)
- SessionStorage (bezpieczniejsze)
- Pamięć aplikacji (najbezpieczniejsze)

**Klient jest odpowiedzialny za:**
- Przechowywanie tokena
- Dodawanie go do każdego żądania API
- Usunięcie przy wylogowaniu

---

## 2️⃣ Web Views - Session Authentication (Stateful)

### Wymagania konfiguracji

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

**Używać JWT gdy:**
- Budujesz API
- Potrzebujesz stateless authentication
- Klient to aplikacja mobilna/SPA

**Używać Sesji gdy:**
- Budujesz tradycyjną aplikację webową
- Renderujesz HTML na serwerze (Twig)
- Potrzebujesz natychmiastowej kontroli nad sesjami

**W tym projekcie powinny być OBA!** 🎉
- API dla frontendu/mobilek: JWT
- Widoki admin/management: Sesje
