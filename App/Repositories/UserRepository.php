<?php
// AI-GENERATED: User repository for authentication lookups (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Repositories;

use App\Database;
use PDO;

class UserRepository
{
    private PDO $pdo;

    private const PENDING_ROLE = 'pending';

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

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

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() !== false;
    }

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

    public function findPendingUsers(): array
    {
        $pendingRoleId = $this->ensureRole(self::PENDING_ROLE);
        $stmt = $this->pdo->prepare('SELECT u.id, u.name, u.email, u.created_at FROM users u WHERE u.role_id = :role_id ORDER BY u.created_at ASC');
        $stmt->execute(['role_id' => $pendingRoleId]);
        return $stmt->fetchAll();
    }

    public function approveUser(int $id, int $roleId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET role_id = :role_id WHERE id = :id');
        $stmt->execute(['role_id' => $roleId, 'id' => $id]);
    }

    public function rejectUser(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function setRole(int $id, int $roleId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET role_id = :role_id WHERE id = :id');
        $stmt->execute(['role_id' => $roleId, 'id' => $id]);
    }

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
