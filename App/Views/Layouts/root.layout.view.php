<?php

/** @var string $contentHTML */
/** @var \Framework\Core\IAuthenticator $auth */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Http\Request|null $request */

// Jednoduché určenie aktívneho modulu pre navbar/sidebar.
// Priorita:
// 1) ak controller poslal $activeModule, použijeme ho,
// 2) inak sa pokúsime odvodiť z query parametra 'c' (napr. c=treasury → 'treasury'),
// 3) fallback je 'home'.
if (isset($activeModule) && is_string($activeModule) && $activeModule !== '') {
    $activeModule = strtolower($activeModule);
} else {
    $fromRequest = null;

    if (isset($request) && $request instanceof \Framework\Http\Request) {
        $fromRequest = $request->get('c');
    } elseif (!empty($_GET['c'])) {
        $fromRequest = $_GET['c'];
    }

    $activeModule = is_string($fromRequest) && $fromRequest !== ''
        ? strtolower($fromRequest)
        : 'home';
}
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <title><?= App\Configuration::APP_NAME ?></title>
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $link->asset('favicons/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $link->asset('favicons/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $link->asset('favicons/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= $link->asset('favicons/site.webmanifest') ?>">
    <link rel="shortcut icon" href="<?= $link->asset('favicons/favicon.ico') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
            crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?= $link->asset('css/styl.css') ?>">
    <link rel="stylesheet" href="<?= $link->asset('css/esn-custom.css') ?>">
    <script src="<?= $link->asset('js/script.js') ?>"></script>
</head>
<body data-active-module="<?= htmlspecialchars($activeModule, ENT_QUOTES) ?>">
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand d-flex" href="<?= $link->url('Home.index') ?>">
            <img src="<?= $link->asset('images/vektor_logo.png') ?>" alt="ESN" width="120" class="me-2">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'home' ? 'active' : '' ?>"
                       href="<?= $link->url('Home.index') ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'treasury' ? 'active' : '' ?>"
                       href="<?= $link->url('Treasury.index') ?>">ESN Treasury</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'esncards' ? 'active' : '' ?>" href="/esncards">ESNcards</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'manual' ? 'active' : '' ?>" href="/manual">Semester Manual</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'profile' ? 'active' : '' ?>" href="/profile">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeModule === 'polls' ? 'active' : '' ?>" href="/polls">Polls</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid mt-3">
    <div class="row">

        <main class="col-md-9">
            <?= $contentHTML ?>
        </main>
    </div>
</div>
</body>
</html>
