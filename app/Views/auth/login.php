<?php require BASE_PATH . '/app/Views/layouts/header.php'; ?>
<section class="auth-card" aria-labelledby="page-heading">
    <p class="eyebrow">AeroBook account</p>
    <h1 id="page-heading">Sign in to AeroBook</h1>
    <p class="intro">Welcome back. Sign in to continue to your account.</p>
    <?php require BASE_PATH . '/app/Views/partials/alerts.php'; ?>
    <form method="post" action="/login" class="auth-form" data-auth-validation novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" maxlength="254" autocomplete="username" required autofocus aria-describedby="email-error" value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <p class="field-error" id="email-error" data-error-for="email" aria-live="polite"></p>

        <label for="password">Password</label>
        <div class="password-field">
            <input id="password" name="password" type="password" autocomplete="current-password" required aria-describedby="password-error">
            <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false" hidden></button>
        </div>
        <p class="field-error" id="password-error" data-error-for="password" aria-live="polite"></p>

        <button class="button" type="submit">Sign in</button>
    </form>
    <p class="form-footnote">New to AeroBook? <a class="create-account-link" href="/register">Create Account</a></p>
</section>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
