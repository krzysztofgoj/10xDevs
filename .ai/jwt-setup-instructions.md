# Instrukcje generowania kluczy JWT

Jeśli komenda `php bin/console lexik:jwt:generate-keypair` nie działa, możesz wygenerować klucze ręcznie używając OpenSSL.

## Krok 1: Utworzenie katalogu dla kluczy

W kontenerze Docker wykonaj:
```bash
mkdir -p config/jwt
```

## Krok 2: Generowanie kluczy OpenSSL

```bash
# Generowanie klucza prywatnego (z szyfrowaniem AES256)
openssl genrsa -out config/jwt/private.pem -aes256 4096
# Podczas generowania zostaniesz poproszony o podanie passphrase - zapamiętaj ją!

# Generowanie klucza publicznego z prywatnego
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

## Krok 3: Ustawienie uprawnień

```bash
chmod 600 config/jwt/private.pem
chmod 644 config/jwt/public.pem
```

## Krok 4: Konfiguracja zmiennych środowiskowych

Dodaj do pliku `.env`:
```env
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=twoja_passphrase_tutaj
```

## Alternatywa: Sprawdzenie instalacji bundle

Jeśli chcesz użyć komendy Symfony, najpierw sprawdź:

1. Czy bundle jest zainstalowany:
```bash
composer show lexik/jwt-authentication-bundle
```

2. Wyczyść cache:
```bash
rm -rf var/cache/*
php bin/console cache:clear
```

3. Sprawdź dostępne komendy:
```bash
php bin/console list lexik
```

Jeśli nadal nie działa, użyj metody OpenSSL powyżej.

