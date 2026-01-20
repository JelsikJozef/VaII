<?php
// AI-GENERATED: Profile overview page (GitHub Copilot / ChatGPT), 2026-01-19

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $profile */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

$profile = $profile ?? [];
$name = (string)($profile['name'] ?? '');
$email = (string)($profile['email'] ?? '');
$role = (string)($profile['role_name'] ?? $profile['role'] ?? '');
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <h1 class="h3 mb-3">Your profile</h1>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars((string)$successMessage, ENT_QUOTES) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars((string)$errorMessage, ENT_QUOTES) ?>
                </div>
            <?php endif; ?>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($name, ENT_QUOTES) ?></div>
                    <div class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($email, ENT_QUOTES) ?></div>
                    <div class="mb-0"><strong>Role:</strong> <?= htmlspecialchars($role, ENT_QUOTES) ?></div>
                </div>
            </div>

            <a class="btn btn-primary" href="<?= $link->url('Profile.edit') ?>">Edit profile</a>
        </div>
    </div>
</div>
