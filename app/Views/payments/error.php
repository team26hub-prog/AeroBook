<?php
declare(strict_types=1);
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/payments.css">
<section class="payment-empty"><h1>Payments unavailable</h1><p>We could not load your payment information. Please try again.</p><a class="payment-button" href="/payments">Try again</a></section>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
