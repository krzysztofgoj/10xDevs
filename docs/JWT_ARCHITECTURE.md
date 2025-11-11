# 🔐 JWT Architecture - Frontend + API

## Architektura autentykacji

System został zaprojektowany tak, aby **frontend (Twig)** i **API** korzystały z **tej samej metody autentykacji - JWT tokenów**.

---

## 🔄 Flow autentykacji

### 1. Logowanie przez formularz

Użytkownik loguje się przez tradycyjny formularz HTML:

```
POST /login (form data)
↓
SecurityController::login()
↓
Symfony Security (form_login)
↓
Sesja PHP + przekierowanie do profilu
```

### 2. Generowanie JWT tokena

Po zalogowaniu, kontroler automatycznie generuje JWT token:

```php
// SecurityController::profile()
$user = $this->getUser();
$jwtToken = $this->authService->createTokenForUser($user);

return $this->render('security/profile.html.twig', [
    'jwt_token' => $jwtToken,
]);
```

### 3. Zapisywanie tokena w przeglądarce

JavaScript automatycznie zapisuje token w `localStorage`:

```javascript
// W szablonie Twig
{% if jwt_token is defined %}
    window.JWTAuth.setToken('{{ jwt_token }}');
{% endif %}
```

Token jest przechowywany w `localStorage` pod kluczem `jwt_token`.

### 4. Używanie tokena w żądaniach API

Wszystkie żądania AJAX do API używają tokena z `localStorage`:

```javascript
const token = window.JWTAuth.getToken();

const response = await fetch('/api/flashcards/generate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ sourceText })
});
```

### 5. Wylogowanie

Przy wylogowaniu token jest usuwany z `localStorage`:

```javascript
// Automatycznie przy kliknięciu "Wyloguj"
window.JWTAuth.removeToken();
```

---

## 🛠️ API zarządzania tokenem

Globalny obiekt `window.JWTAuth` dostępny na wszystkich stronach:

```javascript
// Zapisz token
window.JWTAuth.setToken(token);

// Pobierz token
const token = window.JWTAuth.getToken();

// Usuń token
window.JWTAuth.removeToken();

// Sprawdź czy token istnieje
const hasToken = window.JWTAuth.hasToken();
```

---

## 🔒 Security Configuration

### Firewalls

```yaml
# config/packages/security.yaml
firewalls:
    # API endpoints - wymagają JWT
    api:
        pattern: ^/api
        stateless: true
        jwt: ~
    
    # Web endpoints - używają sesji
    main:
        lazy: true
        provider: app_user_provider
        form_login: ~
        logout: ~
```

### Access Control

```yaml
access_control:
    # API - wymagają JWT tokena
    - { path: ^/api/login, roles: PUBLIC_ACCESS }
    - { path: ^/api/register, roles: PUBLIC_ACCESS }
    - { path: ^/api, roles: ROLE_USER }
    
    # Web - wymagają sesji (ale JavaScript używa JWT)
    - { path: ^/login, roles: PUBLIC_ACCESS }
    - { path: ^/register, roles: PUBLIC_ACCESS }
    - { path: ^/, roles: ROLE_USER }
```

---

## 🎯 Dlaczego to działa?

### Frontend (Twig) + JavaScript

1. **Użytkownik loguje się** przez formularz → dostaje **sesję PHP**
2. **Kontroler generuje JWT token** → przekazuje do widoku
3. **JavaScript zapisuje token** w `localStorage`
4. **Wszystkie żądania AJAX** używają **JWT tokena** (nie sesji)

### Korzyści

✅ **Jedna metoda autentykacji** - JWT dla wszystkiego  
✅ **Bezstanowe API** - JWT nie wymaga sesji  
✅ **SPA-ready** - łatwo przenieść na React/Vue  
✅ **Bezpieczne** - token w localStorage, nie w cookies  
✅ **Spójne** - API i frontend używają tej samej logiki  

---

## 📝 Przykład użycia

### W kontrolerze (generowanie tokena)

```php
use App\Service\AuthService;

class MyController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    #[Route('/my-page', name: 'my_page')]
    public function myPage(): Response
    {
        $user = $this->getUser();
        $jwtToken = $this->authService->createTokenForUser($user);

        return $this->render('my_page.html.twig', [
            'jwt_token' => $jwtToken,
        ]);
    }
}
```

### W szablonie Twig

```twig
{% block javascripts %}
<script>
    // Zapisz token
    {% if jwt_token is defined %}
        window.JWTAuth.setToken('{{ jwt_token }}');
    {% endif %}

    // Użyj tokena w API call
    async function fetchData() {
        const token = window.JWTAuth.getToken();
        
        const response = await fetch('/api/my-endpoint', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        return await response.json();
    }
</script>
{% endblock %}
```

---

## 🔍 Debugging

### Sprawdź token w konsoli przeglądarki

```javascript
// F12 -> Console
console.log('Token:', window.JWTAuth.getToken());
console.log('Has token:', window.JWTAuth.hasToken());
```

### Sprawdź localStorage

```javascript
// F12 -> Application -> Local Storage
localStorage.getItem('jwt_token');
```

### Dekoduj token (tylko payload, bez weryfikacji)

```javascript
function parseJwt(token) {
    const base64Url = token.split('.')[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(
        atob(base64).split('').map(c => 
            '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)
        ).join('')
    );
    return JSON.parse(jsonPayload);
}

const token = window.JWTAuth.getToken();
console.log('Token payload:', parseJwt(token));
```

---

## 🚨 Troubleshooting

### "Access Denied" error

**Problem:** Brak tokena lub nieprawidłowy token

**Rozwiązanie:**
```javascript
// Wyloguj i zaloguj ponownie
window.JWTAuth.removeToken();
window.location.href = '/logout';
```

### Token wygasł

**Problem:** JWT ma TTL (domyślnie 1h)

**Rozwiązanie:**
1. Wyloguj się i zaloguj ponownie
2. Lub zaimplementuj refresh token (opcjonalnie)

### Token nie jest zapisany

**Problem:** `localStorage` zablokowany lub JavaScript error

**Rozwiązanie:**
```javascript
// Sprawdź w konsoli
console.log('localStorage available:', typeof(Storage) !== 'undefined');
```

---

## 🔄 Różnice vs czysta sesja PHP

| Aspekt | Sesja PHP | JWT Token |
|--------|-----------|-----------|
| **Stan** | Stateful (serwer) | Stateless |
| **Storage** | Serwer (pliki/Redis) | Client (localStorage) |
| **API calls** | Cookie automatycznie | Header ręcznie |
| **SPA support** | Słabe | Świetne |
| **Skalowalność** | Średnia | Wysoka |
| **Bezpieczeństwo** | Dobre (httpOnly) | Dobre (short TTL) |

---

## 📚 Powiązane pliki

### Backend
- `src/Service/AuthService.php` - generowanie JWT
- `src/Controller/SecurityController.php` - logowanie + token
- `src/Controller/FlashcardViewController.php` - przekazywanie tokena
- `config/packages/security.yaml` - konfiguracja JWT

### Frontend
- `templates/base.html.twig` - `window.JWTAuth` helper
- `templates/security/profile.html.twig` - zapisywanie tokena
- `templates/flashcards/generate.html.twig` - używanie tokena

---

## 🎓 Best Practices

1. ✅ **Zawsze sprawdzaj token przed API call**
   ```javascript
   const token = window.JWTAuth.getToken();
   if (!token) {
       window.location.href = '/login';
       return;
   }
   ```

2. ✅ **Obsługuj błędy 401 Unauthorized**
   ```javascript
   if (response.status === 401) {
       window.JWTAuth.removeToken();
       window.location.href = '/login';
   }
   ```

3. ✅ **Nie loguj tokena w produkcji**
   ```javascript
   // DEV only
   if (process.env.NODE_ENV === 'development') {
       console.log('Token:', token);
   }
   ```

4. ✅ **Używaj HTTPS w produkcji**
   - Token w localStorage jest bezpieczny tylko przez HTTPS
   - Nigdy HTTP w produkcji!

---

## 🚀 Przyszłe usprawnienia (opcjonalne)

- [ ] **Refresh tokens** - automatyczne odnawianie wygasłych tokenów
- [ ] **Token rotation** - zmiana tokena co N minut
- [ ] **Remember me** - dłuższy TTL dla wybranych użytkowników
- [ ] **Multi-device logout** - wycofanie wszystkich tokenów
- [ ] **Rate limiting** - ochrona przed brute-force



