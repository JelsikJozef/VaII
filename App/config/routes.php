<?php

// Jednoduchá definícia rout pre potreby predmetu VAII v štýle VAIICKO.
// Framework samotný zatiaľ číta c=Controller&a=action z query stringu,
// preto tento súbor berme ako deklaratívny zoznam URL → Controller@action.

return [
    'GET' => [
        '/' => 'Home@index',
        '/treasury' => 'Treasury@index',
        '/treasury/new' => 'Treasury@new',
        '/treasury/refresh' => 'Treasury@refresh',
    ],
    'POST' => [
        '/treasury/store' => 'Treasury@store',
    ],
];
