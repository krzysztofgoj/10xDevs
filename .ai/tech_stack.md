# Stos technologiczny – 10x-cards

## Backend

### Język programowania
- **PHP 8.3** – główny język programowania aplikacji

### Framework
- **Symfony 6.4** – framework webowy zapewniający strukturę aplikacji, routing, dependency injection i inne komponenty

### Template Engine
- **Twig 3.8** – silnik szablonów do renderowania widoków HTML

## Baza danych

- **PostgreSQL 15** – relacyjna baza danych do przechowywania danych użytkowników, fiszek i metadanych

## Infrastruktura i narzędzia

### Konteneryzacja
- **Docker** – konteneryzacja aplikacji
- **Docker Compose** – orkiestracja kontenerów (PHP + PostgreSQL)

### Serwer webowy
- **Apache** – serwer HTTP z włączonym mod_rewrite

## Narzędzia deweloperskie

### Testy
- **PHPUnit 10.5** – framework do testów jednostkowych i integracyjnych

### Zarządzanie zależnościami
- **Composer** – menedżer pakietów PHP

## Rozszerzenia PHP

- **pdo** / **pdo_pgsql** – obsługa PostgreSQL
- **intl** – obsługa internacjonalizacji
- **mbstring** – obsługa wielobajtowych stringów
- **xml** – parsowanie XML
- **zip** – obsługa archiwów ZIP

## Architektura

- **MVC (Model-View-Controller)** – wzorzec architektoniczny zapewniany przez Symfony
- **PSR-4** – standard autoloadingu klas PHP
- **Namespace**: `App\` mapowany na katalog `src/`

## Porty i konfiguracja

- **Port aplikacji**: 8080 (mapowany na 80 w kontenerze)
- **Port PostgreSQL**: 5433 (mapowany na 5432 w kontenerze)



