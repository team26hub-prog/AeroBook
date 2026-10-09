<?php
declare(strict_types=1);
http_response_code(500);
$title='Admin page unavailable';
$adminHeaderTitle=$title;
$adminHeaderStandalone=true;
$e=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
require BASE_PATH.'/app/Views/layouts/admin-start.php';
?>
        <section class="auth-card">
            <h2>Admin page unavailable</h2>
            <p class="intro">We couldn't load this page. Please try again.</p>
            <a class="button" href="/admin">Return to dashboard</a>
        </section>
<?php require BASE_PATH.'/app/Views/layouts/admin-end.php'; ?>
<?php require BASE_PATH.'/app/Views/layouts/footer.php'; ?>
