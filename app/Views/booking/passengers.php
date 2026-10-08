<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$old=is_array($old)?array_values($old):[];$passengerCount=max(1,min(9,count($old)));$success=null;
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/booking.css">
<div class="booking-page">
    <a class="booking-back" href="/flights/details?id=<?= (int)$flight['id'] ?>">Back to flight details</a>
    <div class="booking-heading"><p class="customer-eyebrow">Step 1 of 2</p><h1>Passenger details</h1><p>Enter the details for everyone travelling. Seat selection and payment are not part of this step.</p></div>
    <?php require BASE_PATH.'/app/Views/partials/alerts.php'; ?>
    <section class="booking-flight-summary"><div><span><?= $e($flight['airline_name']) ?> · <?= $e($flight['flight_number']) ?></span><strong><?= $e($flight['departure_code']) ?> → <?= $e($flight['arrival_code']) ?></strong></div><div><span>Departure</span><strong><?= $e(date('D, M j · H:i',strtotime($flight['departure_at']))) ?></strong></div><div><span>Fare per passenger</span><strong><?= $e($flight['currency']) ?> <?= $e(number_format((float)$flight['base_fare'],2)) ?></strong></div></section>
    <form class="booking-form" method="post" action="/booking/review" id="passenger-form">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <label class="passenger-count-label" for="passenger-count">Number of passengers<select id="passenger-count" aria-controls="passenger-list"><?php for($i=1;$i<=9;$i++): ?><option value="<?= $i ?>" <?= $i===$passengerCount?'selected':'' ?>><?= $i ?> <?= $i===1?'passenger':'passengers' ?></option><?php endfor ?></select></label>
        <div id="passenger-list">
        <?php for($i=0;$i<9;$i++): $person=$old[$i]??[];$visible=$i<$passengerCount; ?>
            <fieldset class="passenger-fields" data-passenger="<?= $i ?>" <?= $visible?'':'hidden' ?>><legend>Passenger <?= $i+1 ?></legend>
                <label>First name<input name="passengers[<?= $i ?>][first_name]" maxlength="100" autocomplete="given-name" value="<?= $e($person['first_name']??'') ?>" <?= $visible?'required':'disabled' ?>></label>
                <label>Last name<input name="passengers[<?= $i ?>][last_name]" maxlength="100" autocomplete="family-name" value="<?= $e($person['last_name']??'') ?>" <?= $visible?'required':'disabled' ?>></label>
                <label>Date of birth<input type="date" name="passengers[<?= $i ?>][date_of_birth]" max="<?= $e(date('Y-m-d')) ?>" value="<?= $e($person['date_of_birth']??'') ?>" <?= $visible?'required':'disabled' ?>></label>
                <label>Gender<select name="passengers[<?= $i ?>][gender]" <?= $visible?'':'disabled' ?>><?php foreach(['unspecified'=>'Prefer not to say','female'=>'Female','male'=>'Male','other'=>'Other'] as $value=>$label): ?><option value="<?= $value ?>" <?= ($person['gender']??'unspecified')===$value?'selected':'' ?>><?= $label ?></option><?php endforeach ?></select></label>
                <label>Passport number <span class="optional-label">Optional</span><input name="passengers[<?= $i ?>][passport_number]" maxlength="30" value="<?= $e($person['passport_number']??'') ?>" <?= $visible?'':'disabled' ?>></label>
                <label>Passport country <span class="optional-label">Optional · two-letter code</span><input name="passengers[<?= $i ?>][passport_country]" maxlength="2" pattern="[A-Za-z]{2}" value="<?= $e($person['passport_country']??'') ?>" <?= $visible?'':'disabled' ?>></label>
            </fieldset>
        <?php endfor ?>
        </div>
        <div class="booking-actions"><a href="/flights">Change flight</a><button class="booking-button" type="submit">Review booking</button></div>
    </form>
</div>
<script>
const passengerCount=document.getElementById('passenger-count');
passengerCount?.addEventListener('change',()=>document.querySelectorAll('[data-passenger]').forEach((fieldset,index)=>{const active=index<Number(passengerCount.value);fieldset.hidden=!active;fieldset.querySelectorAll('input,select').forEach(input=>{input.disabled=!active;if(input.name.endsWith('[first_name]')||input.name.endsWith('[last_name]')||input.name.endsWith('[date_of_birth]'))input.required=active})}));
</script>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
