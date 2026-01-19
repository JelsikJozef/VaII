<?php
// AI-GENERATED: Update module links for manual card (GitHub Copilot / ChatGPT), 2026-01-18

/** @var \Framework\Support\LinkGenerator $link */
?>

<div class="container mt-4">
    <h1 class="mb-4">Modules</h1>

    <?php if (!empty($news)): ?>
    <section class="mb-4">
        <h2 class="h5">Latest news</h2>
        <ul class="list-group">
            <?php foreach ($news as $item): ?>
                <li class="list-group-item">
                    <div class="small text-muted mb-1"><?= htmlspecialchars((string)($item['ts'] ?? ''), ENT_QUOTES) ?></div>
                    <div><?= htmlspecialchars((string)($item['message'] ?? ''), ENT_QUOTES) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-12 col-md-6 col-lg-3">
            <a href="<?= $link->url('Treasury.index') ?>" class="text-decoration-none text-reset">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Treasury</h5>
                        <p class="card-text mb-0">
                            Manage treasury transactions and balances.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <a href="<?= $link->url('Esncards.index') ?>" class="text-decoration-none text-reset">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">ESNcards</h5>
                        <p class="card-text mb-0">
                            Work with ESNcard records.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <a href="<?= $link->url('Manual.index') ?>" class="text-decoration-none text-reset">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Semester Manual</h5>
                        <p class="card-text mb-0">
                            Access the semester manual module.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <a href="<?= $link->url('Polls.index') ?>" class="text-decoration-none text-reset">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Polls</h5>
                        <p class="card-text mb-0">
                            Manage and view polls.
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <?php if (!$user?->isLoggedIn()): ?>
        <div class="col-12 col-md-6 col-lg-3">
            <a href="<?= $link->url('Auth.registerForm') ?>" class="text-decoration-none text-reset">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Register</h5>
                        <p class="card-text mb-0">
                            Create your account (admin approval required).
                        </p>
                    </div>
                </div>
            </a>
        </div>
        <?php elseif ($user?->getRole() === 'admin'): ?>
        <div class="col-12 col-md-6 col-lg-3">
            <a href="<?= $link->url('AdminRegistrations.index') ?>" class="text-decoration-none text-reset">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Admin: Registrations</h5>
                        <p class="card-text mb-0">
                            Review and approve pending users.
                        </p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
