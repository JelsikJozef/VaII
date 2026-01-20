<?php
// AI-GENERATED: Polls repository user joins for creator names (GitHub Copilot / ChatGPT), 2026-01-20

namespace App\Repositories;

use App\Database;
use PDO;
use PDOException;

class PollsRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.question, p.is_active, p.created_by AS created_by_user_id, p.created_at, '
            . 'u_created.name AS created_by_name, u_created.email AS created_by_email '
            . 'FROM polls p '
            . 'LEFT JOIN users u_created ON u_created.id = p.created_by '
            . 'ORDER BY p.created_at DESC'
        );
        $stmt->execute();

        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    public function findPollById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.question, p.is_active, p.created_by AS created_by_user_id, p.created_at, '
            . 'u_created.name AS created_by_name, u_created.email AS created_by_email '
            . 'FROM polls p '
            . 'LEFT JOIN users u_created ON u_created.id = p.created_by '
            . 'WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function listOptions(int $pollId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, poll_id, text AS option_text FROM poll_options WHERE poll_id = :poll_id ORDER BY id ASC');
        $stmt->execute(['poll_id' => $pollId]);

        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    public function createPoll(string $question, bool $isActive, ?int $createdByUserId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO polls (question, created_by, is_active, created_at) VALUES (:question, :created_by, :is_active, NOW())'
        );
        $stmt->execute([
            'question' => $question,
            'created_by' => $createdByUserId,
            'is_active' => $isActive ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function addOption(int $pollId, string $optionText): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO poll_options (poll_id, text, created_at) VALUES (:poll_id, :text, NOW())');
        $stmt->execute([
            'poll_id' => $pollId,
            'text' => $optionText,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function deletePoll(int $pollId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM polls WHERE id = :id');
        $stmt->execute(['id' => $pollId]);
    }

    public function hasUserVoted(int $pollId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM poll_votes WHERE poll_id = :poll_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            'poll_id' => $pollId,
            'user_id' => $userId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function getResults(int $pollId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.id, o.text AS option_text, COALESCE(COUNT(v.id), 0) AS vote_count
             FROM poll_options o
             LEFT JOIN poll_votes v ON v.option_id = o.id AND v.poll_id = o.poll_id
             WHERE o.poll_id = :poll_id
             GROUP BY o.id, o.text
             ORDER BY o.id ASC'
        );
        $stmt->execute(['poll_id' => $pollId]);

        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    public function addVote(int $pollId, int $optionId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO poll_votes (poll_id, option_id, user_id, created_at)
             SELECT :poll_id_value, :option_id_value, :user_id, NOW()
             FROM poll_options
             WHERE id = :option_id_filter AND poll_id = :poll_id_filter'
        );
        $stmt->execute([
            'poll_id_value' => $pollId,
            'option_id_value' => $optionId,
            'user_id' => $userId,
            'option_id_filter' => $optionId,
            'poll_id_filter' => $pollId,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new PDOException('Option does not belong to the selected poll.');
        }
    }
}
