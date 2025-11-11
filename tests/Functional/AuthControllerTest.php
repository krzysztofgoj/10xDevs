<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

final class AuthControllerTest extends BaseWebTestCase
{
    /**
     * Test successful user registration
     */
    public function testRegisterSuccess(): void
    {
        $userData = [
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ];

        $this->makeJsonRequest('POST', '/api/register', $userData);

        $this->assertJsonResponse(Response::HTTP_CREATED);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('userId', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertEquals('newuser@example.com', $data['email']);
        $this->assertNotEmpty($data['token']);
    }

    /**
     * Test registration with existing email
     */
    public function testRegisterWithExistingEmail(): void
    {
        // Create existing user
        $this->createUser('existing@example.com', 'password123');

        $userData = [
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ];

        $this->makeJsonRequest('POST', '/api/register', $userData);

        $this->assertJsonResponse(Response::HTTP_CONFLICT);
        $this->assertResponseHasError('User with this email already exists');
    }

    /**
     * Test registration with invalid email
     */
    public function testRegisterWithInvalidEmail(): void
    {
        $userData = [
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ];

        $this->makeJsonRequest('POST', '/api/register', $userData);

        $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Test registration with short password
     */
    public function testRegisterWithShortPassword(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirm' => '123',
        ];

        $this->makeJsonRequest('POST', '/api/register', $userData);

        $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Test registration with mismatched passwords
     */
    public function testRegisterWithMismatchedPasswords(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirm' => 'different123',
        ];

        $this->makeJsonRequest('POST', '/api/register', $userData);

        $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Test registration with missing fields
     */
    public function testRegisterWithMissingFields(): void
    {
        $userData = [
            'email' => 'test@example.com',
            // Missing password and password_confirm
        ];

        $this->makeJsonRequest('POST', '/api/register', $userData);

        $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Test successful login
     */
    public function testLoginSuccess(): void
    {
        // Create user
        $this->createUser('testuser@example.com', 'password123');

        $credentials = [
            'email' => 'testuser@example.com',
            'password' => 'password123',
        ];

        $this->makeJsonRequest('POST', '/api/login', $credentials);

        $this->assertJsonResponse(Response::HTTP_OK);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('userId', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertEquals('testuser@example.com', $data['email']);
        $this->assertNotEmpty($data['token']);
    }

    /**
     * Test login with invalid credentials
     */
    public function testLoginWithInvalidPassword(): void
    {
        // Create user
        $this->createUser('testuser@example.com', 'password123');

        $credentials = [
            'email' => 'testuser@example.com',
            'password' => 'wrongpassword',
        ];

        $this->makeJsonRequest('POST', '/api/login', $credentials);

        $this->assertJsonResponse(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseHasError('Invalid credentials');
    }

    /**
     * Test login with non-existent user
     */
    public function testLoginWithNonExistentUser(): void
    {
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $this->makeJsonRequest('POST', '/api/login', $credentials);

        $this->assertJsonResponse(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseHasError('Invalid credentials');
    }

    /**
     * Test login with missing fields
     */
    public function testLoginWithMissingFields(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            // Missing password
        ];

        $this->makeJsonRequest('POST', '/api/login', $credentials);

        $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Test login with invalid email format
     */
    public function testLoginWithInvalidEmailFormat(): void
    {
        $credentials = [
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $this->makeJsonRequest('POST', '/api/login', $credentials);

        $this->assertJsonResponse(Response::HTTP_BAD_REQUEST);
        $data = $this->getResponseData();
        $this->assertArrayHasKey('errors', $data);
    }
}

