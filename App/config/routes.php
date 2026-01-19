<?php
// AI-GENERATED: Add auth routes (GitHub Copilot / ChatGPT), 2026-01-18

// Jednoduchá definícia rout pre potreby predmetu VAII v štýle VAIICKO.
// Framework samotný zatiaľ číta c=Controller&a=action z query stringu,
// preto tento súbor berme ako deklaratívny zoznam URL → Controller@action.

return [
    'GET' => [
        '/' => 'Home@index',
        '/login' => 'Auth@loginForm',
        '/logout' => 'Auth@logout',
        '/treasury' => 'Treasury@index',
        '/treasury/new' => 'Treasury@new',
        '/treasury/edit/{id}' => 'Treasury@edit',
        '/treasury/delete/{id}' => 'Treasury@delete',
        '/treasury/refresh' => 'Treasury@refresh',
        '/esncards' => 'Esncards@index',
        '/esncards/new' => 'Esncards@new',
        '/esncards/edit/{id}' => 'Esncards@edit',
        '/esncards/delete/{id}' => 'Esncards@delete',
        '/manual' => 'Manual@index',
        '/manual/new' => 'Manual@new',
        '/manual/edit/{id}' => 'Manual@edit',
        '/manual/delete/{id}' => 'Manual@delete',
        '/manual/{id}' => 'Manual@show',
        '/manual/{id}/attachments/delete/{attId}' => 'Manual@deleteAttachment',
    ],
    'POST' => [
        '/login' => 'Auth@login',
        '/treasury/store' => 'Treasury@store',
        '/treasury/update/{id}' => 'Treasury@update',
        '/esncards/store' => 'Esncards@store',
        '/esncards/update/{id}' => 'Esncards@update',
        '/manual/store' => 'Manual@store',
        '/manual/update/{id}' => 'Manual@update',
        '/manual/{id}/attachments/upload' => 'Manual@uploadAttachmentJson',
        '/manual/{id}/attachments/delete/{attId}' => 'Manual@deleteAttachment',
    ],
];
