<?php
// AI-GENERATED: ESNcards index view with filters (GitHub Copilot / ChatGPT), 2026-01-18

/** @var \Framework\Support\View $view */
$view->setLayout('root');

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $esncards */
/** @var string $search */
/** @var string $status */
/** @var bool $canManage */

$esncards = $esncards ?? [];
$search = $search ?? '';
$status = $status ?? '';
$canManage = $canManage ?? false;

$statusBadge = static function (string $value): array {
    return match ($value) {
        'assigned' => ['Assigned', 'badge bg-success'],
        'inactive' => ['Inactive', 'badge bg-danger'],
        default => ['Available', 'badge bg-secondary'],
    };
};
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1">ESNcards</h1>
            <p class="text-muted mb-0">Manage ESNcard inventory and assignments.</p>
        </div>
        <?php if ($canManage): ?>
            <div>
                <a href="<?= $link->url('Esncards.new') ?>" class="btn btn-primary">New card</a>
            </div>
        <?php endif; ?>
    </div>

    <form method="get" action="<?= $link->url('Esncards.index') ?>" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="c" value="esncards">
        <input type="hidden" name="a" value="index">
        <div class="col-md-6">
            <label for="search" class="form-label">Search by card number or email</label>
            <input type="text" name="q" id="search" class="form-control" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="Search...">
        </div>
        <div class="col-md-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="" <?= $status === '' ? 'selected' : '' ?>>All</option>
                <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option>
                <option value="assigned" <?= $status === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary flex-grow-1">Filter</button>
            <a href="<?= $link->url('Esncards.index') ?>" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <?php if (empty($esncards)): ?>
        <div class="alert alert-info">No ESNcards found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th scope="col">Card number</th>
                    <th scope="col">Status</th>
                    <th scope="col">Assigned to</th>
                    <th scope="col">Email</th>
                    <th scope="col">Assigned at</th>
                    <?php if ($canManage): ?>
                        <th scope="col" class="text-end">Actions</th>
                    <?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($esncards as $card):
                    $badge = $statusBadge((string)($card['status'] ?? 'available'));
                    $id = (int)($card['id'] ?? 0);
                    $editUrl = $link->url('Esncards.edit', ['id' => $id]);
                    $deleteUrl = $link->url('Esncards.delete', ['id' => $id]);
                ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($card['card_number'] ?? ''), ENT_QUOTES) ?></td>
                        <td><span class="<?= $badge[1] ?>"><?= htmlspecialchars($badge[0], ENT_QUOTES) ?></span></td>
                        <td><?= htmlspecialchars((string)($card['assigned_to_name'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($card['assigned_to_email'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($card['assigned_at'] ?? ''), ENT_QUOTES) ?></td>
                        <?php if ($canManage): ?>
                            <td class="text-end">
                                <a href="<?= $editUrl ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                <form method="post" action="<?= $deleteUrl ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this card?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id, ENT_QUOTES) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
