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
        return $this->html([
            'activeModule' => 'auth',
            'errors' => [],
            'email' => '',
            'genericError' => null,
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
            ], 'login');
        }

        $auth = $this->app->getAuthenticator();
        $success = $auth?->login($email, $password) ?? false;

        if (!$success) {
            return $this->html([
                'activeModule' => 'auth',
                'errors' => [],
                'email' => $email,
                'genericError' => 'Invalid credentials.',
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
}
