<?php

namespace App\Repositories;

use App\Database;
use PDO;

/**
 * Repository responsible for accessing and manipulating treasury transactions.
 *
 * This class encapsulates all database operations related to the `transactions`
 * table, such as listing transactions, creating new records, updating them,
 * deleting them, and computing the current balance.
 *
 * Typical usage:
 *
 *  $repo = new TransactionRepository();
 *  $transactions = $repo->findAll();
 *  $balance = $repo->getBalance();
 */
class TransactionRepository
{
    /**
     * Low‑level PDO connection used for all database queries.
     */
    private PDO $pdo;

    /**
     * Create the repository.
     *
     * If no PDO instance is provided, a default connection is obtained
     * from App\Database::getConnection().
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Fetch all transactions from the database ordered by creation time.
     *
     * @return array<int, array<string, mixed>> List of transactions as
     *                                          associative arrays where keys
     *                                          correspond to column names.
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
     * Persist a new treasury transaction.
     *
     * The meaning of the `type` parameter depends on your domain model
     * but is typically either "deposit" or "withdrawal". This is later
     * used by {@see getBalance()} to determine whether the amount
     * increases or decreases the treasury.
     *
     * @param int         $cashboxId   Cashbox identifier.
     * @param string      $type        Transaction type (e.g. "deposit", "withdrawal").
     * @param float       $amount      Positive monetary amount of the transaction.
     * @param string      $description Human‑readable description or note.
     * @param string      $status      Workflow status (e.g. "pending", "approved").
     * @param int|null    $createdBy   Optional identifier of the user who created it.
     * @param int|null    $approvedBy  Optional identifier of the user who approved it.
     *
     * @return int The auto‑generated ID of the newly created transaction.
     */
    public function create(int $cashboxId, string $type, float $amount, string $description, string $status = 'pending', ?int $createdBy = null, ?int $approvedBy = null): int
    {
        $sql = 'INSERT INTO transactions (cashbox_id, type, amount, description, status, created_by, approved_by, created_at, approved_at)' .
            ' VALUES (:cashbox_id, :type, :amount, :description, :status, :created_by, :approved_by, NOW(), NULL)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'cashbox_id' => $cashboxId,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'status' => $status,
            'created_by' => $createdBy,
            'approved_by' => $approvedBy,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Calculate the current treasury balance.
     *
     * The balance is defined as:
     *
     *   sum(amount) for all transactions with type = 'deposit'
     *   minus
     *   sum(amount) for all other transaction types.
     *
     * If there are no transactions, this method returns 0.0.
     *
     * @return float Current computed balance of the treasury.
     */
    public function getBalance(): float
    {
        $sql = "SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) AS balance FROM transactions";
        $stmt = $this->pdo->query($sql);
        $row = $stmt->fetch();

        return isset($row['balance']) ? (float)$row['balance'] : 0.0;
    }

    /**
     * Fetch a single transaction by its primary key.
     *
     * @param int $id Identifier of the transaction record.
     *
     * @return array<string, mixed>|null Associative array of column values
     *                                   or null if no transaction exists
     *                                   with the given ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transactions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Update an existing transaction record.
     *
     * All main attributes (type, amount, description, status) must be supplied.
     * If the ID does not exist, the call silently does nothing.
     *
     * @param int    $id          ID of the transaction to update.
     * @param string $type        New transaction type.
     * @param float  $amount      New amount.
     * @param string $description New description.
     * @param string $status      New status value.
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
     * Delete a transaction by its ID.
     *
     * If the record does not exist, the call succeeds without effect.
     *
     * @param int $id ID of the transaction to delete.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM transactions WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Ensure at least one cashbox exists and return its id.
     */
    public function ensureDefaultCashboxId(): int
    {
        $existing = $this->pdo->query('SELECT id FROM cashboxes ORDER BY id ASC LIMIT 1');
        $id = $existing?->fetchColumn();
        if ($id !== false && $id !== null) {
            return (int)$id;
        }

        $stmt = $this->pdo->prepare('INSERT INTO cashboxes (name, start_date, initial_balance) VALUES (:name, CURDATE(), 0)');
        $stmt->execute(['name' => 'Default cashbox']);

        return (int)$this->pdo->lastInsertId();
    }
}
