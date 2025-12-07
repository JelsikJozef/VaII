<?php

namespace App\Repositories;

use App\Database;
use PDO;

class TransactionRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Fetch all transactions from the database.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $sql = 'SELECT * FROM transactions ORDER BY created_at DESC';

        $stmt = $this->pdo->query($sql);

        $rows = $stmt->fetchAll();

        // In case PDO is not set to FETCH_ASSOC for some reason, cast rows to array of assoc arrays
        if (!is_array($rows)) {
            return [];
        }

        return $rows;
    }

    /**
     * Persist a treasury transaction.
     */
    public function create(string $type, float $amount, string $description, string $status = 'pending', ?string $proposedBy = null): int
    {
        $sql = 'INSERT INTO transactions (type, amount, description, status, proposed_by, created_at)' .
            ' VALUES (:type, :amount, :description, :status, :proposed_by, NOW())';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'status' => $status,
            'proposed_by' => $proposedBy,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Calculate the current treasury balance (deposits minus withdrawals).
     */
    public function getBalance(): float
    {
        $sql = "SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) AS balance FROM transactions";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();

        return isset($row['balance']) ? (float)$row['balance'] : 0.0;
    }

    /**
     * Fetch a single transaction by its ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transactions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Update an existing transaction.
     */
    public function update(int $id, string $type, float $amount, string $description, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE transactions SET type = :type, amount = :amount, description = :description, status = :status WHERE id = :id');
        $stmt->execute([
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'status' => $status,
            'id' => $id,
        ]);
    }

    /**
     * Delete a transaction.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM transactions WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
