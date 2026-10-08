<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$duration=(int)$flight['duration_minutes'];
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/flights.css">
<div class="flight-shell detail-shell">
    <a class="back-link" href="/flights">← Back to flight search</a>
    <?php $errors=[];require BASE_PATH.'/app/Views/partials/alerts.php'; ?>
    <section class="detail-card"><p class="flight-eyebrow">Flight details</p><div class="detail-title"><div><h1><?= $e($flight['airline_name']) ?> · <?= $e($flight['flight_number']) ?></h1><p><?= $e($flight['airline_code']) ?> · Direct flight</p></div><span class="availability"><?= (int)$flight['available_seats'] ?> seats available</span></div>
        <div class="detail-route"><div class="detail-airport"><span class="airport-code"><?= $e($flight['departure_code']) ?></span><strong><?= $e($flight['departure_city']) ?>, <?= $e($flight['departure_country']) ?></strong><span><?= $e($flight['departure_name']) ?></span><time><?= $e(date('D, M j, Y · H:i',strtotime($flight['departure_at']))) ?></time></div><div class="detail-duration"><span><?= intdiv($duration,60) ?>h <?= $duration%60 ?>m</span><i></i><small>Nonstop</small></div><div class="detail-airport"><span class="airport-code"><?= $e($flight['arrival_code']) ?></span><strong><?= $e($flight['arrival_city']) ?>, <?= $e($flight['arrival_country']) ?></strong><span><?= $e($flight['arrival_name']) ?></span><time><?= $e(date('D, M j, Y · H:i',strtotime($flight['arrival_at']))) ?></time></div></div>
        <div class="detail-summary"><div><span>Flight</span><strong><?= $e($flight['flight_number']) ?></strong></div><div><span>Duration</span><strong><?= intdiv($duration,60) ?>h <?= $duration%60 ?>m</strong></div><div><span>Available seats</span><strong><?= (int)$flight['available_seats'] ?></strong></div><div class="summary-price"><span>Price per passenger</span><strong><?= $e($flight['currency']) ?> <?= $e(number_format((float)$flight['base_fare'],2)) ?></strong></div></div>
        <?php if($selected): ?><div class="selection-note" role="status"><strong>Flight selected.</strong> It is ready for the next step. No booking has been created.</div><?php endif ?>
        <form method="post" action="/flights/select" class="select-flight-form"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="flight_id" value="<?= (int)$flight['id'] ?>"><button class="flight-button" type="submit">Select Flight</button><a href="/flights">Choose another flight</a></form>
    </section>
</div>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
