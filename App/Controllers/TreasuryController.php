<?php
// AI-GENERATED: Protect treasury actions with role guard (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use App\Services\TreasuryService;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\JsonResponse;
use Framework\Http\Responses\Response;
use Framework\Http\Session;

class TreasuryController extends BaseController
{
    private ?TreasuryService $service = null;
    private ?Session $flashSession = null;

    public function authorize(Request $request, string $action): bool
    {
        // Coarse controller-level guard only; service enforces record-level permissions.
        // Enforce login for ALL actions.
        return $this->requireLogin();
    }

    public function index(Request $request): Response
    {
        $result = $this->svc()->index($this->userContext());

        $data = $result['payload'] ?? [];
        $data['successMessage'] = $this->consumeFlash('treasury.success');
        $data['errorMessage'] = $this->consumeFlash('treasury.error');

        return $this->html($data, 'index');
    }

    public function new(Request $request): Response
    {
        $result = $this->svc()->newForm($this->userContext(), [
            'type' => $request->get('type'),
        ]);

        return $this->html($result['payload'] ?? [], 'new');
    }

    public function store(Request $request): Response
    {
        $result = $this->svc()->store($this->userContext(), [
            'type' => $request->post('type'),
            'amountRaw' => $request->post('amount'),
            'description' => $request->post('description'),
        ]);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            $data = $result['payload'] ?? [];
            $data['errors'] = $result['errors'] ?? [];
            return $this->html($data, 'new');
        }

        return $this->redirect($this->url('Treasury.index'));
    }

    public function edit(Request $request): Response
    {
        $id = (int)($request->get('id') ?? 0);
        $result = $this->svc()->editForm($this->userContext(), $id);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            return $this->redirect($this->url('Treasury.index'));
        }

        $data = $result['payload'] ?? [];
        $data['errors'] = $result['errors'] ?? ($data['errors'] ?? []);

        return $this->html($data, 'edit');
    }

    public function update(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        $result = $this->svc()->update($this->userContext(), $id, [
            'type' => $request->post('type'),
            'amountRaw' => $request->post('amount'),
            'description' => $request->post('description'),
            'status' => $request->post('status'),
        ]);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        if (!($result['ok'] ?? false)) {
            $data = $result['payload'] ?? [];
            $data['errors'] = $result['errors'] ?? [];
            return $this->html($data, 'edit');
        }

        return $this->redirect($this->url('Treasury.index'));
    }

    public function delete(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        $result = $this->svc()->delete($this->userContext(), $id);

        if (!empty($result['flash'])) {
            $this->writeFlashFromDomainResult($result['flash']);
        }

        return $this->redirect($this->url('Treasury.index'));
    }

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->svc()->refresh($this->userContext());

        $payload = $result['payload'] ?? [];
        $response = $this->json($payload);
        if (isset($result['httpStatus'])) {
            $response->setStatusCode((int)$result['httpStatus']);
        }

        return $response;
    }

    public function setStatusJson(Request $request): JsonResponse
    {
        $id = $this->extractTransactionId($request);
        if ($id <= 0) {
            return $this->json(['ok' => false, 'message' => 'Invalid transaction id.'])->setStatusCode(400);
        }

        $status = (string)($request->post('status') ?? '');

        $result = $this->svc()->setStatus($this->userContext(), $id, $status);

        $payload = $result['payload'] ?? [];
        if (!($result['ok'] ?? false) && !empty($result['errors']) && is_array($result['errors'])) {
            // Keep existing JS client contract.
            $payload['fields'] = $result['errors'];
        }

        $response = $this->json($payload);
        if (isset($result['httpStatus'])) {
            $response->setStatusCode((int)$result['httpStatus']);
        }

        return $response;
    }

    private function svc(): TreasuryService
    {
        if ($this->service === null) {
            $this->service = new TreasuryService();
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
            $this->flash('treasury.success', $message);
            return;
        }

        $this->flash('treasury.error', $message);
    }

    private function extractTransactionId(Request $request): int
    {
        // 1) Prefer regular framework inputs (query/body)
        $id = (int)($request->value('id') ?? 0);
        if ($id > 0) {
            return $id;
        }

        // 2) Fallback: pretty route param in /treasury/status/{id}
        // Request doesn't currently expose route params, so parse URI as a last resort.
        $uri = (string)($request->server('REQUEST_URI') ?? '');
        if (preg_match('#/treasury/status/(\d+)#', $uri, $m)) {
            return (int)$m[1];
        }

        return 0;
    }
}
