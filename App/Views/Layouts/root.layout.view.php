<?php

/** @var string $contentHTML */
/** @var \Framework\Core\IAuthenticator $auth */
/** @var \Framework\Support\LinkGenerator $link */
/** @var float|int|null $currentBalance */
/** @var string|null $activeModule */

$activeModule = $activeModule ?? 'home';

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>ESN UNIZA – Internal Tools</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <link href="<?= $link->asset('css/esn-custom.css') ?>" rel="stylesheet">
</head>
<body data-current-balance="<?= htmlspecialchars((string)($currentBalance ?? 0), ENT_QUOTES) ?>">
<nav class="navbar navbar-expand-lg esn-navbar">
    <div class="container-fluid">
        <a class="navbar-brand esn-brand" href="/">
            ESN UNIZA
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNavbar" aria-controls="mainNavbar"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/treasury">Treasury</a></li>
                <li class="nav-item"><a class="nav-link" href="/esncards">ESNcards</a></li>
                <li class="nav-item"><a class="nav-link" href="/manual">Semester Manual</a></li>
                <li class="nav-item"><a class="nav-link" href="/polls">Polls</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid mt-3">
    <div class="row">
        <!-- sidebar – na mobiloch hore, na veľkých vľavo -->
        <aside class="col-12 col-lg-3 mb-3 mb-lg-0">
            <div class="p-3 esn-sidebar rounded-4">
                <h6 class="text-muted text-uppercase mb-3">Modules</h6>
                <nav class="nav flex-column">
                    <a class="nav-link <?= $activeModule === 'home' ? 'active' : '' ?>" href="/">Home</a>
                    <a class="nav-link <?= $activeModule === 'treasury' ? 'active' : '' ?>" href="/treasury">Treasury</a>
                    <a class="nav-link <?= $activeModule === 'esncards' ? 'active' : '' ?>" href="/esncards">ESNcards</a>
                    <a class="nav-link <?= $activeModule === 'manual' ? 'active' : '' ?>" href="/manual">Semester Manual</a>
                    <a class="nav-link <?= $activeModule === 'polls' ? 'active' : '' ?>" href="/polls">Polls</a>
                </nav>
            </div>
        </aside>

        <!-- hlavný obsah -->
        <main class="col-12 col-lg-9">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script src="/public/js/app.js"></script>
</body>
</html>
