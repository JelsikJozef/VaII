<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use App\Repositories\ActivityRepository;
use App\Repositories\NewsRepository;

/**
 * Class HomeController
 * Handles actions related to the home page and other public actions.
 *
 * This controller includes actions that are accessible to all users, including a default landing page and a contact
 * page. It provides a mechanism for authorizing actions based on user permissions.
 *
 * @package App\Controllers
 */
class HomeController extends BaseController
{
    /**
     * Authorizes controller actions based on the specified action name.
     *
     * In this implementation, all actions are authorized unconditionally.
     *
     * @param string $action The action name to authorize.
     * @return bool Returns true, allowing all actions.
     */
    public function authorize(Request $request, string $action): bool
    {
        return true;
    }

    /**
     * Displays the default home page.
     *
     * This action serves the main HTML view of the home page.
     *
     * @return Response The response object containing the rendered HTML for the home page.
     */
    public function index(Request $request): Response
    {
        $activities = [];
        try {
            $activities = (new ActivityRepository())->latest(10);
        } catch (\Throwable) {
            $activities = [];
        }

        if (empty($activities)) {
            $legacy = (new NewsRepository())->latest(10);
            $activities = array_map(static function (array $item): array {
                $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];

                $actorName = $meta['actor_name'] ?? $meta['name'] ?? null;
                $actorEmail = $meta['actor_email'] ?? $meta['email'] ?? null;
                $detailsParts = [];
                foreach ($meta as $key => $value) {
                    if (in_array($key, ['actor_name', 'name', 'actor_email', 'email'], true)) {
                        continue;
                    }
                    if (is_scalar($value)) {
                        $detailsParts[] = $key . ': ' . (string)$value;
                    }
                }
                $details = $detailsParts !== [] ? implode(', ', $detailsParts) : null;

                return [
                    'title' => (string)($item['message'] ?? ''),
                    'details' => $details,
                    'action' => (string)($item['type'] ?? 'activity'),
                    'actor_name' => $actorName,
                    'actor_email' => $actorEmail,
                    'created_at' => (string)($item['ts'] ?? ''),
                ];
            }, $legacy);
        }

        return $this->html([
            'activeModule' => 'home',
            'activities' => $activities,
        ]);
    }

    /**
     * Displays the contact page.
     *
     * This action serves the HTML view for the contact page, which is accessible to all users without any
     * authorization.
     *
     * @return Response The response object containing the rendered HTML for the contact page.
     */
    public function contact(Request $request): Response
    {
        return $this->html([
            'activeModule' => 'home',
        ]);
    }
}
