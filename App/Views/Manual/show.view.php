<?php
// AI-GENERATED: Manual detail view (GitHub Copilot / ChatGPT), 2026-01-18

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $article */
/** @var bool $canManage */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

$article = $article ?? [];
$canManage = $canManage ?? false;
$title = (string)($article['title'] ?? 'Untitled');
$category = (string)($article['category'] ?? '');
$difficulty = (string)($article['difficulty'] ?? '');
$content = (string)($article['content'] ?? '');
$createdAt = (string)($article['created_at'] ?? '');
$updatedAt = (string)($article['updated_at'] ?? '');
$articleId = (int)($article['id'] ?? 0);

$difficultyLabels = [
    'easy' => 'Easy',
    'medium' => 'Medium',
    'hard' => 'Hard',
];

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="text-muted mb-1">Knowledge Base</p>
            <h1 class="h3 mb-0"><?= htmlspecialchars($title, ENT_QUOTES) ?></h1>
            <div class="text-muted small">
                Created <?= htmlspecialchars($createdAt, ENT_QUOTES) ?>
                <?php if ($updatedAt !== '' && $updatedAt !== $createdAt): ?>
                    · Updated <?= htmlspecialchars($updatedAt, ENT_QUOTES) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $link->url('Manual.index') ?>" class="btn btn-outline-secondary">Back</a>
            <?php if ($canManage): ?>
                <a href="<?= $link->url('Manual.edit', ['id' => $articleId]) ?>" class="btn btn-primary">Edit</a>
                <form method="post" action="<?= $link->url('Manual.delete', ['id' => $articleId]) ?>" class="d-inline" onsubmit="return confirm('Delete this article?');">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$articleId, ENT_QUOTES) ?>">
                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <div class="mb-3 text-muted">
        <?php if ($category !== ''): ?>
            <span class="badge bg-secondary me-2">Category: <?= htmlspecialchars($category, ENT_QUOTES) ?></span>
        <?php endif; ?>
        <?php if ($difficulty !== ''): ?>
            <span class="badge bg-info text-dark">Difficulty: <?= htmlspecialchars($difficultyLabels[$difficulty] ?? ucfirst($difficulty), ENT_QUOTES) ?></span>
        <?php endif; ?>
    </div>

    <article class="card card-body mb-4">
        <div class="manual-content">
            <?= nl2br(htmlspecialchars($content, ENT_QUOTES)) ?>
        </div>
    </article>

    <section class="mb-4">
        <h2 class="h5">Attachments</h2>
        <p class="text-muted mb-0">Attachment uploads coming soon.</p>
    </section>
</div>
