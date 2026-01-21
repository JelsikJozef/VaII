<?php
// AI-GENERATED: ESNcards index redesigned to card layout (GitHub Copilot / ChatGPT), 2026-01-20

/**
 * ESNcards: Index.
 *
 * Lists ESN cards, supports searching and filtering by status.
 * When `$canManage` is true, shows create/edit/delete controls.
 *
 * Expected variables:
 * - \Framework\Support\View $view
 * - \Framework\Support\LinkGenerator $link
 * - array<int,array<string,mixed>> $esncards
 * - string $search Query string
 * - string $status Status filter (available|assigned|inactive or empty)
 * - bool $canManage
 * - string|null $successMessage
 * - string|null $errorMessage
 */

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
    $normalized = strtolower($value);
    return match ($normalized) {
        'assigned' => ['Assigned', 'esn-pill esn-pill--success'],
        'inactive' => ['Inactive', 'esn-pill esn-pill--muted'],
        default => ['Available', 'esn-pill esn-pill--neutral'],
    };
};
?>

<div class="esncards-page esn-page">
    <div class="esncards-shell">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3 esn-page-header">
            <div>
                <h1 class="esn-title mb-1">ESNcards</h1>
                <p class="esn-subtitle mb-0">Manage ESNcard inventory and assignments.</p>
            </div>
            <?php if ($canManage): ?>
                <div class="d-flex align-items-center">
                    <a href="<?= $link->url('Esncards.new') ?>" class="btn btn-primary">New card</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="esn-card card mb-4">
            <div class="card-body">
                <form method="get" action="<?= $link->url('Esncards.index') ?>" class="row g-3 align-items-end esn-filter-row esncards-filter">
                    <input type="hidden" name="c" value="esncards">
                    <input type="hidden" name="a" value="index">
                    <div class="col-12 col-lg-7">
                        <label for="search" class="form-label">Search by card number or email</label>
                        <input type="text" name="q" id="search" class="form-control" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="Search by card number or email">
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="" <?= $status === '' ? 'selected' : '' ?>>All</option>
                            <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="assigned" <?= $status === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-2">
                        <div class="esncards-filter__actions">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="<?= $link->url('Esncards.index') ?>" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($esncards)): ?>
            <div class="esn-card card p-4">
                <h2 class="h5 mb-1">No ESN cards found</h2>
                <p class="text-muted mb-3">Try adjusting filters or create a new card.</p>
                <?php if ($canManage): ?>
                    <a href="<?= $link->url('Esncards.new') ?>" class="btn btn-primary">New card</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="esncards-grid">
                <?php foreach ($esncards as $card):
                    $badge = $statusBadge((string)($card['status'] ?? 'available'));
                    $id = (int)($card['id'] ?? 0);
                    $editUrl = $link->url('Esncards.edit', ['id' => $id]);
                    $deleteUrl = $link->url('Esncards.delete', ['id' => $id]);
                    // AI-GENERATED: Unified ESNcard assignment timestamp formatting (GitHub Copilot / ChatGPT), 2026-01-20
                    $assignedAt = $formatDateTime($card['assigned_at'] ?? null);
                    $assignedName = trim((string)($card['assigned_to_name'] ?? ''));
                    $assignedEmail = trim((string)($card['assigned_to_email'] ?? ''));
                    $hasAssignee = $assignedName !== '' || $assignedEmail !== '';
                ?>
                <article class="esncards-card card h-100">
                    <div class="esncards-card__inner">
                        <div class="esncards-card__header">
                            <div>
                                <div class="esn-label text-muted">Card number</div>
                                <div class="esncards-card__number"><?= htmlspecialchars((string)($card['card_number'] ?? ''), ENT_QUOTES) ?></div>
                            </div>
                            <span class="<?= $badge[1] ?>"><?= htmlspecialchars($badge[0], ENT_QUOTES) ?></span>
                        </div>

                        <div class="esncards-card__body">
                            <div class="esn-label text-muted">Assignment</div>
                            <div class="fw-semibold mb-1"><?= htmlspecialchars($hasAssignee ? $assignedName : 'Unassigned', ENT_QUOTES) ?></div>
                            <div class="text-muted small esncards-card__email">Email: <?= htmlspecialchars($assignedEmail !== '' ? $assignedEmail : '—', ENT_QUOTES) ?></div>
                            <div class="text-muted small">Assigned at: <?= htmlspecialchars($assignedAt, ENT_QUOTES) ?></div>
                        </div>

                        <?php if ($canManage): ?>
                            <div class="esncards-card__footer">
                                <!-- Actions stay inside the card footer to avoid floating outside the grid -->
                                <a href="<?= $editUrl ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" action="<?= $deleteUrl ?>" onsubmit="return confirm('Are you sure you want to delete this card?');">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id, ENT_QUOTES) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
