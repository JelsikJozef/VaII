<?php
// AI-GENERATED: Poll creation form view (GitHub Copilot / ChatGPT), 2026-01-19

/**
 * Polls: New.
 *
 * Poll creation form. Options are entered one-per-line.
 *
 * Expected variables:
 * - \Framework\Support\View $view
 * - \Framework\Support\LinkGenerator $link
 * - array<string,array<int,string>> $errors
 * - string $question
 * - string $status (open|closed)
 * - string $optionsInput Multiline option text
 */

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $errors */
/** @var string $question */
/** @var string $status */
/** @var string $optionsInput */

$errors = $errors ?? [];
$question = $question ?? '';
$status = $status ?? 'open';
$optionsInput = $optionsInput ?? '';

$getFieldError = static function (array $errs, string $field): string {
    return isset($errs[$field][0]) ? (string)$errs[$field][0] : '';
};
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 mb-0">Create poll</h1>
                <a href="<?= $link->url('Polls.index') ?>" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="post" action="<?= $link->url('Polls.store') ?>" novalidate>
                <div class="mb-3">
                    <label for="question" class="form-label">Question</label>
                    <input
                        type="text"
                        id="question"
                        name="question"
                        class="form-control <?= $getFieldError($errors, 'question') !== '' ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($question, ENT_QUOTES) ?>"
                        maxlength="300"
                        required
                    >
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'question') ?: 'Question is required (5-300 characters).', ENT_QUOTES) ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="options" class="form-label">Options</label>
                    <textarea
                        id="options"
                        name="options"
                        class="form-control <?= $getFieldError($errors, 'options') !== '' ? 'is-invalid' : '' ?>"
                        rows="6"
                        required
                    ><?= htmlspecialchars($optionsInput, ENT_QUOTES) ?></textarea>
                    <div class="form-text">Enter one option per line (at least two, unique, max 200 characters).</div>
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'options') ?: 'Please enter at least two unique options (max 200 chars each).', ENT_QUOTES) ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="form-select <?= $getFieldError($errors, 'status') !== '' ? 'is-invalid' : '' ?>"
                        required
                    >
                        <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'status') ?: 'Choose open or closed.', ENT_QUOTES) ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Create</button>
                <a href="<?= $link->url('Polls.index') ?>" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>
