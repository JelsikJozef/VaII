<?php
// AI-GENERATED: Knowledge base articles controller (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use App\Repositories\ManualRepository;
use App\Services\MarkdownRenderer;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\JsonResponse;
use Framework\Http\Session;

/**
 * Manual / knowledge-base controller.
 *
 * Features:
 * - Browsing and searching articles (with optional filters).
 * - Viewing a single article.
 * - Admin-only management actions (create/edit/delete and related helpers).
 * - Markdown is rendered to safe HTML via {@see MarkdownRenderer}.
 *
 * Authorization:
 * - index(), show(): require login
 * - all other actions: role `admin`
 *
 * Side-effects:
 * - Mutates articles via {@see ManualRepository} for admin actions.
 * - Uses session keys `manual.success` / `manual.error` as flash messages.
 * - Some actions return JSON (e.g. internal helpers / async operations).
 */
class ManualController extends BaseController
{
    private ?ManualRepository $repository = null;
    private ?Session $flashSession = null;
    private ?MarkdownRenderer $markdown = null;

    public function authorize(Request $request, string $action): bool
    {
        $action = strtolower($action);
        if (in_array($action, ['index', 'show'], true)) {
            return $this->requireLogin();
        }

        return $this->requireRole(['admin']);
    }

    public function index(Request $request): Response
    {
        $q = trim((string)($request->get('q') ?? ''));
        $category = trim((string)($request->get('category') ?? ''));
        $difficultyRaw = trim((string)($request->get('difficulty') ?? ''));
        $allowedDifficulties = ['easy', 'medium', 'hard'];
        $difficulty = in_array($difficultyRaw, $allowedDifficulties, true) ? $difficultyRaw : '';

        $articles = $this->repo()->findAllArticles(
            $q !== '' ? $q : null,
            $category !== '' ? $category : null,
            $difficulty !== '' ? $difficulty : null
        );

        $renderer = $this->markdownRenderer();
        foreach ($articles as &$article) {
            $raw = (string)($article['content'] ?? '');
            $safeHtml = $renderer->toSafeHtml($raw);
            $article['content_html'] = $safeHtml;
            $article['content_plain'] = trim(strip_tags($safeHtml));
        }
        unset($article);

        return $this->html([
            'activeModule' => 'manual',
            'articles' => $articles,
            'q' => $q,
            'category' => $category,
            'difficulty' => $difficulty,
            'canManage' => $this->requireRole(['admin']),
            'successMessage' => $this->consumeFlash('manual.success'),
            'errorMessage' => $this->consumeFlash('manual.error'),
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int)($request->get('id') ?? 0);
        if ($id <= 0) {
            $this->flash('manual.error', 'Article not found.');
            return $this->redirect($this->url('Manual.index'));
        }

        $article = $this->repo()->findArticleById($id);
        if ($article === null) {
            $this->flash('manual.error', 'Article not found.');
            return $this->redirect($this->url('Manual.index'));
        }

        $renderer = $this->markdownRenderer();
        $article['content_html'] = $renderer->toSafeHtml((string)($article['content'] ?? ''));

        $attachments = $this->repo()->listAttachments($id);

        return $this->html([
            'activeModule' => 'manual',
            'article' => $article,
            'attachments' => $attachments,
            'canManage' => $this->requireRole(['admin']),
            'successMessage' => $this->consumeFlash('manual.success'),
            'errorMessage' => $this->consumeFlash('manual.error'),
        ], 'show');
    }

    public function new(Request $request): Response
    {
        return $this->html([
            'activeModule' => 'manual',
            'errors' => [],
            'title' => '',
            'category' => '',
            'difficulty' => '',
            'content' => '',
        ], 'new');
    }

    public function store(Request $request): Response
    {
        $input = $this->collectInput($request);
        [$errors, $normalized] = $this->validateInput($input);

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'manual',
                'errors' => $errors,
                'title' => $input['title'],
                'category' => $input['category'],
                'difficulty' => $input['difficulty'],
                'content' => $input['content'],
            ], 'new');
        }

        $normalized['created_by_user_id'] = $this->user?->getIdentity()?->getId();
        $this->repo()->createArticle($normalized);

        $this->flash('manual.success', 'Article created successfully.');

        return $this->redirect($this->url('Manual.index'));
    }

    public function edit(Request $request): Response
    {
        $id = (int)($request->get('id') ?? 0);
        if ($id <= 0) {
            $this->flash('manual.error', 'Article not found.');
            return $this->redirect($this->url('Manual.index'));
        }

        $article = $this->repo()->findArticleById($id);
        if ($article === null) {
            $this->flash('manual.error', 'Article not found.');
            return $this->redirect($this->url('Manual.index'));
        }

        return $this->html([
            'activeModule' => 'manual',
            'errors' => [],
            'id' => $id,
            'title' => (string)($article['title'] ?? ''),
            'category' => (string)($article['category'] ?? ''),
            'difficulty' => (string)($article['difficulty'] ?? ''),
            'content' => (string)($article['content'] ?? ''),
        ], 'edit');
    }

    public function update(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        if ($id <= 0) {
            $this->flash('manual.error', 'Article not found.');
            return $this->redirect($this->url('Manual.index'));
        }

        $existing = $this->repo()->findArticleById($id);
        if ($existing === null) {
            $this->flash('manual.error', 'Article not found.');
            return $this->redirect($this->url('Manual.index'));
        }

        $input = $this->collectInput($request);
        [$errors, $normalized] = $this->validateInput($input);

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'manual',
                'errors' => $errors,
                'id' => $id,
                'title' => $input['title'],
                'category' => $input['category'],
                'difficulty' => $input['difficulty'],
                'content' => $input['content'],
            ], 'edit');
        }

        $this->repo()->updateArticle($id, $normalized);
        $this->flash('manual.success', 'Article updated successfully.');

        return $this->redirect($this->url('Manual.show', ['id' => $id]));
    }

    public function delete(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        if ($id <= 0) {
            $this->flash('manual.error', 'Article not found.');
            return $this->redirect($this->url('Manual.index'));
        }

        $existing = $this->repo()->findArticleById($id);
        if ($existing === null) {
            $this->flash('manual.error', 'Article not found.');
            return $this->redirect($this->url('Manual.index'));
        }

        $this->repo()->deleteArticle($id);
        $this->flash('manual.success', 'Article deleted.');

        return $this->redirect($this->url('Manual.index'));
    }

    public function uploadAttachmentJson(Request $request): Response
    {
        if (!$this->requireRole(['admin'])) {
            if (!$request->isAjax()) {
                $this->flash('manual.error', 'Forbidden');
                return $this->redirect($this->url('Manual.show', ['id' => (int)($request->get('id') ?? 0)]));
            }
            return $this->attachmentError('Forbidden', 403);
        }

        $articleId = (int)($request->get('id') ?? 0);
        if ($articleId <= 0) {
            if (!$request->isAjax()) {
                $this->flash('manual.error', 'Article not found.');
                return $this->redirect($this->url('Manual.index'));
            }
            return $this->attachmentError('Article not found.', 404);
        }

        $article = $this->repo()->findArticleById($articleId);
        if ($article === null) {
            if (!$request->isAjax()) {
                $this->flash('manual.error', 'Article not found.');
                return $this->redirect($this->url('Manual.index'));
            }
            return $this->attachmentError('Article not found.', 404);
        }

        $uploaded = $request->file('file');
        if ($uploaded === null) {
            if (!$request->isAjax()) {
                $this->flash('manual.error', 'File is required.');
                return $this->redirect($this->url('Manual.show', ['id' => $articleId]));
            }
            return $this->attachmentError('File is required.', 400, ['file' => ['File is required.']]);
        }

        if (!$uploaded->isOk()) {
            $msg = $uploaded->getErrorMessage() ?? 'Upload failed.';
            if (!$request->isAjax()) {
                $this->flash('manual.error', $msg);
                return $this->redirect($this->url('Manual.show', ['id' => $articleId]));
            }
            return $this->attachmentError($msg, 400, ['file' => [$msg]]);
        }

        $maxSize = 10 * 1024 * 1024;
        if ($uploaded->getSize() > $maxSize) {
            if (!$request->isAjax()) {
                $this->flash('manual.error', 'File is too large (max 10 MB).');
                return $this->redirect($this->url('Manual.show', ['id' => $articleId]));
            }
            return $this->attachmentError('File is too large (max 10 MB).', 400, ['file' => ['File is too large (max 10 MB).']]);
        }

        $mime = $this->detectMimeType($uploaded->getFileTempPath()) ?? $uploaded->getType();
        $allowed = [
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
        ];

        if (!array_key_exists($mime, $allowed)) {
            return $this->attachmentError('Unsupported file type.', 400, ['file' => ['Allowed types: PDF, DOCX, PNG, JPG.']]);
        }

        $extension = $allowed[$mime];
        $originalName = $this->sanitizeOriginalFilename($uploaded->getName());
        $storageDir = $this->getAttachmentStorageDir();

        if (!is_dir($storageDir) && !mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
            return $this->attachmentError('Storage path unavailable.', 500);
        }

        $storedName = '';
        $targetPath = '';
        do {
            $storedName = $this->generateStoredFilename($extension);
            $targetPath = $storageDir . DIRECTORY_SEPARATOR . $storedName;
        } while (file_exists($targetPath));

        if (!$uploaded->store($targetPath)) {
            return $this->attachmentError('Failed to save uploaded file.', 500, ['file' => ['Failed to save uploaded file.']]);
        }

        $relativePath = 'uploads/manual/' . $storedName;

        try {
            $attachmentId = $this->repo()->addAttachment($articleId, $relativePath, null);
        } catch (\Throwable $e) {
            @unlink($targetPath);
            if (!$request->isAjax()) {
                $this->flash('manual.error', 'Could not save attachment.');
                return $this->redirect($this->url('Manual.show', ['id' => $articleId]));
            }
            return $this->attachmentError('Could not save attachment.', 500);
        }

        if (!$request->isAjax()) {
            $this->flash('manual.success', 'Attachment uploaded.');
            return $this->redirect($this->url('Manual.show', ['id' => $articleId]));
        }

        return $this->json([
            'ok' => true,
            'attachment' => [
                'id' => $attachmentId,
                'file_path' => $relativePath,
                'url' => null,
                'description' => null,
            ],
        ]);
    }

    public function deleteAttachment(Request $request): Response
    {
        if (!$this->requireRole(['admin'])) {
            $this->flash('manual.error', 'You are not allowed to delete attachments.');
            return $this->redirect($this->url('Manual.index'));
        }

        $articleId = (int)($request->get('id') ?? 0);
        $attachmentId = (int)($request->get('attId') ?? $request->post('attId') ?? 0);

        if ($articleId <= 0 || $attachmentId <= 0) {
            $this->flash('manual.error', 'Attachment not found.');
            return $this->redirect($this->url('Manual.index'));
        }

        $article = $this->repo()->findArticleById($articleId);
        if ($article === null) {
            $this->flash('manual.error', 'Article not found.');
            return $this->redirect($this->url('Manual.index'));
        }

        $attachment = $this->repo()->findAttachmentById($attachmentId);
        if ($attachment === null || (int)($attachment['article_id'] ?? 0) !== $articleId) {
            $this->flash('manual.error', 'Attachment not found.');
            return $this->redirect($this->url('Manual.show', ['id' => $articleId]));
        }

        $filePath = (string)($attachment['file_path'] ?? '');
        if ($filePath !== '') {
            $fullPath = $this->getPublicDir() . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filePath);
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $this->repo()->deleteAttachment($attachmentId);

        $this->flash('manual.success', 'Attachment deleted.');
        return $this->redirect($this->url('Manual.show', ['id' => $articleId]));
    }

    private function collectInput(Request $request): array
    {
        return [
            'title' => trim((string)($request->post('title') ?? '')),
            'category' => trim((string)($request->post('category') ?? '')),
            'difficulty' => trim((string)($request->post('difficulty') ?? '')),
            'content' => trim((string)($request->post('content') ?? '')),
        ];
    }

    private function validateInput(array $input): array
    {
        $errors = [];

        $title = $input['title'];
        $category = $input['category'];
        $difficulty = $input['difficulty'];
        $content = $input['content'];

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

    private function repo(): ManualRepository
    {
        if ($this->repository === null) {
            $this->repository = new ManualRepository();
        }

        return $this->repository;
    }

    private function markdownRenderer(): MarkdownRenderer
    {
        if ($this->markdown === null) {
            $this->markdown = new MarkdownRenderer();
        }

        return $this->markdown;
    }

    private function sanitizeOriginalFilename(string $name): string
    {
        $trimmed = trim(str_replace("\0", '', $name));
        $basename = basename($trimmed);
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $basename);
        if ($safe === '') {
            $safe = 'file';
        }
        return mb_substr($safe, 0, 255);
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

    private function attachmentError(string $message, int $status, array $fieldErrors = []): JsonResponse
    {
        return $this->json([
            'ok' => false,
            'message' => $message,
            'fields' => $fieldErrors,
        ])->setStatusCode($status);
    }

    private function flash(string $key, mixed $value): void
    {
        if ($this->flashSession === null) {
            $this->flashSession = new Session('flash');
        }
        $this->flashSession->set($key, $value);
    }

    private function consumeFlash(string $key): ?string
    {
        if ($this->flashSession === null) {
            $this->flashSession = new Session('flash');
        }

        $value = $this->flashSession->get($key);
        $this->flashSession->unset($key);

        return $value === null ? null : (string)$value;
    }
}
