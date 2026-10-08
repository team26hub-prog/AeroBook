<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<?php if($success): ?><div class="customer-notice" role="status"><?= $e($success) ?></div><?php endif ?>
<?php if($section==='home'): ?>
    <section class="customer-welcome"><p class="customer-eyebrow">Your AeroBook home</p><h1>Welcome, <?= $e($userName) ?></h1><p>Search available flights and find the right route for your next journey.</p><a class="customer-primary" href="/flights">Search flights</a></section>
    <section class="customer-home-grid"><article class="customer-home-card"><span class="home-card-icon">✈</span><h2>Find a flight</h2><p>Choose your departure, destination, and travel date to see available flights.</p><a href="/flights">Start flight search →</a></article><article class="customer-home-card"><span class="home-card-icon">◷</span><h2>Plan your trip</h2><p>Review flight times, duration, fare, and current seat availability before selecting a flight.</p><a href="/flights">Explore flights →</a></article></section>
<?php else: ?>
    <section class="customer-placeholder"><p class="customer-eyebrow">Customer area</p><h1><?= $e($sectionTitle) ?></h1><p><?= $e($sectionMessage) ?></p><a class="customer-primary" href="/flights">Search flights</a></section>
<?php endif ?>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
