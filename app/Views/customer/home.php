<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<?php if($success): ?><div class="customer-notice" role="status"><?= $e($success) ?></div><?php endif ?>
<?php if(!empty($errors)): ?><div class="alert alert-error" role="alert"><ul><?php foreach($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
<?php if($section==='home'): ?>
<div class="customer-home-sections">
<section class="customer-hero" aria-labelledby="hero-heading">
    <div class="customer-hero-eyebrow"><span class="customer-eyebrow"><?= $e($appName ?? 'AeroBook') ?></span><span>·</span><span>Your travel journey starts here</span></div>
    <h1 id="hero-heading">From bookings to flights, your next adventure is only a search away.</h1>
    <p class="customer-hero-lead">Search hundreds of routes, compare fares, choose your seat, and get a ready-to-use e-ticket, all in one place.</p>
    <div class="customer-hero-actions">
        <?php if(!$isAuthenticated): ?><a class="customer-primary" href="/login">Login</a><a class="customer-ghost" href="/register">Sign Up</a><?php endif ?>
        <a class="customer-primary" href="/flights"><span class="customer-button-label">Search flights</span><span class="customer-button-arrow" aria-hidden="true">→</span></a>
        <a class="customer-ghost" href="/account?section=bookings"><span class="customer-button-label">View my bookings</span></a>
    </div>

</section>

<section class="customer-about" aria-labelledby="about-heading">
    <div class="customer-section-head">
        <p class="customer-eyebrow">About AeroBook</p>
        <h2 id="about-heading">A smarter way to book your trip</h2>
    </div>
    <p class="customer-about-lead">AeroBook turns the hassle of airline ticketing into a simple, transparent, and enjoyable experience. Create an account to search flights, plan your itinerary, pick a seat, and receive a digital e-ticket that is easy to view or share.</p>
    <div class="customer-feature-grid">
        <article class="customer-feature-card">
            <span class="feature-mark" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15.5 8.5l-4 4 4 4"/></svg></span>
            <h3>Instant flight search</h3>
            <p>Filter by route, date, and travel class to find available seats and the best fares in seconds.</p>
            <a class="customer-ghost" href="/flights">Explore flights <span aria-hidden="true">→</span></a>
        </article>
        <article class="customer-feature-card">
            <span class="feature-mark" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20"/><path d="M8.5 8.5c2 3 7 7 7 7"/><path d="M8.5 15.5c-2 3-7 7-7 7"/></svg></span>
            <h3>Simple booking flow</h3>
            <p>Enter passenger details, review your itinerary, and confirm in just a few steps before you pay.</p>
            <a class="customer-ghost" href="/flights">Book a trip <span aria-hidden="true">→</span></a>
        </article>
        <article class="customer-feature-card">
            <span class="feature-mark" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9Z"/><path d="M2 9l4-3 4 3 4-3 4 3"/><path d="M6 9h12"/></svg></span>
            <h3>E-tickets in your pocket</h3>
            <p>Every confirmed booking includes a ready-to-print e-ticket with your flight, seat, and passenger details.</p>
            <a class="customer-ghost" href="/account?section=tickets">View e-tickets <span aria-hidden="true">→</span></a>
        </article>
    </div>
</section>
<section class="customer-steps" aria-labelledby="steps-heading">
    <div class="customer-section-head">
        <p class="customer-eyebrow">How it works</p>
        <h2 id="steps-heading">Book a flight in four easy steps</h2>
    </div>
    <ol class="customer-step-list">
        <li class="customer-step">
            <div class="customer-step-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15.5 8.5l-4 4 4 4"/></svg></div>
            <p class="customer-step-number">01</p>
            <h3>Search and compare</h3>
            <p>Pick your departure and destination, choose a date, and review the best available options.</p>
            <a class="customer-ghost" href="/flights">Search flights <span aria-hidden="true">→</span></a>
        </li>
        <li class="customer-step">
            <div class="customer-step-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20"/><path d="M8.5 8.5c2 3 7 7 7 7"/><path d="M8.5 15.5c-2 3-7 7-7 7"/></svg></div>
            <p class="customer-step-number">02</p>
            <h3>Select your flight</h3>
            <p>Review the schedule, duration, and seat map, then select the option that fits you.</p>
            <a class="customer-ghost" href="/flights">View flight details <span aria-hidden="true">→</span></a>
        </li>
        <li class="customer-step">
            <div class="customer-step-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7L9 18l-5-5"/></svg></div>
            <p class="customer-step-number">03</p>
            <h3>Add passengers and seats</h3>
            <p>Enter each passenger detail, confirm the total, and choose seats for everyone in your party.</p>
            <a class="customer-ghost" href="/flights">Add passengers <span aria-hidden="true">→</span></a>
        </li>
        <li class="customer-step">
            <div class="customer-step-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20"/><path d="M8.5 8.5c2 3 7 7 7 7"/><path d="M8.5 15.5c-2 3-7 7-7 7"/></svg></div>
            <p class="customer-step-number">04</p>
            <h3>Pay and download your ticket</h3>
            <p>Complete payment, then access and share your e-ticket straight from your account.</p>
            <a class="customer-ghost" href="/account?section=tickets">View e-tickets <span aria-hidden="true">→</span></a>
        </li>
    </ol>
</section>

<?php if(!empty($popularRoutes)): ?>
<section class="customer-trip-cards" aria-labelledby="trips-heading">
    <div class="customer-section-head">
        <p class="customer-eyebrow">Popular routes</p>
        <h2 id="trips-heading">Travels our customers choose most</h2>
    </div>
    <div class="customer-trip-list">
        <?php foreach($popularRoutes ?? [] as $index=>$route): ?>
        <article class="customer-trip-card">
            <span class="trip-rank"><?= $e($index + 1) ?></span>
            <div class="trip-delta">
                <time><?= $e($route['city'] ?? 'City') ?></time>
                <span class="trip-line" aria-hidden="true"><?= $e($route['airport'] ?? '') ?></span>
            </div>
            <h3><?= $e($route['origin'] ?? 'Origin') ?> → <?= $e($route['destination'] ?? 'Destination') ?></h3>
            <p><?= $e($route['duration'] ?? '2h 45m') ?>&nbsp;·&nbsp;<?= $e($route['stops'] ?? 'Direct') ?></p>
            <a class="customer-ghost" href="/flights?departure_id=<?= (int)($route['departure_id'] ?? 1) ?>&arrival_id=<?= (int)($route['arrival_id'] ?? 2) ?>&date=<?= $e($route['date'] ?? date('Y-m-d')) ?>">See flight option <span aria-hidden="true">→</span></a>
        </article>
        <?php endforeach ?>
    </div>
    <a class="customer-ghost" href="/flights">Browse all routes <span aria-hidden="true">→</span></a>
</section>
<?php endif ?>
<section class="customer-travel-tips" aria-labelledby="tips-heading">
    <div class="customer-section-head">
        <p class="customer-eyebrow">Travel smarter</p>
        <h2 id="tips-heading">Tips to save time and money</h2>
    </div>
    <div class="customer-tip-grid">
        <div class="customer-tip-card">
            <span class="tip-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15.5 8.5l-4 4 4 4"/></svg></span>
            <h4>Book ahead for better fares</h4>
            <p>Flights booked a few weeks ahead are often cheaper and more likely to have seats available.</p>
        </div>
        <div class="customer-tip-card">
            <span class="tip-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20"/><path d="M8.5 8.5c2 3 7 7 7 7"/><path d="M8.5 15.5c-2 3-7 7-7 7"/></svg></span>
            <h4>Pick the right baggage</h4>
            <p>Match your baggage option to how far you plan to travel, and keep your carry-on light.</p>
        </div>
        <div class="customer-tip-card">
            <span class="tip-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7L9 18l-5-5"/></svg></span>
            <h4>Keep your e-ticket handy</h4>
            <p>Save a screenshot or download the PDF so you can show it at the airport without delay.</p>
        </div>
        <div class="customer-tip-card">
            <span class="tip-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15.5 8.5l-4 4 4 4"/></svg></span>
            <h4>Check the weather at your destination</h4>
            <p>A quick weather check helps you pack the right layers and avoid surprises.</p>
        </div>
    </div>
</section>

<section class="customer-faq" aria-labelledby="faq-heading">
    <div class="customer-section-head">
        <p class="customer-eyebrow">FAQ</p>
        <h2 id="faq-heading">Flight questions, answered</h2>
    </div>
    <div class="customer-faq-list">
        <details class="customer-faq-item">
            <summary class="customer-faq-question">How do I book a flight?<span class="customer-faq-toggle" aria-hidden="true"></span></summary>
            <div class="customer-faq-answer">Search for a flight, choose an available option, enter your passenger details, and create your booking. Then select seats and submit your payment details.</div>
        </details>
        <details class="customer-faq-item">
            <summary class="customer-faq-question">Can I change or cancel a booking?<span class="customer-faq-toggle" aria-hidden="true"></span></summary>
            <div class="customer-faq-answer">Manage your booking from My Bookings. If cancellation is available for your booking, a Cancel booking button will appear. Changes depend on the airline and fare rules.</div>
        </details>
        <details class="customer-faq-item">
            <summary class="customer-faq-question">How do I get my e-ticket?<span class="customer-faq-toggle" aria-hidden="true"></span></summary>
            <div class="customer-faq-answer">After an admin verifies your payment and confirms your booking, your e-ticket will be available in E-Tickets. You can view and print each ticket separately.</div>
        </details>
    </div>
</section>

<section class="customer-cta" aria-labelledby="cta-heading">
    <div class="customer-cta-card">
        <span class="customer-eyebrow">Start your journey</span>
        <h2 id="cta-heading">Find your next flight today</h2>
        <p>Compare routes, check seat availability, and book in minutes. It only takes a few clicks to get where you are going.</p>
        <a class="customer-primary" href="/flights"><span class="customer-button-label">Search flights now</span><span class="customer-button-arrow" aria-hidden="true">→</span></a>
    </div>
</section>
</div>
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
