<?php

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use App\Repositories\TransactionRepository;

class TreasuryController extends BaseController
{
    public function authorize(Request $request, string $action): bool
    {
        return true;
    }

    public function index(Request $request): Response
    {
        $repository = new TransactionRepository();
        $transactions = $repository->findAll();

        return $this->html([
            'activeModule' => 'treasury',
            'transactions' => $transactions,
        ]);
    }

    public function new(Request $request): Response
    {
        // urcenie default hodnoty typu podla zdroja (query parameter ?type=deposit|withdrawal)
        $typeParam = (string)($request->get('type') ?? '');
        $defaultType = '';
        if ($typeParam === 'deposit') {
            $defaultType = 'deposit';
        } elseif ($typeParam === 'withdrawal') {
            $defaultType = 'withdrawal';
        }

        $data = [
            'activeModule' => 'treasury',
            'errors' => [],
            'type' => $defaultType,
            'amount' => '',
            'description' => '',
        ];

        return $this->html($data);
    }

    public function store(Request $request): Response
    {
        $type = trim((string)($request->post('type') ?? ''));
        $amountRaw = (string)($request->post('amount') ?? '');
        $description = trim((string)($request->post('description') ?? ''));

        $errors = [];

        // validate type
        if ($type === '') {
            $errors['type'][] = 'Type is required.';
        } elseif (!in_array($type, ['deposit', 'withdrawal'], true)) {
            $errors['type'][] = 'Type must be deposit or withdrawal.';
        }

        // validate amount
        if ($amountRaw === '') {
            $errors['amount'][] = 'Amount is required.';
        } elseif (!is_numeric($amountRaw)) {
            $errors['amount'][] = 'Amount must be a number.';
        } else {
            $amount = (float)$amountRaw;
            if ($amount <= 0) {
                $errors['amount'][] = 'Amount must be greater than 0.';
            }
        }

        // validate description
        if ($description === '') {
            $errors['description'][] = 'Description is required.';
        } elseif (mb_strlen($description) > 255) {
            $errors['description'][] = 'Description must be at most 255 characters.';
        }

        // Ak sú chyby, zobrazíme formulár znova v rámci jedného requestu (bez redirectu a bez session)
        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'treasury',
                'errors' => $errors,
                'type' => $type,
                'amount' => $amountRaw,
                'description' => $description,
            ], 'Treasury/new');
        }

        // TODO: uloženie transakcie do databázy a prípadná aktualizácia zostatku

        // Po úspechu len redirect na index bez flash správy
        return $this->redirect(['Treasury', 'index']);
    }
}
