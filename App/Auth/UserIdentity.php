<?php
// AI-GENERATED: Lightweight identity value object (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Auth;

use Framework\Core\IIdentity;

class UserIdentity implements IIdentity
{
    private int $id;
    private string $name;
    private string $email;
    private string $role;

    public function __construct(int $id, string $name, string $email, string $role)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->role = strtolower($role);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
