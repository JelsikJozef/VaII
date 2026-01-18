<?php
// AI-GENERATED: Database-backed authenticator using sessions (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Auth;

use App\Repositories\UserRepository;
use Framework\Auth\SessionAuthenticator;
use Framework\Core\App;
use Framework\Core\IIdentity;
use App\Auth\UserIdentity;

class DbAuthenticator extends SessionAuthenticator
{
    private UserRepository $users;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->users = new UserRepository();
    }

    protected function authenticate(string $username, string $password): ?IIdentity
    {
        $user = $this->users->findByEmail($username);
        if ($user === null) {
            return null;
        }

        $hash = (string)($user['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return null;
        }

        $roleName = (string)($user['role_name'] ?? $user['role'] ?? '');
        if ($roleName === '') {
            $roleName = 'member';
        }

        $name = (string)($user['name'] ?? $user['email'] ?? 'User');

        return new UserIdentity((int)$user['id'], $name, (string)$user['email'], $roleName);
    }
}
