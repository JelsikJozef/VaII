<?php
// AI-GENERATED: Lightweight identity implementation for session auth (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Auth;

use Framework\Core\IIdentity;

/**
 * Simple identity value object.
 *
 * Implements the framework {@see IIdentity} contract so it can be stored in the
 * session by session-based authenticators.
 *
 * This class intentionally contains no DB lookups or logic; it just holds
 * user metadata for the current request/session.
 *
 * Typical use-cases:
 * - A lightweight identity for tests or demos.
 * - An identity created by an authenticator that doesn't need extra behavior.
 *
 * If you rely on role checks being case-insensitive, pass a normalized (e.g. lowercase)
 * role string, or use {@see UserIdentity} which normalizes automatically.
 */
class SimpleIdentity implements IIdentity
{
    /** @var int Numeric user id */
    private int $id;

    /** @var string Display name */
    private string $name;

    /** @var string Login/email address */
    private string $email;

    /** @var string Role name (as provided; no normalization performed) */
    private string $role;

    /**
     * @param int $id User id
     * @param string $name Display name
     * @param string $email Email used for login/contact
     * @param string $role Role for authorization checks (e.g. 'admin', 'member')
     */
    public function __construct(int $id, string $name, string $email, string $role)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
    }

    /**
     * Unique user identifier.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Human-friendly display name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Email address / username.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Role name used for authorization checks.
     */
    public function getRole(): string
    {
        return $this->role;
    }
}
