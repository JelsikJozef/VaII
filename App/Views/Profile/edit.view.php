<?php
// AI-GENERATED: Profile edit and password change page (GitHub Copilot / ChatGPT), 2026-01-19

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $errorsProfile */
/** @var array $errorsPassword */
/** @var string $name */
/** @var string $email */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

$errorsProfile = $errorsProfile ?? [];
$errorsPassword = $errorsPassword ?? [];
$name = $name ?? '';
$email = $email ?? '';

$getError = static function (array $errors, string $field): string {
    if (!isset($errors[$field]) || !is_array($errors[$field]) || count($errors[$field]) === 0) {
        return '';
    }

    return (string)$errors[$field][0];
};
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <h1 class="h3 mb-3">Edit profile</h1>

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

            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Profile details</h2>
                            <form id="profile-form" method="post" action="<?= $link->url('Profile.update') ?>" novalidate>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input
                                        type="text"
                                        class="form-control <?= $getError($errorsProfile, 'name') !== '' ? 'is-invalid' : '' ?>"
                                        id="name"
                                        name="name"
                                        value="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
                                        required
                                        minlength="2"
                                        maxlength="255"
                                    >
                                    <div class="invalid-feedback">
                                        <?= $getError($errorsProfile, 'name') !== ''
                                            ? htmlspecialchars($getError($errorsProfile, 'name'), ENT_QUOTES)
                                            : 'Please enter your name (2-255 characters).'
                                        ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input
                                        type="email"
                                        class="form-control <?= $getError($errorsProfile, 'email') !== '' ? 'is-invalid' : '' ?>"
                                        id="email"
                                        name="email"
                                        value="<?= htmlspecialchars($email, ENT_QUOTES) ?>"
                                        required
                                        maxlength="255"
                                    >
                                    <div class="invalid-feedback">
                                        <?= $getError($errorsProfile, 'email') !== ''
                                            ? htmlspecialchars($getError($errorsProfile, 'email'), ENT_QUOTES)
                                            : 'Please enter a valid email address.'
                                        ?>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Save changes</button>
                                <a href="<?= $link->url('Profile.index') ?>" class="btn btn-secondary ms-2">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Change password</h2>
                            <form id="password-form" method="post" action="<?= $link->url('Profile.changePassword') ?>" novalidate>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current password</label>
                                    <input
                                        type="password"
                                        class="form-control <?= $getError($errorsPassword, 'current_password') !== '' ? 'is-invalid' : '' ?>"
                                        id="current_password"
                                        name="current_password"
                                        required
                                        autocomplete="current-password"
                                    >
                                    <div class="invalid-feedback">
                                        <?= $getError($errorsPassword, 'current_password') !== ''
                                            ? htmlspecialchars($getError($errorsPassword, 'current_password'), ENT_QUOTES)
                                            : 'Please enter your current password.'
                                        ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New password</label>
                                    <input
                                        type="password"
                                        class="form-control <?= $getError($errorsPassword, 'new_password') !== '' ? 'is-invalid' : '' ?>"
                                        id="new_password"
                                        name="new_password"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                    >
                                    <div class="invalid-feedback">
                                        <?= $getError($errorsPassword, 'new_password') !== ''
                                            ? htmlspecialchars($getError($errorsPassword, 'new_password'), ENT_QUOTES)
                                            : 'Password must be at least 8 characters.'
                                        ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password_confirm" class="form-label">Confirm new password</label>
                                    <input
                                        type="password"
                                        class="form-control <?= $getError($errorsPassword, 'new_password_confirm') !== '' ? 'is-invalid' : '' ?>"
                                        id="new_password_confirm"
                                        name="new_password_confirm"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                    >
                                    <div class="invalid-feedback">
                                        <?= $getError($errorsPassword, 'new_password_confirm') !== ''
                                            ? htmlspecialchars($getError($errorsPassword, 'new_password_confirm'), ENT_QUOTES)
                                            : 'Passwords must match.'
                                        ?>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Update password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
