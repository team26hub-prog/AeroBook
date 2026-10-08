<?php
declare(strict_types=1);
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/seats.css">
<section class="seat-empty"><h1>Seat selection unavailable</h1><p>We couldn’t load seat selection. Please try again.</p><a class="seat-button" href="/seat-selection">Try again</a></section>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
