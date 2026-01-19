<?php
// AI-GENERATED: Implement session login/logout with validation (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Controllers;

use App\Configuration;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Class AuthController
 *
 * This controller handles authentication actions such as login, logout, and redirection to the login page. It manages
 * user sessions and interactions with the authentication system.
 *
 * @package App\Controllers
 */
class AuthController extends BaseController
{
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
        $session = $this->app->getSession();
        $success = $session->get('auth.success');
        $session->remove('auth.success');

        return $this->html([
            'activeModule' => 'auth',
            'errors' => [],
            'email' => '',
            'genericError' => null,
            'successMessage' => $success,
        ], 'login');
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
        $email = trim((string)($request->post('email') ?? ''));
        $password = (string)($request->post('password') ?? '');

        $errors = [];
        if ($email === '') {
            $errors['email'][] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Email is not valid.';
        }

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        }

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'auth',
                'errors' => $errors,
                'email' => $email,
                'genericError' => null,
                'successMessage' => null,
            ], 'login');
        }

        $auth = $this->app->getAuthenticator();
        $success = $auth?->login($email, $password) ?? false;

        if (!$success) {
            return $this->html([
                'activeModule' => 'auth',
                'errors' => [],
                'email' => $email,
                'genericError' => 'Invalid credentials or awaiting approval.',
                'successMessage' => null,
            ], 'login');
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
        return $this->html([
            'activeModule' => 'auth',
            'errors' => [],
            'name' => '',
            'email' => '',
        ], 'register');
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
        $name = trim((string)($request->post('name') ?? ''));
        $email = trim((string)($request->post('email') ?? ''));
        $password = (string)($request->post('password') ?? '');
        $passwordConfirm = (string)($request->post('password_confirm') ?? '');

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
        } elseif ((new \App\Repositories\UserRepository())->emailExists($email)) {
            $errors['email'][] = 'Email is already in use.';
        }

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters.';
        }

        if ($passwordConfirm === '') {
            $errors['password_confirm'][] = 'Please confirm the password.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'][] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'auth',
                'errors' => $errors,
                'name' => $name,
                'email' => $email,
                'successMessage' => null,
            ], 'register');
        }

        $repo = new \App\Repositories\UserRepository();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $repo->createPendingUser($name, $email, $hash);

        $this->app->getSession()->set('auth.success', 'Registration submitted. Wait for admin approval.');

        return $this->redirect($this->url('Auth.loginForm'));
    }
}
