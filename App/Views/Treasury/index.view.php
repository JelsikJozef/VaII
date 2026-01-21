<?php
// AI-GENERATED: Treasury cards show creator/approver names (GitHub Copilot / ChatGPT), 2026-01-20

/**
 * Treasury: Index.
 *
 * Shows the current balance, pending balance, and a table/list of transactions.
 * Includes action buttons (edit/delete/moderate) based on the current user role
 * and ownership.
 *
 * Expected variables:
 * - \Framework\Support\View $view
 * - \Framework\Support\LinkGenerator $link
 * - array<int,array<string,mixed>> $transactions
 * - float $currentBalance
 * - float $pendingBalance
 * - string|null $successMessage
 * - string|null $errorMessage
 * - \Framework\Auth\AppUser|null $user (provided by the framework)
 */

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $transactions */
/** @var string|null $errorMessage */
/** @var string|null $successMessage */

$transactions = $transactions ?? [];
$currentBalance = $currentBalance ?? 0.0;
$pendingBalance = $pendingBalance ?? 0.0;

// Permissions must be computed outside the view (controller/service).
// Each transaction is expected to contain booleans:
// - canEdit, canDelete, canApproveReject

$formatUser = static function (array $tx, string $nameKey, string $emailKey): string {
    $name = trim((string)($tx[$nameKey] ?? ''));
    $email = trim((string)($tx[$emailKey] ?? ''));
    if ($name !== '') {
        return $name;
    }
    if ($email !== '') {
        return $email;
    }
    return 'Unknown user';
};

$formatAmount = static function ($amount, string $type): string {
    $value = is_numeric($amount) ? (float)$amount : 0.0;
    $formatted = number_format($value, 2, ',', ' ');
    $sign = $type === 'withdrawal' ? '-' : '+';

    return sprintf('%s %s €', $sign, $formatted);
};

$statusMap = [
    'pending' => ['Pending', 'treasury-status--pending'],
    'approved' => ['Approved', 'treasury-status--approved'],
    'rejected' => ['Rejected', 'treasury-status--rejected'],
];

$typeMap = [
    'deposit' => ['Deposit', 'treasury-chip--deposit'],
    'withdrawal' => ['Withdrawal', 'treasury-chip--withdrawal'],
];

?>

<div class="treasury-page">
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger mb-3">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success mb-3">
            <?= htmlspecialchars($successMessage, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <section class="treasury-hero">
        <div class="treasury-hero__eyebrow">ESN Treasury</div>
        <h1 class="treasury-hero__title">Welcome to the Treasury of ESN UNIZA</h1>
        <p class="treasury-hero__subtitle">
            Propose withdrawal or add deposit
        </p>
        <p class="treasury-hero__balance">Current balance (approved): <strong id="treasury-balance-approved"><?= number_format((float)$currentBalance, 2, ',', ' ') ?> €</strong></p>
        <p class="treasury-hero__subtitle small mb-2">Pending amount awaiting approval: <strong id="treasury-balance-pending"><?= number_format((float)$pendingBalance, 2, ',', ' ') ?> €</strong></p>
        <div class="treasury-hero__cta">
            <!-- Use string destination "Treasury.new" + parameters; avoid array destination when passing $parameters -->
            <a href="<?= $link->url('Treasury.new', ['type' => 'withdrawal']) ?>" class="btn treasury-btn treasury-btn--withdrawal">
                Propose Withdrawal
            </a>
            <a href="<?= $link->url('Treasury.new', ['type' => 'deposit']) ?>" class="btn treasury-btn treasury-btn--deposit">
                Add deposit
            </a>
        </div>
    </section>

    <section class="treasury-section">
        <div class="treasury-section__header">
            <h2>Recent transactions:</h2>
            <?php if (!empty($transactions)): ?>
                <div class="treasury-filter">
                    <label for="transaction-status-filter">
                        Status
                        <select id="transaction-status-filter">
                            <option value="all">All</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($transactions)): ?>
            <p id="no-transactions-message" class="treasury-empty">No transactions yet.</p>
        <?php else: ?>
            <p id="no-transactions-message" class="treasury-empty d-none">No transactions for selected status.</p>
            <div id="transactions-grid" class="treasury-grid">
                <?php foreach ($transactions as $tx):
                    $type = $tx['type'] ?? 'deposit';
                    $status = $tx['status'] ?? 'pending';
                    $typeData = $typeMap[$type] ?? $typeMap['deposit'];
                    $statusData = $statusMap[$status] ?? $statusMap['pending'];
                    $title = trim((string)($tx['title'] ?? $tx['description'] ?? 'Untitled transaction'));
                    $createdAt = $formatDateTime($tx['created_at'] ?? null);
                    $proposedBy = $formatUser($tx, 'created_by_name', 'created_by_email');
                    $approvedLabel = null;
                    $approvedByRaw = $tx['approved_by'] ?? null;
                    if ($approvedByRaw === null) {
                        $approvedLabel = 'Not approved yet';
                    } else {
                        $approvedLabel = 'Approved by ' . $formatUser($tx, 'approved_by_name', 'approved_by_email');
                    }
                    $editUrl = $link->url('Treasury.edit', ['id' => $tx['id'] ?? 0]);
                    $deleteUrl = $link->url('Treasury.delete', ['id' => $tx['id'] ?? 0]);
                ?>
                <article class="treasury-card" data-status="<?= htmlspecialchars($status, ENT_QUOTES) ?>">
                    <header class="treasury-card__header">
                        <span class="treasury-chip <?= $typeData[1] ?>">
                            <?= htmlspecialchars($typeData[0], ENT_QUOTES) ?>
                        </span>
                        <span class="treasury-amount <?= $type === 'withdrawal' ? 'treasury-amount--negative' : 'treasury-amount--positive' ?>">
                            <?= htmlspecialchars($formatAmount($tx['amount'] ?? 0, $type), ENT_QUOTES) ?>
                        </span>
                    </header>
                    <h3 class="treasury-card__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h3>
                    <p class="treasury-card__meta">Proposed by <?= htmlspecialchars($proposedBy, ENT_QUOTES) ?></p>
                    <p class="treasury-card__meta text-muted mb-1"><?= htmlspecialchars($approvedLabel, ENT_QUOTES) ?></p>
                    <footer class="treasury-card__footer">
                        <span class="treasury-card__date"><?= htmlspecialchars($createdAt, ENT_QUOTES) ?></span>
                        <span class="treasury-status <?= $statusData[1] ?> js-tx-status" data-id="<?= htmlspecialchars((string)($tx['id'] ?? 0), ENT_QUOTES) ?>">
                            <?= htmlspecialchars($statusData[0], ENT_QUOTES) ?></span>
                    </footer>
                    <div class="treasury-card__actions mt-3 d-flex gap-2">
                        <?php if (!empty($tx['canEdit'])): ?>
                            <a href="<?= $editUrl ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <?php endif; ?>

                        <?php if (!empty($tx['canDelete'])): ?>
                            <form method="post" action="<?= $deleteUrl ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string)($tx['id'] ?? 0), ENT_QUOTES) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        <?php endif; ?>

                        <?php if (!empty($tx['canApproveReject'])): ?>
                            <button type="button" class="btn btn-sm btn-success js-tx-approve" data-id="<?= htmlspecialchars((string)($tx['id'] ?? 0), ENT_QUOTES) ?>">Approve</button>
                            <button type="button" class="btn btn-sm btn-danger js-tx-reject" data-id="<?= htmlspecialchars((string)($tx['id'] ?? 0), ENT_QUOTES) ?>">Reject</button>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
