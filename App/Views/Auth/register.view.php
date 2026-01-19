<?php
// AI-GENERATED: Registration form for pending approval (GitHub Copilot / ChatGPT), 2026-01-19

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */
/** @var array $errors */
/** @var string $name */
/** @var string $email */

$view->setLayout('auth');
$errors = $errors ?? [];
$name = $name ?? '';
$email = $email ?? '';
?>

<div class="container mt-5" id="auth-register">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h1 class="mb-4">Register</h1>

            <form method="post" action="<?= $link->url('Auth.register') ?>" novalidate>
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input
                        type="text"
                        class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
                        required
                        minlength="2"
                        maxlength="255"
                    >
                    <?php if (!empty($errors['name'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars((string)implode(' ', $errors['name']), ENT_QUOTES) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email, ENT_QUOTES) ?>"
                        required
                        maxlength="255"
                    >
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars((string)implode(' ', $errors['email']), ENT_QUOTES) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                    >
                    <?php if (!empty($errors['password'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars((string)implode(' ', $errors['password']), ENT_QUOTES) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirm password</label>
                    <input
                        type="password"
                        class="form-control <?= !empty($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                        id="password_confirm"
                        name="password_confirm"
                        required
                        minlength="8"
                    >
                    <?php if (!empty($errors['password_confirm'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars((string)implode(' ', $errors['password_confirm']), ENT_QUOTES) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Submit registration</button>
                <a href="<?= $link->url('Auth.loginForm') ?>" class="btn btn-link">Back to login</a>
            </form>
        </div>
    </div>
</div>
