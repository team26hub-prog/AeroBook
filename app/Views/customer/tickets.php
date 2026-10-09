<?php
declare(strict_types=1);
?>
<link rel="stylesheet" href="/assets/css/tickets.css">
<section class="ticket-page">
    <div class="ticket-page-heading"><div><p class="customer-eyebrow">Confirmed bookings</p><h1>E-Tickets</h1><p>Print an individual ticket or choose Save as PDF in the print dialog.</p></div></div>
    <?php if(!$tickets): ?><div class="ticket-empty"><h2>No e-tickets yet</h2><p>Tickets appear here after an administrator verifies the payment and confirms your booking.</p><a href="/account?section=bookings">View my bookings</a></div><?php else: ?>
        <div class="ticket-list">
        <?php foreach($tickets as $ticket): ?><article class="e-ticket" data-ticket-number="<?= $e($ticket['ticket_number']) ?>">
            <header class="e-ticket-header"><div><span class="ticket-brand">AeroBook <small>BOARDING DOCUMENT</small></span><p><?= $e($ticket['airline_name']) ?> &middot; <?= $e($ticket['flight_number']) ?></p></div><div class="ticket-header-actions"><span class="ticket-state <?= $e($ticket['ticket_status']) ?>"><?= $e(ucfirst($ticket['ticket_status'])) ?></span><button class="ticket-print-button" type="button" data-print-ticket aria-label="<?= $e('Print ticket '.$ticket['ticket_number'].' for '.$ticket['first_name'].' '.$ticket['last_name']) ?>">Print ticket</button></div></header>
            <div class="ticket-route"><div><span>From</span><strong><?= $e($ticket['departure_code']) ?></strong><small><?= $e($ticket['departure_city']) ?></small><small><?= $e($ticket['departure_airport']) ?></small></div><span class="ticket-route-arrow" aria-hidden="true">&rarr;</span><div><span>To</span><strong><?= $e($ticket['arrival_code']) ?></strong><small><?= $e($ticket['arrival_city']) ?></small><small><?= $e($ticket['arrival_airport']) ?></small></div></div>
            <div class="ticket-details"><div><span>Passenger</span><strong><?= $e($ticket['first_name'].' '.$ticket['last_name']) ?></strong></div><div><span>Date of birth</span><strong><?= $e(date('M j, Y',strtotime($ticket['date_of_birth']))) ?></strong></div><div><span>Departure</span><strong><?= $e(date('D, M j, Y',strtotime($ticket['departure_at']))) ?></strong></div><div><span>Time</span><strong><?= $e(date('H:i',strtotime($ticket['departure_at']))) ?></strong></div><div><span>Seat</span><strong><?= $e($ticket['seat_number']??'—') ?></strong></div><div><span>Booking status</span><strong><?= $e(ucfirst($ticket['booking_status'])) ?></strong></div><div><span>Payment amount</span><strong><?= $e($ticket['currency']) ?> <?= $e(number_format((float)($ticket['payment_amount']??$ticket['booking_total']),2)) ?></strong></div><div><span>PNR</span><strong class="ticket-code"><?= $e($ticket['pnr']) ?></strong></div></div>
            <footer class="ticket-footer"><div><span>Ticket number</span><strong class="ticket-code"><?= $e($ticket['ticket_number']) ?></strong></div><div><span>Issued</span><strong><?= $e(date('M j, Y H:i',strtotime($ticket['issued_at']))) ?></strong></div><span class="ticket-arrival-summary">Arrives <?= $e(date('D, M j, Y H:i',strtotime($ticket['arrival_at']))) ?></span></footer>
        </article><?php endforeach ?>
        </div>
    <?php endif ?>
</section>
<script defer src="/assets/js/ticket-print.js"></script>
