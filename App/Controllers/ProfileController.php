<?php
// AI-GENERATED: User self-service profile management (GitHub Copilot / ChatGPT), 2026-01-19

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use App\Auth\UserIdentity;
use App\Configuration;
use App\Repositories\UserRepository;
use Framework\Auth\AppUser;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Session;

/**
 * Logged-in user profile controller.
 *
 * Provides self-service profile management:
 * - Viewing the current user’s information
 * - Editing name/email
 * - Changing password
 *
 * Authorization:
 * - All actions require the user to be logged in.
 *
 * Side-effects & security notes:
 * - `update()` persists profile changes and refreshes the identity stored in the session
 *   (`Configuration::IDENTITY_SESSION_KEY`) so the navbar/UI immediately reflects changes.
 * - `changePassword()` updates the password hash and regenerates the PHP session id
 *   (when available) to reduce session fixation risk.
 * - Uses session keys `profile.success` / `profile.error` as flash messages.
 */
class ProfileController extends BaseController
{
    private ?UserRepository $repository = null;
    private ?Session $flashSession = null;

    public function authorize(Request $request, string $action): bool
    {
        return $this->requireLogin();
    }

    public function index(Request $request): Response
    {
        $identity = $this->user?->getIdentity();
        if ($identity === null) {
            return $this->redirect($this->url('Auth.loginForm'));
        }

        $user = $this->repo()->findById($identity->getId()) ?? [
            'id' => $identity->getId(),
            'name' => $identity->getName(),
            'email' => $identity->getEmail(),
            'role_name' => $identity->getRole(),
        ];

        return $this->html([
            'activeModule' => 'profile',
            'profile' => $user,
            'successMessage' => $this->consumeFlash('profile.success'),
            'errorMessage' => $this->consumeFlash('profile.error'),
        ]);
    }

    public function edit(Request $request): Response
    {
        $identity = $this->user?->getIdentity();
        if ($identity === null) {
            return $this->redirect($this->url('Auth.loginForm'));
        }

        $user = $this->repo()->findById($identity->getId());
        if ($user === null) {
            $this->flash('profile.error', 'Profile not found.');
            return $this->redirect($this->url('Profile.index'));
        }

        return $this->html([
            'activeModule' => 'profile',
            'errorsProfile' => [],
            'errorsPassword' => [],
            'name' => (string)($user['name'] ?? ''),
            'email' => (string)($user['email'] ?? ''),
            'successMessage' => $this->consumeFlash('profile.success'),
            'errorMessage' => $this->consumeFlash('profile.error'),
        ], 'edit');
    }

    public function update(Request $request): Response
    {
        $identity = $this->user?->getIdentity();
        if ($identity === null) {
            return $this->redirect($this->url('Auth.loginForm'));
        }

        $user = $this->repo()->findById($identity->getId());
        if ($user === null) {
            $this->flash('profile.error', 'Profile not found.');
            return $this->redirect($this->url('Profile.index'));
        }

        $name = trim((string)($request->post('name') ?? ''));
        $email = trim((string)($request->post('email') ?? ''));

        $errors = $this->validateProfileInput($name, $email, (int)$user['id']);

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'profile',
                'errorsProfile' => $errors,
                'errorsPassword' => [],
                'name' => $name,
                'email' => $email,
                'successMessage' => $this->consumeFlash('profile.success'),
                'errorMessage' => $this->consumeFlash('profile.error'),
            ], 'edit');
        }

        $this->repo()->updateProfile((int)$user['id'], $name, $email);

        $roleName = (string)($user['role_name'] ?? $identity->getRole());
        $newIdentity = new UserIdentity((int)$user['id'], $name, $email, $roleName);
        $this->app->getSession()->set(Configuration::IDENTITY_SESSION_KEY, $newIdentity);
        $this->user = new AppUser($newIdentity);

        $this->flash('profile.success', 'Profile updated successfully.');

        return $this->redirect($this->url('Profile.index'));
    }

    public function changePassword(Request $request): Response
    {
        $identity = $this->user?->getIdentity();
        if ($identity === null) {
            return $this->redirect($this->url('Auth.loginForm'));
        }

        $user = $this->repo()->findById($identity->getId());
        if ($user === null) {
            $this->flash('profile.error', 'Profile not found.');
            return $this->redirect($this->url('Profile.index'));
        }

        $currentPassword = (string)($request->post('current_password') ?? '');
        $newPassword = (string)($request->post('new_password') ?? '');
        $newPasswordConfirm = (string)($request->post('new_password_confirm') ?? '');

        $errors = $this->validatePasswordChange($currentPassword, $newPassword, $newPasswordConfirm, (string)($user['password_hash'] ?? ''));

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'profile',
                'errorsProfile' => [],
                'errorsPassword' => $errors,
                'name' => (string)($user['name'] ?? ''),
                'email' => (string)($user['email'] ?? ''),
                'successMessage' => $this->consumeFlash('profile.success'),
                'errorMessage' => $this->consumeFlash('profile.error'),
            ], 'edit');
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->repo()->updatePasswordHash((int)$user['id'], $hash);

        if (function_exists('session_regenerate_id')) {
            @session_regenerate_id(true);
        }

        $this->flash('profile.success', 'Password updated successfully.');

        return $this->redirect($this->url('Profile.index'));
    }

    private function validateProfileInput(string $name, string $email, int $userId): array
    {
        $errors = [];

        if ($name === '') {
            $errors['name'][] = 'Name is required.';
        } elseif (mb_strlen($name) < 2) {
            $errors['name'][] = 'Name must be at least 2 characters.';
        } elseif (mb_strlen($name) > 255) {
            $errors['name'][] = 'Name must be at most 255 characters.';
        }

        if ($email === '') {
            $errors['email'][] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Email is not valid.';
        } elseif (mb_strlen($email) > 255) {
            $errors['email'][] = 'Email must be at most 255 characters.';
        } elseif ($this->repo()->emailExistsForOtherUser($email, $userId)) {
            $errors['email'][] = 'Email is already used by another account.';
        }

        return $errors;
    }

    private function validatePasswordChange(
        string $currentPassword,
        string $newPassword,
        string $newPasswordConfirm,
        string $currentHash
    ): array {
        $errors = [];

        if ($currentPassword === '') {
            $errors['current_password'][] = 'Current password is required.';
        } elseif ($currentHash === '' || !password_verify($currentPassword, $currentHash)) {
            $errors['current_password'][] = 'Current password is incorrect.';
        }

        if ($newPassword === '') {
            $errors['new_password'][] = 'New password is required.';
        } elseif (strlen($newPassword) < 8) {
            $errors['new_password'][] = 'New password must be at least 8 characters.';
        }

        if ($newPasswordConfirm === '') {
            $errors['new_password_confirm'][] = 'Please confirm the new password.';
        } elseif ($newPassword !== $newPasswordConfirm) {
            $errors['new_password_confirm'][] = 'Password confirmation does not match.';
        }

        return $errors;
    }

    private function repo(): UserRepository
    {
        if ($this->repository === null) {
            $this->repository = new UserRepository();
        }

        return $this->repository;
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
