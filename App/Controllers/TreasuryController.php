<?php

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
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

        $errors = [];
        $balance = $this->repo()->getBalance();

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
            } elseif ($type === 'withdrawal' && $amount > $balance) {
                $errors['amount'][] = 'Withdrawal cannot exceed current balance.';
            }
        }

        if ($description === '') {
            $errors['description'][] = 'Description is required.';
        } elseif (mb_strlen($description) > 255) {
            $errors['description'][] = 'Description must be at most 255 characters.';
        }

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'treasury',
                'errors' => $errors,
                'type' => $type,
                'amount' => $amountRaw,
                'description' => $description,
                'currentBalance' => $balance,
            ], 'Treasury/new');
        }

        $amount = (float)$amountRaw;
        $proposedBy = $this->user?->getName();
        $this->repo()->create($type, $amount, $description, 'pending', $proposedBy);

        $this->flash('treasury.success', 'Transaction successfully registered.');

        return $this->redirect($this->url('Treasury.index'));
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
