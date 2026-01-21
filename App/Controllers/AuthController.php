<?php
// AI-GENERATED: Implement session login/logout with validation (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Controllers;

use App\Configuration;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use App\Services\AuthService;

/**
 * Authentication controller (login / logout / registration).
 *
 * Responsibilities:
 * - Render login and registration forms.
 * - Validate submitted credentials.
 * - Delegate authentication to the configured authenticator
 *   (see `App/Auth/DbAuthenticator.php`).
 * - Create new user accounts in `pending` state.
 *
 * Authorization:
 * - Public: index(), loginForm(), login(), registerForm(), register()
 * - Requires login: logout()
 *
 * Side-effects:
 * - On successful login, the authenticator stores an {@see \Framework\Core\IIdentity}
 *   in the session.
 * - On registration, a user is created with role `pending` and a success message
 *   is stored in session key `auth.success`.
 */
class AuthController extends BaseController
{
    private ?AuthService $service = null;

    /**
     * Authorizes the requested action.
     *
     * This method determines if the requested action requires authentication. The login and logout actions have
     * specific requirements: login and loginForm are accessible without authentication, while logout requires
     * the user to be logged in.
     *
     * @param Request $request The current request instance.
     * @param string $action The action to authorize.
     * @return bool True if the action is authorized, false otherwise.
     */
    public function authorize(Request $request, string $action): bool
    {
        // Allow loginForm/login without auth, guard logout for logged-in only
        if ($action === 'logout') {
            return $this->requireLogin();
        }

        return true;
    }

    /**
     * Redirects to the login page.
     *
     * This action serves as the default landing point for the authentication section of the application, directing
     * users to the login URL specified in the configuration.
     *
     * @return Response The response object for the redirection to the login page.
     */
    public function index(Request $request): Response
    {
        return $this->redirect($this->url('Auth.loginForm'));
    }

    /**
     * Displays the login form.
     *
     * This action renders the login view, including any error messages and previously entered email address.
     *
     * @return Response The response object that renders the login form view.
     */
    public function loginForm(Request $request): Response
    {
        $result = $this->svc()->loginForm($this->userContext());
        $data = $result['payload'] ?? [];
        $data['successMessage'] = $this->consumeFlash('auth.success');
        $data['errorMessage'] = $this->consumeFlash('auth.error');
        return $this->html($data, 'login');
    }

    /**
     * Authenticates a user and processes the login request.
     *
     * This action handles user login attempts. It validates the provided email and password, and if valid, attempts
     * to authenticate the user. On successful login, the user is redirected to the home page. Otherwise, the login
     * form is re-rendered with error messages.
     *
     * @return Response The response object which can either redirect on success or render the login view with
     *                  error messages on failure.
     */
    public function login(Request $request): Response
    {
        $email = (string)($request->post('email') ?? '');
        $password = (string)($request->post('password') ?? '');

        $result = $this->svc()->login($this->userContext(), [
            'email' => $email,
            'password' => $password,
        ]);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            $data = $result['payload'] ?? [];
            $data['errors'] = $result['errors'] ?? [];
            $data['successMessage'] = $this->consumeFlash('auth.success');
            $data['errorMessage'] = $this->consumeFlash('auth.error');
            return $this->html($data, 'login');
        }

        // Service validated credentials; now perform session login via authenticator.
        $auth = $this->app->getAuthenticator();
        $loginOk = $auth?->login($email, $password) ?? false;
        if (!$loginOk) {
            $this->flash('auth.error', 'Unable to log in. Please try again.');
            $data = $result['payload'] ?? [];
            $data['errors'] = [];
            $data['genericError'] = 'Unable to log in. Please try again.';
            return $this->html($data, 'login');
        }

        return $this->redirect($this->url('Home.index'));
    }

    /**
     * Logs out the current user.
     *
     * This action terminates the user's session and redirects them to the login page. It effectively clears any
     * authentication tokens or session data associated with the user.
     *
     * @return Response The response object that redirects to the login page.
     */
    public function logout(Request $request): Response
    {
        $result = $this->svc()->logout($this->userContext());
        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }
        $this->app->getAuthenticator()?->logout();
        return $this->redirect($this->url('Auth.loginForm'));
    }

    /**
     * Displays the registration form.
     *
     * This action renders the registration view, including any error messages and previously entered data.
     *
     * @return Response The response object that renders the registration form view.
     */
    public function registerForm(Request $request): Response
    {
        $result = $this->svc()->registerForm($this->userContext());
        $data = $result['payload'] ?? [];
        $data['successMessage'] = $this->consumeFlash('auth.success');
        $data['errorMessage'] = $this->consumeFlash('auth.error');
        return $this->html($data, 'register');
    }

    /**
     * Processes the registration request.
     *
     * This action handles user registration attempts. It validates the provided data, and if valid, creates a new
     * pending user account. The user is then redirected to the login page with a success message. Otherwise, the
     * registration form is re-rendered with error messages.
     *
     * @return Response The response object which can either redirect on success or render the registration view with
     *                  error messages on failure.
     */
    public function register(Request $request): Response
    {
        $result = $this->svc()->register($this->userContext(), [
            'name' => $request->post('name'),
            'email' => $request->post('email'),
            'password' => $request->post('password'),
            'password_confirm' => $request->post('password_confirm'),
        ]);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            $data = $result['payload'] ?? [];
            $data['errors'] = $result['errors'] ?? [];
            return $this->html($data, 'register');
        }

        return $this->redirect($this->url('Auth.loginForm'));
    }

    private function svc(): AuthService
    {
        if ($this->service === null) {
            $this->service = new AuthService();
        }
        return $this->service;
    }

    private function userContext(): array
    {
        $id = $this->user?->getIdentity()?->getId();
        $role = $this->user?->getIdentity()?->getRole();
        if ($role === null && $this->user?->getRole() !== null) {
            $role = $this->user->getRole();
        }
        return [
            'userId' => $id !== null ? (int)$id : null,
            'role' => $role !== null ? (string)$role : null,
            'isLoggedIn' => $id !== null,
        ];
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

    /** @param array{type?:string,message?:string} $flash */
    private function writeFlashFromDomainResult(array $flash): void
    {
        $type = (string)($flash['type'] ?? '');
        $message = (string)($flash['message'] ?? '');
        if ($message === '') {
            return;
        }
        if ($type === 'success') {
            $this->flash('auth.success', $message);
            return;
        }
        $this->flash('auth.error', $message);
    }
}
