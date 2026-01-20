<?php
// AI-GENERATED: Activity repository enriches actor names via users join (GitHub Copilot / ChatGPT), 2026-01-20

namespace App\Repositories;

use App\Database;
use PDO;

class ActivityRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * @param array<string,mixed> $event
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
