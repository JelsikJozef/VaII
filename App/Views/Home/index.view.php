<?php
// AI-GENERATED: Dashboard activity actor names fallback (GitHub Copilot / ChatGPT), 2026-01-20

/** @var \Framework\Support\LinkGenerator $link */
?>

<div class="container-lg esn-page">
    <header class="esn-page-header">
        <div class="esn-kicker">DASHBOARD</div>
        <h1 class="esn-title">Welcome to ESN UNIZA section system</h1>
        <p class="esn-subtitle">Where would you like to procced ?</p>
    </header>

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-8 order-1">
            <section>
                <div class="module-grid">
                    <a href="<?= $link->url('Treasury.index') ?>" class="module-card-link">
                        <div class="card esn-card module-card h-100">
                            <div class="card-body">
                                <h3 class="module-card__title">Treasury</h3>
                                <p class="module-card__desc mb-0">Manage treasury transactions and balances.</p>
                            </div>
                        </div>
                    </a>

                    <a href="<?= $link->url('Esncards.index') ?>" class="module-card-link">
                        <div class="card esn-card module-card h-100">
                            <div class="card-body">
                                <h3 class="module-card__title">ESNcards</h3>
                                <p class="module-card__desc mb-0">Work with ESNcard records.</p>
                            </div>
                        </div>
                    </a>

                    <a href="<?= $link->url('Manual.index') ?>" class="module-card-link">
                        <div class="card esn-card module-card h-100">
                            <div class="card-body">
                                <h3 class="module-card__title">Semester Manual</h3>
                                <p class="module-card__desc mb-0">Access the semester manual module.</p>
                            </div>
                        </div>
                    </a>

                    <a href="<?= $link->url('Polls.index') ?>" class="module-card-link">
                        <div class="card esn-card module-card h-100">
                            <div class="card-body">
                                <h3 class="module-card__title">Polls</h3>
                                <p class="module-card__desc mb-0">Manage and view polls.</p>
                            </div>
                        </div>
                    </a>

                    <?php if (!$user?->isLoggedIn()): ?>
                    <a href="<?= $link->url('Auth.registerForm') ?>" class="module-card-link module-span-2">
                        <div class="card esn-card module-card h-100">
                            <div class="card-body">
                                <h3 class="module-card__title">Register</h3>
                                <p class="module-card__desc mb-0">Create your account (admin approval required).</p>
                            </div>
                        </div>
                    </a>
                    <?php elseif ($user?->getRole() === 'admin'): ?>
                    <a href="<?= $link->url('AdminRegistrations.index') ?>" class="module-card-link module-span-2">
                        <div class="card esn-card module-card h-100">
                            <div class="card-body">
                                <h3 class="module-card__title">Admin: Registrations</h3>
                                <p class="module-card__desc mb-0">Review and approve pending users.</p>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-4 order-2">
            <section class="card esn-card activity-card">
                <div class="d-flex align-items-start justify-content-between esn-card-header">
                    <div>
                        <p class="esn-activity-eyebrow mb-1">Latest activity</p>
                        <h2 class="h5 mb-0">Recent changes in the system</h2>
                    </div>
                    <a href="#" class="esn-activity-link">View all</a>
                </div>
                <div class="activity-list" role="list">
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $item): ?>
                            <?php
                                $actionValue = (string)($item['action'] ?? '');
                                $dotClass = 'activity-dot';
                                $actionLower = strtolower($actionValue);
                                if (str_contains($actionLower, 'assign') || str_contains($actionLower, 'create')) {
                                    $dotClass .= ' activity-dot--success';
                                } elseif (str_contains($actionLower, 'delete')) {
                                    $dotClass .= ' activity-dot--danger';
                                } elseif (str_contains($actionLower, 'status') || str_contains($actionLower, 'update')) {
                                    $dotClass .= ' activity-dot--warning';
                                } else {
                                    $dotClass .= ' activity-dot--info';
                                }
                                $actorName = trim((string)($item['actor_name'] ?? ''));
                                $actorEmail = trim((string)($item['actor_email'] ?? ''));
                                $userId = $item['user_id'] ?? null;
                                if ($actorName !== '') {
                                    $actor = $actorName;
                                } elseif ($actorEmail !== '') {
                                    $actor = $actorEmail;
                                } elseif ($userId === null) {
                                    $actor = 'System';
                                } else {
                                    $actor = 'Unknown user';
                                }
                                $timestamp = $formatDateTime($item['created_at'] ?? null);
                                $detailsRaw = (string)($item['details'] ?? '');
                                $detailsText = '';
                                if ($detailsRaw !== '') {
                                    $decoded = json_decode($detailsRaw, true);
                                    if (is_array($decoded) && !empty($decoded)) {
                                        $pairs = [];
                                        foreach ($decoded as $k => $v) {
                                            if (in_array($k, ['user', 'created_by', 'approved_by', 'updated_by'], true)) {
                                                $pairs[] = $k . ': ' . 'Unknown user';
                                                continue;
                                            }
                                            $pairs[] = $k . ': ' . (is_scalar($v) ? (string)$v : json_encode($v));
                                        }
                                        $detailsText = implode(', ', $pairs);
                                    } else {
                                        $detailsText = $detailsRaw;
                                    }
                                }
                            ?>
                            <article class="activity-item" data-action="<?= htmlspecialchars($actionValue, ENT_QUOTES) ?>" role="listitem">
                                <span class="<?= $dotClass ?>" aria-hidden="true"><span class="activity-dot__inner"></span></span>
                                <div class="activity-body">
                                    <div class="activity-title">
                                        <?= htmlspecialchars((string)($item['title'] ?? ''), ENT_QUOTES) ?>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="activity-chip"><?= htmlspecialchars($actionValue !== '' ? $actionValue : 'ACTIVITY', ENT_QUOTES) ?></span>
                                        <span class="esn-activity-separator" aria-hidden="true">•</span>
                                        <span class="activity-actor">by <?= htmlspecialchars($actor, ENT_QUOTES) ?></span>
                                        <span class="esn-activity-separator" aria-hidden="true">•</span>
                                        <span class="activity-time js-iso-time"><?= htmlspecialchars($timestamp, ENT_QUOTES) ?></span>
                                    </div>
                                    <?php if ($detailsText !== ''): ?>
                                        <div class="activity-details text-muted"><?= htmlspecialchars($detailsText, ENT_QUOTES) ?></div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="activity-empty" role="listitem">
                            <span class="activity-dot activity-dot--info" aria-hidden="true"><span class="activity-dot__inner"></span></span>
                            <div class="activity-body">
                                <div class="activity-title">No recent activity yet.</div>
                                <div class="activity-meta">System updates will appear here.</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
