<?php declare(strict_types=1); ?>
<link rel="stylesheet" href="/assets/css/admin-dashboard.css">
<form class="admin-dashboard-search" method="get" action="/admin/bookings" role="search">
    <label for="admin-find-booking">Find a booking<input type="search" id="admin-find-booking" name="q" placeholder="Booking reference, customer, or flight number" required></label>
    <button class="admin-button" type="submit">Find booking</button>
</form>
<details class="admin-getting-started" <?= (int)$stats['flights']===0 ? 'open' : '' ?>>
    <summary>New to AeroBook? Set up your first bookable flight</summary>
    <ol>
        <li><a href="/admin/airlines#admin-create">Add an airline</a><span>Use its name and official airline codes.</span></li>
        <li><a href="/admin/airports#admin-create">Add departure and arrival airports</a><span>You need two different active airports.</span></li>
        <li><a href="/admin/flights#admin-create">Schedule the flight</a><span>Choose its route, times, and fare.</span></li>
        <li><a href="/admin/seats#admin-create">Create its seat layout</a><span>Seats give customers capacity to book.</span></li>
    </ol>
</details>
<section class="admin-priority" aria-labelledby="priority-heading">
    <span class="admin-priority-icon" aria-hidden="true"><?= $menuIcon('payments') ?></span>
    <div><p class="admin-eyebrow">Next up</p><h2 id="priority-heading"><?= (int)$stats['pending_payments'] ?> payment<?= (int)$stats['pending_payments']===1?'':'s' ?> awaiting review</h2><p><?= (int)$stats['pending_payments']>0?'Verify submitted payments to confirm bookings and issue e-tickets.':'You are caught up on payment reviews. Explore bookings or schedule your next flight.' ?></p></div>
    <a class="admin-button" href="/admin/payments<?= (int)$stats['pending_payments']>0 ? '?status=awaiting_review' : '' ?>"><?= (int)$stats['pending_payments']>0 ? 'Review payments' : 'View payments' ?> &rarr;</a>
</section>
<section class="stat-grid" aria-label="Operations totals">
    <?php foreach(['bookings'=>'Bookings','flights'=>'Flights','payments'=>'Payments','users'=>'Users'] as $key=>$label): ?>
    <article class="stat-card"><div class="dashboard-stat-label"><span><?= $label ?></span><span class="dashboard-card-icon"><?= $menuIcon($key==='users'?'passengers':$key) ?></span></div><strong><?= number_format((int)$stats[$key]) ?></strong><?php if($key==='users'): ?><small>Registered accounts</small><?php else: ?><a class="admin-stat-link" href="/admin/<?= $key ?>">Manage <?= strtolower($label) ?> &rarr;</a><?php endif ?></article>
    <?php endforeach ?>
</section>
<nav class="admin-quick-tasks" aria-label="Common admin tasks">
    <a href="/admin/flights#admin-create"><?= $menuIcon('flights') ?><span><strong>Schedule a flight</strong><small>Route, time, and fare</small></span><span aria-hidden="true">&nearr;</span></a>
    <a href="/admin/seats#admin-create"><?= $menuIcon('seats') ?><span><strong>Manage seats</strong><small>Layouts and availability</small></span><span aria-hidden="true">&nearr;</span></a>
    <a href="/admin/bookings"><?= $menuIcon('bookings') ?><span><strong>Find a reservation</strong><small>PNR, customer, or flight</small></span><span aria-hidden="true">&nearr;</span></a>
</nav>
<article class="admin-card revenue-card"><div><span class="dashboard-icon-heading"><span class="dashboard-card-icon"><?= $menuIcon('payments') ?></span><span>Verified payment volume</span></span><strong><?= $e(number_format((float)$stats['revenue'],2)) ?> PKR</strong></div><a class="admin-text-link" href="/admin/payments?status=verified">View verified payments &rarr;</a></article>
<section class="admin-card admin-records" data-admin-table aria-labelledby="recent-heading">
    <div class="card-heading"><div><h2 id="recent-heading">Recent bookings</h2><p>Latest reservations. Search by PNR, customer, or flight.</p></div><a class="admin-text-link" href="/admin/bookings">All bookings &rarr;</a></div>
    <div class="table-wrap"><table><caption class="admin-sr-only">Recent bookings</caption><thead><tr><th>PNR</th><th>Customer</th><th>Flight</th><th>Booked</th><th>Amount</th><th>Status</th></tr></thead><tbody>
    <?php foreach($stats['recent_bookings'] as $r): ?><tr data-row-status="<?= $e($r['status']) ?>"><td><strong><?= $e($r['pnr']) ?></strong></td><td><?= $e($r['full_name']) ?></td><td><?= $e($r['flight_number']) ?></td><td><?= $e($r['booked_at']) ?></td><td><?= $e($r['currency'].' '.number_format((float)$r['total_amount'],2)) ?></td><td><span class="badge <?= $e($r['status']) ?>"><?= $e(ucfirst($r['status'])) ?></span></td></tr><?php endforeach ?>
    <?php if(!$stats['recent_bookings']): ?><tr><td colspan="6" class="empty">No bookings yet. Reservations appear here when customers book a flight.</td></tr><?php endif ?>
    </tbody></table></div>
</section>
<details class="admin-analytics-details"><summary>View activity charts and reports</summary>
<?php require BASE_PATH.'/app/Views/admin/charts.php'; ?>
</details>
