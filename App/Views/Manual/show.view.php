<?php
// AI-GENERATED: Manual detail shows author names (GitHub Copilot / ChatGPT), 2026-01-20

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $article */
/** @var array $attachments */
/** @var bool $canManage */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

$article = $article ?? [];
$attachments = $attachments ?? [];
$canManage = $canManage ?? false;
$title = (string)($article['title'] ?? 'Untitled');
$category = (string)($article['category'] ?? '');
$difficulty = (string)($article['difficulty'] ?? '');
$content = (string)($article['content'] ?? '');
$contentHtml = (string)($article['content_html'] ?? '');
$createdAt = $formatDateTime($article['created_at'] ?? null);
$updatedAt = $formatDateTime($article['updated_at'] ?? null);
$articleId = (int)($article['id'] ?? 0);

$formatUser = static function (array $row, string $nameKey, string $emailKey): string {
    $name = trim((string)($row[$nameKey] ?? ''));
    $email = trim((string)($row[$emailKey] ?? ''));
    if ($name !== '') {
        return $name;
    }
    if ($email !== '') {
        return $email;
    }
    return 'Unknown user';
};
$creator = $formatUser($article, 'created_by_name', 'created_by_email');
$updater = $formatUser($article, 'updated_by_name', 'updated_by_email');

$difficultyLabels = [
    'easy' => 'Easy',
    'medium' => 'Medium',
    'hard' => 'Hard',
];

?>

<div class="container mt-4" id="manual-detail" data-article-id="<?= htmlspecialchars((string)$articleId, ENT_QUOTES) ?>" data-can-manage="<?= $canManage ? '1' : '0' ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="text-muted mb-1">Knowledge Base</p>
            <h1 class="h3 mb-0"><?= htmlspecialchars($title, ENT_QUOTES) ?></h1>
            <div class="text-muted small">
                Created by <?= htmlspecialchars($creator, ENT_QUOTES) ?> <?= htmlspecialchars($createdAt, ENT_QUOTES) ?>
                <?php if ($updatedAt !== '—' && $updatedAt !== $createdAt): ?>
                    · Updated by <?= htmlspecialchars($updater, ENT_QUOTES) ?> <?= htmlspecialchars($updatedAt, ENT_QUOTES) ?>
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
        <div class="markdown-body">
            <?= $contentHtml ?>
        </div>
    </article>

    <section class="mb-4" id="attachments-section">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Attachments</h2>
            <?php if ($canManage): ?>
                <span class="text-muted small">Max 10 MB · PDF, DOCX, PNG, JPG</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($attachments)): ?>
            <ul class="list-group" id="attachmentsList">
                <?php foreach ($attachments as $attachment): ?>
                    <?php
                    $attachmentId = (int)($attachment['id'] ?? 0);
                    $filePath = (string)($attachment['file_path'] ?? '');
                    $urlPath = (string)($attachment['url'] ?? '');
                    $label = $filePath !== '' ? basename($filePath) : ($urlPath !== '' ? $urlPath : 'attachment');
                    $href = $filePath !== '' ? '/' . ltrim($filePath, '/') : $urlPath;
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-attachment-id="<?= htmlspecialchars((string)$attachmentId, ENT_QUOTES) ?>">
                        <div>
                            <?php if ($href !== ''): ?>
                                <a href="<?= htmlspecialchars($href, ENT_QUOTES) ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars($label, ENT_QUOTES) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">(no link)</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($canManage): ?>
                            <form method="post" action="<?= $link->url('Manual.deleteAttachment', ['id' => $articleId, 'attId' => $attachmentId]) ?>" class="d-inline" onsubmit="return confirm('Delete this attachment?');">
                                <input type="hidden" name="attId" value="<?= htmlspecialchars((string)$attachmentId, ENT_QUOTES) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted mb-0" id="attachments-empty">No attachments yet.</p>
            <ul class="list-group d-none" id="attachmentsList"></ul>
        <?php endif; ?>

        <?php if ($canManage): ?>
            <form id="attachmentUploadForm" class="mt-3" action="<?= $link->url('Manual.uploadAttachmentJson', ['id' => $articleId]) ?>" method="post" enctype="multipart/form-data" data-article-id="<?= htmlspecialchars((string)$articleId, ENT_QUOTES) ?>" data-delete-template="<?= htmlspecialchars($link->url('Manual.deleteAttachment', ['id' => $articleId, 'attId' => '__ATT_ID__']), ENT_QUOTES) ?>">
                <div class="input-group">
                    <input type="file" name="file" class="form-control" required aria-label="Attachment file">
                    <button class="btn btn-primary" type="submit">Upload</button>
                </div>
                <div class="form-text">Allowed: PDF, DOCX, PNG, JPG. Max 10 MB.</div>
            </form>
        <?php endif; ?>
    </section>
</div>
