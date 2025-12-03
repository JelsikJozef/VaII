<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Session;

class TreasuryController extends BaseController
{
    public function authorize(Request $request, string $action): bool
    {
        return true;
    }

    public function index(Request $request): Response
    {
        return $this->html([
            'activeModule' => 'treasury',
        ]);
    }

    public function new(Request $request): Response
    {
        // načítanie chýb a starých hodnôt zo session (ak existujú)
        $session = new Session();

        $errors = $session->get('treasury_errors') ?? [];
        $old = $session->get('treasury_old') ?? [];

        // po jednorazovom zobrazení ich odstránime (flash-like správanie)
        $session->remove('treasury_errors');
        $session->remove('treasury_old');

        $data = [
            'activeModule' => 'treasury',
            'errors' => $errors,
            'type' => $old['type'] ?? '',
            'amount' => $old['amount'] ?? '',
            'description' => $old['description'] ?? '',
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

        // if there are validation errors, redirect back to the form with errors and old input
        if (!empty($errors)) {
            $session = new Session();
            $session->set('treasury_errors', $errors);
            $session->set('treasury_old', [
                'type' => $type,
                'amount' => $amountRaw,
                'description' => $description,
            ]);

            return $this->redirect(['Treasury', 'new']);
        }

        // TODO: uloženie transakcie do databázy a prípadná aktualizácia zostatku

        // po úspešnom spracovaní môžeme pridať jednoduchú flash správu (nepovinné)
        $session = new Session();
        $session->set('treasury_success', 'Transaction has been created.');

        return $this->redirect(['Treasury', 'index']);
    }
}
