<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/booking.css">
<div class="booking-page booking-confirmation"><div class="confirmation-mark" aria-hidden="true">✓</div><p class="customer-eyebrow">Booking created</p><h1>Your booking is pending</h1><p class="confirmation-intro">Your booking was created. Keep this reference for your records.</p>
    <section class="confirmation-reference"><span>PNR / booking reference</span><strong><?= $e($booking['pnr']) ?></strong><span class="booking-status">Pending</span></section>
    <section class="booking-flight-summary"><div><span>Flight</span><strong><?= $e($booking['airline_name'].' · '.$booking['flight_number']) ?></strong></div><div><span>Route</span><strong><?= $e($booking['departure_code'].' → '.$booking['arrival_code']) ?></strong></div><div><span>Departure</span><strong><?= $e(date('D, M j · H:i',strtotime($booking['departure_at']))) ?></strong></div><div><span>Total</span><strong><?= $e($booking['currency']) ?> <?= $e(number_format((float)$booking['total_amount'],2)) ?></strong></div></section>
    <section class="review-passengers"><h2>Passengers</h2><?php foreach($booking['passengers'] as $person): ?><p><?= $e($person['first_name'].' '.$person['last_name']) ?> <span><?= $e($person['date_of_birth']) ?></span></p><?php endforeach ?></section>
    <p class="booking-disclaimer">Select one available seat for each passenger, then submit payment details for admin verification. E-ticket generation will be available in a later step.</p><div class="booking-actions"><a class="booking-button" href="/seat-selection?booking_id=<?= (int)$booking['id'] ?>">Continue to seat selection</a><a href="/flights">Search more flights</a><a href="/account?section=bookings">My bookings</a></div>
</div>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
