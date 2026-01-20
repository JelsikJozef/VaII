<?php
// AI-GENERATED: Polls listing shows creator names (GitHub Copilot / ChatGPT), 2026-01-20

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $polls */
/** @var bool $canManage */

$polls = $polls ?? [];
$canManage = !empty($canManage);

$formatUser = static function (array $row): string {
    $name = trim((string)($row['created_by_name'] ?? ''));
    $email = trim((string)($row['created_by_email'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    if ($email !== '') {
        return $email;
    }
    return 'Unknown user';
};

$statusClasses = [
    1 => 'bg-success-subtle text-success border-success-subtle',
    0 => 'bg-danger-subtle text-danger border-danger-subtle',
];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Polls</h1>
        <?php if ($canManage): ?>
            <a href="<?= $link->url('Polls.new') ?>" class="btn btn-primary">New poll</a>
        <?php endif; ?>
    </div>

    <?php if (empty($polls)): ?>
        <p class="text-muted">No polls yet.</p>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($polls as $poll):
                 $pollId = (int)($poll['id'] ?? 0);
                 $isActive = (int)($poll['is_active'] ?? 0) === 1;
                 $badgeClass = $statusClasses[$isActive ? 1 : 0] ?? 'bg-light text-body border-secondary-subtle';
                 $question = (string)($poll['question'] ?? '');
                // AI-GENERATED: Unified poll timestamp formatting (GitHub Copilot / ChatGPT), 2026-01-20
                 $createdAt = $formatDateTime($poll['created_at'] ?? null);
                 $creator = $formatUser($poll);
            ?>
            <div class="list-group-item d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h5 mb-1">
                        <a href="<?= $link->url('Polls.show', ['id' => $pollId]) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($question, ENT_QUOTES) ?>
                        </a>
                    </h2>
                    <div class="small text-muted">Created by <?= htmlspecialchars($creator, ENT_QUOTES) ?> • <?= htmlspecialchars($createdAt, ENT_QUOTES) ?></div>
                </div>
                <div class="text-end ms-3">
                    <span class="badge rounded-pill border <?= $badgeClass ?>">
                        <?= $isActive ? 'Open' : 'Closed' ?>
                    </span>
                    <?php if ($canManage): ?>
                        <form method="post" action="<?= $link->url('Polls.delete', ['id' => $pollId]) ?>" class="mt-2" onsubmit="return confirm('Delete this poll?');">
                            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$pollId, ENT_QUOTES) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
