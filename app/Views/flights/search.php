<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/flights.css">
<div class="flight-shell">
    <header class="flight-heading"><div><p class="flight-eyebrow">AeroBook flights</p><h1>Find your next flight</h1><p class="flight-lead">Search routes and schedules to find an available flight.</p></div></header>
    <?php require BASE_PATH.'/app/Views/partials/alerts.php'; ?>
    <section class="flight-panel" aria-labelledby="search-title"><div class="panel-heading"><div><h2 id="search-title">Search flights</h2><p>Choose your route and travel date.</p></div></div>
        <form class="flight-search-form" method="get" action="/flights">
            <label>Departure airport<select name="departure_id" required><option value="">Select departure</option><?php foreach($airports as $airport): ?><option value="<?= (int)$airport['id'] ?>" <?= (string)$airport['id']===$filters['departure_id']?'selected':'' ?>><?= $e($airport['city'].' · '.$airport['iata_code'].' — '.$airport['name']) ?></option><?php endforeach ?></select></label>
            <label>Destination airport<select name="arrival_id" required><option value="">Select destination</option><?php foreach($airports as $airport): ?><option value="<?= (int)$airport['id'] ?>" <?= (string)$airport['id']===$filters['arrival_id']?'selected':'' ?>><?= $e($airport['city'].' · '.$airport['iata_code'].' — '.$airport['name']) ?></option><?php endforeach ?></select></label>
            <label>Travel date<input type="date" name="date" min="<?= $e(date('Y-m-d')) ?>" value="<?= $e($filters['date']) ?>" required></label>
            <button class="flight-button" type="submit">Search flights</button>
        </form>
    </section>
    <?php if(is_array($results)): ?>
        <section class="flight-results" aria-live="polite"><div class="results-heading"><div><h2>Available flights</h2><p><?= count($results) ?> <?= count($results)===1?'flight':'flights' ?> found for <?= $e(date('D, M j, Y',strtotime($filters['date']))) ?></p></div></div>
        <?php if(!$results): ?><div class="no-results"><strong>No flights found</strong><p>Try another date or route. Only flights with available seats are shown.</p></div><?php else: ?>
            <?php foreach($results as $flight): $duration=(int)$flight['duration_minutes']; ?>
                <article class="flight-card"><div class="flight-card-top"><div><span class="airline-code"><?= $e($flight['airline_code']) ?></span><strong><?= $e($flight['flight_number']) ?></strong><span class="airline-name"><?= $e($flight['airline_name']) ?></span></div><span class="availability"><?= (int)$flight['available_seats'] ?> seats available</span></div>
                    <div class="flight-route"><div class="route-point"><time><?= $e(date('H:i',strtotime($flight['departure_at']))) ?></time><strong><?= $e($flight['departure_code']) ?></strong><span><?= $e($flight['departure_city']) ?></span></div><div class="route-duration"><span><?= intdiv($duration,60) ?>h <?= $duration%60 ?>m</span><i></i><small>Direct</small></div><div class="route-point arrival"><time><?= $e(date('H:i',strtotime($flight['arrival_at']))) ?></time><strong><?= $e($flight['arrival_code']) ?></strong><span><?= $e($flight['arrival_city']) ?></span></div></div>
                    <div class="flight-card-bottom"><span><?= $e(date('D, M j, Y',strtotime($flight['departure_at']))) ?> · Arrival <?= $e(date('D, M j',strtotime($flight['arrival_at']))) ?></span><strong class="flight-price"><?= $e($flight['currency']) ?> <?= $e(number_format((float)$flight['base_fare'],2)) ?></strong><a class="flight-button secondary-button" href="/flights/details?id=<?= (int)$flight['id'] ?>">Flight details</a></div>
                </article>
            <?php endforeach ?>
        <?php endif ?>
        </section>
    <?php endif ?>
    <p class="flight-footnote">Selecting a flight does not create a booking.</p>
</div>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
