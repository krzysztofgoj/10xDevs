#!/bin/bash

# Skrypt testowy dla endpointów autoryzacji
# Użycie: ./test-auth.sh [base_url]
# Przykład: ./test-auth.sh http://localhost:8080

BASE_URL="${1:-http://localhost:8080}"
API_URL="${BASE_URL}/api"

echo "🧪 Testowanie endpointów autoryzacji"
echo "======================================"
echo ""

# Kolory dla outputu
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Funkcja pomocnicza do wyświetlania wyników
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
    fi
}

# Test 1: Rejestracja użytkownika
echo "📝 Test 1: Rejestracja użytkownika"
echo "-----------------------------------"
REGISTER_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${API_URL}/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }')

HTTP_CODE=$(echo "$REGISTER_RESPONSE" | tail -n1)
BODY=$(echo "$REGISTER_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 201 ]; then
    print_result 0 "Rejestracja zakończona sukcesem (HTTP $HTTP_CODE)"
    TOKEN=$(echo "$BODY" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
    USER_ID=$(echo "$BODY" | grep -o '"userId":[0-9]*' | cut -d':' -f2)
    echo "   Token: ${TOKEN:0:50}..."
    echo "   User ID: $USER_ID"
else
    print_result 1 "Rejestracja nie powiodła się (HTTP $HTTP_CODE)"
    echo "   Response: $BODY"
fi
echo ""

# Test 2: Próba rejestracji tego samego użytkownika (powinno zwrócić błąd)
echo "📝 Test 2: Próba rejestracji istniejącego użytkownika"
echo "------------------------------------------------------"
DUPLICATE_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${API_URL}/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }')

HTTP_CODE=$(echo "$DUPLICATE_RESPONSE" | tail -n1)
BODY=$(echo "$DUPLICATE_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 409 ]; then
    print_result 0 "Poprawnie zwrócono błąd konfliktu (HTTP $HTTP_CODE)"
else
    print_result 1 "Oczekiwano HTTP 409, otrzymano HTTP $HTTP_CODE"
    echo "   Response: $BODY"
fi
echo ""

# Test 3: Logowanie
echo "📝 Test 3: Logowanie"
echo "---------------------"
LOGIN_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${API_URL}/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }')

HTTP_CODE=$(echo "$LOGIN_RESPONSE" | tail -n1)
BODY=$(echo "$LOGIN_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 200 ]; then
    print_result 0 "Logowanie zakończone sukcesem (HTTP $HTTP_CODE)"
    TOKEN=$(echo "$BODY" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
    echo "   Token: ${TOKEN:0:50}..."
else
    print_result 1 "Logowanie nie powiodło się (HTTP $HTTP_CODE)"
    echo "   Response: $BODY"
fi
echo ""

# Test 4: Logowanie z nieprawidłowym hasłem
echo "📝 Test 4: Logowanie z nieprawidłowym hasłem"
echo "--------------------------------------------"
WRONG_PASSWORD_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${API_URL}/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "wrongpassword"
  }')

HTTP_CODE=$(echo "$WRONG_PASSWORD_RESPONSE" | tail -n1)
BODY=$(echo "$WRONG_PASSWORD_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 401 ]; then
    print_result 0 "Poprawnie zwrócono błąd autoryzacji (HTTP $HTTP_CODE)"
else
    print_result 1 "Oczekiwano HTTP 401, otrzymano HTTP $HTTP_CODE"
    echo "   Response: $BODY"
fi
echo ""

# Test 5: Walidacja - nieprawidłowy email
echo "📝 Test 5: Walidacja - nieprawidłowy email"
echo "--------------------------------------------"
INVALID_EMAIL_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${API_URL}/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "invalid-email",
    "password": "password123"
  }')

HTTP_CODE=$(echo "$INVALID_EMAIL_RESPONSE" | tail -n1)
BODY=$(echo "$INVALID_EMAIL_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 400 ]; then
    print_result 0 "Poprawnie zwrócono błąd walidacji (HTTP $HTTP_CODE)"
else
    print_result 1 "Oczekiwano HTTP 400, otrzymano HTTP $HTTP_CODE"
    echo "   Response: $BODY"
fi
echo ""

# Test 6: Walidacja - za krótkie hasło
echo "📝 Test 6: Walidacja - za krótkie hasło"
echo "----------------------------------------"
SHORT_PASSWORD_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${API_URL}/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "newuser@example.com",
    "password": "short"
  }')

HTTP_CODE=$(echo "$SHORT_PASSWORD_RESPONSE" | tail -n1)
BODY=$(echo "$SHORT_PASSWORD_RESPONSE" | sed '$d')

if [ "$HTTP_CODE" -eq 400 ]; then
    print_result 0 "Poprawnie zwrócono błąd walidacji (HTTP $HTTP_CODE)"
else
    print_result 1 "Oczekiwano HTTP 400, otrzymano HTTP $HTTP_CODE"
    echo "   Response: $BODY"
fi
echo ""

echo "======================================"
echo "✅ Testy zakończone"
echo ""
echo "💡 Aby przetestować ręcznie, użyj:"
echo "   curl -X POST ${API_URL}/register -H 'Content-Type: application/json' -d '{\"email\":\"user@example.com\",\"password\":\"password123\"}'"
echo "   curl -X POST ${API_URL}/login -H 'Content-Type: application/json' -d '{\"email\":\"user@example.com\",\"password\":\"password123\"}'"

