<?php
// AI-GENERATED: Manual index view (GitHub Copilot / ChatGPT), 2026-01-18

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $articles */
/** @var string $q */
/** @var string $category */
/** @var string $difficulty */
/** @var bool $canManage */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

$articles = $articles ?? [];
$q = $q ?? '';
$category = $category ?? '';
$difficulty = $difficulty ?? '';
$canManage = $canManage ?? false;

$difficultyLabels = [
    'easy' => 'Easy',
    'medium' => 'Medium',
    'hard' => 'Hard',
];

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">Knowledge Base</h1>
            <p class="text-muted mb-0">Semester manual articles</p>
        </div>
        <?php if ($canManage): ?>
            <a href="<?= $link->url('Manual.new') ?>" class="btn btn-primary">New Article</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form method="get" action="<?= $link->url('Manual.index') ?>" class="card card-body mb-3">
        <input type="hidden" name="c" value="manual">
        <input type="hidden" name="a" value="index">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="q" class="form-label">Search</label>
                <input type="text" id="q" name="q" class="form-control" value="<?= htmlspecialchars($q, ENT_QUOTES) ?>" placeholder="Search title or content">
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">Category</label>
                <input type="text" id="category" name="category" class="form-control" value="<?= htmlspecialchars($category, ENT_QUOTES) ?>" maxlength="255">
            </div>
            <div class="col-md-3">
                <label for="difficulty" class="form-label">Difficulty</label>
                <select id="difficulty" name="difficulty" class="form-select">
                    <option value="">Any</option>
                    <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>Easy</option>
                    <option value="medium" <?= $difficulty === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="hard" <?= $difficulty === 'hard' ? 'selected' : '' ?>>Hard</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-secondary">Filter</button>
            </div>
        </div>
    </form>

    <?php if (empty($articles)): ?>
        <p class="text-muted">No articles found.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Difficulty</th>
                    <th>Created</th>
                    <?php if ($canManage): ?>
                        <th class="text-end">Actions</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($articles as $article):
                     $id = (int)($article['id'] ?? 0);
                     $title = (string)($article['title'] ?? 'Untitled');
                     $cat = (string)($article['category'] ?? '');
                     $diff = (string)($article['difficulty'] ?? '');
                     $createdAt = $formatDateTime($article['created_at'] ?? null);
                ?>
                    <tr>
                        <td>
                            <a href="<?= $link->url('Manual.show', ['id' => $id]) ?>">
                                <?= htmlspecialchars($title, ENT_QUOTES) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($cat !== '' ? $cat : '—', ENT_QUOTES) ?></td>
                        <td>
                            <?php if ($diff !== ''): ?>
                                <span class="badge bg-info text-dark">
                                    <?= htmlspecialchars($difficultyLabels[$diff] ?? ucfirst($diff), ENT_QUOTES) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">n/a</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($createdAt, ENT_QUOTES) ?></td>
                        <?php if ($canManage): ?>
                            <td class="text-end">
                                <a href="<?= $link->url('Manual.edit', ['id' => $id]) ?>" class="btn btn-sm btn-outline-primary me-2">Edit</a>
                                <form method="post" action="<?= $link->url('Manual.delete', ['id' => $id]) ?>" class="d-inline" onsubmit="return confirm('Delete this article?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id, ENT_QUOTES) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
