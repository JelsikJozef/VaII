<?php
// AI-GENERATED: Knowledge base articles repository (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Repositories;

use App\Database;
use PDO;
use PDOException;

class ManualRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * List articles with optional search and filters.
     */
    public function findAllArticles(?string $q = null, ?string $category = null, ?string $difficulty = null): array
    {
        $sql = 'SELECT * FROM knowledge_articles WHERE 1=1';
        $params = [];

        if ($q !== null && trim($q) !== '') {
            $sql .= ' AND (LOWER(title) LIKE :q OR LOWER(content) LIKE :q_content)';
            $needle = '%' . strtolower(trim($q)) . '%';
            $params['q'] = $needle;
            $params['q_content'] = $needle;
        }

        if ($category !== null && trim($category) !== '') {
            $sql .= ' AND LOWER(category) LIKE :category';
            $params['category'] = '%' . strtolower(trim($category)) . '%';
        }

        if ($difficulty !== null && trim($difficulty) !== '') {
            $sql .= ' AND LOWER(difficulty) = :difficulty';
            $params['difficulty'] = strtolower(trim($difficulty));
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    /**
     * Find single article by id.
     */
    public function findArticleById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM knowledge_articles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Create new article and return its id.
     */
    public function createArticle(array $data): int
    {
        $params = [
            'title' => $data['title'],
            'category' => $data['category'],
            'difficulty' => $data['difficulty'],
            'content' => $data['content'],
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'created_by' => $data['created_by_user_id'] ?? null,
        ];

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO knowledge_articles (title, category, difficulty, content, created_by_user_id, created_at, updated_at)
                 VALUES (:title, :category, :difficulty, :content, :created_by_user_id, NOW(), NOW())'
            );
            $stmt->execute($params);
        } catch (PDOException $e) {
            if ($e->getCode() !== '42S22') {
                throw $e;
            }

            try {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO knowledge_articles (title, category, difficulty, content, created_by, created_at, updated_at)
                     VALUES (:title, :category, :difficulty, :content, :created_by, NOW(), NOW())'
                );
                $stmt->execute([
                    'title' => $params['title'],
                    'category' => $params['category'],
                    'difficulty' => $params['difficulty'],
                    'content' => $params['content'],
                    'created_by' => $params['created_by'],
                ]);
            } catch (PDOException $inner) {
                if ($inner->getCode() !== '42S22' && $inner->getCode() !== '23000') {
                    throw $inner;
                }

                // Last fallback: insert without creator columns if schema lacks both
                $stmt = $this->pdo->prepare(
                    'INSERT INTO knowledge_articles (title, category, difficulty, content, created_at, updated_at)
                     VALUES (:title, :category, :difficulty, :content, NOW(), NOW())'
                );
                $stmt->execute([
                    'title' => $params['title'],
                    'category' => $params['category'],
                    'difficulty' => $params['difficulty'],
                    'content' => $params['content'],
                ]);
            }
        }

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update article by id.
     */
    public function updateArticle(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE knowledge_articles SET title = :title, category = :category, difficulty = :difficulty, content = :content, updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'title' => $data['title'],
            'category' => $data['category'],
            'difficulty' => $data['difficulty'],
            'content' => $data['content'],
            'id' => $id,
        ]);
    }

    /**
     * Delete article by id.
     */
    public function deleteArticle(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM knowledge_articles WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * List attachments for the given article.
     */
    public function listAttachments(int $articleId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, article_id, file_path, url, description, created_at FROM attachments WHERE article_id = :id ORDER BY created_at DESC'
        );
        $stmt->execute(['id' => $articleId]);
        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    /**
     * Insert a new attachment and return its id.
     */
    public function addAttachment(int $articleId, string $filePath, ?string $description = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO attachments (article_id, file_path, description) VALUES (:article_id, :file_path, :description)'
        );
        $stmt->execute([
            'article_id' => $articleId,
            'file_path' => $filePath,
            'description' => $description,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Find single attachment by id.
     */
    public function findAttachmentById(int $attId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, article_id, file_path, url, description FROM attachments WHERE id = :id'
        );
        $stmt->execute(['id' => $attId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Delete attachment by id.
     */
    public function deleteAttachment(int $attId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM attachments WHERE id = :id');
        $stmt->execute(['id' => $attId]);
    }
}
