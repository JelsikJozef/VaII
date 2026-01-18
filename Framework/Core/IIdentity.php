<?php
// AI-GENERATED: Expand identity contract with email and role accessors (GitHub Copilot / ChatGPT), 2026-01-18

namespace Framework\Core;

/**
 * Interface IIdentity
 *
 * Represents a user identity with a method to retrieve the user's name.
 * Other methods can be added as needed to extend the identity functionality.
 *
 * @package Framework\Core
 */
interface IIdentity
{
    public function getName(): string;
    public function getEmail(): string;
    public function getRole(): string;
}
