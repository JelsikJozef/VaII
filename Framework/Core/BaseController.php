<?php

namespace Framework\Core;

use Exception;
use Framework\Auth\AppUser;
use Framework\Http\Request;
use Framework\Http\Responses\JsonResponse;
use Framework\Http\Responses\RedirectResponse;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\ViewResponse;

/**
 * Common controller base with app access, helpers for responses, and basic auth guards.
 */
abstract class BaseController
{
    /**
     * Framework app instance available to controllers.
     */
    protected App $app;

    /**
     * Current authenticated user wrapper.
     */
    protected AppUser $user;

    /**
     * Controller name without the "Controller" suffix (e.g., Home).
     */
    public function getName(): string
    {
        return str_replace("Controller", "", $this->getClassName());
    }

    /**
     * Fully qualified controller class name.
     */
    public function getClassName(): string
    {
        $arr = explode("\\", get_class($this));
        return end($arr);
    }

    /**
     * Inject the current app instance and cache the user.
     */
    public function setApp(App $app): void
    {
        $this->app = $app;
        $this->user = $app->getAppUser(); // Initialize the user property
    }

    /**
     * Override to restrict access to controller actions.
     */
    public function authorize(Request $request, string $action): bool
    {
        return true;
    }

    /**
     * Default action contract for controllers.
     */
    abstract public function index(Request $request): Response;

    /**
     * Render a view with optional data; infers the view name when omitted.
     */
    protected function html(array $data = [], string $viewName = null): ViewResponse
    {
        if ($viewName == null) {
            $viewName = $this->app->getRouter()->getControllerName() . DIRECTORY_SEPARATOR .
                $this->app->getRouter()->getAction();
        } else {
            $viewName = is_string($viewName) ?
                ($this->app->getRouter()->getControllerName() . DIRECTORY_SEPARATOR . $viewName) :
                ($viewName['0'] . DIRECTORY_SEPARATOR . $viewName['1']);
        }
        return new ViewResponse($this->app, $viewName, $data);
    }

    /**
     * Return JSON data.
     */
    protected function json(mixed $data): JsonResponse
    {
        return new JsonResponse($data);
    }

    /**
     * Redirect to a given URL.
     */
    protected function redirect(string $redirectUrl): RedirectResponse
    {
        return new RedirectResponse($redirectUrl);
    }

    /**
     * Build a route URL, optionally absolute and with merged query params.
     *
     * @throws Exception
     */
    protected function url(
        string|array $destination,
        array $parameters = [],
        bool $absolute = false,
        bool $appendParameters = false
    ): string {
        return $this->app->getLinkGenerator()->url($destination, $parameters, $absolute, $appendParameters);
    }

    /**
     * True when current user has an identity.
     */
    protected function requireLogin(): bool
    {
        return $this->user?->isLoggedIn() ?? false;
    }

    /**
     * Ensure the user has one of the allowed roles.
     */
    protected function requireRole(array $roles): bool
    {
        if (!$this->requireLogin()) {
            return false;
        }

        $currentRole = $this->user->getRole();
        if ($currentRole === null) {
            return false;
        }

        return in_array($currentRole, $roles, true);
    }
}
