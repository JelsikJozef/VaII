<?php
// AI-GENERATED: Knowledge base articles controller (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use App\Repositories\ManualRepository;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Session;

class ManualController extends BaseController
{
    private ?ManualRepository $repository = null;
    private ?Session $flashSession = null;

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

        return $this->html([
            'activeModule' => 'manual',
            'article' => $article,
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
