<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $transactions */

$transactions = $transactions ?? [];

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Treasury</h1>
        <a href="<?= $link->url(['Treasury', 'new']) ?>" class="btn btn-primary esn-btn-primary">New transaction</a>
    </div>

    <div class="mb-3">
        <label for="transaction-status-filter" class="form-label">Filter by status</label>
        <select id="transaction-status-filter" class="form-select">
            <option value="all">All</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    <?php if (empty($transactions)): ?>
        <p id="no-transactions-message" class="text-muted">No transactions yet.</p>
    <?php else: ?>
        <p id="no-transactions-message" class="text-muted d-none">No transactions for selected status.</p>

        <div class="table-responsive">
            <table id="transactions-table" class="table table-striped align-middle">
                <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Type</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Description</th>
                    <th scope="col">Status</th>
                    <th scope="col">Created at</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions as $tx): ?>
                    <tr data-status="<?= htmlspecialchars($tx['status'] ?? 'pending', ENT_QUOTES) ?>">
                        <td><?= htmlspecialchars((string)($tx['id'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($tx['type'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($tx['amount'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($tx['description'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($tx['status'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($tx['created_at'] ?? ''), ENT_QUOTES) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
