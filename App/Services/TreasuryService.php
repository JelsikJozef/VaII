<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NewsRepository;
use App\Repositories\TransactionRepository;

/**
 * Treasury business logic (validation, permissions, workflows).
 *
 * HTTP-agnostic: returns DomainResult only.
 */
class TreasuryService
{
    public function __construct(
        private readonly TransactionRepository $transactions = new TransactionRepository(),
        private readonly NewsRepository $news = new NewsRepository(),
    ) {
    }

    /**
     * DomainResult shape:
     * - ok: bool
     * - payload: array
     * - errors?: array<string, array<int,string>>
     * - flash?: array{type:string,message:string}
     * - httpStatus?: int (only for JSON endpoints)
     */

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @return array{ok:bool,payload:array,errors?:array,flash?:array}
     */
    public function index(array $user): array
    {
        $transactions = $this->transactions->findAll();
        $balance = $this->transactions->getBalance();
        $pendingTotal = $this->transactions->getPendingTotal();

        $role = isset($user['role']) ? (string)$user['role'] : null;
        $userId = isset($user['userId']) ? $user['userId'] : null;
        $isModerator = in_array($role, ['treasurer', 'admin'], true);

        $transactionsWithFlags = array_map(
            fn(array $tx): array => $this->withIndexFlags($tx, $isModerator, $userId),
            is_array($transactions) ? $transactions : []
        );

        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'treasury',
                'transactions' => $transactionsWithFlags,
                'currentBalance' => $balance,
                'pendingBalance' => $pendingTotal,
            ],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @param array{type?:string|null} $query
     * @return array{ok:bool,payload:array,errors?:array,flash?:array}
     */
    public function newForm(array $user, array $query): array
    {
        $typeParam = (string)($query['type'] ?? '');
        $defaultType = '';
        if ($typeParam === 'deposit') {
            $defaultType = 'deposit';
        } elseif ($typeParam === 'withdrawal') {
            $defaultType = 'withdrawal';
        }

        $balance = $this->transactions->getBalance();

        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'treasury',
                'errors' => [],
                'type' => $defaultType,
                'amount' => '',
                'description' => '',
                'currentBalance' => $balance,
            ],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @param array{type?:string|null,amountRaw?:string|null,description?:string|null} $input
     * @return array{ok:bool,payload:array,errors?:array,flash?:array}
     */
    public function store(array $user, array $input): array
    {
        $type = trim((string)($input['type'] ?? ''));
        $amountRaw = (string)($input['amountRaw'] ?? '');
        $description = trim((string)($input['description'] ?? ''));

        $balance = $this->transactions->getBalance();
        $errors = $this->validateTransactionInput($type, $amountRaw, $description, $balance);

        if (!empty($errors)) {
            return [
                'ok' => false,
                'payload' => [
                    'activeModule' => 'treasury',
                    'type' => $type,
                    'amount' => $amountRaw,
                    'description' => $description,
                    'currentBalance' => $balance,
                ],
                'errors' => $errors,
            ];
        }

        $amount = (float)$amountRaw;
        $cashboxId = $this->transactions->ensureDefaultCashboxId();
        $createdBy = $user['userId'] ?? null;
        $txId = $this->transactions->create($cashboxId, $type, $amount, $description, 'pending', $createdBy, null);

        $this->news->log('transaction.created', 'New transaction submitted', [
            'id' => $txId,
            'type' => $type,
            'amount' => $amount,
            'user' => $createdBy,
        ]);

        return [
            'ok' => true,
            'payload' => ['id' => $txId],
            'flash' => ['type' => 'success', 'message' => 'Transaction successfully registered.'],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @return array{ok:bool,payload:array,errors?:array,flash?:array}
     */
    public function editForm(array $user, int $id): array
    {
        if ($id <= 0) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Transaction not found.'],
            ];
        }

        $transaction = $this->transactions->findById($id);
        if ($transaction === null) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Transaction not found.'],
            ];
        }

        $userId = $user['userId'] ?? null;
        $role = isset($user['role']) ? (string)$user['role'] : null;
        $isModerator = in_array($role, ['treasurer', 'admin'], true);

        $ownerId = (int)($transaction['created_by'] ?? 0);
        $isOwnerPending = ($ownerId > 0 && $userId !== null && (int)$userId === $ownerId && strtolower((string)($transaction['status'] ?? '')) === 'pending');

        if (!$isModerator && !$isOwnerPending) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'You cannot edit this transaction.'],
            ];
        }

        $balance = $this->transactions->getBalance();

        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'treasury',
                'errors' => [],
                'transactionId' => $id,
                'type' => (string)($transaction['type'] ?? ''),
                'amount' => (string)($transaction['amount'] ?? ''),
                'description' => (string)($transaction['description'] ?? ''),
                'status' => (string)($transaction['status'] ?? 'pending'),
                'currentBalance' => $balance,

                // View-only flags (no role/ownership logic in the view).
                'canEditType' => $isModerator,
                'canEditStatus' => $isModerator,
            ],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @param array{type?:string|null,amountRaw?:string|null,description?:string|null,status?:string|null} $input
     * @return array{ok:bool,payload:array,errors?:array,flash?:array}
     */
    public function update(array $user, int $id, array $input): array
    {
        if ($id <= 0) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Transaction not found.'],
            ];
        }

        $transaction = $this->transactions->findById($id);
        if ($transaction === null) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Transaction not found.'],
            ];
        }

        $userId = $user['userId'] ?? null;
        $role = isset($user['role']) ? (string)$user['role'] : null;
        $isModerator = in_array($role, ['treasurer', 'admin'], true);

        $ownerId = (int)($transaction['created_by'] ?? 0);
        $isOwnerPending = ($ownerId > 0 && $userId !== null && (int)$userId === $ownerId && strtolower((string)($transaction['status'] ?? '')) === 'pending');

        if (!$isModerator && !$isOwnerPending) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'You cannot edit this transaction.'],
            ];
        }

        $type = trim((string)($input['type'] ?? ''));
        $amountRaw = (string)($input['amountRaw'] ?? '');
        $description = trim((string)($input['description'] ?? ''));
        $status = trim((string)($input['status'] ?? 'pending'));

        if (!$isModerator) {
            $type = (string)($transaction['type'] ?? 'deposit');
            $status = (string)($transaction['status'] ?? 'pending');
        }

        $balance = $this->transactions->getBalance();
        $errors = $this->validateTransactionInput($type, $amountRaw, $description, $balance, $transaction);

        if ($isModerator) {
            if ($status === '') {
                $errors['status'][] = 'Status is required.';
            } elseif (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
                $errors['status'][] = 'Status must be pending, approved, or rejected.';
            }
        }

        if (!empty($errors)) {
            return [
                'ok' => false,
                'payload' => [
                    'activeModule' => 'treasury',
                    'transactionId' => $id,
                    'type' => $type,
                    'amount' => $amountRaw,
                    'description' => $description,
                    'status' => $status,
                    'currentBalance' => $balance,

                    // View-only flags (no role/ownership logic in the view).
                    'canEditType' => $isModerator,
                    'canEditStatus' => $isModerator,
                ],
                'errors' => $errors,
            ];
        }

        $amount = (float)$amountRaw;
        $this->transactions->update($id, $type, $amount, $description, $status);

        $this->news->log('transaction.updated', 'Transaction updated', [
            'id' => $id,
            'status' => $status,
            'user' => $userId,
        ]);

        return [
            'ok' => true,
            'payload' => ['id' => $id],
            'flash' => ['type' => 'success', 'message' => 'Transaction updated'],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @return array{ok:bool,payload:array,errors?:array,flash?:array}
     */
    public function delete(array $user, int $id): array
    {
        if ($id <= 0) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Transaction not found.'],
            ];
        }

        $transaction = $this->transactions->findById($id);
        if ($transaction === null) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Transaction not found.'],
            ];
        }

        $userId = $user['userId'] ?? null;
        $role = isset($user['role']) ? (string)$user['role'] : null;

        $isModerator = in_array($role, ['admin', 'treasurer'], true);
        if (!$isModerator) {
            $ownerId = (int)($transaction['created_by'] ?? 0);
            $status = strtolower((string)($transaction['status'] ?? ''));
            $isOwnerPending = ($ownerId > 0 && $userId !== null && (int)$userId === $ownerId && $status === 'pending');
            if (!$isOwnerPending) {
                return [
                    'ok' => false,
                    'payload' => [],
                    'flash' => ['type' => 'error', 'message' => 'You cannot delete this transaction.'],
                ];
            }
        }

        $this->transactions->delete($id);
        $this->news->log('transaction.deleted', 'Transaction deleted', [
            'id' => $id,
            'user' => $userId,
        ]);

        return [
            'ok' => true,
            'payload' => ['id' => $id],
            'flash' => ['type' => 'success', 'message' => 'Transaction deleted'],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @return array{ok:bool,payload:array}
     */
    public function refresh(array $user): array
    {
        $transactions = $this->transactions->findAll();
        $balance = $this->transactions->getBalance();

        return [
            'ok' => true,
            'payload' => [
                'transactions' => $transactions,
                'balance' => $balance,
            ],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @return array{ok:bool,payload:array,errors?:array,httpStatus?:int}
     */
    public function setStatus(array $user, int $id, string $status): array
    {
        $role = isset($user['role']) ? (string)$user['role'] : null;
        if (!in_array($role, ['treasurer', 'admin'], true)) {
            return $this->jsonError('Forbidden', 403);
        }

        if ($id <= 0) {
            return $this->jsonError('Invalid transaction id.', 400);
        }

        $transaction = $this->transactions->findById($id);
        if ($transaction === null) {
            return $this->jsonError('Transaction not found.', 404);
        }

        $status = trim($status);
        if ($status === '') {
            return $this->jsonError('Status is required.', 400, ['status' => ['Status is required.']]);
        }
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return $this->jsonError('Status must be approved or rejected.', 400, ['status' => ['Status must be approved or rejected.']]);
        }
        if (($transaction['status'] ?? '') !== 'pending') {
            return $this->jsonError('Only pending transactions can be updated.', 400, ['status' => ['Only pending transactions can be updated.']]);
        }

        $approverId = $user['userId'] ?? null;
        $this->transactions->setStatus($id, $status, $approverId);

        $this->news->log('transaction.status', 'Transaction status updated', [
            'id' => $id,
            'status' => $status,
            'user' => $approverId,
        ]);

        return [
            'ok' => true,
            'payload' => [
                'ok' => true,
                'id' => $id,
                'status' => $status,
                'balance' => $this->transactions->getBalance(),
                'pending' => $this->transactions->getPendingTotal(),
            ],
        ];
    }

    /**
     * @return array{ok:bool,payload:array,errors:array,httpStatus:int}
     */
    private function jsonError(string $message, int $statusCode, array $fields = []): array
    {
        $payload = ['ok' => false, 'message' => $message];

        return [
            'ok' => false,
            'payload' => $payload,
            'errors' => $fields,
            'httpStatus' => $statusCode,
        ];
    }

    private function withIndexFlags(array $tx, bool $isModerator, ?int $userId): array
    {
        $ownerId = (int)($tx['created_by'] ?? 0);
        $status = strtolower((string)($tx['status'] ?? ''));
        $isOwnerPending = ($ownerId > 0 && $userId !== null && (int)$userId === $ownerId && $status === 'pending');

        $tx['canEdit'] = $isModerator || $isOwnerPending;
        $tx['canDelete'] = $isModerator || $isOwnerPending;
        $tx['canApproveReject'] = $isModerator && $status === 'pending';

        return $tx;
    }

    /**
     * @return array<string, array<int, string>>
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
}
