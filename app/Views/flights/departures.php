<?php declare(strict_types=1); ?>
<section class="airport-departures" id="airport-departures" aria-labelledby="departures-heading">
    <div class="results-heading"><div><h2 id="departures-heading"><?= $selectedAirport?'Flights from '.$e($selectedAirport['city']):'Available departures' ?></h2><p><?= $filters['date']!==''?'Travel date: '.$e($filters['date']).'. ':'All upcoming dates. ' ?><?= count($flights) ?> <?= count($flights)===1?'available flight':'available flights' ?>. <a href="/flights">Choose another airport</a></p></div></div>
    <?php if($flightsUnavailable): ?>
    <div class="no-results"><h3>Flights temporarily unavailable</h3><p>Please refresh or try your search again.</p></div>
    <?php elseif(!$flights): ?>
    <div class="no-results"><h3>No available departures</h3><p>Try another airport, destination or date. Only upcoming flights with available seats are shown.</p></div>
    <?php else: ?>
    <ul class="airport-departure-list">
        <?php foreach($flights as $flight): $duration=(int)$flight['duration_minutes']; ?>
        <li class="airport-departure" data-flight-id="<?= (int)$flight['id'] ?>">
            <div class="airport-departure-route"><h3><?= $e($flight['departure_code']) ?> <span aria-hidden="true">&rarr;</span> <?= $e($flight['arrival_city']) ?> <span class="airport-destination-code"><?= $e($flight['arrival_code']) ?></span></h3><p><?= $e($flight['airline_name'].' · '.$flight['flight_number']) ?></p><span class="departure-availability" data-seat-count aria-live="polite" aria-atomic="true"><?= (int)$flight['available_seats'] ?> seats available</span></div>
            <div class="airport-departure-schedule"><time datetime="<?= $e(str_replace(' ','T',$flight['departure_at'])) ?>"><?= $e(date('D, d M Y · H:i',strtotime($flight['departure_at']))) ?></time><span>Arrival <?= $e(date('d M, H:i',strtotime($flight['arrival_at']))) ?> · <?= intdiv($duration,60) ?>h <?= $duration%60 ?>m</span></div>
            <div class="airport-departure-fare"><span>Per passenger</span><strong><?= $e($flight['currency']) ?> <?= $e(number_format((float)$flight['base_fare'],2)) ?></strong><a class="flight-button secondary-button" href="/flights/details?id=<?= (int)$flight['id'] ?>" aria-label="<?= $e('View flight '.$flight['flight_number'].' to '.$flight['arrival_city']) ?>">View flight</a></div>
        </li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</section>
