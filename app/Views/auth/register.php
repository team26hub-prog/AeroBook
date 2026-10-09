<?php $authPage=true;require BASE_PATH . '/app/Views/layouts/header.php'; ?>
<section class="auth-card" aria-labelledby="page-heading">
    <p class="eyebrow">Customer account</p>
    <h1 id="page-heading">Create your AeroBook account</h1>
    <p class="intro">Register to get started with AeroBook.</p>
    <?php require BASE_PATH . '/app/Views/partials/alerts.php'; ?>
    <form method="post" action="/register" class="auth-form" data-auth-validation novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <label for="full_name">Full name</label>
        <input id="full_name" name="full_name" type="text" maxlength="150" autocomplete="name" required aria-describedby="full_name-error" value="<?= htmlspecialchars((string) ($old['full_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <p class="field-error" id="full_name-error" data-error-for="full_name" aria-live="polite"></p>

        <label for="email">Email address</label>
        <input id="email" name="email" type="email" maxlength="254" autocomplete="email" required aria-describedby="email-error" value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <p class="field-error" id="email-error" data-error-for="email" aria-live="polite"></p>

        <label for="password">Password</label>
        <div class="password-field">
            <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required aria-describedby="password-hint password-error">
            <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false" hidden></button>
        </div>
        <p class="field-hint" id="password-hint">Use 8 to 72 characters.</p>
        <p class="field-error" id="password-error" data-error-for="password" aria-live="polite"></p>

        <label for="password_confirmation">Confirm password</label>
        <div class="password-field">
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password" required aria-describedby="password_confirmation-error">
            <button class="password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show password" aria-pressed="false" hidden></button>
        </div>
        <p class="field-error" id="password_confirmation-error" data-error-for="password_confirmation" aria-live="polite"></p>

        <button class="button" type="submit">Create account</button>
    </form>
    <p class="form-footnote">Already registered? <a href="/login">Sign in</a></p>
</section>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
