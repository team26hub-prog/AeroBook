<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<?php if($success): ?><div class="customer-notice" role="status"><?= $e($success) ?></div><?php endif ?>
<?php if($section==='home'): ?>
    <section class="customer-welcome"><p class="customer-eyebrow">Your AeroBook home</p><h1>Welcome, <?= $e($userName) ?></h1><p>Search available flights and find the right route for your next journey.</p><a class="customer-primary" href="/flights">Search flights</a></section>
    <section class="customer-home-grid"><article class="customer-home-card"><span class="home-card-icon">✈</span><h2>Find a flight</h2><p>Choose your departure, destination, and travel date to see available flights.</p><a href="/flights">Start flight search →</a></article><article class="customer-home-card"><span class="home-card-icon">◷</span><h2>Plan your trip</h2><p>Review flight times, duration, fare, and current seat availability before selecting a flight.</p><a href="/flights">Explore flights →</a></article></section>
<?php elseif($section==='bookings'): ?>
    <section class="customer-bookings"><div class="customer-section-heading"><div><p class="customer-eyebrow">Your trips</p><h1>My Bookings</h1><p>Bookings created from your selected flights.</p></div><a class="customer-primary" href="/flights">Search flights</a></div>
    <?php if(empty($bookings)): ?><div class="customer-placeholder"><h2>No bookings yet</h2><p>When you create a booking, its reference and status will appear here.</p><a class="customer-primary" href="/flights">Find a flight</a></div><?php else: ?><div class="customer-booking-list"><?php foreach($bookings as $booking): ?><article class="customer-booking-card"><div class="customer-booking-main"><span class="customer-eyebrow"><?= $e($booking['flight_number']) ?> · <?= $e(date('D, M j, Y',strtotime($booking['departure_at']))) ?></span><h2><?= $e($booking['departure_code'].' → '.$booking['arrival_code']) ?></h2><p><?= (int)$booking['passenger_count'] ?> <?= (int)$booking['passenger_count']===1?'passenger':'passengers' ?> · <?= $e($booking['currency']) ?> <?= $e(number_format((float)$booking['total_amount'],2)) ?></p></div><div class="customer-booking-meta"><span class="customer-booking-status <?= $e($booking['status']) ?>"><?= $e($booking['status']) ?></span><strong>PNR <?= $e($booking['pnr']) ?></strong><a href="/booking/confirmation?id=<?= (int)$booking['id'] ?>">View booking</a></div></article><?php endforeach ?></div><?php endif ?></section>
<?php else: ?>
    <section class="customer-placeholder"><p class="customer-eyebrow">Customer area</p><h1><?= $e($sectionTitle) ?></h1><p><?= $e($sectionMessage) ?></p><a class="customer-primary" href="/flights">Search flights</a></section>
<?php endif ?>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
