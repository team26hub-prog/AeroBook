<?php
declare(strict_types=1);
// Presentation-only airport gallery; flight availability stays in the existing search flow.
$homeAirports = [
    ['Karachi', 'KHI', 'Jinnah International Airport', 'karachi'],
    ['Lahore', 'LHE', 'Allama Iqbal International Airport', 'lahore'],
    ['Islamabad', 'ISB', 'Islamabad International Airport', 'islamabad'],
    ['Dubai', 'DXB', 'Dubai International Airport', 'dubai'],
    ['Sialkot', 'SKT', 'Sialkot International Airport', 'sialkot'],
];
?>
<link rel="stylesheet" href="/assets/css/home.css">
<div class="aero-home">
    <section class="aero-hero" aria-labelledby="hero-heading">
        <img class="aero-hero-image" src="/assets/images/airplane.jpg" alt="Passenger airplane flying overhead in a clear blue sky" width="1280" height="915" fetchpriority="high">
        <div class="aero-hero-copy">
            <p class="aero-kicker"><span aria-hidden="true"></span> A world of possibilities</p>
            <h1 id="hero-heading">Your next chapter<br>starts in the sky.</h1>
            <p>From a quick getaway to a long-awaited reunion. Find your flight, choose your seat, and make the journey yours.</p>
            <div class="aero-hero-actions"><a class="customer-primary" href="/flights">Search flights <span aria-hidden="true">&nearr;</span></a><a class="aero-hero-link" href="/account?section=bookings">My bookings <span aria-hidden="true">&rarr;</span></a></div>
            <div class="aero-hero-note"><span aria-hidden="true">&#10003;</span> Flight search <span aria-hidden="true">&middot;</span> Seat selection <span aria-hidden="true">&middot;</span> Digital tickets</div>
        </div>
        <span class="aero-hero-caption">AeroBook / Make your way</span>
    </section>

    <nav class="aero-journey-bar" aria-label="Travel shortcuts">
        <div><span class="aero-kicker">Your journey, simplified</span><strong>Everything for your trip. One place.</strong></div>
        <a href="/flights"><?= $menuIcon('search') ?><span>Find a flight</span><span aria-hidden="true">&rarr;</span></a>
        <a href="/account?section=bookings"><?= $menuIcon('bookings') ?><span>Manage booking</span><span aria-hidden="true">&rarr;</span></a>
        <a href="/account?section=tickets"><?= $menuIcon('tickets') ?><span>My e-tickets</span><span aria-hidden="true">&rarr;</span></a>
    </nav>

    <section class="aero-destinations" aria-labelledby="airports-heading">
        <div class="aero-section-heading"><div><p class="aero-kicker">Explore our network</p><h2 id="airports-heading">Five airports. Endless reasons to go.</h2></div><a class="aero-text-link" href="/flights">Explore flights <span aria-hidden="true">&rarr;</span></a></div>
        <div class="aero-airport-grid">
            <?php foreach ($homeAirports as [$city, $code, $airportName, $image]): ?>
            <figure class="aero-airport">
                <img src="/assets/images/airports/<?= $e($image) ?>.jpg" alt="<?= $e($airportName) ?>" width="960" height="640" loading="lazy" decoding="async">
                <figcaption><span class="aero-airport-code"><?= $e($code) ?></span><h3><?= $e($city) ?></h3><p><?= $e($airportName) ?></p></figcaption>
            </figure>
            <?php endforeach ?>
        </div>
        <p class="aero-network-note">Choose your route and date in flight search to see current schedules and availability.</p>
    </section>

    <?php if (!empty($popularRoutes)): ?>
    <section class="aero-routes" aria-labelledby="trips-heading">
        <div class="aero-section-heading"><div><p class="aero-kicker">Popular routes</p><h2 id="trips-heading">Find inspiration for your next trip</h2></div></div>
        <div class="aero-route-list">
            <?php foreach ($popularRoutes as $route): ?>
            <a href="/flights?departure_id=<?= (int)($route['departure_id'] ?? 1) ?>&amp;arrival_id=<?= (int)($route['arrival_id'] ?? 2) ?>&amp;date=<?= $e($route['date'] ?? date('Y-m-d')) ?>"><strong><?= $e($route['origin'] ?? 'Origin') ?> <span aria-hidden="true">&rarr;</span> <?= $e($route['destination'] ?? 'Destination') ?></strong><span><?= $e($route['city'] ?? '') ?> &middot; <?= $e($route['airport'] ?? '') ?></span><small><?= $e($route['duration'] ?? '2h 45m') ?> &middot; <?= $e($route['stops'] ?? 'Direct') ?></small><span class="aero-text-link">See flights &rarr;</span></a>
            <?php endforeach ?>
        </div>
    </section>
    <?php endif ?>

    <section class="aero-guide" aria-labelledby="guide-heading">
        <div class="aero-guide-intro"><p class="aero-kicker">A smarter way to travel</p><h2 id="guide-heading">From takeoff plans<br>to ticket in hand.</h2><p>Search, book, and manage your trip with AeroBook. Your itinerary, passengers, seats, and tickets stay together in your account.</p><a class="aero-text-link" href="/flights">Plan your journey <span aria-hidden="true">&rarr;</span></a><img class="aero-guide-image" src="/assets/images/airplane-clouds.jpg" alt="Boeing 747 flying into clouds" width="960" height="1291" loading="lazy" decoding="async"></div>
        <ol class="aero-steps">
            <li><span>01</span><div><h3>Find your flight</h3><p>Choose your route and date, then compare available flights.</p></div></li>
            <li><span>02</span><div><h3>Make it yours</h3><p>Add passenger details, review your booking, and select seats.</p></div></li>
            <li><span>03</span><div><h3>Submit payment, get ready to go</h3><p>After payment verification and booking confirmation, view and print your e-ticket.</p></div></li>
        </ol>
        <div class="aero-travel-note"><strong>Before you fly</strong><p>Book ahead, check your airline's baggage allowance, save your issued e-ticket, and check the weather at your destination.</p></div>
    </section>

    <section class="aero-support" aria-labelledby="faq-heading">
        <div><p class="aero-kicker">Here to help</p><h2 id="faq-heading">A little clarity <br>before you fly.</h2><?php if (!$isAuthenticated): ?><p>Keep your travel plans together.</p><div class="aero-account-links"><a class="aero-text-link" href="/login">Login</a><a class="aero-text-link" href="/register">Sign Up</a></div><?php else: ?><a class="aero-text-link" href="/account?section=profile">Your travel account &rarr;</a><?php endif ?></div>
        <div class="aero-faq-list">
            <details><summary>How do I book a flight?</summary><p>Search for a flight, choose an available option, enter your passenger details, and create your booking. Then select seats and submit your payment details.</p></details>
            <details><summary>Can I change or cancel a booking?</summary><p>Manage your booking from My Bookings. If cancellation is available, a Cancel booking button will appear. Changes depend on the airline and fare rules.</p></details>
            <details><summary>When will my e-ticket be ready?</summary><p>After an admin verifies your payment and confirms your booking, your e-ticket will be available in E-Tickets. You can view and print each ticket separately.</p></details>
        </div>
    </section>
    <footer class="aero-home-footer"><span>AeroBook <span aria-hidden="true">/</span> Your journey starts here</span><a href="/assets/images/photo-credits.html">Photo credits</a></footer>
</div>
