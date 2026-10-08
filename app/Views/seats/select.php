<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$rows=[];$letters=[];
foreach($seats as $seat){if(preg_match('/^(\d+)([A-Z]+)$/i',$seat['seat_number'],$parts)){$row=(int)$parts[1];$letter=strtoupper($parts[2]);$rows[$row][$letter]=$seat;$letters[$letter]=$letter;}}
ksort($rows,SORT_NUMERIC);ksort($letters,SORT_STRING);$assignedCount=count(array_filter($passengers,static fn($p)=>$p['seat_id']!==null));
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/seats.css">
<div class="seat-page">
    <a class="seat-back" href="/seat-selection">← All pending bookings</a>
    <div class="seat-heading"><p class="customer-eyebrow">PNR <?= $e($booking['pnr']) ?> · <?= $e($booking['flight_number']) ?></p><h1>Choose your seats</h1><p><?= $e($booking['departure_code'].' → '.$booking['arrival_code']) ?> · <?= $e(date('D, M j, Y · H:i',strtotime($booking['departure_at']))) ?></p></div>
    <?php if($success): ?><div class="customer-notice" role="status"><?= $e($success) ?></div><?php endif ?><?php if($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
    <form class="seat-selection-form" method="post" action="/seat-selection/save" id="seat-selection-form">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
        <section class="seat-passenger-panel"><div class="seat-panel-heading"><div><h2>Passengers</h2><p>Select one seat for each passenger.</p></div><span><?= $assignedCount ?> / <?= count($passengers) ?> selected</span></div>
            <div class="seat-passenger-list">
            <?php foreach($passengers as $index=>$passenger): ?><label class="seat-passenger-row"><span><strong><?= $e($passenger['first_name'].' '.$passenger['last_name']) ?></strong><small>Passenger <?= $index+1 ?></small></span><select name="passengers[<?= (int)$passenger['id'] ?>]" required data-passenger-select><option value="">Choose a seat</option><?php foreach($seats as $seat): ?><?php if($seat['display_state']==='available'||$seat['display_state']==='assigned'): ?><option value="<?= (int)$seat['id'] ?>" <?= (int)($passenger['seat_id']??0)===(int)$seat['id']?'selected':'' ?>><?= $e($seat['seat_number'].' · '.ucwords(str_replace('_',' ',$seat['cabin_class'])).($seat['display_state']==='assigned'?' · selected on this booking':'')) ?></option><?php endif ?><?php endforeach ?></select></label><?php endforeach ?>
            </div>
        </section>
        <section class="seat-map-panel"><div class="seat-panel-heading"><div><h2>Seat layout</h2><p>Tap an available seat to assign it to the selected passenger.</p></div></div>
            <label class="active-passenger-label">Assign seat to<select id="active-passenger"><?php foreach($passengers as $index=>$passenger): ?><option value="<?= (int)$passenger['id'] ?>"><?= $e($passenger['first_name'].' '.$passenger['last_name']) ?></option><?php endforeach ?></select></label>
            <div class="seat-legend"><span><i class="available-key"></i>Available</span><span><i class="assigned-key"></i>On this booking</span><span><i class="occupied-key"></i>Occupied</span><span><i class="blocked-key"></i>Unavailable</span></div>
            <?php if(!$rows): ?><p class="seat-empty-inline">No seat layout has been generated for this flight.</p><?php else: ?><div class="aircraft-map"><div class="aircraft-label">FRONT OF AIRCRAFT</div><div class="seat-grid" style="--seat-columns:<?= count($letters) ?>"><div class="seat-column-labels"><span></span><?php foreach($letters as $letter): ?><b><?= $e($letter) ?></b><?php endforeach ?></div>
                <?php foreach($rows as $rowNumber=>$rowSeats): ?><div class="seat-row"><span class="row-number"><?= (int)$rowNumber ?></span><?php foreach($letters as $letter): $seat=$rowSeats[$letter]??null;if(!$seat): ?><span class="seat-gap"></span><?php else: $state=$seat['display_state'];?><button type="button" class="map-seat <?= $e($state) ?>" data-seat-id="<?= (int)$seat['id'] ?>" data-seat-number="<?= $e($seat['seat_number']) ?>" aria-label="Seat <?= $e($seat['seat_number']) ?>, <?= $e($state) ?>" <?= in_array($state,['occupied','blocked'],true)?'disabled':'' ?>><?= $e($letter) ?></button><?php endif ?><?php endforeach ?></div><?php endforeach ?>
            </div></div><?php endif ?>
            <p class="seat-map-help" id="seat-map-help" aria-live="polite">Choose a passenger above, then tap an available seat.</p>
        </section>
        <div class="seat-actions"><a href="/account?section=bookings">My bookings</a><button class="seat-button" type="submit">Save seat selection</button></div>
    </form>
</div>
<script>
const activePassenger=document.getElementById('active-passenger');
const seatSelects=[...document.querySelectorAll('[data-passenger-select]')];
const seatButtons=[...document.querySelectorAll('.map-seat')];
const seatHelp=document.getElementById('seat-map-help');
const refreshSeatMap=()=>seatButtons.forEach(button=>{const used=seatSelects.some(select=>select.value===button.dataset.seatId);button.classList.toggle('chosen',used);if(button.dataset.originalState==='available'&&used)button.classList.add('chosen')});
seatButtons.forEach(button=>{button.dataset.originalState=button.classList.contains('assigned')?'assigned':'available';button.addEventListener('click',()=>{const passenger=activePassenger?.value;const target=seatSelects.find(select=>select.name===`passengers[${passenger}]`);if(!target)return;const duplicate=seatSelects.some(select=>select!==target&&select.value===button.dataset.seatId);if(duplicate){seatHelp.textContent=`Seat ${button.dataset.seatNumber} is already assigned to another passenger.`;return}target.value=button.dataset.seatId;seatHelp.textContent=`Seat ${button.dataset.seatNumber} assigned to ${target.closest('.seat-passenger-row').querySelector('strong').textContent}.`;refreshSeatMap()})});
seatSelects.forEach(select=>select.addEventListener('change',()=>{const duplicate=seatSelects.some(other=>other!==select&&other.value!==''&&other.value===select.value);if(duplicate){select.value='';seatHelp.textContent='Each passenger must have a different seat.'}refreshSeatMap()}));
document.getElementById('seat-selection-form')?.addEventListener('submit',event=>{if(seatSelects.some(select=>!select.value)){event.preventDefault();seatHelp.textContent='Choose an available seat for every passenger before saving.'}});
refreshSeatMap();
</script>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
