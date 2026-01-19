<?php
// AI-GENERATED: Poll detail and voting view (GitHub Copilot / ChatGPT), 2026-01-19

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

$getFieldError = static function (array $errs, string $field): string {
    return isset($errs[$field][0]) ? (string)$errs[$field][0] : '';
};

$status = (int)($poll['is_active'] ?? 0);
$statusClasses = [
    1 => 'bg-success-subtle text-success border-success-subtle',
    0 => 'bg-danger-subtle text-danger border-danger-subtle',
];
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
            <div class="small text-muted">Created at <?= htmlspecialchars((string)($poll['created_at'] ?? ''), ENT_QUOTES) ?></div>
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
