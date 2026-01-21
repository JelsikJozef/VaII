<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ManualRepository;
use Framework\Http\UploadedFile;
use App\Services\MarkdownRenderer;
use PDOException;

/**
 * Manual/knowledge base business logic.
 * HTTP-agnostic; returns DomainResult only.
 */
class ManualService
{
    private const ALLOWED_MIME = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
    ];
    private const MAX_UPLOAD_BYTES = 10485760; // 10 MB

    public function __construct(
        private readonly ManualRepository $manuals = new ManualRepository(),
        private readonly MarkdownRenderer $markdown = new MarkdownRenderer(),
    ) {
    }

    /** @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user */
    public function index(array $user, array $filters = []): array
    {
        $q = $filters['q'] ?? null;
        $category = $filters['category'] ?? null;
        $difficulty = $filters['difficulty'] ?? null;

        $articles = $this->manuals->findAllArticles(
            $q !== '' ? $q : null,
            $category !== '' ? $category : null,
            $difficulty !== '' ? $difficulty : null
        );

        foreach ($articles as &$article) {
            $raw = (string)($article['content'] ?? '');
            $safeHtml = $this->markdown->toSafeHtml($raw);
            $article['content_html'] = $safeHtml;
            $article['content_plain'] = trim(strip_tags($safeHtml));
            $article['canEdit'] = $this->canManage($user['role'] ?? null);
            $article['canDelete'] = $this->canManage($user['role'] ?? null);
        }
        unset($article);

        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'manual',
                'articles' => $articles,
                'canManage' => $this->canManage($user['role'] ?? null),
            ],
        ];
    }

    /** @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user */
    public function show(array $user, int $id): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'payload' => [], 'flash' => ['type' => 'error', 'message' => 'Article not found.']];
        }

        $article = $this->manuals->findArticleById($id);
        if ($article === null) {
            return ['ok' => false, 'payload' => [], 'flash' => ['type' => 'error', 'message' => 'Article not found.']];
        }

        $article['content_html'] = $this->markdown->toSafeHtml((string)($article['content'] ?? ''));
        $attachments = $this->manuals->listAttachments($id);

        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'manual',
                'article' => $article,
                'attachments' => $attachments,
                'canManage' => $this->canManage($user['role'] ?? null),
            ],
        ];
    }

    /** @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user */
    public function newForm(array $user): array
    {
        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'manual',
                'errors' => [],
                'title' => '',
                'category' => '',
                'difficulty' => '',
                'content' => '',
            ],
        ];
    }

    /**
     * @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user
     * @param array<string,mixed> $input
     * @param UploadedFile|array|null $uploadedFiles
     */
    public function store(array $user, array $input, UploadedFile|array|null $uploadedFiles = null): array
    {
        if (!$this->canManage($user['role'] ?? null)) {
            return ['ok' => false, 'payload' => [], 'flash' => ['type' => 'error', 'message' => 'Forbidden']];
        }

        [$errors, $normalized] = $this->validateInput($input);
        if (!empty($errors)) {
            return [
                'ok' => false,
                'payload' => [
                    'activeModule' => 'manual',
                    'title' => $input['title'] ?? '',
                    'category' => $input['category'] ?? '',
                    'difficulty' => $input['difficulty'] ?? '',
                    'content' => $input['content'] ?? '',
                ],
                'errors' => $errors,
            ];
        }

        $normalized['created_by_user_id'] = $user['userId'] ?? null;

        try {
            $articleId = $this->manuals->createArticle($normalized);
            $this->handleUpload($articleId, $uploadedFiles);
        } catch (PDOException $e) {
            return ['ok' => false, 'payload' => [], 'flash' => ['type' => 'error', 'message' => 'Could not create article.']];
        }

        return [
            'ok' => true,
            'payload' => ['id' => $articleId],
            'flash' => ['type' => 'success', 'message' => 'Article created successfully.'],
        ];
    }

    /**
     * @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user
     * @param array<string,mixed> $input
     * @param UploadedFile|array|null $uploadedFiles
     */
    public function update(array $user, int $id, array $input, UploadedFile|array|null $uploadedFiles = null): array
    {
        if (!$this->canManage($user['role'] ?? null)) {
            return ['ok' => false, 'payload' => [], 'flash' => ['type' => 'error', 'message' => 'Forbidden']];
        }

        $existing = $this->manuals->findArticleById($id);
        if ($existing === null) {
            return ['ok' => false, 'payload' => [], 'flash' => ['type' => 'error', 'message' => 'Article not found.']];
        }

        [$errors, $normalized] = $this->validateInput($input);
        if (!empty($errors)) {
            return [
                'ok' => false,
                'payload' => [
                    'activeModule' => 'manual',
                    'id' => $id,
                    'title' => $input['title'] ?? '',
                    'category' => $input['category'] ?? '',
                    'difficulty' => $input['difficulty'] ?? '',
                    'content' => $input['content'] ?? '',
                ],
                'errors' => $errors,
            ];
        }

        try {
            $this->manuals->updateArticle($id, $normalized);
            $this->handleUpload($id, $uploadedFiles);
        } catch (PDOException $e) {
            return ['ok' => false, 'payload' => [], 'flash' => ['type' => 'error', 'message' => 'Could not update article.']];
        }

        return [
            'ok' => true,
            'payload' => ['id' => $id],
            'flash' => ['type' => 'success', 'message' => 'Article updated successfully.'],
        ];
    }

    /**
     * @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user
     */
    public function editForm(array $user, int $id): array
    {
        $article = $this->manuals->findArticleById($id);
        if ($article === null) {
            return ['ok' => false, 'payload' => [], 'flash' => ['type' => 'error', 'message' => 'Article not found.']];
        }

        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'manual',
                'errors' => [],
                'id' => $id,
                'title' => (string)($article['title'] ?? ''),
                'category' => (string)($article['category'] ?? ''),
                'difficulty' => (string)($article['difficulty'] ?? ''),
                'content' => (string)($article['content'] ?? ''),
            ],
        ];
    }

    /**
     * @param array{userId?:int|null,role?:string|null,isLoggedIn:bool} $user
     */
    public function delete(array $user, int $id): array
    {
        if (!$this->canManage($user['role'] ?? null)) {
            return ['ok' => false, 'payload' => [], 'flash' => ['type' => 'error', 'message' => 'Forbidden']];
        }

        $article = $this->manuals->findArticleById($id);
        if ($article === null) {
            return ['ok' => false, 'payload' => [], 'flash' => ['type' => 'error', 'message' => 'Article not found.']];
        }

        // delete attachments from disk then repo
        $attachments = $this->manuals->listAttachments($id);
        foreach ($attachments as $att) {
            $this->deleteAttachmentFile((string)($att['file_path'] ?? ''));
            $this->manuals->deleteAttachment((int)($att['id'] ?? 0));
        }

        $this->manuals->deleteArticle($id);

        return [
            'ok' => true,
            'payload' => [],
            'flash' => ['type' => 'success', 'message' => 'Article deleted.'],
        ];
    }

    private function canManage(?string $role): bool
    {
        return $role === 'admin';
    }

    /**
     * @param array<string,mixed> $input
     * @return array{0:array<string,array<int,string>>,1:array<string,mixed>}
     */
    private function validateInput(array $input): array
    {
        $errors = [];
        $title = trim((string)($input['title'] ?? ''));
        $category = trim((string)($input['category'] ?? ''));
        $difficulty = trim((string)($input['difficulty'] ?? ''));
        $content = trim((string)($input['content'] ?? ''));

        if ($title === '') {
            $errors['title'][] = 'Title is required.';
        } elseif (mb_strlen($title) < 3 || mb_strlen($title) > 255) {
            $errors['title'][] = 'Title must be between 3 and 255 characters.';
        }

        if ($category !== '' && mb_strlen($category) > 255) {
            $errors['category'][] = 'Category must be at most 255 characters.';
        }

        $allowedDifficulties = ['easy', 'medium', 'hard'];
        if ($difficulty !== '' && !in_array($difficulty, $allowedDifficulties, true)) {
            $errors['difficulty'][] = 'Invalid difficulty selected.';
        }

        if ($content === '') {
            $errors['content'][] = 'Content is required.';
        } elseif (mb_strlen($content) < 10) {
            $errors['content'][] = 'Content must be at least 10 characters long.';
        }

        $normalized = [
            'title' => $title,
            'category' => $category !== '' ? $category : null,
            'difficulty' => $difficulty !== '' ? $difficulty : null,
            'content' => $content,
        ];

        return [$errors, $normalized];
    }

    private function handleUpload(int $articleId, UploadedFile|array|null $uploadedFiles): void
    {
        if ($uploadedFiles === null) {
            return;
        }
        $files = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }
            if (!$file->isOk()) {
                continue;
            }
            if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
                continue;
            }
            $mime = $this->detectMimeType($file->getFileTempPath()) ?? $file->getType();
            if (!array_key_exists($mime, self::ALLOWED_MIME)) {
                continue;
            }
            $extension = self::ALLOWED_MIME[$mime];
            $storedName = $this->generateStoredFilename($extension);
            $targetPath = $this->getAttachmentStorageDir() . DIRECTORY_SEPARATOR . $storedName;
            if (!is_dir(dirname($targetPath))) {
                @mkdir(dirname($targetPath), 0775, true);
            }
            if ($file->store($targetPath)) {
                $relative = 'uploads/manual/' . $storedName;
                $this->manuals->addAttachment($articleId, $relative, null);
            }
        }
    }

    private function deleteAttachmentFile(string $relativePath): void
    {
        if ($relativePath === '') {
            return;
        }
        $fullPath = $this->getPublicDir() . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function generateStoredFilename(string $extension): string
    {
        $base = bin2hex(random_bytes(16));
        return $base . '.' . $extension;
    }

    private function getAttachmentStorageDir(): string
    {
        return $this->getPublicDir() . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'manual';
    }

    private function getPublicDir(): string
    {
        $publicDir = realpath(__DIR__ . '/../../public');
        if ($publicDir === false) {
            $publicDir = __DIR__ . '/../../public';
        }
        return rtrim($publicDir, DIRECTORY_SEPARATOR);
    }

    private function detectMimeType(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }
        $mime = finfo_file($finfo, $path) ?: null;
        finfo_close($finfo);
        return $mime ? strtolower($mime) : null;
    }
}
