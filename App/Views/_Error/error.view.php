<?php

/**
 * Error page view.
 *
 * Renders a minimal error page for exceptions handled by the framework.
 * When `$showDetail` is enabled (typically in development), prints stack traces
 * and any chained previous exceptions.
 *
 * Expected variables:
 * - \Framework\Http\HttpException $exception
 * - bool $showDetail
 * - \Framework\Support\View $view
 */

/** @var \Framework\Http\HttpException $exception */
/** @var bool $showDetail */
/** @var \Framework\Support\View $view */

$view->setLayout(null);

?>

<h1><?= $exception->getCode() . " - " . $exception->getMessage() ?></h1>

<?php
if ($showDetail && $exception->getCode() != 500) :
    ?>
    <?= get_class($exception) ?>: <strong><?= $exception->getMessage() ?></strong>
    in file <strong><?= $exception->getFile() ?></strong>
    at line <strong><?= $exception->getLine() ?></strong>
    <pre>Stack trace:<br><?= $exception->getTraceAsString() ?></pre>
<?php endif; ?>

<?php
while ($showDetail && $exception->getPrevious() != null) { ?>
    <?= get_class($exception->getPrevious()) ?>: <strong><?= $exception->getPrevious()->getMessage() ?></strong>
    in file <strong><?= $exception->getPrevious()->getFile() ?></strong>
    at line <strong><?= $exception->getPrevious()->getLine() ?></strong>
    <pre>Stack trace:<br><?= $exception->getPrevious()->getTraceAsString() ?></pre>
    <?php $exception = $exception->getPrevious(); ?>
<?php } ?>
