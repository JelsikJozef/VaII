<?php
// AI-GENERATED: ESNcards CRUD controller with role guards (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use App\Repositories\EsncardRepository;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Session;

class EsncardsController extends BaseController
{
    private ?EsncardRepository $repository = null;
    private ?Session $flashSession = null;

    public function authorize(Request $request, string $action): bool
    {
        $action = strtolower($action);
        if ($action === 'index') {
            return $this->requireLogin();
        }

        return $this->requireRole(['treasurer', 'admin']);
    }

    public function index(Request $request): Response
    {
        $search = trim((string)($request->get('q') ?? ''));
        $status = trim((string)($request->get('status') ?? ''));
        $statusFilter = in_array($status, ['available', 'assigned', 'inactive'], true) ? $status : '';

        $cards = $this->repo()->findAll(
            $search !== '' ? $search : null,
            $statusFilter !== '' ? $statusFilter : null
        );

        return $this->html([
            'activeModule' => 'esncards',
            'esncards' => $cards,
            'search' => $search,
            'status' => $statusFilter,
            'canManage' => $this->requireRole(['treasurer', 'admin']),
            'successMessage' => $this->consumeFlash('esncards.success'),
            'errorMessage' => $this->consumeFlash('esncards.error'),
        ]);
    }

    public function new(Request $request): Response
    {
        return $this->html([
            'activeModule' => 'esncards',
            'errors' => [],
            'card_number' => '',
            'status' => 'available',
            'assigned_to_name' => '',
            'assigned_to_email' => '',
            'assigned_at' => '',
        ], 'new');
    }

    public function store(Request $request): Response
    {
        $input = $this->collectInput($request);
        [$errors, $normalized] = $this->validateInput($input, null);

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'esncards',
                'errors' => $errors,
                'card_number' => $input['card_number'],
                'status' => $input['status'],
                'assigned_to_name' => $input['assigned_to_name'],
                'assigned_to_email' => $input['assigned_to_email'],
                'assigned_at' => $input['assigned_at'],
            ], 'new');
        }

        $this->repo()->create($normalized);
        $this->flash('esncards.success', 'Card created successfully.');

        return $this->redirect($this->url('Esncards.index'));
    }

    public function edit(Request $request): Response
    {
        $id = (int)($request->get('id') ?? 0);
        if ($id <= 0) {
            $this->flash('esncards.error', 'Card not found.');
            return $this->redirect($this->url('Esncards.index'));
        }

        $card = $this->repo()->findById($id);
        if ($card === null) {
            $this->flash('esncards.error', 'Card not found.');
            return $this->redirect($this->url('Esncards.index'));
        }

        return $this->html([
            'activeModule' => 'esncards',
            'errors' => [],
            'id' => $id,
            'card_number' => (string)($card['card_number'] ?? ''),
            'status' => (string)($card['status'] ?? 'available'),
            'assigned_to_name' => (string)($card['assigned_to_name'] ?? ''),
            'assigned_to_email' => (string)($card['assigned_to_email'] ?? ''),
            'assigned_at' => $this->formatDateTimeLocal($card['assigned_at'] ?? null),
        ], 'edit');
    }

    public function update(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        if ($id <= 0) {
            $this->flash('esncards.error', 'Card not found.');
            return $this->redirect($this->url('Esncards.index'));
        }

        $existing = $this->repo()->findById($id);
        if ($existing === null) {
            $this->flash('esncards.error', 'Card not found.');
            return $this->redirect($this->url('Esncards.index'));
        }

        $input = $this->collectInput($request);
        [$errors, $normalized] = $this->validateInput($input, $id);

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'esncards',
                'errors' => $errors,
                'id' => $id,
                'card_number' => $input['card_number'],
                'status' => $input['status'],
                'assigned_to_name' => $input['assigned_to_name'],
                'assigned_to_email' => $input['assigned_to_email'],
                'assigned_at' => $input['assigned_at'],
            ], 'edit');
        }

        $this->repo()->update($id, $normalized);
        $this->flash('esncards.success', 'Card updated successfully.');

        return $this->redirect($this->url('Esncards.index'));
    }

    public function delete(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        if ($id <= 0) {
            $this->flash('esncards.error', 'Card not found.');
            return $this->redirect($this->url('Esncards.index'));
        }

        $existing = $this->repo()->findById($id);
        if ($existing === null) {
            $this->flash('esncards.error', 'Card not found.');
            return $this->redirect($this->url('Esncards.index'));
        }

        $this->repo()->delete($id);
        $this->flash('esncards.success', 'Card deleted.');

        return $this->redirect($this->url('Esncards.index'));
    }

    private function collectInput(Request $request): array
    {
        return [
            'card_number' => trim((string)($request->post('card_number') ?? '')),
            'status' => trim((string)($request->post('status') ?? '')),
            'assigned_to_name' => trim((string)($request->post('assigned_to_name') ?? '')),
            'assigned_to_email' => trim((string)($request->post('assigned_to_email') ?? '')),
            'assigned_at' => trim((string)($request->post('assigned_at') ?? '')),
        ];
    }

    /**
     * @return array{0: array, 1: array}
     */
    private function validateInput(array $input, ?int $id): array
    {
        $errors = [];

        $cardNumber = $input['card_number'];
        $status = $input['status'];
        $assignedName = $input['assigned_to_name'];
        $assignedEmail = $input['assigned_to_email'];
        $assignedAtRaw = $input['assigned_at'];

        if ($cardNumber === '') {
            $errors['card_number'][] = 'Card number is required.';
        } elseif (mb_strlen($cardNumber) > 50) {
            $errors['card_number'][] = 'Card number must be at most 50 characters.';
        } elseif ($this->repo()->existsByCardNumber($cardNumber, $id)) {
            $errors['card_number'][] = 'Card number must be unique.';
        }

        if ($status === '') {
            $errors['status'][] = 'Status is required.';
        } elseif (!in_array($status, ['available', 'assigned', 'inactive'], true)) {
            $errors['status'][] = 'Status must be available, assigned, or inactive.';
        }

        $assignedAt = null;
        $normalizedName = null;
        $normalizedEmail = null;

        if ($status === 'assigned') {
            if ($assignedName === '') {
                $errors['assigned_to_name'][] = 'Assignee name is required when the card is assigned.';
            } else {
                $normalizedName = $assignedName;
            }

            if ($assignedEmail === '') {
                $errors['assigned_to_email'][] = 'Assignee email is required when the card is assigned.';
            } elseif (!filter_var($assignedEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['assigned_to_email'][] = 'Provide a valid email address.';
            } else {
                $normalizedEmail = $assignedEmail;
            }

            if ($assignedAtRaw !== '') {
                $timestamp = strtotime($assignedAtRaw);
                if ($timestamp === false) {
                    $errors['assigned_at'][] = 'Assigned at must be a valid date.';
                } else {
                    $assignedAt = date('Y-m-d 00:00:00', $timestamp);
                }
            } else {
                $assignedAt = null; // Date optional per request; stored as NULL when not provided
            }
        }

        $normalized = [
            'card_number' => $cardNumber,
            'status' => $status,
            'assigned_to_name' => $status === 'assigned' ? $normalizedName : null,
            'assigned_to_email' => $status === 'assigned' ? $normalizedEmail : null,
            'assigned_at' => $status === 'assigned' ? $assignedAt : null,
        ];

        return [$errors, $normalized];
    }

    private function formatDateTimeLocal(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return (new \DateTime($value))->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    private function repo(): EsncardRepository
    {
        if ($this->repository === null) {
            $this->repository = new EsncardRepository();
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
