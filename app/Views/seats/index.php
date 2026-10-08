<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/seats.css">
<div class="seat-page">
    <div class="seat-heading"><p class="customer-eyebrow">Customer area</p><h1>Seat Selection</h1><p>Choose seats for passengers on one of your pending bookings.</p></div>
    <?php if($success): ?><div class="customer-notice" role="status"><?= $e($success) ?></div><?php endif ?><?php if($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
    <?php if(!$bookings): ?><section class="seat-empty"><h2>No pending bookings</h2><p>After creating a booking, it will appear here for seat selection.</p><a class="seat-button" href="/flights">Search flights</a></section><?php else: ?>
        <div class="seat-booking-list"><?php foreach($bookings as $booking): ?><article class="seat-booking-card"><div class="seat-booking-route"><span><?= $e($booking['flight_number']) ?> · PNR <?= $e($booking['pnr']) ?></span><h2><?= $e($booking['departure_code'].' → '.$booking['arrival_code']) ?></h2><p><?= $e(date('D, M j, Y · H:i',strtotime($booking['departure_at']))) ?></p></div><div class="seat-booking-status"><span><?= (int)$booking['assigned_count'] ?> / <?= (int)$booking['passenger_count'] ?> seats selected</span><a class="seat-button" href="/seat-selection?booking_id=<?= (int)$booking['id'] ?>"><?= (int)$booking['assigned_count']>0?'Review seats':'Choose seats' ?></a></div></article><?php endforeach ?></div>
    <?php endif ?>
</div>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
