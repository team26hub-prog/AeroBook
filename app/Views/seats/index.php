<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/seats.css">
<link rel="stylesheet" href="/assets/css/seat-selection.css">
<div class="seat-page">
    <div class="seat-heading"><p class="customer-eyebrow">Customer area</p><h1>Seat Selection</h1><p>Choose seats for passengers on one of your pending bookings.</p></div>
    <?php require BASE_PATH.'/app/Views/partials/alerts.php'; ?>
    <?php if($bookings): ?><div class="seat-panel-heading"><div><h2>Your pending bookings</h2><p>Choose a booking below to view its passengers and seat map.</p></div><span><?= count($bookings) ?> <?= count($bookings)===1?'booking':'bookings' ?></span></div><?php endif ?>
    <?php if(!$bookings): ?><section class="seat-empty"><h2>No pending bookings</h2><p>Create a booking before selecting seats. Your pending bookings will appear here so you can assign a seat to every passenger.</p><a class="seat-button" href="/flights">Search flights</a></section><?php else: ?>
        <div class="seat-booking-list"><?php foreach($bookings as $booking): ?><article class="seat-booking-card"><div class="seat-booking-route"><span><?= $e($booking['flight_number']) ?> · PNR <?= $e($booking['pnr']) ?></span><h2><?= $e($booking['departure_code'].' → '.$booking['arrival_code']) ?></h2><p><?= $e(date('D, M j, Y · H:i',strtotime($booking['departure_at']))) ?></p></div><div class="seat-booking-status"><span class="seat-booking-count"><?= (int)$booking['passenger_count'] ?> <?= (int)$booking['passenger_count']===1?'passenger':'passengers' ?> &middot; Pending booking</span><?php if((int)$booking['passenger_count']>0&&(int)$booking['assigned_count']===(int)$booking['passenger_count']): ?><span class="seat-ready">Seats saved &middot; Ready for payment</span><?php endif ?><span><?= (int)$booking['assigned_count'] ?> / <?= (int)$booking['passenger_count'] ?> seats selected</span><a class="seat-button" href="/seat-selection?booking_id=<?= (int)$booking['id'] ?>"><?= (int)$booking['assigned_count']>0?'Review seats':'Choose seats' ?></a></div></article><?php endforeach ?></div>
    <?php endif ?>
</div>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
