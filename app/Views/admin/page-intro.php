<?php
declare(strict_types=1);
$adminDescriptions=[
    'dashboard'=>'Your operations at a glance. Review what needs attention, then manage travel.',
    'airlines'=>'Manage airline names, codes, and availability in one place.',
    'airports'=>'Keep airport details and timezones up to date.',
    'flights'=>'Schedule flights and update fares, departure times, and statuses.',
    'seats'=>'Generate seat layouts and manage availability for each flight.',
    'bookings'=>'Find reservations, review passengers, and update booking statuses.',
    'payments'=>'Check payment details and proof before verifying or rejecting a submission.',
];
$adminCreateLabels=['airlines'=>'Add airline','airports'=>'Add airport','flights'=>'Schedule flight','seats'=>'Create seat layout'];
?>
<div class="admin-page-intro"><p><?= $e($adminDescriptions[$section] ?? '') ?></p><div class="admin-page-shortcuts"><?php if(isset($adminCreateLabels[$section])): ?><a class="admin-button" href="#admin-create"><?= $e($adminCreateLabels[$section]) ?> <span aria-hidden="true">+</span></a><?php endif ?><a class="admin-text-link" href="/admin<?= $section==='dashboard'?'/bookings':'' ?>"><?= $section==='dashboard'?'Manage bookings':'Operations overview' ?> &rarr;</a></div></div>
<p class="admin-action-feedback" id="admin-action-feedback" role="status" aria-live="polite"></p>
