<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $errors */
/** @var string $type */
/** @var string $amount */
/** @var string $description */

$errors = $errors ?? [];
$type = $type ?? '';
$amount = $amount ?? '';
$description = $description ?? '';

$getFieldError = static function (array $errors, string $field): string {
    if (!isset($errors[$field]) || !is_array($errors[$field]) || count($errors[$field]) === 0) {
        return '';
    }

    return (string)$errors[$field][0];
};

?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <h1 class="mb-3">New treasury transaction</h1>

            <!-- Balance preview area for dynamic JS recalculation -->
            <div class="mb-3">
                <div>Current balance: <strong id="current-balance">0.00</strong></div>
                <div>New balance: <strong id="new-balance">0.00</strong></div>
            </div>

            <form id="treasury-form" method="post" action="<?= $link->url(['Treasury', 'store']) ?>" novalidate>
                <div class="mb-3">
                    <label for="type" class="form-label">Type</label>
                    <select
                        id="type"
                        name="type"
                        class="form-select <?= $getFieldError($errors, 'type') !== '' ? 'is-invalid' : '' ?>"
                        required
                    >
                        <option value="" <?= $type === '' ? 'selected' : '' ?> disabled>Choose type</option>
                        <option value="deposit" <?= $type === 'deposit' ? 'selected' : '' ?>>Deposit</option>
                        <option value="withdrawal" <?= $type === 'withdrawal' ? 'selected' : '' ?>>Withdrawal</option>
                    </select>
                    <div class="invalid-feedback">
                        <?= $getFieldError($errors, 'type') !== ''
                            ? htmlspecialchars($getFieldError($errors, 'type'), ENT_QUOTES)
                            : 'Please choose transaction type.'
                        ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="amount" class="form-label">Amount (€)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control <?= $getFieldError($errors, 'amount') !== '' ? 'is-invalid' : '' ?>"
                        id="amount"
                        name="amount"
                        value="<?= htmlspecialchars($amount, ENT_QUOTES) ?>"
                        required
                    >
                    <div class="invalid-feedback">
                        <?= $getFieldError($errors, 'amount') !== ''
                            ? htmlspecialchars($getFieldError($errors, 'amount'), ENT_QUOTES)
                            : 'Please enter a positive amount.'
                        ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea
                        class="form-control <?= $getFieldError($errors, 'description') !== '' ? 'is-invalid' : '' ?>"
                        id="description"
                        name="description"
                        rows="3"
                        maxlength="255"
                        required
                    ><?= htmlspecialchars($description, ENT_QUOTES) ?></textarea>
                    <div class="invalid-feedback">
                        <?= $getFieldError($errors, 'description') !== ''
                            ? htmlspecialchars($getFieldError($errors, 'description'), ENT_QUOTES)
                            : 'Please enter description (max. 255 characters).'
                        ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary esn-btn-primary">Save</button>
                <a href="<?= $link->url(['Treasury', 'index']) ?>" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>
