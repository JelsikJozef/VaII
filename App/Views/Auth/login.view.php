<?php
// AI-GENERATED: Login form view with validation messages (GitHub Copilot / ChatGPT), 2026-01-18

/**
 * Auth: Login form.
 *
 * Renders the login form and displays validation errors and generic auth errors.
 *
 * Expected variables:
 * - \Framework\Support\View $view
 * - \Framework\Support\LinkGenerator $link
 * - array<string,array<int,string>> $errors Field validation errors.
 * - string $email Previously entered email (sticky field).
 * - string|null $genericError Generic login error (invalid credentials / pending).
 * - string|null $successMessage One-time success message (e.g. after registration).
 */

/** @var string|null $message */
/** @var string|null $successMessage */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container mt-5" id="auth-login">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h1 class="mb-4">Login</h1>

            <?php if (!empty($genericError)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars((string)$genericError, ENT_QUOTES) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars((string)$successMessage, ENT_QUOTES) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= $link->url('Auth.login') ?>" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" required value="<?= htmlspecialchars((string)($email ?? ''), ENT_QUOTES) ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars((string)implode(' ', $errors['email']), ENT_QUOTES) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">Show</button>
                        <?php if (!empty($errors['password'])): ?>
                            <div class="invalid-feedback d-block">
                                <?= htmlspecialchars((string)implode(' ', $errors['password']), ENT_QUOTES) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
                <a class="btn btn-link" href="<?= $link->url('Auth.registerForm') ?>">Register</a>
            </form>
        </div>
    </div>
</div>
<script>
// Simple client-side toggle for password visibility
const toggleBtn = document.getElementById('togglePassword');
const pwdInput = document.getElementById('password');
if (toggleBtn && pwdInput) {
    toggleBtn.addEventListener('click', () => {
        const isPassword = pwdInput.getAttribute('type') === 'password';
        pwdInput.setAttribute('type', isPassword ? 'text' : 'password');
        toggleBtn.textContent = isPassword ? 'Hide' : 'Show';
    });
}
</script>
