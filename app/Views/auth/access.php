<?php require BASE_PATH . '/app/Views/layouts/header.php'; ?>
<section class="auth-card access-card" aria-labelledby="page-heading">
    <p class="eyebrow"><?= $escape($GLOBALS['config']['app']['name'] ?? 'AeroBook') ?></p>
    <h1 id="page-heading"><?= $escape($heading) ?></h1>
    <?php require BASE_PATH . '/app/Views/partials/alerts.php'; ?>
    <p class="intro"><?= $escape($message) ?></p>
    <form method="post" action="<?= $escape($logoutPath) ?>">
        <input type="hidden" name="_csrf" value="<?= $escape($csrf) ?>">
        <button class="button button-secondary" type="submit">Sign out</button>
    </form>
</section>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
