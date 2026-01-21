<?php
// AI-GENERATED: Lightweight identity value object (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Auth;

use Framework\Core\IIdentity;

/**
 * Authenticated user identity.
 *
 * This is the primary `IIdentity` implementation used by `DbAuthenticator`.
 * It is designed to be serializable into the session (via the framework's
 * session authenticator) and to expose the core user fields required for
 * authorization and UI.
 *
 * Stored fields:
 * - id: numeric user id
 * - name: display name
 * - email: login name / contact email
 * - role: authorization role (normalized to lowercase)
 */
class UserIdentity implements IIdentity
{
    /** @var int Numeric user id */
    private int $id;

    /** @var string Display name */
    private string $name;

    /** @var string Email / username */
    private string $email;

    /** @var string Normalized (lowercase) role */
    private string $role;

    /**
     * @param int $id User id
     * @param string $name Display name
     * @param string $email Email / username
     * @param string $role Role name; will be normalized to lowercase
     */
    public function __construct(int $id, string $name, string $email, string $role)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;

        // Normalize for consistent role checks (e.g. 'Admin' == 'admin').
        $this->role = strtolower($role);
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
     * Normalized role name.
     */
    public function getRole(): string
    {
        return $this->role;
    }
}
