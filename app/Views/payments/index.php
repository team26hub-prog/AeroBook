<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/payments.css">
<div class="payment-page">
    <div class="payment-heading"><p class="customer-eyebrow">Customer area</p><h1>Payments</h1><p>Submit manual payment details and track admin verification for your bookings.</p></div>
    <?php if($success): ?><div class="customer-notice" role="status"><?= $e($success) ?></div><?php endif ?><?php if($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
    <?php if(!$bookings): ?><section class="payment-empty"><h2>No bookings yet</h2><p>Your booking payment options will appear here after you book a flight.</p><a class="payment-button" href="/flights">Search flights</a></section><?php else: ?>
        <div class="payment-booking-list">
        <?php foreach($bookings as $booking): $seatReady=(int)$booking['passenger_count']>0&&(int)$booking['assigned_count']===(int)$booking['passenger_count'];$paymentStatus=$booking['latest_payment_status']; ?>
            <article class="payment-booking-card"><div class="payment-booking-main"><p class="customer-eyebrow"><?= $e($booking['flight_number']) ?> &middot; PNR <?= $e($booking['pnr']) ?></p><h2><?= $e($booking['departure_code'].' &rarr; '.$booking['arrival_code']) ?></h2><p><?= $e(date('D, M j, Y H:i',strtotime($booking['departure_at']))) ?> &middot; <?= (int)$booking['passenger_count'] ?> <?= (int)$booking['passenger_count']===1?'passenger':'passengers' ?></p><strong><?= $e($booking['currency']) ?> <?= $e(number_format((float)$booking['total_amount'],2)) ?></strong></div>
                <div class="payment-booking-action"><span class="payment-status <?= $e($paymentStatus??$booking['status']) ?>"><?= $e($paymentStatus?ucwords(str_replace('_',' ',$paymentStatus)):($seatReady?'Payment needed':($booking['status']==='pending'?'Select seats first':ucwords($booking['status'])))) ?></span>
                    <?php if($booking['status']==='pending'&&!$seatReady): ?><a class="payment-button secondary" href="/seat-selection?booking_id=<?= (int)$booking['id'] ?>">Complete seat selection</a><?php elseif($booking['latest_payment_id']): ?><a class="payment-button" href="/payments?booking_id=<?= (int)$booking['id'] ?>"><?= $paymentStatus==='rejected'?'Resubmit payment':'View payment status' ?></a><?php elseif($booking['status']==='pending'): ?><a class="payment-button" href="/payments?booking_id=<?= (int)$booking['id'] ?>">Submit payment</a><?php else: ?><span class="payment-status <?= $e($booking['status']) ?>"><?= $e(ucwords($booking['status'])) ?></span><?php endif ?>
                </div></article>
        <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
