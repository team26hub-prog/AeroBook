<?php require BASE_PATH.'/app/Views/layouts/customer-start.php'; ?>
<link rel="stylesheet" href="/assets/css/flights.css">
<div class="flight-shell"><section class="no-results unavailable-card"><p class="flight-eyebrow"><?= !empty($failed)?'Search unavailable':'Flight unavailable' ?></p><h1><?= !empty($failed)?'Flight details could not be loaded':'This flight cannot be selected' ?></h1><p><?= !empty($failed)?'Please try again in a moment.':'It may have departed or no longer have available seats. Search again to see current options.' ?></p><a class="flight-button" href="/flights">Search flights</a></section></div>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
