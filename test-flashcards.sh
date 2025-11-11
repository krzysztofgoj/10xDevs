#!/bin/bash

# Test script for Flashcard Generation API
# Usage: ./test-flashcards.sh [email] [password]

set -e

BASE_URL="http://localhost:8080"
EMAIL="${1:-test@example.com}"
PASSWORD="${2:-password123}"

echo "🎴 Testing Flashcard Generation API"
echo "=================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Login and get token
echo "📝 Step 1: Logging in as $EMAIL..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.token // empty')

if [ -z "$TOKEN" ] || [ "$TOKEN" == "null" ]; then
    echo -e "${RED}❌ Login failed!${NC}"
    echo "Response: $LOGIN_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✅ Login successful!${NC}"
echo "Token: ${TOKEN:0:20}..."
echo ""

# Step 2: Generate flashcards
echo "🤖 Step 2: Generating flashcards..."
SOURCE_TEXT="Artificial intelligence is intelligence demonstrated by machines, in contrast to natural intelligence displayed by animals including humans. AI research has been defined as the field of study of intelligent agents, which refers to any system that perceives its environment and takes actions that maximize its chance of achieving its goals."

GENERATE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/flashcards/generate" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"sourceText\":\"$SOURCE_TEXT\"}")

# Check if response is valid JSON array
if echo "$GENERATE_RESPONSE" | jq empty 2>/dev/null; then
    FLASHCARD_COUNT=$(echo "$GENERATE_RESPONSE" | jq 'length')
    echo -e "${GREEN}✅ Generated $FLASHCARD_COUNT flashcards!${NC}"
    echo ""
    echo "Generated flashcards:"
    echo "$GENERATE_RESPONSE" | jq -C '.'
    echo ""
else
    echo -e "${RED}❌ Failed to generate flashcards!${NC}"
    echo "Response: $GENERATE_RESPONSE"
    exit 1
fi

# Step 3: Save selected flashcards
echo "💾 Step 3: Saving selected flashcards to database..."

# Select first 2 flashcards from generated ones
SELECTED_FLASHCARDS=$(echo "$GENERATE_RESPONSE" | jq '[.[0:2] | .[] | {question, answer, source: "ai"}]')

BULK_REQUEST=$(jq -n --argjson flashcards "$SELECTED_FLASHCARDS" '{flashcards: $flashcards}')

SAVE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/flashcards/bulk" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "$BULK_REQUEST")

# Check if response is valid JSON array
if echo "$SAVE_RESPONSE" | jq empty 2>/dev/null; then
    SAVED_COUNT=$(echo "$SAVE_RESPONSE" | jq 'length')
    echo -e "${GREEN}✅ Saved $SAVED_COUNT flashcards!${NC}"
    echo ""
    echo "Saved flashcards:"
    echo "$SAVE_RESPONSE" | jq -C '.'
    echo ""
else
    echo -e "${RED}❌ Failed to save flashcards!${NC}"
    echo "Response: $SAVE_RESPONSE"
    exit 1
fi

# Step 4: Test validation - too few words
echo "🔍 Step 4: Testing validation (too few words)..."
VALIDATION_RESPONSE=$(curl -s -X POST "$BASE_URL/api/flashcards/generate" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"sourceText":"Only four words here"}')

if echo "$VALIDATION_RESPONSE" | grep -q "at least 5 words"; then
    echo -e "${GREEN}✅ Validation working correctly!${NC}"
    echo "Response: $VALIDATION_RESPONSE"
else
    echo -e "${YELLOW}⚠️  Validation response unexpected${NC}"
    echo "Response: $VALIDATION_RESPONSE"
fi
echo ""

# Step 5: Test authentication
echo "🔒 Step 5: Testing authentication (without token)..."
AUTH_RESPONSE=$(curl -s -X POST "$BASE_URL/api/flashcards/generate" \
  -H "Content-Type: application/json" \
  -d "{\"sourceText\":\"$SOURCE_TEXT\"}")

if echo "$AUTH_RESPONSE" | grep -q "JWT"; then
    echo -e "${GREEN}✅ Authentication protection working!${NC}"
    echo "Response: $AUTH_RESPONSE"
else
    echo -e "${YELLOW}⚠️  Authentication response unexpected${NC}"
    echo "Response: $AUTH_RESPONSE"
fi
echo ""

echo "=================================="
echo -e "${GREEN}🎉 All tests completed!${NC}"

