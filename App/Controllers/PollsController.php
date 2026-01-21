<?php
// AI-GENERATED: Polls CRUD and voting controller (GitHub Copilot / ChatGPT), 2026-01-19

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use App\Services\PollsService;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Session;

/**
 * Polls controller (HTTP only).
 *
 * Rules:
 * - Controller only reads HTTP input, enforces coarse login, calls exactly one service method, and returns a Response.
 * - PollsService enforces all business rules and role/record-level permissions.
 * - Flash messages are written by controller based on service DomainResult.
 */
class PollsController extends BaseController
{
    private ?PollsService $service = null;
    private ?Session $flashSession = null;

    public function authorize(Request $request, string $action): bool
    {
        // Coarse controller-level guard only. Service enforces role/record-level permissions.
        return $this->requireLogin();
    }

    public function index(Request $request): Response
    {
        $result = $this->svc()->index($this->userContext());

        $data = $result['payload'] ?? [];
        $data['successMessage'] = $this->consumeFlash('polls.success');
        $data['errorMessage'] = $this->consumeFlash('polls.error');

        return $this->html($data);
    }

    public function show(Request $request): Response
    {
        $id = (int)($request->get('id') ?? 0);
        $result = $this->svc()->show($this->userContext(), $id);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            return $this->redirect($this->url('Polls.index'));
        }

        $data = $result['payload'] ?? [];
        $data['successMessage'] = $this->consumeFlash('polls.success');
        $data['errorMessage'] = $this->consumeFlash('polls.error');

        return $this->html($data, 'show');
    }

    public function new(Request $request): Response
    {
        $result = $this->svc()->newForm($this->userContext());

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            return $this->redirect($this->url('Polls.index'));
        }

        return $this->html($result['payload'] ?? [], 'new');
    }

    public function store(Request $request): Response
    {
        $result = $this->svc()->store($this->userContext(), [
            'question' => $request->post('question'),
            'status' => $request->post('status'),
            'options' => $request->post('options'),
        ]);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            $data = $result['payload'] ?? [];
            $data['errors'] = $result['errors'] ?? [];
            return $this->html($data, 'new');
        }

        $pollId = (int)(($result['payload']['id'] ?? 0));
        if ($pollId > 0) {
            return $this->redirect($this->url('Polls.show', ['id' => $pollId]));
        }

        return $this->redirect($this->url('Polls.index'));
    }

    public function vote(Request $request): Response
    {
        $pollIdRequest = (int)($request->get('id') ?? $request->post('id') ?? 0);
        $optionId = (int)($request->post('option_id') ?? 0);

        $result = $this->svc()->vote($this->userContext(), [
            'pollId' => $pollIdRequest,
            'optionId' => $optionId,
        ]);

        $canonicalId = (int)($result['payload']['pollId'] ?? $pollIdRequest);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            if (!empty($result['errors'])) {
                $data = $result['payload'] ?? [];
                $data['errors'] = $result['errors'] ?? [];
                return $this->html($data, 'show');
            }

            if ($canonicalId > 0) {
                return $this->redirect($this->url('Polls.show', ['id' => $canonicalId]));
            }
            return $this->redirect($this->url('Polls.index'));
        }

        return $this->redirect($this->url('Polls.show', ['id' => $canonicalId]));
    }

    public function delete(Request $request): Response
    {
        $pollId = (int)($request->get('id') ?? $request->post('id') ?? 0);
        $result = $this->svc()->delete($this->userContext(), $pollId);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        return $this->redirect($this->url('Polls.index'));
    }

    private function svc(): PollsService
    {
        if ($this->service === null) {
            $this->service = new PollsService();
        }

        return $this->service;
    }

    /**
     * @return array{userId?:int|null,role?:string|null,isLoggedIn:bool}
     */
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

    /** @param array{type?:string,message?:string} $flash */
    private function writeFlashFromDomainResult(array $flash): void
    {
        $type = (string)($flash['type'] ?? '');
        $message = (string)($flash['message'] ?? '');
        if ($message === '') {
            return;
        }

        if ($type === 'success') {
            $this->flash('polls.success', $message);
            return;
        }

        $this->flash('polls.error', $message);
    }
}
