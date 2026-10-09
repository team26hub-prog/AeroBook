<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$rows=[];$letters=[];
foreach($seats as $seat){if(preg_match('/^(\d+)([A-Z]+)$/i',$seat['seat_number'],$parts)){$row=(int)$parts[1];$letter=strtoupper($parts[2]);$rows[$row][$letter]=$seat;$letters[$letter]=$letter;}}
ksort($rows,SORT_NUMERIC);ksort($letters,SORT_STRING);
$selectableIds=array_map('intval',array_column(array_filter($seats,static fn($s)=>in_array($s['display_state'],['available','assigned'],true)),'id'));
$assignedCount=count(array_filter($passengers,static fn($p)=>$p['seat_id']!==null&&in_array((int)$p['seat_id'],$selectableIds,true)));
$letterCount=count($letters);$leftCount=(int)ceil($letterCount/2);$rightCount=$letterCount-$leftCount;
$seatTracks='minmax(20px,.4fr) repeat('.max(1,$leftCount).',minmax(26px,1fr))'.($rightCount?' minmax(20px,.5fr) repeat('.$rightCount.',minmax(26px,1fr))':'');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/seats.css">
<link rel="stylesheet" href="/assets/css/seat-selection.css">
<div class="seat-page">
    <a class="seat-back" href="/seat-selection">&larr; All pending bookings</a>
    <div class="seat-heading"><p class="customer-eyebrow">Plan your journey</p><h1>Choose your seats</h1><p>Assign a different seat to every passenger, then continue to payment.</p></div>
    <?php require BASE_PATH.'/app/Views/partials/alerts.php'; ?>
    <ol class="seat-workflow" aria-label="Booking steps"><li>Booking created</li><li class="current" aria-current="step">Select seats</li><li>Payment</li><li>E-ticket after verification</li></ol>
    <section class="seat-booking-summary" aria-labelledby="seat-summary-heading">
        <div><p class="customer-eyebrow"><?= $e($booking['airline_name']) ?> &middot; <?= $e($booking['flight_number']) ?></p><h2 id="seat-summary-heading"><?= $e($booking['departure_code']) ?> &rarr; <?= $e($booking['arrival_code']) ?></h2><p><?= $e($booking['departure_city']) ?> to <?= $e($booking['arrival_city']) ?></p></div>
        <dl><div><dt>Booking reference</dt><dd><?= $e($booking['pnr']) ?></dd></div><div><dt>Departure</dt><dd><?= $e(date('d M Y, H:i',strtotime($booking['departure_at']))) ?></dd></div><div><dt>Arrival</dt><dd><?= $e(date('d M Y, H:i',strtotime($booking['arrival_at']))) ?></dd></div><div><dt>Passengers</dt><dd><?= count($passengers) ?></dd></div><div><dt>Booking total</dt><dd><?= $e($booking['currency']) ?> <?= $e(number_format((float)$booking['total_amount'],2)) ?></dd></div><div><dt>Status</dt><dd><?= $e(ucfirst($booking['status'])) ?></dd></div></dl>
    </section>
    <form class="seat-selection-form" method="post" action="/seat-selection/save" id="seat-selection-form">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
        <section class="seat-passenger-panel"><div class="seat-panel-heading"><div><h2>Passenger assignments</h2><p>Use the dropdowns or choose a passenger and tap the seat map.</p></div><span id="seat-selection-progress" role="status" aria-live="polite"><?= $assignedCount ?> of <?= count($passengers) ?> passengers have seats</span></div>
            <div class="seat-progress-track" aria-hidden="true"><span id="seat-progress-fill" style="width:<?= count($passengers)?round($assignedCount/count($passengers)*100):0 ?>%"></span></div>
            <div class="seat-passenger-list">
            <?php foreach($passengers as $index=>$passenger): ?><label class="seat-passenger-row"><span><strong><?= $e($passenger['first_name'].' '.$passenger['last_name']) ?></strong><small>Passenger <?= $index+1 ?> &middot; <span data-assignment-label><?= $e(in_array((int)($passenger['seat_id']??0),$selectableIds,true)?'Seat '.$passenger['seat_number']:'Not selected') ?></span></small></span><select name="passengers[<?= (int)$passenger['id'] ?>]" required data-passenger-select data-passenger-id="<?= (int)$passenger['id'] ?>"><option value="">Choose a seat</option><?php foreach($seats as $seat): ?><?php if(in_array($seat['display_state'],['available','assigned'],true)): ?><option value="<?= (int)$seat['id'] ?>" data-seat-number="<?= $e($seat['seat_number']) ?>" <?= (int)($passenger['seat_id']??0)===(int)$seat['id']?'selected':'' ?>><?= $e($seat['seat_number'].' - '.ucwords(str_replace('_',' ',$seat['cabin_class']))) ?></option><?php endif ?><?php endforeach ?></select></label><?php endforeach ?>
            </div>
        </section>
        <section class="seat-map-panel"><div class="seat-panel-heading"><div><h2>Seat map</h2><p>Seat numbers and cabin classes are shown below. Reserved and blocked seats cannot be selected.</p></div></div>
            <label class="active-passenger-label">Assign seat to<select id="active-passenger"><?php foreach($passengers as $passenger): ?><option value="<?= (int)$passenger['id'] ?>"><?= $e($passenger['first_name'].' '.$passenger['last_name']) ?></option><?php endforeach ?></select></label>
            <div class="seat-legend"><span><i class="available-key"></i>Available</span><span><i class="selected-key"></i>Selected</span><span><i class="occupied-key"></i>Reserved</span><span><i class="blocked-key"></i>Blocked</span></div>
            <?php if(!$rows): ?><p class="seat-empty-inline">No seat layout is available for this flight. Please return to your bookings or try again later.</p><?php else: ?>
            <div class="aircraft-map"><div class="aircraft-label">FRONT OF AIRCRAFT</div><div class="seat-grid aircraft-seat-grid" style="--seat-tracks:<?= $e($seatTracks) ?>">
                <div class="seat-column-labels"><span></span><?php foreach(array_values($letters) as $column=>$letter): ?><?php if($rightCount&&$column===$leftCount): ?><span class="seat-aisle-heading" aria-hidden="true">Aisle</span><?php endif ?><b><?= $e($letter) ?></b><?php endforeach ?></div>
                <?php $previousCabin=null;foreach($rows as $rowNumber=>$rowSeats): $cabins=array_unique(array_column($rowSeats,'cabin_class'));$cabinLabel=implode(' / ',array_map(static fn($c)=>ucwords(str_replace('_',' ',$c)),$cabins)); ?>
                    <?php if($cabinLabel!==$previousCabin): $previousCabin=$cabinLabel; ?><div class="seat-cabin-label"><?= $e($cabinLabel) ?></div><?php endif ?>
                    <div class="seat-row"><span class="row-number"><?= (int)$rowNumber ?></span><?php foreach(array_values($letters) as $column=>$letter): ?><?php if($rightCount&&$column===$leftCount): ?><span class="seat-aisle" aria-hidden="true"></span><?php endif ?><?php $seat=$rowSeats[$letter]??null;if(!$seat): ?><span class="seat-gap" aria-hidden="true"></span><?php else: $state=$seat['display_state']; ?><button type="button" class="map-seat <?= $e($state) ?>" data-seat-id="<?= (int)$seat['id'] ?>" data-seat-number="<?= $e($seat['seat_number']) ?>" data-seat-state="<?= $e($state) ?>" data-cabin="<?= $e(ucwords(str_replace('_',' ',$seat['cabin_class']))) ?>" aria-pressed="<?= $state==='assigned'?'true':'false' ?>" aria-label="<?= $e('Seat '.$seat['seat_number'].', '.ucwords(str_replace('_',' ',$seat['cabin_class'])).', '.($state==='occupied'?'reserved':$state)) ?>" <?= in_array($state,['occupied','blocked'],true)?'disabled':'' ?>><?= $e($seat['seat_number']) ?></button><?php endif ?><?php endforeach ?></div>
                <?php endforeach ?>
            </div></div><?php endif ?>
            <p class="seat-map-help" id="seat-map-help" aria-live="polite">Choose a passenger above, then tap an available seat.</p>
        </section>
        <div class="seat-actions"><a href="/account?section=bookings">My bookings</a><button class="seat-button" id="save-seat-selection" type="submit">Save seats and continue to payment</button></div>
        <p class="seat-save-note">Seat choices are saved when you continue. Availability is checked again before saving.</p>
        <noscript><p class="seat-save-note">Choose a seat from each passenger's dropdown, then save to continue.</p></noscript>
    </form>
</div>
<script defer src="/assets/js/seat-selection.js"></script>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
