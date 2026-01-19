<?php

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $errors */
/** @var string $type */
/** @var string $amount */
/** @var string $description */
/** @var string $status */
/** @var float $currentBalance */
/** @var int $transactionId */
/** @var bool $isModerator */
/** @var bool $isOwnerPending */

$errors = $errors ?? [];
$type = $type ?? '';
$amount = $amount ?? '';
$description = $description ?? '';
$status = $status ?? 'pending';
$transactionId = $transactionId ?? 0;
$currentBalance = isset($currentBalance) && is_numeric($currentBalance) ? (float)$currentBalance : 0.0;

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
            <h1 class="mb-3">Edit treasury transaction</h1>

            <div class="mb-3">
                <div>Current balance: <strong id="current-balance"><?= number_format($currentBalance, 2) ?></strong></div>
                <div>New balance: <strong id="new-balance"><?= number_format($currentBalance, 2) ?></strong></div>
            </div>

            <form id="treasury-form" method="post" action="<?= $link->url('Treasury.update', ['id' => $transactionId]) ?>" novalidate>
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$transactionId, ENT_QUOTES) ?>">

                <?php if ($isModerator): ?>
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
                <?php else: ?>
                <div class="mb-3">
                    <label class="form-label d-block">Type</label>
                    <span class="form-control-plaintext"><?= htmlspecialchars($type !== '' ? ucfirst($type) : 'N/A', ENT_QUOTES) ?></span>
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES) ?>">
                </div>
                <?php endif; ?>

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

                <?php if ($isModerator): ?>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="form-select <?= $getFieldError($errors, 'status') !== '' ? 'is-invalid' : '' ?>"
                        required
                    >
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                    <div class="invalid-feedback">
                        <?= $getFieldError($errors, 'status') !== ''
                            ? htmlspecialchars($getFieldError($errors, 'status'), ENT_QUOTES)
                            : 'Please pick a status.'
                        ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="mb-3">
                    <label class="form-label d-block">Status</label>
                    <span class="form-control-plaintext"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES) ?></span>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES) ?>">
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary esn-btn-primary">Update</button>
                <a href="<?= $link->url('Treasury.index') ?>" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>

