<?php
// AI-GENERATED: ESNcards repository with CRUD and filters (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Repositories;

use App\Database;
use PDO;

class EsncardRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * List ESNcards with optional search and status filter.
     */
    public function findAll(?string $search = null, ?string $status = null): array
    {
        $sql = 'SELECT * FROM esncards WHERE 1=1';
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (card_number LIKE :q_card OR assigned_to_email LIKE :q_email)';
            $params['q_card'] = '%' . trim($search) . '%';
            $params['q_email'] = '%' . trim($search) . '%';
        }

        if ($status !== null && trim($status) !== '') {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    /**
     * Find single ESNcard by id.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM esncards WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Check uniqueness of card number, optionally excluding one record.
     */
    public function existsByCardNumber(string $cardNumber, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM esncards WHERE card_number = :card_number';
        $params = ['card_number' => $cardNumber];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Insert new ESNcard and return generated id.
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO esncards (card_number, status, assigned_to_name, assigned_to_email, assigned_at, created_at)
             VALUES (:card_number, :status, :assigned_to_name, :assigned_to_email, :assigned_at, NOW())'
        );

        $stmt->execute([
            'card_number' => $data['card_number'],
            'status' => $data['status'],
            'assigned_to_name' => $data['assigned_to_name'],
            'assigned_to_email' => $data['assigned_to_email'],
            'assigned_at' => $data['assigned_at'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update ESNcard by id.
     */
    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE esncards SET card_number = :card_number, status = :status, assigned_to_name = :assigned_to_name,
                    assigned_to_email = :assigned_to_email, assigned_at = :assigned_at
             WHERE id = :id'
        );

        $stmt->execute([
            'card_number' => $data['card_number'],
            'status' => $data['status'],
            'assigned_to_name' => $data['assigned_to_name'],
            'assigned_to_email' => $data['assigned_to_email'],
            'assigned_at' => $data['assigned_at'],
            'id' => $id,
        ]);
    }

    /**
     * Delete ESNcard by id.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM esncards WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
