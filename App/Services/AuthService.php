<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

/**
 * Authentication business logic (validation, password hashing/verification).
 * Returns DomainResult only.
 */
class AuthService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
    ) {
    }

    /**
     * @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user
     * @return array{ok:bool,payload:array}
     */
    public function loginForm(array $user): array
    {
        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'auth',
                'errors' => [],
                'email' => '',
                'genericError' => null,
            ],
        ];
    }

    /**
     * @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user
     * @param array{email?:string|null,password?:string|null} $input
     * @return array{ok:bool,payload:array,errors?:array,flash?:array}
     */
    public function login(array $user, array $input): array
    {
        $email = trim((string)($input['email'] ?? ''));
        $password = (string)($input['password'] ?? '');

        $errors = [];
        if ($email === '') {
            $errors['email'][] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Email is not valid.';
        }
        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        }

        if (!empty($errors)) {
            return [
                'ok' => false,
                'payload' => [
                    'activeModule' => 'auth',
                    'email' => $email,
                    'genericError' => null,
                ],
                'errors' => $errors,
            ];
        }

        $userRow = $this->users->findByEmail($email);
        $hash = (string)($userRow['password_hash'] ?? '');
        $roleName = strtolower((string)($userRow['role_name'] ?? $userRow['role'] ?? ''));
        $allowRole = $roleName === '' || $roleName !== 'pending';

        if ($userRow === null || $hash === '' || !$allowRole || !password_verify($password, $hash)) {
            return [
                'ok' => false,
                'payload' => [
                    'activeModule' => 'auth',
                    'email' => $email,
                    'genericError' => 'Invalid credentials or awaiting approval.',
                ],
                'errors' => [],
            ];
        }

        return [
            'ok' => true,
            'payload' => [
                'email' => $email,
            ],
            'flash' => ['type' => 'success', 'message' => 'Login successful.'],
        ];
    }

    /**
     * @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user
     * @return array{ok:bool,payload:array}
     */
    public function registerForm(array $user): array
    {
        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'auth',
                'errors' => [],
                'name' => '',
                'email' => '',
            ],
        ];
    }

    /**
     * @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user
     * @param array{name?:string|null,email?:string|null,password?:string|null,password_confirm?:string|null} $input
     * @return array{ok:bool,payload:array,errors?:array,flash?:array}
     */
    public function register(array $user, array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $passwordConfirm = (string)($input['password_confirm'] ?? '');

        $errors = [];
        if ($name === '') {
            $errors['name'][] = 'Name is required.';
        } elseif (mb_strlen($name) < 2) {
            $errors['name'][] = 'Name must be at least 2 characters.';
        } elseif (mb_strlen($name) > 255) {
            $errors['name'][] = 'Name must be at most 255 characters.';
        }

        if ($email === '') {
            $errors['email'][] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Email is not valid.';
        } elseif (mb_strlen($email) > 255) {
            $errors['email'][] = 'Email must be at most 255 characters.';
        } elseif ($this->users->emailExists($email)) {
            $errors['email'][] = 'Email is already in use.';
        }

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters.';
        }

        if ($passwordConfirm === '') {
            $errors['password_confirm'][] = 'Please confirm the password.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'][] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return [
                'ok' => false,
                'payload' => [
                    'activeModule' => 'auth',
                    'name' => $name,
                    'email' => $email,
                ],
                'errors' => $errors,
            ];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->users->createPendingUser($name, $email, $hash);

        return [
            'ok' => true,
            'payload' => [],
            'flash' => ['type' => 'success', 'message' => 'Registration submitted. Wait for admin approval.'],
        ];
    }

    /**
     * @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user
     * @return array{ok:bool,payload:array,flash?:array}
     */
    public function logout(array $user): array
    {
        return [
            'ok' => true,
            'payload' => [],
            'flash' => ['type' => 'success', 'message' => 'Logged out successfully.'],
        ];
    }
}
