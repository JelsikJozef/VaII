<?php
// AI-GENERATED: User repository for authentication lookups (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Repositories;

use App\Database;
use PDO;

/**
 * Users repository.
 *
 * Encapsulates all DB access for user accounts and roles.
 *
 * Used by:
 * - Authentication (`DbAuthenticator` -> {@see findByEmail()})
 * - Registration (creating a `pending` user via {@see createPendingUser()})
 * - Admin approval / role management (see `AdminRegistrationsController`)
 * - Profile screen (lookup by id)
 *
 * Tables used (expected):
 * - users(id, name, email, password_hash, role_id, created_at, ...)
 * - roles(id, name)
 */
class UserRepository
{
    /** PDO connection used for queries. */
    private PDO $pdo;

    /** Role slug used for newly registered users awaiting approval. */
    private const PENDING_ROLE = 'pending';

    /**
     * @param PDO|null $pdo Optional PDO injection for tests/DI.
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Find a user by email, including role name.
     *
     * @param string $email
     * @return array<string,mixed>|null Row with keys: id, name, email, password_hash, role_name.
     */
    public function findByEmail(string $email): ?array
    {
        $sql = 'SELECT u.id, u.name, u.email, u.password_hash, r.name AS role_name
                FROM users u
                INNER JOIN roles r ON r.id = u.role_id
                WHERE u.email = :email
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Check if a user with the given email exists.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Find a user by id, including role name.
     *
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT u.id, u.name, u.email, u.password_hash, r.name AS role_name
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                WHERE u.id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Create a new user with the `pending` role.
     *
     * The password must already be hashed (typically `password_hash()`).
     *
     * @return int New user id.
     */
    public function createPendingUser(string $name, string $email, string $passwordHash): int
    {
        $pendingRoleId = $this->ensureRole(self::PENDING_ROLE);
        $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password_hash, role_id) VALUES (:name, :email, :hash, :role_id)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'hash' => $passwordHash,
            'role_id' => $pendingRoleId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * List users who are currently pending approval.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findPendingUsers(): array
    {
        $pendingRoleId = $this->ensureRole(self::PENDING_ROLE);
        $stmt = $this->pdo->prepare('SELECT u.id, u.name, u.email, u.created_at FROM users u WHERE u.role_id = :role_id ORDER BY u.created_at ASC');
        $stmt->execute(['role_id' => $pendingRoleId]);
        return $stmt->fetchAll();
    }

    /**
     * List all users with their role labels.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findAllUsers(): array
    {
        $sql = 'SELECT u.id, u.name, u.email, u.created_at, r.name AS role_name
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                ORDER BY u.created_at ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Approve user by assigning a role.
     */
    public function approveUser(int $id, int $roleId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET role_id = :role_id WHERE id = :id');
        $stmt->execute(['role_id' => $roleId, 'id' => $id]);
    }

    /**
     * Reject a user registration (currently implemented as a delete).
     */
    public function rejectUser(int $id): void
    {
        $this->deleteUser($id);
    }

    /**
     * Update a user's role.
     */
    public function setRole(int $id, int $roleId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET role_id = :role_id WHERE id = :id');
        $stmt->execute(['role_id' => $roleId, 'id' => $id]);
    }

    /**
     * Permanently delete a user.
     */
    public function deleteUser(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * List available roles.
     *
     * @param bool $includePending When false, excludes the `pending` role.
     * @return array<int,array{id:mixed,name:mixed}>
     */
    public function listRoles(bool $includePending = false): array
    {
        $sql = 'SELECT id, name FROM roles';
        if (!$includePending) {
            $sql .= ' WHERE name <> :pending';
        }
        $sql .= ' ORDER BY name ASC';

        $stmt = $this->pdo->prepare($sql);
        $params = $includePending ? [] : ['pending' => self::PENDING_ROLE];
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ensure a role exists and returns its id.
     */
    private function ensureRole(string $roleName): int
    {
        $select = $this->pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $select->execute(['name' => $roleName]);
        $found = $select->fetchColumn();
        if ($found !== false) {
            return (int)$found;
        }

        $insert = $this->pdo->prepare('INSERT INTO roles (name) VALUES (:name)');
        $insert->execute(['name' => $roleName]);
        return (int)$this->pdo->lastInsertId();
    }
}
