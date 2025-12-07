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
}

