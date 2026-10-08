<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$success=null;$errors=[];
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/booking.css">
<div class="booking-page">
    <a class="booking-back" href="/booking/passengers">Edit passenger details</a>
    <div class="booking-heading"><p class="customer-eyebrow">Step 2 of 2</p><h1>Review your booking</h1><p>Check the flight and passenger details before creating your booking.</p></div>
    <section class="booking-flight-summary"><div><span><?= $e($flight['airline_name']) ?> · <?= $e($flight['flight_number']) ?></span><strong><?= $e($flight['departure_code']) ?> → <?= $e($flight['arrival_code']) ?></strong></div><div><span>Departure</span><strong><?= $e(date('D, M j · H:i',strtotime($flight['departure_at']))) ?></strong></div><div><span>Arrival</span><strong><?= $e(date('D, M j · H:i',strtotime($flight['arrival_at']))) ?></strong></div></section>
    <section class="review-passengers"><h2>Passengers (<?= count($passengers) ?>)</h2><?php foreach($passengers as $index=>$person): ?><article class="review-passenger"><div class="review-passenger-name"><span>Passenger <?= $index+1 ?></span><strong><?= $e($person['first_name'].' '.$person['last_name']) ?></strong></div><div><span>Date of birth</span><strong><?= $e($person['date_of_birth']) ?></strong></div><div><span>Gender</span><strong><?= $e(ucfirst($person['gender'])) ?></strong></div><?php if($person['passport_number']): ?><div><span>Passport</span><strong><?= $e($person['passport_number']) ?><?= $person['passport_country']?' · '.$e($person['passport_country']):'' ?></strong></div><?php endif ?></article><?php endforeach ?></section>
    <section class="booking-total"><div><span><?= count($passengers) ?> × <?= $e($flight['currency']) ?> <?= $e(number_format((float)$flight['base_fare'],2)) ?> per passenger</span><strong>Total amount</strong></div><strong class="total-amount"><?= $e($flight['currency']) ?> <?= $e(number_format((float)$total,2)) ?></strong></section>
    <p class="booking-disclaimer">Confirming creates a pending booking and PNR. Seats are not selected or reserved in this step. Payment and e-ticket issuance are not included.</p>
    <div class="booking-actions"><a href="/booking/passengers">Edit passengers</a><form method="post" action="/booking/create"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="booking-button" type="submit">Confirm booking</button></form></div>
</div>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
