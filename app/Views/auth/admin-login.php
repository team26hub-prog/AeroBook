<?php require BASE_PATH . '/app/Views/layouts/header.php'; ?>
<section class="auth-card" aria-labelledby="page-heading">
    <p class="eyebrow">Restricted access</p>
    <h1 id="page-heading">Administrator sign in</h1>
    <p class="intro">Sign in with an active AeroBook administrator account.</p>
    <?php require BASE_PATH . '/app/Views/partials/alerts.php'; ?>
    <form method="post" action="/admin/login" class="auth-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label for="email">Admin email address</label>
        <input id="email" name="email" type="email" maxlength="254" autocomplete="username" required autofocus value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <button class="button" type="submit">Sign in as admin</button>
    </form>
    <p class="form-footnote"><a href="/login">Customer sign in</a></p>
</section>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
