<?php
declare(strict_types=1);
$headerEscape=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$headerTitle=$adminHeaderTitle??($nav[$section??'']??'Admin panel');
?>
<link rel="stylesheet" href="/assets/css/admin-header.css">
<header class="admin-header<?= !empty($adminHeaderStandalone)?' admin-header-standalone':'' ?>">
    <div class="admin-header-inner">
    <?php if(empty($adminHeaderStandalone)): ?>
    <button class="admin-menu-toggle" type="button" aria-label="Open admin navigation" aria-expanded="false" aria-controls="admin-sidebar"><span>Menu</span></button>
    <a class="mobile-navbar-logo" href="/admin" aria-label="AeroBook admin overview"><img src="/assets/images/aerobook-logo.png?v=globe-transparent" alt="" width="1536" height="1024"></a>
    <?php endif ?>
    <div class="admin-header-title">
        <p>AeroBook operations</p>
        <h1 title="<?= $headerEscape($headerTitle) ?>"><?= $headerEscape($headerTitle) ?></h1>
    </div>
    <nav class="admin-header-nav" aria-label="Admin header navigation">
        <?php if(empty($adminHeaderStandalone)): ?><a class="admin-header-site" href="/">View website <span aria-hidden="true">&nearr;</span></a><?php endif ?>
        <time datetime="<?= date('Y-m-d') ?>"><?= date('D, M j, Y') ?></time>
    </nav>
    </div>
</header>
