<?php
// AI-GENERATED: Activity repository enriches actor names via users join (GitHub Copilot / ChatGPT), 2026-01-20

namespace App\Repositories;

use App\Database;
use PDO;

/**
 * Activity log repository.
 *
 * Persists and reads the application activity feed from the `activity_log` table.
 * The home page uses {@see latest()} to display recent actions.
 *
 * Storage model (columns used):
 * - id (auto increment)
 * - created_at (timestamp)
 * - user_id (nullable FK to users.id)
 * - actor_name / actor_email (nullable overrides; falls back to users table)
 * - entity / entity_id (optional reference to a domain entity)
 * - action (string event key, e.g. `transaction.created`)
 * - title (short human text)
 * - details (optional longer description)
 * - meta_json (optional JSON string, arbitrary metadata)
 *
 * Notes on enrichment:
 * - `latest()` does a LEFT JOIN to `users` and uses COALESCE so that if
 *   `actor_name`/`actor_email` are not stored in the activity row, the feed can
 *   still show the user's current name/email.
 */
class ActivityRepository
{
    /**
     * PDO connection used for all queries.
     */
    private PDO $pdo;

    /**
     * @param PDO|null $pdo Optional PDO to support testing/DI. When null, uses
     *                      {@see Database::getConnection()}.
     */
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Insert an activity event.
     *
     * Expected input keys (all optional unless specified):
     * - user_id: int|null
     * - actor_name: string|null (override display name)
     * - actor_email: string|null (override display email)
     * - entity: string (required; defaults to empty string)
     * - entity_id: int|string|null
     * - action: string (required; defaults to empty string)
     * - title: string (required; defaults to empty string)
     * - details: string|null
     * - meta_json: string|null (JSON string)
     *
     * @param array<string,mixed> $event Event payload.
     *
     * @return int Inserted row id.
     */
    public function log(array $event): int
    {
        $sql = 'INSERT INTO activity_log (created_at, user_id, actor_name, actor_email, entity, entity_id, action, title, details, meta_json)
                VALUES (NOW(), :user_id, :actor_name, :actor_email, :entity, :entity_id, :action, :title, :details, :meta_json)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $event['user_id'] ?? null,
            'actor_name' => $event['actor_name'] ?? null,
            'actor_email' => $event['actor_email'] ?? null,
            'entity' => $event['entity'] ?? '',
            'entity_id' => $event['entity_id'] ?? null,
            'action' => $event['action'] ?? '',
            'title' => $event['title'] ?? '',
            'details' => $event['details'] ?? null,
            'meta_json' => $event['meta_json'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Fetch the most recent activity events.
     *
     * Filtering:
     * - $excludeActions removes exact action keys.
     * - $excludePrefixes removes action keys by prefix (LIKE 'prefix%').
     *
     * Behavior:
     * - Always returns an array.
     * - If the DB query fails, returns an empty array (exceptions are swallowed).
     * - $limit is clamped to at least 1.
     *
     * Returned row shape (keys):
     * - id, created_at, user_id
     * - actor_name, actor_email (COALESCE(row override, users table))
     * - entity, entity_id, action, title, details, meta_json
     *
     * @param int $limit Max number of rows.
     * @param array<int,string> $excludeActions List of exact action keys to exclude.
     * @param array<int,string> $excludePrefixes List of action prefixes to exclude.
     *
     * @return array<int,array<string,mixed>>
     */
    public function latest(int $limit = 10, array $excludeActions = [], array $excludePrefixes = []): array
    {
        $limit = max(1, $limit);
        $conditions = [];
        $params = [];

        if (!empty($excludeActions)) {
            $placeholders = [];
            foreach (array_values($excludeActions) as $idx => $action) {
                $ph = 'ex_act_' . $idx;
                $placeholders[] = ':' . $ph;
                $params[$ph] = $action;
            }
            $conditions[] = 'a.action NOT IN (' . implode(', ', $placeholders) . ')';
        }

        if (!empty($excludePrefixes)) {
            foreach (array_values($excludePrefixes) as $idx => $prefix) {
                $ph = 'ex_pref_' . $idx;
                $conditions[] = 'a.action NOT LIKE :' . $ph;
                $params[$ph] = $prefix . '%';
            }
        }

        $whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions) . ' ') : '';

        try {
            $stmt = $this->pdo->prepare(
                'SELECT a.id, a.created_at, a.user_id, '
                . 'COALESCE(a.actor_name, u.name) AS actor_name, '
                . 'COALESCE(a.actor_email, u.email) AS actor_email, '
                . 'a.entity, a.entity_id, a.action, a.title, a.details, a.meta_json '
                . 'FROM activity_log a '
                . 'LEFT JOIN users u ON u.id = a.user_id '
                . $whereSql
                . 'ORDER BY a.created_at DESC, a.id DESC LIMIT :lim'
            );
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val, PDO::PARAM_STR);
            }
            $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } catch (\PDOException) {
            return [];
        }

        return is_array($rows) ? $rows : [];
    }
}
