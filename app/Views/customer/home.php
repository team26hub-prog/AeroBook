<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<?php if($success): ?><div class="customer-notice" role="status"><?= $e($success) ?></div><?php endif ?>
<?php if(!empty($errors)): ?><div class="alert alert-error" role="alert"><ul><?php foreach($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<?php if($section==='home'): ?>
<?php require BASE_PATH.'/app/Views/customer/home-dashboard.php'; ?>
<?php elseif($section==='bookings'): ?>
    <section class="customer-bookings"><div class="customer-section-heading"><div><p class="customer-eyebrow">Your trips</p><h1>My Bookings</h1><p>Flight, passenger, seat, payment, and ticket details for your bookings.</p></div><a class="customer-primary" href="/flights">Search flights</a></div>
    <?php if(empty($bookings)): ?><div class="customer-placeholder"><h2>No bookings yet</h2><p>When you create a booking, its reference and status will appear here.</p><a class="customer-primary" href="/flights">Find a flight</a></div><?php else: ?><div class="customer-booking-list"><?php foreach($bookings as $booking): ?>
        <article class="customer-booking-card"><div class="customer-booking-card-head"><div class="customer-booking-main"><span class="customer-eyebrow"><?= $e($booking['airline_name'].' '.$booking['flight_number']) ?> &middot; PNR <?= $e($booking['pnr']) ?></span><h2><?= $e($booking['departure_code'].' → '.$booking['arrival_code']) ?></h2><p><?= $e(date('D, M j, Y H:i',strtotime($booking['departure_at']))) ?> &middot; <?= $e($booking['departure_city'].' to '.$booking['arrival_city']) ?></p></div><span class="customer-booking-status <?= $e($booking['status']) ?>"><?= $e(ucwords($booking['status'])) ?></span></div>
            <div class="booking-summary-details"><div><span>Booking status</span><strong><?= $e(ucwords($booking['status'])) ?></strong></div><div><span>Payment status</span><strong class="payment-status-pill <?= $e($booking['payment_status']??'not-submitted') ?>"><?= $e($booking['payment_status']?ucwords(str_replace('_',' ',$booking['payment_status'])):'Not submitted') ?></strong></div><div><span>Total</span><strong><?= $e($booking['currency']) ?> <?= $e(number_format((float)$booking['total_amount'],2)) ?></strong></div><div><span>Arrival</span><strong><?= $e(date('D, M j, Y H:i',strtotime($booking['arrival_at']))) ?></strong></div></div>
            <section class="booking-passengers"><h3>Passengers and seats</h3><?php foreach($booking['passengers'] as $person): ?><div class="booking-passenger"><div><strong><?= $e($person['first_name'].' '.$person['last_name']) ?></strong><small><?= $e(date('M j, Y',strtotime($person['date_of_birth']))) ?> &middot; <?= $e(ucfirst($person['gender'])) ?></small></div><div><span>Seat</span><strong><?= $e($person['seat_number']??'Not selected') ?></strong></div><div><span>E-ticket</span><?php if($person['ticket_number']): ?><a href="/account?section=tickets"><?= $e($person['ticket_number']) ?> (<?= $e(ucfirst($person['ticket_status'])) ?>)</a><?php else: ?><strong>Not issued</strong><?php endif ?></div></div><?php endforeach ?></section>
            <div class="customer-booking-actions"><a href="/booking/confirmation?id=<?= (int)$booking['id'] ?>">View booking summary</a><?php if($booking['status']==='confirmed'): ?><a href="/account?section=tickets">View / print e-ticket</a><?php endif ?><?php if((int)$booking['can_cancel']===1): ?><form method="post" action="/bookings/cancel" data-confirm="Cancel this booking? Its issued e-tickets will be voided and selected seats released." data-confirm-title="Cancel booking?" data-confirm-button="Yes, cancel booking"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>"><button class="booking-cancel-button" type="submit">Cancel booking</button></form><?php endif ?></div>
        </article>
    <?php endforeach ?></div><?php endif ?></section>
<?php elseif($section==='tickets'): ?>
    <?php require BASE_PATH.'/app/Views/customer/tickets.php'; ?>
<?php elseif($section==='profile'): ?>
    <?php require BASE_PATH.'/app/Views/customer/profile.php'; ?>
<?php else: ?>
    <section class="customer-placeholder"><p class="customer-eyebrow">Customer area</p><h1><?= $e($sectionTitle) ?></h1><p><?= $e($sectionMessage) ?></p><a class="customer-primary" href="/flights">Search flights</a></section>
<?php endif ?>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
