<?php
// AI-GENERATED: Poll detail shows creator names (GitHub Copilot / ChatGPT), 2026-01-20

/**
 * Polls: Show (detail + voting).
 *
 * Shows a single poll, its options, current results, and the voting form.
 * Also renders admin controls when `$canManage` is enabled.
 *
 * Expected variables:
 * - \Framework\Support\View $view
 * - \Framework\Support\LinkGenerator $link
 * - array<string,mixed> $poll
 * - array<int,array<string,mixed>> $options
 * - array<int,array<string,mixed>> $results
 * - array<string,array<int,string>> $errors
 * - int $selectedOptionId
 * - bool $hasVoted
 * - bool $canManage
 */

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $poll */
/** @var array $options */
/** @var array $results */
/** @var array $errors */
/** @var int $selectedOptionId */
/** @var bool $hasVoted */
/** @var bool $canManage */

$poll = $poll ?? [];
$options = $options ?? [];
$results = $results ?? [];
$errors = $errors ?? [];
$selectedOptionId = (int)($selectedOptionId ?? 0);
$hasVoted = !empty($hasVoted);
$canManage = !empty($canManage);

$formatUser = static function (array $poll): string {
    $name = trim((string)($poll['created_by_name'] ?? ''));
    $email = trim((string)($poll['created_by_email'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    if ($email !== '') {
        return $email;
    }
    return 'Unknown user';
};

$status = (int)($poll['is_active'] ?? 0);
$creator = $formatUser($poll);
// AI-GENERATED: Unified poll detail timestamp formatting (GitHub Copilot / ChatGPT), 2026-01-20
$createdAt = $formatDateTime($poll['created_at'] ?? null);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h4 mb-0"><?= htmlspecialchars((string)($poll['question'] ?? 'Poll'), ENT_QUOTES) ?></h1>
                <span class="badge rounded-pill border <?= $statusClasses[$status === 1 ? 1 : 0] ?? 'bg-light text-body border-secondary-subtle' ?>">
                    <?= $status === 1 ? 'Open' : 'Closed' ?>
                </span>
            </div>
            <div class="small text-muted">Created by <?= htmlspecialchars($creator, ENT_QUOTES) ?> • <?= htmlspecialchars($createdAt, ENT_QUOTES) ?></div>
        </div>
        <a href="<?= $link->url('Polls.index') ?>" class="btn btn-outline-secondary">Back</a>
    </div>

    <?php if ($status === 1 && !$hasVoted): ?>
        <form method="post" action="<?= $link->url('Polls.vote', ['id' => $poll['id'] ?? 0]) ?>" class="card mb-4" novalidate>
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)($poll['id'] ?? 0), ENT_QUOTES) ?>">
            <div class="card-header">Vote</div>
            <div class="card-body">
                <?php if (empty($options)): ?>
                    <p class="text-muted mb-0">No options available for this poll.</p>
                <?php else: ?>
                    <?php foreach ($options as $option):
                        $optId = (int)($option['id'] ?? 0);
                        $optText = (string)($option['option_text'] ?? '');
                    ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input <?= $getFieldError($errors, 'option_id') !== '' ? 'is-invalid' : '' ?>"
                                   type="radio"
                                   name="option_id"
                                   id="option-<?= htmlspecialchars((string)$optId, ENT_QUOTES) ?>"
                                   value="<?= htmlspecialchars((string)$optId, ENT_QUOTES) ?>"
                                   <?= $selectedOptionId === $optId ? 'checked' : '' ?>
                                   required>
                            <label class="form-check-label" for="option-<?= htmlspecialchars((string)$optId, ENT_QUOTES) ?>">
                                <?= htmlspecialchars($optText, ENT_QUOTES) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <div class="invalid-feedback d-block">
                        <?= htmlspecialchars($getFieldError($errors, 'option_id') ?: '', ENT_QUOTES) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary" <?= empty($options) ? 'disabled' : '' ?>>Submit vote</button>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-info">
            <?= $status !== 1
                ? 'Poll is closed for voting.'
                : 'You already voted in this poll.' ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Results</div>
        <div class="card-body">
            <?php if (empty($results)): ?>
                <p class="text-muted mb-0">No results yet.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($results as $row):
                        $optText = (string)($row['option_text'] ?? '');
                        $votes = (int)($row['vote_count'] ?? 0);
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?= htmlspecialchars($optText, ENT_QUOTES) ?></span>
                            <span class="badge text-bg-primary"><?= htmlspecialchars((string)$votes, ENT_QUOTES) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
