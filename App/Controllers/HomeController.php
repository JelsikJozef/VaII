<?php
// AI-GENERATED: Home dashboard activity feed update (GitHub Copilot / ChatGPT), 2026-01-20

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

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
        $activities = (new ActivityRepository())->latest(10);

        if (empty($activities)) {
            $fallbackNews = (new NewsRepository())->latest(10);
            $activities = $this->mapNewsToActivities($fallbackNews);
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

    /**
     * @param array<int,array<string,mixed>> $newsItems
     * @return array<int,array<string,mixed>>
     */
    private function mapNewsToActivities(array $newsItems): array
    {
        $mapped = [];
        foreach ($newsItems as $item) {
            $mapped[] = [
                'title' => (string)($item['message'] ?? ''),
                'action' => (string)($item['type'] ?? ''),
                'details' => isset($item['meta']) ? json_encode($item['meta'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'actor_name' => 'System',
                'actor_email' => null,
                'created_at' => (string)($item['ts'] ?? null),
            ];
        }
        return $mapped;
    }
}
