<?php
// AI-GENERATED: Manual index redesigned to card layout (GitHub Copilot / ChatGPT), 2026-01-20

/**
 * Manual: Article list.
 *
 * Displays a searchable/filterable list of knowledge-base articles.
 * When `$canManage` is true, shows admin controls (create/edit/delete links).
 *
 * Expected variables:
 * - \Framework\Support\View $view
 * - \Framework\Support\LinkGenerator $link
 * - array<int,array<string,mixed>> $articles (each row may include content_html/content_plain)
 * - string $q Search query
 * - string $category Category filter
 * - string $difficulty Difficulty filter
 * - bool $canManage Whether admin actions are available
 * - string|null $successMessage
 * - string|null $errorMessage
 */

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

$formatUser = static function (array $row): string {
    $name = trim((string)($row['created_by_name'] ?? ''));
    $email = trim((string)($row['created_by_email'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    if ($email !== '') {
        return $email;
    }
    return 'System';
};

$difficultyPill = static function (?string $diff): ?array {
    if ($diff === null || $diff === '') {
        return null;
    }
    $normalized = strtolower($diff);
    return match ($normalized) {
        'easy' => ['Easy', 'esn-pill esn-pill--success'],
        'medium' => ['Medium', 'esn-pill esn-pill--info'],
        'hard' => ['Hard', 'esn-pill esn-pill--danger'],
        default => [ucfirst($normalized), 'esn-pill esn-pill--neutral'],
    };
};
?>

<div class="container esn-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3 esn-page-header">
        <div>
            <h1 class="esn-title mb-1">Knowledge Base</h1>
            <p class="esn-subtitle mb-0">Semester manual articles</p>
        </div>
        <?php if ($canManage): ?>
            <div class="d-flex align-items-center">
                <a href="<?= $link->url('Manual.new') ?>" class="btn btn-primary">New Article</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <div class="esn-card card mb-4">
        <div class="card-body">
            <form method="get" action="<?= $link->url('Manual.index') ?>" class="row g-3 align-items-end esn-filter-row">
                <input type="hidden" name="c" value="manual">
                <input type="hidden" name="a" value="index">
                <div class="col-12 col-lg-6">
                    <label for="q" class="form-label">Search</label>
                    <input type="text" id="q" name="q" class="form-control" value="<?= htmlspecialchars($q, ENT_QUOTES) ?>" placeholder="Search title or content">
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="category" class="form-label">Category</label>
                    <input type="text" id="category" name="category" class="form-control" value="<?= htmlspecialchars($category, ENT_QUOTES) ?>" maxlength="255" placeholder="Category">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label for="difficulty" class="form-label">Difficulty</label>
                    <select id="difficulty" name="difficulty" class="form-select">
                        <option value="">Any</option>
                        <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>Easy</option>
                        <option value="medium" <?= $difficulty === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="hard" <?= $difficulty === 'hard' ? 'selected' : '' ?>>Hard</option>
                    </select>
                </div>
                <div class="col-12 col-lg-1 d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                    <a href="<?= $link->url('Manual.index') ?>" class="btn btn-outline-secondary flex-fill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($articles)): ?>
        <div class="esn-card card p-4">
            <h2 class="h5 mb-1">No articles found</h2>
            <p class="text-muted mb-3">Try adjusting filters or create a new article.</p>
            <?php if ($canManage): ?>
                <a href="<?= $link->url('Manual.new') ?>" class="btn btn-primary">New Article</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="esn-card-grid">
            <?php foreach ($articles as $article):
                $id = (int)($article['id'] ?? 0);
                $title = (string)($article['title'] ?? 'Untitled');
                $cat = trim((string)($article['category'] ?? ''));
                $diff = $difficultyPill($article['difficulty'] ?? null);
                $createdAt = $formatDateTime($article['created_at'] ?? null);
                $creator = $formatUser($article);
                $contentSnippet = trim((string)($article['content_plain'] ?? ''));
                if (strlen($contentSnippet) > 160) {
                    $contentSnippet = substr($contentSnippet, 0, 157) . '…';
                }
            ?>
            <article class="esn-card esn-record-card manual-card card h-100">
                <div class="manual-card__inner">
                    <header class="manual-card__header">
                        <h2 class="manual-card__title">
                            <a href="<?= $link->url('Manual.show', ['id' => $id]) ?>" class="manual-card__title-link">
                                <?= htmlspecialchars($title, ENT_QUOTES) ?>
                            </a>
                        </h2>
                    </header>

                    <div class="manual-card__meta text-muted small">By <?= htmlspecialchars($creator, ENT_QUOTES) ?> • <?= htmlspecialchars($createdAt, ENT_QUOTES) ?></div>

                    <div class="manual-card__badges">
                        <?php if ($cat !== ''): ?>
                            <span class="esn-pill esn-pill--neutral">Category: <?= htmlspecialchars($cat, ENT_QUOTES) ?></span>
                        <?php endif; ?>
                        <?php if ($diff !== null): ?>
                            <span class="<?= $diff[1] ?>">Difficulty: <?= htmlspecialchars($diff[0], ENT_QUOTES) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="manual-card__body">
                        <?php if ($contentSnippet !== ''): ?>
                            <p class="manual-card__excerpt text-muted mb-0"><?= htmlspecialchars($contentSnippet, ENT_QUOTES) ?></p>
                        <?php else: ?>
                            <p class="manual-card__placeholder text-muted mb-0">No preview available.</p>
                        <?php endif; ?>
                    </div>

                    <footer class="manual-card__footer">
                        <a href="<?= $link->url('Manual.show', ['id' => $id]) ?>" class="btn btn-sm btn-outline-secondary">Open</a>
                        <?php if ($canManage): ?>
                            <div class="manual-card__footer-actions">
                                <a href="<?= $link->url('Manual.edit', ['id' => $id]) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" action="<?= $link->url('Manual.delete', ['id' => $id]) ?>" onsubmit="return confirm('Delete this article?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id, ENT_QUOTES) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </footer>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
