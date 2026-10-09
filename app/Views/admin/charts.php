<?php
declare(strict_types=1);
$chartDefinitions=[
    'trends'=>['Booking Trends','Bookings created from 8 October 2026 onward.','No bookings in this period.','Bookings'],
    'revenue'=>['Revenue Overview','Verified PKR payments by payment date · From 8 October 2026 onward.','No verified PKR payments in this period.','Revenue (PKR)'],
    'bookingStatuses'=>['Booking Status Distribution','Current status of all bookings.','No bookings yet.','Bookings'],
    'paymentStatuses'=>['Payment Status Overview','Current status of all payment submissions.','No payment submissions yet.','Payments'],
];
$chartIcons=['trends'=>'trends','revenue'=>'bars','bookingStatuses'=>'distribution','paymentStatuses'=>'payments'];
?>
<link rel="stylesheet" href="/assets/css/admin-charts.css">
<section class="dashboard-analytics" aria-labelledby="analytics-heading">
    <div class="dashboard-analytics-heading"><div><h2 id="analytics-heading">Travel activity</h2><p id="dashboard-refresh-status" role="status" aria-live="polite"><?= $charts===null?'Chart data is temporarily unavailable. Try refreshing.':'Live data · Refreshes every 30 seconds.' ?></p></div><button class="small-button" type="button" id="refresh-dashboard-charts">Refresh charts</button></div>
    <div class="dashboard-chart-grid">
    <?php foreach($chartDefinitions as $key=>[$heading,$description,$empty,$metric]): $series=$charts===null?['labels'=>[],'values'=>[]]:($key==='trends'?$charts['trends']['daily']:$charts[$key]);$hasData=array_sum($series['values'])>0; ?>
        <article class="admin-card dashboard-chart-card" data-dashboard-chart="<?= $e($key) ?>">
            <div class="dashboard-chart-heading"><div><h3 class="dashboard-icon-heading" id="chart-heading-<?= $e($key) ?>"><span class="dashboard-card-icon"><?= $menuIcon($chartIcons[$key]) ?></span><span><?= $e($heading) ?></span></h3><p data-chart-description><?= $e($description) ?></p></div>
            <?php if($key==='trends'): ?><label class="dashboard-trend-filter">Group by<select id="booking-trend-period"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></label><?php endif ?></div>
            <div class="dashboard-chart-frame"><canvas id="chart-<?= $e($key) ?>" role="img" aria-labelledby="chart-heading-<?= $e($key) ?>" hidden><?= $e($heading) ?>. See the data table below.</canvas><p class="dashboard-chart-empty" data-chart-empty data-empty-message="<?= $e($empty) ?>"><?= $charts===null?'Chart data is temporarily unavailable.':($hasData?'Loading chart…':$empty) ?></p></div>
            <details class="dashboard-chart-table"><summary>View data</summary><div class="dashboard-data-scroll"><table><thead><tr><th><?= $key==='trends'||$key==='revenue'?'Period':($key==='routes'?'Route':'Status') ?></th><th><?= $e($metric) ?></th></tr></thead><tbody data-chart-table>
            <?php foreach($series['labels'] as $i=>$label): ?><tr><td><?= $e($label) ?></td><td><?= $key==='revenue'?'PKR '.$e(number_format($series['values'][$i],2)):(int)$series['values'][$i] ?></td></tr><?php endforeach ?>
            <?php if(!$series['labels']): ?><tr><td colspan="2">No data available.</td></tr><?php endif ?>
            </tbody></table></div></details>
        </article>
    <?php endforeach ?>
    </div>
    <noscript><p>Enable JavaScript to view interactive charts. The data tables show the latest figures from this page load.</p></noscript>
</section>
<script type="application/json" id="dashboard-chart-data"><?= json_encode($charts,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script defer src="/assets/js/admin-charts.js"></script>
