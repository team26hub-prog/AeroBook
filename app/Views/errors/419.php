<?php $title = 'Session expired'; require BASE_PATH . '/app/Views/layouts/header.php'; ?>
<section class="auth-card" aria-labelledby="page-heading">
    <p class="eyebrow">Request could not be verified</p>
    <h1 id="page-heading">Your form session expired</h1>
    <p class="intro">Reload the page and submit the form again.</p>
    <a class="button button-link" href="/login">Return to sign in</a>
</section>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
