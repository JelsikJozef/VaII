<?php
// AI-GENERATED: Database-backed authenticator using sessions (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Auth;

use App\Repositories\UserRepository;
use Framework\Auth\SessionAuthenticator;
use Framework\Core\App;
use Framework\Core\IIdentity;

/**
 * Database-backed session authenticator.
 *
 * This class plugs into the framework's `SessionAuthenticator` and provides
 * the app-specific logic needed to validate credentials against the database.
 *
 * High-level flow:
 * 1) The framework receives a login request.
 * 2) `SessionAuthenticator` calls {@see authenticate()}.
 * 3) If {@see authenticate()} returns a non-null {@see IIdentity}, the framework
 *    stores it in the session and considers the user logged in.
 *
 * Notes:
 * - The username is treated as an email address (see {@see UserRepository::findByEmail()}).
 * - Passwords are verified using PHP's `password_verify()` against the stored hash.
 * - Users with role `pending` are rejected (not allowed to log in yet).
 */
class DbAuthenticator extends SessionAuthenticator
{
    /**
     * Repository used to fetch user records for authentication.
     *
     * Expected fields used by this authenticator:
     * - id
     * - email
     * - name (optional; falls back to email)
     * - password_hash
     * - role_name or role (optional; defaults to `member`)
     */
    private UserRepository $users;

    /**
     * @param App $app Framework application container.
     */
    public function __construct(App $app)
    {
        parent::__construct($app);

        // `SessionAuthenticator` doesn't know how to query the application's DB.
        // We do that here via a repository.
        $this->users = new UserRepository();
    }

    /**
     * Validate credentials and build an identity for the session.
     *
     * Returning `null` means authentication failed (invalid credentials, not allowed).
     * Returning an {@see IIdentity} means success and the framework will persist it
     * to the session.
     */
    protected function authenticate(string $username, string $password): ?IIdentity
    {
        // Username is an email in this application.
        $user = $this->users->findByEmail($username);
        if ($user === null) {
            // Don't reveal whether the email exists; just fail.
            return null;
        }

        // Verify password using the stored hash.
        $hash = (string)($user['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return null;
        }

        // Resolve role (repo may return role_name via join; keep a fallback).
        $roleName = (string)($user['role_name'] ?? $user['role'] ?? '');

        // Block users awaiting approval.
        if (strtolower($roleName) === 'pending') {
            return null;
        }

        // Defensive default role, so checks have a stable baseline.
        if ($roleName === '') {
            $roleName = 'member';
        }

        // Friendly name to show in UI.
        $name = (string)($user['name'] ?? $user['email'] ?? 'User');

        // Store only the minimal info needed for authorization/UX in the session.
        return new UserIdentity((int)$user['id'], $name, (string)$user['email'], $roleName);
    }
}
