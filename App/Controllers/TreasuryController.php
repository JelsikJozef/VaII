<?php
// AI-GENERATED: Protect treasury actions with role guard (GitHub Copilot / ChatGPT), 2026-01-18

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\JsonResponse;
use Framework\Http\Session;
use App\Repositories\TransactionRepository;

/**
 * Controller responsible for managing treasury transactions.
 *
 * This controller exposes a small CRUD interface for the `transactions` table:
 * - index(): overview of all transactions and current balance
 * - new()/store(): creation of a new transaction
 * - edit()/update(): modification of an existing transaction
 * - delete(): removal of a transaction
 *
 * It also provides a JSON endpoint (refresh()) and small helper methods for
 * validation, repository access and flash message handling.
 */
class TreasuryController extends BaseController
{
    /**
     * Repository instance used to query and mutate the `transactions` table.
     *
     * Lazily created by {@see repo()} on first use and reused for the lifetime
     * of the controller instance.
     */
    private ?TransactionRepository $repository = null;

    /**
     * Session instance used exclusively for storing and reading flash messages.
     */
    private ?Session $flashSession = null;

    /**
     * Checks whether the current user is allowed to execute a given action.
     *
     * At the moment this method allows all actions unconditionally, but it can
     * be extended later to implement role-based or permission-based checks.
     *
     * @param Request $request Incoming HTTP request.
     * @param string  $action  Name of the controller method that will be invoked.
     *
     * @return bool True if access is granted, false otherwise.
     */
    public function authorize(Request $request, string $action): bool
    {
        return $this->requireRole(['treasurer', 'admin']);
    }

    /**
     * Renders the main treasury screen with a list of transactions.
     *
     * The view receives:
     * - all transactions,
     * - the current balance computed from them,
     * - optional success and error flash messages.
     *
     * @param Request $request Incoming HTTP request.
     *
     * @return Response HTML response containing the treasury index page.
     */
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

    /**
     * Displays a blank form for creating a new transaction.
     *
     * The initial transaction type can be pre-selected using the `type` query
     * parameter (`deposit` or `withdrawal`). The current balance is also passed
     * to the view so the user can see available funds when creating a
     * withdrawal.
     *
     * @param Request $request Incoming HTTP request.
     *
     * @return Response HTML response with the new-transaction form.
     */
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

    /**
     * Processes submission of the "new transaction" form.
     *
     * The method:
     * - reads and normalizes form fields (type, amount, description),
     * - validates them against the current balance,
     * - if validation fails, re-renders the form with error messages,
     * - if validation succeeds, inserts a new transaction record and sets a
     *   success flash message before redirecting back to the index page.
     *
     * @param Request $request Incoming HTTP request containing POST data.
     *
     * @return Response Redirect to the treasury index or a re-rendered form with errors.
     */
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
        $cashboxId = $this->repo()->ensureDefaultCashboxId();
        $createdBy = $this->user?->getIdentity()?->getId();
        $this->repo()->create($cashboxId, $type, $amount, $description, 'pending', $createdBy, null);

        $this->flash('treasury.success', 'Transaction successfully registered.');

        return $this->redirect($this->url('Treasury.index'));
    }

    /**
     * Returns the current set of transactions and balance as JSON.
     *
     * Useful for client-side code that periodically refreshes the table of
     * transactions using AJAX.
     *
     * @param Request $request Incoming HTTP request.
     *
     * @return JsonResponse JSON structure with keys `transactions` and `balance`.
     */
    public function refresh(Request $request): JsonResponse
    {
        $transactions = $this->repo()->findAll();
        $balance = $this->repo()->getBalance();

        return $this->json([
            'transactions' => $transactions,
            'balance' => $balance,
        ]);
    }

    /**
     * Shows a form pre-filled with data of an existing transaction.
     *
     * The transaction ID is read from the `id` route parameter. If the ID is
     * missing or the transaction cannot be found, the user is redirected back
     * to the index with an error flash message.
     *
     * @param Request $request Incoming HTTP request.
     *
     * @return Response HTML edit form or redirect to the index when the record is not found.
     */
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

    /**
     * Processes submission of the "edit transaction" form.
     *
     * The method:
     * - reads the transaction ID and ensures the record exists,
     * - reads updated field values from POST data,
     * - validates them, taking the original transaction into account so that
     *   balance checks remain correct,
     * - on validation failure, re-renders the edit form with error messages,
     * - on success, performs an UPDATE in the repository and redirects back to
     *   the index with a success flash message.
     *
     * @param Request $request Incoming HTTP request containing POST data.
     *
     * @return Response Redirect to the index or re-rendered edit form with errors.
     */
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

    /**
     * Deletes a transaction identified by its ID.
     *
     * The ID can come from either the route or the request data. If the record
     * exists it is removed from the database, and a success flash message is
     * stored. When the record cannot be found the user is redirected with an
     * error flash message instead.
     *
     * @param Request $request Incoming HTTP request.
     *
     * @return Response Redirect to the treasury index page.
     */
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
     * Validates user input for creating or updating a transaction.
     *
     * The validation rules are shared between `store()` and `update()` and
     * cover:
     * - required type (`deposit` or `withdrawal`),
     * - numeric amount greater than zero,
     * - withdrawal not exceeding the allowed balance (with optional adjustment
     *   for the original transaction when editing),
     * - non-empty description up to 255 characters.
     *
     * @param string     $type                Transaction type as entered by the user.
     * @param string     $amountRaw           Raw amount value from the form.
     * @param string     $description         Description entered by the user.
     * @param float      $balance             Current balance before applying the change.
     * @param array|null $existingTransaction Existing transaction when updating, or null when creating.
     *
     * @return array Array of validation errors indexed by field name.
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

    /**
     * Returns the transaction repository instance.
     *
     * The repository is constructed on first access and cached for subsequent
     * calls to avoid repeated instantiation.
     *
     * @return TransactionRepository Repository used for all transaction queries and mutations.
     */
    private function repo(): TransactionRepository
    {
        if ($this->repository === null) {
            $this->repository = new TransactionRepository();
        }

        return $this->repository;
    }

    /**
     * Returns the session object used to store flash messages.
     *
     * The underlying session instance is resolved from the application and then
     * cached for later calls.
     *
     * @return Session Active session associated with the current request.
     */
    private function session(): Session
    {
        if ($this->flashSession === null) {
            $this->flashSession = $this->app->getSession();
        }

        return $this->flashSession;
    }

    /**
     * Stores a one-time message in the session under the given key.
     *
     * Flash messages are read by {@see consumeFlash()} and are typically used
     * to show success or error information after redirects.
     *
     * @param string $key   Identifier under which the message will be stored.
     * @param mixed  $value Arbitrary value; most often a human-readable string.
     */
    private function flash(string $key, mixed $value): void
    {
        $this->session()->set($key, $value);
    }

    /**
     * Reads and removes a flash message from the session.
     *
     * If no message is stored under the given key, null is returned.
     *
     * @param string $key Identifier of the flash message.
     *
     * @return mixed|null Stored flash value or null when not present.
     */
    private function consumeFlash(string $key): mixed
    {
        $value = $this->session()->get($key);
        $this->session()->remove($key);

        return $value;
    }
}
