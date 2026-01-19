<?php
// AI-GENERATED: User repository for authentication lookups (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Repositories;

use App\Database;
use PDO;

class UserRepository
{
    private PDO $pdo;

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

    public function updateProfile(int $id, string $name, string $email): void
    {
        $sql = 'UPDATE users SET name = :name, email = :email WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'id' => $id,
        ]);
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $sql = 'UPDATE users SET password_hash = :hash WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'hash' => $passwordHash,
            'id' => $id,
        ]);
    }

    public function emailExistsForOtherUser(string $email, int $userId): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email AND id <> :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'id' => $userId,
        ]);

        return $stmt->fetchColumn() !== false;
    }
}
