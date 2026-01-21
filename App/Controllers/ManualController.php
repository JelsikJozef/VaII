<?php
// AI-GENERATED: Knowledge base articles controller (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use App\Repositories\ManualRepository;
use App\Services\ManualService;
use App\Services\MarkdownRenderer;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\JsonResponse;
use Framework\Http\Session;

class ManualController extends BaseController
{
    private ?ManualRepository $repository = null;
    private ?ManualService $service = null;
    private ?Session $flashSession = null;

    public function authorize(Request $request, string $action): bool
    {
        return $this->requireLogin();
    }

    public function index(Request $request): Response
    {
        $result = $this->svc()->index($this->userContext(), [
            'q' => $request->get('q'),
            'category' => $request->get('category'),
            'difficulty' => $request->get('difficulty'),
        ]);

        $data = $result['payload'] ?? [];
        $data['q'] = (string)($request->get('q') ?? '');
        $data['category'] = (string)($request->get('category') ?? '');
        $data['difficulty'] = (string)($request->get('difficulty') ?? '');
        $data['successMessage'] = $this->consumeFlash('manual.success');
        $data['errorMessage'] = $this->consumeFlash('manual.error');

        return $this->html($data);
    }

    public function show(Request $request): Response
    {
        $id = (int)($request->get('id') ?? 0);
        $result = $this->svc()->show($this->userContext(), $id);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            return $this->redirect($this->url('Manual.index'));
        }

        $data = $result['payload'] ?? [];
        $data['successMessage'] = $this->consumeFlash('manual.success');
        $data['errorMessage'] = $this->consumeFlash('manual.error');

        return $this->html($data, 'show');
    }

    public function new(Request $request): Response
    {
        $result = $this->svc()->newForm($this->userContext());
        return $this->html($result['payload'] ?? [], 'new');
    }

    public function store(Request $request): Response
    {
        $result = $this->svc()->store(
            $this->userContext(),
            $this->collectInput($request),
            $request->file('file')
        );

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            $data = $result['payload'] ?? [];
            $data['errors'] = $result['errors'] ?? [];
            return $this->html($data, 'new');
        }

        return $this->redirect($this->url('Manual.index'));
    }

    public function edit(Request $request): Response
    {
        $id = (int)($request->get('id') ?? 0);
        $result = $this->svc()->editForm($this->userContext(), $id);
        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }
        if (!($result['ok'] ?? false)) {
            return $this->redirect($this->url('Manual.index'));
        }
        return $this->html($result['payload'] ?? [], 'edit');
    }

    public function update(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        $result = $this->svc()->update(
            $this->userContext(),
            $id,
            $this->collectInput($request),
            $request->file('file')
        );

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            $data = $result['payload'] ?? [];
            $data['errors'] = $result['errors'] ?? [];
            return $this->html($data, 'edit');
        }

        return $this->redirect($this->url('Manual.show', ['id' => $id]));
    }

    public function delete(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        $result = $this->svc()->delete($this->userContext(), $id);
        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }
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

        // Store an internal relative path (NOT a public URL)
        $relativePath = 'manual/' . $storedName;

        try {
            $attachmentId = $this->repo()->addAttachment($articleId, $relativePath, null);
        } catch (\Throwable $e) {
            // Do not suppress filesystem errors
            if (is_file($targetPath)) {
                unlink($targetPath);
            }
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
            $absolute = $this->resolveAttachmentAbsolutePath($filePath);
            if ($absolute !== null && is_file($absolute)) {
                unlink($absolute);
            }
        }

        $this->repo()->deleteAttachment($attachmentId);

        $this->flash('manual.success', 'Attachment deleted.');
        return $this->redirect($this->url('Manual.show', ['id' => $articleId]));
    }

    /**
     * Download a manual attachment via a controlled endpoint.
     *
     * Requires login (and admin if your rule is that manual access is restricted).
     */
    public function downloadAttachment(Request $request): Response
    {
        // Manual controller authorize() already requires login.
        // If you want *admin only* downloads, uncomment:
        // if (!$this->requireRole(['admin'])) { return $this->redirect($this->url('Manual.index')); }

        $attachmentId = (int)($request->get('id') ?? 0);
        if ($attachmentId <= 0) {
            return $this->redirect($this->url('Manual.index'));
        }

        $attachment = $this->repo()->findAttachmentById($attachmentId);
        if ($attachment === null) {
            return $this->redirect($this->url('Manual.index'));
        }

        $filePath = (string)($attachment['file_path'] ?? '');
        if ($filePath === '') {
            return $this->redirect($this->url('Manual.index'));
        }

        $absolute = $this->resolveAttachmentAbsolutePath($filePath);
        if ($absolute === null || !is_file($absolute) || !is_readable($absolute)) {
            return $this->redirect($this->url('Manual.index'));
        }

        $downloadName = basename($filePath);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($absolute) ?: 'application/octet-stream';

        if (ob_get_level() > 0) {
            // Avoid corrupting binary output
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . $this->headerSafeFilename($downloadName) . '"');
        header('Content-Length: ' . (string)filesize($absolute));

        $fp = fopen($absolute, 'rb');
        if ($fp === false) {
            return $this->redirect($this->url('Manual.index'));
        }
        fpassthru($fp);
        fclose($fp);
        exit;
    }

    /**
     * Attachment storage directory (non-public).
     */
    private function getAttachmentStorageDir(): string
    {
        // Project root: App/Controllers -> App -> project
        $projectRoot = dirname(__DIR__, 2);
        return $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'manual';
    }

    /**
     * Resolve internal file_path to an absolute path, enforcing that it stays under the storage base dir.
     */
    private function resolveAttachmentAbsolutePath(string $internalFilePath): ?string
    {
        $rel = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $internalFilePath);
        $rel = ltrim($rel, DIRECTORY_SEPARATOR);

        // We only allow files stored under manual/
        $prefix = 'manual' . DIRECTORY_SEPARATOR;
        if ($rel !== 'manual' && !str_starts_with($rel, $prefix)) {
            return null;
        }

        $baseDir = $this->getAttachmentStorageDir();
        $baseReal = realpath($baseDir);
        if ($baseReal === false) {
            return null;
        }

        $candidate = $baseDir . DIRECTORY_SEPARATOR . substr($rel, strlen($prefix));
        $candidateReal = realpath($candidate);
        if ($candidateReal === false) {
            return null;
        }

        // Ensure candidate is inside base dir
        $baseReal = rtrim($baseReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $candidateRealNorm = rtrim($candidateReal, DIRECTORY_SEPARATOR);
        if (!str_starts_with($candidateRealNorm . DIRECTORY_SEPARATOR, $baseReal)) {
            return null;
        }

        return $candidateReal;
    }

    private function headerSafeFilename(string $name): string
    {
        // Minimal header hardening: remove quotes and CRLF
        $name = str_replace(["\r", "\n", '"'], '', $name);
        $name = trim($name);
        return $name !== '' ? $name : 'download';
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

    private function svc(): ManualService
    {
        if ($this->service === null) {
            $this->service = new ManualService();
        }
        return $this->service;
    }

    private function userContext(): array
    {
        $id = $this->user?->getIdentity()?->getId();
        $role = $this->user?->getIdentity()?->getRole();
        if ($role === null && $this->user?->getRole() !== null) {
            $role = $this->user->getRole();
        }
        return [
            'userId' => $id !== null ? (int)$id : null,
            'role' => $role !== null ? (string)$role : null,
            'isLoggedIn' => $id !== null,
        ];
    }

    private function repo(): ManualRepository
    {
        if ($this->repository === null) {
            $this->repository = new ManualRepository();
        }
        return $this->repository;
    }

    private function session(): Session
    {
        if ($this->flashSession === null) {
            $this->flashSession = $this->app->getSession();
        }
        return $this->flashSession;
    }

    private function flash(string $key, mixed $value): void
    {
        $this->session()->set($key, $value);
    }

    private function consumeFlash(string $key): mixed
    {
        $value = $this->session()->get($key);
        $this->session()->remove($key);
        return $value;
    }

    /** @param array{type?:string,message?:string} $flash */
    private function writeFlashFromDomainResult(array $flash): void
    {
        $type = (string)($flash['type'] ?? '');
        $message = (string)($flash['message'] ?? '');
        if ($message === '') {
            return;
        }
        if ($type === 'success') {
            $this->flash('manual.success', $message);
            return;
        }
        $this->flash('manual.error', $message);
    }
}
