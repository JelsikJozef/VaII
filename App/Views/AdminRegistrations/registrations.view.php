<?php
// AI-GENERATED: Admin pending registrations view (GitHub Copilot / ChatGPT), 2026-01-19

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */
/** @var array $pending */
/** @var array $roles */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

$view->setLayout('root');
$pending = $pending ?? [];
$roles = $roles ?? [];
?>

<div class="container mt-4">
    <h1 class="mb-3">Pending registrations</h1>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars((string)$successMessage, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars((string)$errorMessage, ENT_QUOTES) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($pending)): ?>
        <p class="text-muted">No pending registrations.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Role</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pending as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)($user['created_at'] ?? ''), ENT_QUOTES) ?></td>
                        <td>
                            <form class="d-flex gap-2" method="post" action="<?= $link->url('AdminRegistrations.setRole', ['id' => $user['id'] ?? 0]) ?>">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES) ?>">
                                <select name="role_id" class="form-select form-select-sm" required>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= htmlspecialchars((string)($role['id'] ?? ''), ENT_QUOTES) ?>">
                                            <?= htmlspecialchars((string)($role['name'] ?? ''), ENT_QUOTES) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Set</button>
                            </form>
                        </td>
                        <td class="text-end">
                            <form class="d-inline" method="post" action="<?= $link->url('AdminRegistrations.approve', ['id' => $user['id'] ?? 0]) ?>">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES) ?>">
                                <input type="hidden" name="role_id" value="<?= htmlspecialchars((string)($roles[0]['id'] ?? ''), ENT_QUOTES) ?>">
                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                            </form>
                            <form class="d-inline ms-2" method="post" action="<?= $link->url('AdminRegistrations.reject', ['id' => $user['id'] ?? 0]) ?>" onsubmit="return confirm('Reject this registration?');">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string)($user['id'] ?? ''), ENT_QUOTES) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
