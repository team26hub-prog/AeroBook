<?php require BASE_PATH . '/app/Views/layouts/header.php'; ?>
<section class="auth-card" aria-labelledby="page-heading">
    <p class="eyebrow">Customer account</p>
    <h1 id="page-heading">Sign in to AeroBook</h1>
    <p class="intro">Welcome back. Enter your account details to continue.</p>
    <?php require BASE_PATH . '/app/Views/partials/alerts.php'; ?>
    <form method="post" action="/login" class="auth-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" maxlength="254" autocomplete="username" required autofocus value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>

        <button class="button" type="submit">Sign in</button>
    </form>
    <p class="form-footnote">New to AeroBook? <a class="create-account-link" href="/register">Create Account</a></p>
</section>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
