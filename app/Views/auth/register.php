<?php require BASE_PATH . '/app/Views/layouts/header.php'; ?>
<section class="auth-card" aria-labelledby="page-heading">
    <p class="eyebrow">Customer account</p>
    <h1 id="page-heading">Create your AeroBook account</h1>
    <p class="intro">Register to get started with AeroBook.</p>
    <?php require BASE_PATH . '/app/Views/partials/alerts.php'; ?>
    <form method="post" action="/register" class="auth-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" type="text" maxlength="150" autocomplete="name" required value="<?= htmlspecialchars((string) ($old['full_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

        <label for="email">Email address</label>
        <input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
        <p class="field-hint">Use 8 to 72 characters.</p>

        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>

        <button class="button" type="submit">Create account</button>
    </form>
    <p class="form-footnote">Already registered? <a href="/login">Sign in</a></p>
</section>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
