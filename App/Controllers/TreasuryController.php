<?php

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\JsonResponse;
use Framework\Http\Session;
use App\Repositories\TransactionRepository;

class TreasuryController extends BaseController
{
    private ?TransactionRepository $repository = null;

    /** @var Session|null */
    private ?Session $flashSession = null;

    public function authorize(Request $request, string $action): bool
    {
        return true;
    }

    public function index(Request $request): Response
    {
        $transactions = $this->repo()->findAll();
        $balance = $this->repo()->getBalance();

        return $this->html([
            'activeModule' => 'treasury',
            'transactions' => $transactions,
            'currentBalance' => $balance,
            'successMessage' => $this->consumeFlash('treasury.success'),
            'errorMessage' => $this->consumeFlash('treasury.error'),
        ]);
    }

    public function new(Request $request): Response
    {
        $typeParam = (string)($request->get('type') ?? '');
        $defaultType = '';
        if ($typeParam === 'deposit') {
            $defaultType = 'deposit';
        } elseif ($typeParam === 'withdrawal') {
            $defaultType = 'withdrawal';
        }

        $balance = $this->repo()->getBalance();

        $data = [
            'activeModule' => 'treasury',
            'errors' => [],
            'type' => $defaultType,
            'amount' => '',
            'description' => '',
            'currentBalance' => $balance,
        ];

        return $this->html($data);
    }

    public function store(Request $request): Response
    {
        $type = trim((string)($request->post('type') ?? ''));
        $amountRaw = (string)($request->post('amount') ?? '');
        $description = trim((string)($request->post('description') ?? ''));

        $balance = $this->repo()->getBalance();

        $errors = $this->validateTransactionInput($type, $amountRaw, $description, $balance);

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'treasury',
                'errors' => $errors,
                'type' => $type,
                'amount' => $amountRaw,
                'description' => $description,
                'currentBalance' => $balance,
            ], 'new');
        }

        $amount = (float)$amountRaw;
        $proposedBy = $this->user?->getName();
        $this->repo()->create($type, $amount, $description, 'pending', $proposedBy);

        $this->flash('treasury.success', 'Transaction successfully registered.');

        return $this->redirect($this->url('Treasury.index'));
    }

    public function refresh(Request $request): JsonResponse
    {
        $transactions = $this->repo()->findAll();
        $balance = $this->repo()->getBalance();

        return $this->json([
            'transactions' => $transactions,
            'balance' => $balance,
        ]);
    }

    public function edit(Request $request): Response
    {
        $id = (int)($request->get('id') ?? 0);
        if ($id <= 0) {
            $this->flash('treasury.error', 'Transaction not found.');
            return $this->redirect($this->url('Treasury.index'));
        }

        $transaction = $this->repo()->findById($id);
        if ($transaction === null) {
            $this->flash('treasury.error', 'Transaction not found.');
            return $this->redirect($this->url('Treasury.index'));
        }

        $balance = $this->repo()->getBalance();

        return $this->html([
            'activeModule' => 'treasury',
            'errors' => [],
            'transactionId' => $id,
            'type' => (string)($transaction['type'] ?? ''),
            'amount' => (string)($transaction['amount'] ?? ''),
            'description' => (string)($transaction['description'] ?? ''),
            'status' => (string)($transaction['status'] ?? 'pending'),
            'currentBalance' => $balance,
        ], 'edit');
    }

    public function update(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        if ($id <= 0) {
            $this->flash('treasury.error', 'Transaction not found.');
            return $this->redirect($this->url('Treasury.index'));
        }

        $transaction = $this->repo()->findById($id);
        if ($transaction === null) {
            $this->flash('treasury.error', 'Transaction not found.');
            return $this->redirect($this->url('Treasury.index'));
        }

        $type = trim((string)($request->post('type') ?? ''));
        $amountRaw = (string)($request->post('amount') ?? '');
        $description = trim((string)($request->post('description') ?? ''));
        $status = trim((string)($request->post('status') ?? 'pending'));

        $balance = $this->repo()->getBalance();

        $errors = $this->validateTransactionInput($type, $amountRaw, $description, $balance, $transaction);

        if ($status === '') {
            $errors['status'][] = 'Status is required.';
        } elseif (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $errors['status'][] = 'Status must be pending, approved, or rejected.';
        }

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'treasury',
                'errors' => $errors,
                'transactionId' => $id,
                'type' => $type,
                'amount' => $amountRaw,
                'description' => $description,
                'status' => $status,
                'currentBalance' => $balance,
            ], 'edit');
        }

        $amount = (float)$amountRaw;
        $this->repo()->update($id, $type, $amount, $description, $status);

        $this->flash('treasury.success', 'Transaction updated');

        return $this->redirect($this->url('Treasury.index'));
    }

    public function delete(Request $request): Response
    {
        $id = (int)($request->get('id') ?? $request->post('id') ?? 0);
        if ($id <= 0) {
            $this->flash('treasury.error', 'Transaction not found.');
            return $this->redirect($this->url('Treasury.index'));
        }

        $transaction = $this->repo()->findById($id);
        if ($transaction === null) {
            $this->flash('treasury.error', 'Transaction not found.');
            return $this->redirect($this->url('Treasury.index'));
        }

        $this->repo()->delete($id);
        $this->flash('treasury.success', 'Transaction deleted');

        return $this->redirect($this->url('Treasury.index'));
    }

    /**
     * Reuse validation rules for store/update operations.
     */
    private function validateTransactionInput(
        string $type,
        string $amountRaw,
        string $description,
        float $balance,
        ?array $existingTransaction = null
    ): array {
        $errors = [];

        if ($type === '') {
            $errors['type'][] = 'Type is required.';
        } elseif (!in_array($type, ['deposit', 'withdrawal'], true)) {
            $errors['type'][] = 'Type must be deposit or withdrawal.';
        }

        if ($amountRaw === '') {
            $errors['amount'][] = 'Amount is required.';
        } elseif (!is_numeric($amountRaw)) {
            $errors['amount'][] = 'Amount must be a number.';
        } else {
            $amount = (float)$amountRaw;
            if ($amount <= 0) {
                $errors['amount'][] = 'Amount must be greater than 0.';
            } elseif ($type === 'withdrawal') {
                $available = $balance;
                if ($existingTransaction !== null) {
                    $originalType = (string)($existingTransaction['type'] ?? 'deposit');
                    $originalAmount = (float)($existingTransaction['amount'] ?? 0);
                    if ($originalType === 'withdrawal') {
                        $available += $originalAmount;
                    } else {
                        $available -= $originalAmount;
                    }
                }

                if ($amount > $available) {
                    $errors['amount'][] = 'Withdrawal cannot exceed current balance.';
                }
            }
        }

        if ($description === '') {
            $errors['description'][] = 'Description is required.';
        } elseif (mb_strlen($description) > 255) {
            $errors['description'][] = 'Description must be at most 255 characters.';
        }

        return $errors;
    }

    private function repo(): TransactionRepository
    {
        if ($this->repository === null) {
            $this->repository = new TransactionRepository();
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
