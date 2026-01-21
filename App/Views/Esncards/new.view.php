<?php
// AI-GENERATED: ESNcards create form view (GitHub Copilot / ChatGPT), 2026-01-18

/**
 * ESNcards: New.
 *
 * Form for creating a new ESN card record.
 *
 * Expected variables:
 * - \Framework\Support\View $view
 * - \Framework\Support\LinkGenerator $link
 * - array<string,array<int,string>> $errors
 * - string $card_number
 * - string $status
 * - string $assigned_to_name
 * - string $assigned_to_email
 * - string $assigned_at
 */

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $errors */
/** @var string $card_number */
/** @var string $status */
/** @var string $assigned_to_name */
/** @var string $assigned_to_email */
/** @var string $assigned_at */

$errors = $errors ?? [];
$card_number = $card_number ?? '';
$status = $status ?? 'available';
$assigned_to_name = $assigned_to_name ?? '';
$assigned_to_email = $assigned_to_email ?? '';
$assigned_at = $assigned_at ?? '';

$getFieldError = static function (array $errs, string $field): string {
    return isset($errs[$field][0]) ? (string)$errs[$field][0] : '';
};
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <h1 class="mb-3">New ESNcard</h1>
            <form method="post" action="<?= $link->url('Esncards.store') ?>" id="esncards-form">
                <div class="mb-3">
                    <label for="card_number" class="form-label">Card number</label>
                    <input
                        type="text"
                        id="card_number"
                        name="card_number"
                        class="form-control <?= $getFieldError($errors, 'card_number') !== '' ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($card_number, ENT_QUOTES) ?>"
                        maxlength="50"
                        required
                    >
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'card_number') ?: 'Card number is required (max 50 chars).', ENT_QUOTES) ?>
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
                        <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="assigned" <?= $status === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'status') ?: 'Please choose status.', ENT_QUOTES) ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="assigned_to_name" class="form-label">Assigned to (name)</label>
                    <input
                        type="text"
                        id="assigned_to_name"
                        name="assigned_to_name"
                        class="form-control <?= $getFieldError($errors, 'assigned_to_name') !== '' ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($assigned_to_name, ENT_QUOTES) ?>"
                        maxlength="255"
                    >
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'assigned_to_name') ?: 'Required when status is Assigned.', ENT_QUOTES) ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="assigned_to_email" class="form-label">Assigned to (email)</label>
                    <input
                        type="email"
                        id="assigned_to_email"
                        name="assigned_to_email"
                        class="form-control <?= $getFieldError($errors, 'assigned_to_email') !== '' ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($assigned_to_email, ENT_QUOTES) ?>"
                        maxlength="255"
                    >
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'assigned_to_email') ?: 'Required when status is Assigned (valid email).', ENT_QUOTES) ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="assigned_at" class="form-label">Assigned on (date)</label>
                    <input
                        type="date"
                        id="assigned_at"
                        name="assigned_at"
                        class="form-control <?= $getFieldError($errors, 'assigned_at') !== '' ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($assigned_at, ENT_QUOTES) ?>"
                    >
                    <div class="invalid-feedback">
                        <?= htmlspecialchars($getFieldError($errors, 'assigned_at') ?: 'Optional date when card was assigned.', ENT_QUOTES) ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?= $link->url('Esncards.index') ?>" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
// AI-GENERATED: Toggle required fields for assigned status (GitHub Copilot / ChatGPT), 2026-01-18
(function () {
    const statusEl = document.getElementById('status');
    const nameEl = document.getElementById('assigned_to_name');
    const emailEl = document.getElementById('assigned_to_email');
    const assignedAtEl = document.getElementById('assigned_at');

    function syncRequired() {
        const isAssigned = statusEl.value === 'assigned';
        [nameEl, emailEl].forEach(el => {
            if (!el) return;
            if (isAssigned) {
                el.setAttribute('required', 'required');
            } else {
                el.removeAttribute('required');
            }
        });
    }

    statusEl?.addEventListener('change', syncRequired);
    syncRequired();
})();
</script>
