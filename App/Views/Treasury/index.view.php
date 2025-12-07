<?php

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $transactions */

$transactions = $transactions ?? [];
$currentBalance = $currentBalance ?? 0.0;

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
    <section class="treasury-hero">
        <div class="treasury-hero__eyebrow">ESN Treasury</div>
        <h1 class="treasury-hero__title">Welcome to the Treasury of ESN UNIZA</h1>
        <p class="treasury-hero__subtitle">
            Propose withdrawal or add deposit
        </p>
        <p class="treasury-hero__balance">Current balance: <strong><?= number_format((float)$currentBalance, 2, ',', ' ') ?> €</strong></p>
        <div class="treasury-hero__cta">
            <!-- Use string destination "Treasury.new" + parameters; avoid array destination when passing $parameters -->
            <a href="<?= $link->url('Treasury.new', ['type' => 'withdrawal']) ?>" class="btn treasury-btn treasury-btn--withdrawal">
                Propose Withdrawal
            </a>
            <a href="<?= $link->url('Treasury.new', ['type' => 'deposit']) ?>" class="btn treasury-btn treasury-btn--deposit">
                Add deposit
            </a>
            <button type="button" id="treasury-refresh-btn" class="btn treasury-btn treasury-btn--refresh">
                Refresh data
            </button>
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
                    $proposedBy = trim((string)($tx['proposed_by'] ?? 'Unspecified member'));
                    $createdAt = $tx['created_at'] ?? '';
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
                    <footer class="treasury-card__footer">
                        <span class="treasury-card__date"><?= htmlspecialchars($createdAt, ENT_QUOTES) ?></span>
                        <span class="treasury-status <?= $statusData[1] ?>">
                            <?= htmlspecialchars($statusData[0], ENT_QUOTES) ?></span>
                    </footer>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
