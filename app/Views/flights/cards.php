<?php
declare(strict_types=1);
?>
<link rel="stylesheet" href="/assets/css/flight-cards.css">
<section class="available-flight-list" aria-labelledby="available-flights-heading">
    <div class="results-heading"><div><h2 id="available-flights-heading"><?= $hasSearch?'Search results':'All available flights' ?></h2><p><?php if($hasSearch): ?>Flights for <?= $e(date('D, M j, Y',strtotime($filters['date']))) ?>. <a href="/flights">View all available flights</a><?php else: ?>Choose a flight to view its details and start your booking.<?php endif ?></p></div><span><?= count($flights) ?> <?= count($flights)===1?'flight':'flights' ?></span></div>
    <?php if($flightsUnavailable): ?>
        <div class="no-results"><h3>Flights temporarily unavailable</h3><p>Please refresh or try your search again to load available flights.</p></div>
    <?php elseif(!$flights): ?>
        <div class="no-results"><h3>No available flights</h3><p><?= $hasSearch?'Try another date or route. Only flights with available seats are shown.':'There are currently no upcoming flights with available seats. Check again later.' ?></p></div>
    <?php else: ?>
        <div class="available-flight-grid">
        <?php foreach($flights as $flight): $duration=(int)$flight['duration_minutes']; ?>
            <article class="available-flight-card" data-flight-id="<?= (int)$flight['id'] ?>">
                <div class="available-flight-header"><div><span class="customer-eyebrow"><?= $e($flight['airline_name']) ?></span><h3><?= $e($flight['flight_number']) ?></h3></div><span class="available-flight-availability" data-seat-count aria-live="polite" aria-atomic="true"><?= (int)$flight['available_seats'] ?> seats available</span></div>
                <div class="available-flight-route"><div><strong><?= $e($flight['departure_code']) ?></strong><span><?= $e($flight['departure_city']) ?></span><time datetime="<?= $e(str_replace(' ','T',$flight['departure_at'])) ?>"><?= $e(date('d M Y, H:i',strtotime($flight['departure_at']))) ?></time></div><span class="available-flight-arrow" aria-hidden="true">&rarr;</span><div><strong><?= $e($flight['arrival_code']) ?></strong><span><?= $e($flight['arrival_city']) ?></span><time datetime="<?= $e(str_replace(' ','T',$flight['arrival_at'])) ?>"><?= $e(date('d M Y, H:i',strtotime($flight['arrival_at']))) ?></time></div></div>
                <p class="available-flight-duration">Direct flight &middot; <?= intdiv($duration,60) ?>h <?= $duration%60 ?>m</p>
                <div class="available-flight-footer"><div><span>Fare per passenger</span><strong><?= $e($flight['currency']) ?> <?= $e(number_format((float)$flight['base_fare'],2)) ?></strong></div><a class="flight-button secondary-button" href="/flights/details?id=<?= (int)$flight['id'] ?>" aria-label="<?= $e('View details for flight '.$flight['flight_number']) ?>">View flight</a></div>
            </article>
        <?php endforeach ?>
        </div>
    <?php endif ?>
</section>
