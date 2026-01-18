<?php
// AI-GENERATED: Align DummyUser with expanded identity contract (GitHub Copilot / ChatGPT), 2026-01-18

namespace Framework\Auth;

use Framework\Core\IIdentity;

/**
 * Class DummyUser
 *
 * A simple implementation of IIdentity for testing purposes.
 *
 * @package Framework\Auth
 */
class DummyUser implements IIdentity
{
    private string $name;

    /**
     * DummyUser constructor.
     *
     * @param string $name The name of the user.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Retrieves the name of the user.
     *
     * @return string The name of the user.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return 'dummy@example.com';
    }

    public function getRole(): string
    {
        return 'admin';
    }
}