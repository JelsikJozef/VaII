<?php
// AI-GENERATED: Admin pending user approvals (GitHub Copilot / ChatGPT), 2026-01-19

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use App\Repositories\UserRepository;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Session;

/**
 * Admin registration approvals controller.
 *
 * Allows administrators to review newly registered users and decide whether
 * they can log in.
 *
 * How it works:
 * - New users are created with role `pending` (see `AuthController::register()`).
 * - While `pending`, `DbAuthenticator` rejects login attempts.
 * - Admins can approve/reject users and optionally set their role.
 *
 * Authorization:
 * - All actions require role `admin`.
 *
 * Actions:
 * - index(): List users and available roles; shows flash messages.
 * - approve(): Approve a pending user and assign a selected role.
 * - reject(): Delete a user record.
 * - setRole(): Change an existing user’s role.
 *
 * Side-effects:
 * - Mutates user records via {@see UserRepository}.
 * - Uses session keys `admin.reg.success` / `admin.reg.error` as flash messages.
 */
class AdminRegistrationsController extends BaseController
{
    private ?UserRepository $repo = null;
    private ?Session $flashSession = null;

    public function authorize(Request $request, string $action): bool
    {
        return $this->requireRole(['admin']);
    }

    public function index(Request $request): Response
    {
        $pending = $this->repository()->findAllUsers();
        $roles = $this->repository()->listRoles(true);

        return $this->html([
            'activeModule' => 'admin',
            'pending' => $pending,
            'roles' => $roles,
            'successMessage' => $this->consumeFlash('admin.reg.success'),
            'errorMessage' => $this->consumeFlash('admin.reg.error'),
        ], 'registrations');
    }

    public function approve(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        $roleId = (int)($request->post('role_id') ?? 0);
        if ($id <= 0 || $roleId <= 0) {
            $this->flash('admin.reg.error', 'Invalid request.');
            return $this->redirect($this->url('AdminRegistrations.index'));
        }

        $this->repository()->approveUser($id, $roleId);
        $this->flash('admin.reg.success', 'User approved.');

        return $this->redirect($this->url('AdminRegistrations.index'));
    }

    public function reject(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        if ($id <= 0) {
            $this->flash('admin.reg.error', 'Invalid request.');
            return $this->redirect($this->url('AdminRegistrations.index'));
        }

        $this->repository()->deleteUser($id);
        $this->flash('admin.reg.success', 'User deleted.');

        return $this->redirect($this->url('AdminRegistrations.index'));
    }

    public function setRole(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        $roleId = (int)($request->post('role_id') ?? 0);
        if ($id <= 0 || $roleId <= 0) {
            $this->flash('admin.reg.error', 'Invalid request.');
            return $this->redirect($this->url('AdminRegistrations.index'));
        }

        $this->repository()->setRole($id, $roleId);
        $this->flash('admin.reg.success', 'Role updated.');

        return $this->redirect($this->url('AdminRegistrations.index'));
    }

    private function repository(): UserRepository
    {
        if ($this->repo === null) {
            $this->repo = new UserRepository();
        }
        return $this->repo;
    }

    private function session(): Session
    {
        if ($this->flashSession === null) {
            $this->flashSession = $this->app->getSession();
        }
        return $this->flashSession;
    }

    private function flash(string $key, mixed $value): void
    {
        $this->session()->set($key, $value);
    }

    private function consumeFlash(string $key): mixed
    {
        $value = $this->session()->get($key);
        $this->session()->remove($key);
        return $value;
    }
}
