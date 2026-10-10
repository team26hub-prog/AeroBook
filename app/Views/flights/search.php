<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$airportImages=['KHI'=>'karachi','LHE'=>'lahore','ISB'=>'islamabad','SKT'=>'sialkot','DXB'=>'dubai'];
$picturedAirports=array_filter($airports,static fn($airport)=>isset($airportImages[$airport['iata_code']]));
$selectedAirport=null;
foreach($airports as $airport){if((string)$airport['id']===$filters['departure_id']){$selectedAirport=$airport;break;}}
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/flights.css">
<div class="flight-shell">
    <header class="flight-heading flight-search-hero"><div><p class="flight-eyebrow">AeroBook flights</p><h1>Find your next flight</h1><p class="flight-lead">Search routes and schedules to find an available flight.</p></div><img src="/assets/images/airplane.jpg" alt="Passenger airplane flying overhead in a blue sky" width="1280" height="915" fetchpriority="high"></header>
    <?php require BASE_PATH.'/app/Views/partials/alerts.php'; ?>
    <section class="flight-panel" aria-labelledby="search-title"><div class="panel-heading"><div><h2 id="search-title">Search flights</h2><p>Choose your route and travel date.</p></div></div>
        <form class="flight-search-form" method="get" action="/flights">
            <label>Departure airport<select name="departure_id" required><option value="">Select departure</option><?php foreach($airports as $airport): ?><option value="<?= (int)$airport['id'] ?>" <?= (string)$airport['id']===$filters['departure_id']?'selected':'' ?>><?= $e($airport['city'].' · '.$airport['iata_code'].' — '.$airport['name']) ?></option><?php endforeach ?></select></label>
            <label>Destination airport<select name="arrival_id" required><option value="">Select destination</option><?php foreach($airports as $airport): ?><option value="<?= (int)$airport['id'] ?>" <?= (string)$airport['id']===$filters['arrival_id']?'selected':'' ?>><?= $e($airport['city'].' · '.$airport['iata_code'].' — '.$airport['name']) ?></option><?php endforeach ?></select></label>
            <label>Travel date<input type="date" name="date" min="<?= $e(date('Y-m-d')) ?>" value="<?= $e($filters['date']) ?>" required></label>
            <button class="flight-button" type="submit">Search flights</button>
        </form>
    </section>
    <?php if($picturedAirports): ?>
    <section class="flight-airport-gallery" aria-labelledby="flight-airports-title">
        <div class="flight-airport-gallery-heading"><div><h2 id="flight-airports-title">Choose your departure airport</h2><p>Pick an airport to see its available departures. Narrow your results using the form above.</p></div><a href="/assets/images/photo-credits.html">Photo credits</a></div>
        <div class="flight-airport-images">
            <?php foreach($picturedAirports as $airport): ?>
            <a class="flight-airport-card" href="/flights?departure_id=<?= (int)$airport['id'] ?>#airport-departures"<?= (string)$airport['id']===$filters['departure_id']?' aria-current="true"':'' ?> aria-label="<?= $e('See available flights from '.$airport['name']) ?>"><img src="/assets/images/airports/<?= $e($airportImages[$airport['iata_code']]) ?>.jpg" alt="<?= $e($airport['name']) ?>" loading="lazy" decoding="async" width="480" height="320"><span class="flight-airport-card-title"><strong><?= $e($airport['city']) ?></strong><b><?= $e($airport['iata_code']) ?></b></span><span class="flight-airport-card-name"><?= $e($airport['name']) ?></span><span class="flight-airport-card-action">View departures <span aria-hidden="true">&rarr;</span></span></a>
            <?php endforeach ?>
        </div>
    </section>
    <?php endif ?>
    <?php if(is_array($results)||$flightsUnavailable): $flights=$results??[]; ?>
        <?php require BASE_PATH.'/app/Views/flights/departures.php'; ?>
    <?php endif ?>
    <p class="flight-footnote">Selecting a flight does not create a booking.</p>
</div>
<script defer src="/assets/js/flight-availability.js"></script>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
