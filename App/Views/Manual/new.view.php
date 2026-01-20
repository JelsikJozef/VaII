<?php
// AI-GENERATED: Manual create form view (GitHub Copilot / ChatGPT), 2026-01-18

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $errors */
/** @var string $title */
/** @var string $category */
/** @var string $difficulty */
/** @var string $content */

$errors = $errors ?? [];
$title = $title ?? '';
$category = $category ?? '';
$difficulty = $difficulty ?? '';
$content = $content ?? '';

$getFieldError = static function (array $errs, string $field): string {
    return isset($errs[$field][0]) ? (string)$errs[$field][0] : '';
};
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 mb-0">New Article</h1>
                <a href="<?= $link->url('Manual.index') ?>" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="post" action="<?= $link->url('Manual.store') ?>" novalidate>
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-control <?= $getFieldError($errors, 'title') !== '' ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($title, ENT_QUOTES) ?>"
                        maxlength="255"
                        required
                    >
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'title') ?: 'Title is required (3-255 characters).', ENT_QUOTES) ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label">Category (optional)</label>
                    <input
                        type="text"
                        id="category"
                        name="category"
                        class="form-control <?= $getFieldError($errors, 'category') !== '' ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($category, ENT_QUOTES) ?>"
                        maxlength="255"
                    >
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'category') ?: 'Maximum 255 characters.', ENT_QUOTES) ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="difficulty" class="form-label">Difficulty (optional)</label>
                    <select
                        id="difficulty"
                        name="difficulty"
                        class="form-select <?= $getFieldError($errors, 'difficulty') !== '' ? 'is-invalid' : '' ?>"
                    >
                        <option value="" <?= $difficulty === '' ? 'selected' : '' ?>>Not set</option>
                        <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>Easy</option>
                        <option value="medium" <?= $difficulty === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="hard" <?= $difficulty === 'hard' ? 'selected' : '' ?>>Hard</option>
                    </select>
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'difficulty') ?: 'Choose one of the allowed values.', ENT_QUOTES) ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <div class="form-text mb-1">You can use Markdown (headings, lists, links, code blocks).</div>
                    <textarea
                        id="content"
                        name="content"
                        class="form-control <?= $getFieldError($errors, 'content') !== '' ? 'is-invalid' : '' ?>"
                        rows="10"
                        required
                    ><?= htmlspecialchars($content, ENT_QUOTES) ?></textarea>
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'content') ?: 'Content is required (min 10 characters).', ENT_QUOTES) ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?= $link->url('Manual.index') ?>" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>
