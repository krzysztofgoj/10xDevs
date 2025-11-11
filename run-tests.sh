#!/bin/bash

# Script to run tests with proper setup
# Usage: ./run-tests.sh [options]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== 10xDevs Test Runner ===${NC}\n"

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}Installing dependencies...${NC}"
    composer install
fi

# Check if JWT keys exist
if [ ! -f "config/jwt/private.pem" ]; then
    echo -e "${YELLOW}Generating JWT keys...${NC}"
    mkdir -p config/jwt
    openssl genpkey -out config/jwt/private.pem -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -aes256 -pass pass:testpassphrase
    openssl pkey -in config/jwt/private.pem -passin pass:testpassphrase -out config/jwt/public.pem -pubout
    chmod 644 config/jwt/private.pem config/jwt/public.pem
    echo -e "${GREEN}JWT keys generated!${NC}"
fi

# Set environment variables
export APP_ENV=test
export JWT_PASSPHRASE=testpassphrase

# Note: Tests use SQLite in-memory database (configured in config/packages/test/doctrine.yaml)
# No database setup needed - schema is created automatically by BaseWebTestCase
echo -e "${GREEN}Using SQLite in-memory database for tests${NC}\n"

# Run tests
echo -e "${GREEN}Running tests...${NC}\n"

if [ "$1" = "--coverage" ]; then
    echo -e "${YELLOW}Running with coverage (this may take longer)...${NC}\n"
    XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html coverage/ --coverage-text
    echo -e "\n${GREEN}Coverage report generated in coverage/index.html${NC}"
elif [ "$1" = "--functional" ]; then
    echo -e "${YELLOW}Running functional tests only...${NC}\n"
    vendor/bin/phpunit tests/Functional
elif [ "$1" = "--unit" ]; then
    echo -e "${YELLOW}Running unit tests only...${NC}\n"
    vendor/bin/phpunit tests/Unit
elif [ "$1" = "--testdox" ]; then
    echo -e "${YELLOW}Running tests with testdox format...${NC}\n"
    vendor/bin/phpunit --testdox
elif [ -n "$1" ]; then
    echo -e "${YELLOW}Running specific test: $1${NC}\n"
    vendor/bin/phpunit "$1"
else
    vendor/bin/phpunit
fi

# Check exit code
if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}✓ All tests passed!${NC}"
else
    echo -e "\n${RED}✗ Some tests failed!${NC}"
    exit 1
fi

