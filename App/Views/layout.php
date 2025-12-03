<?php
/** @var string $content */

// Určenie aktívnej stránky pre zvýraznenie v navbare a sidebare.
// Rozlišujeme len medzi úvodnou stránkou ("/") a Treasury ("/treasury"),
// všetko ostatné berieme ako "home".
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isTreasury = str_starts_with($requestUri, '/treasury');
$activePage = $isTreasury ? 'treasury' : 'home';
?><!doctype html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \App\Configuration::APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISf5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="/css/esn-custom.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg esn-navbar">
    <div class="container-fluid">
        <a class="navbar-brand esn-brand" href="/">VAIICKO</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="/">Home</a></li>
                <li class="nav-item"><a class="nav-link <?= $activePage === 'treasury' ? 'active' : '' ?>" href="/treasury">Treasury</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container-fluid mt-3">
    <div class="row flex-column flex-lg-row">
        <aside class="col-12 col-lg-3 mb-3 mb-lg-0 esn-sidebar">
            <h6 class="text-muted text-uppercase mb-3">Moduly</h6>
            <nav class="nav flex-column">
                <a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="/">Dashboard</a>
                <a class="nav-link <?= $activePage === 'treasury' ? 'active' : '' ?>" href="/treasury">Treasury</a>
            </nav>
        </aside>
        <main class="col-12 col-lg-9">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="/js/app.js"></script>
</body>
</html>
