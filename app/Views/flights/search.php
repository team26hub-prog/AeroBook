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
    <?php if(is_array($results)||$flightsUnavailable): $flights=$results??[]; ?>
        <?php require BASE_PATH.'/app/Views/flights/cards.php'; ?>
    <?php endif ?>
    <p class="flight-footnote">Selecting a flight does not create a booking.</p>
</div>
<script defer src="/assets/js/flight-availability.js"></script>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
