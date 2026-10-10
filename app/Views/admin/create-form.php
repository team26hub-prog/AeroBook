<?php
declare(strict_types=1);
if (!in_array($section, ['airlines', 'airports', 'flights', 'seats'], true)) return;
$createTitle = ['airlines'=>'Add an airline','airports'=>'Add an airport','flights'=>'Schedule a flight','seats'=>'Create a seat layout'][$section];
$missingFlightSetup = $section === 'flights' && (!$options['airlines'] || count($options['airports']) < 2);
$missingSeats = $section === 'seats' && !$options['flights'];
?>
<details class="admin-card admin-create admin-create-details" id="admin-create" <?= !$rows || $errors ? 'open' : '' ?>>
    <summary><span><?= $e($createTitle) ?></span><span class="admin-summary-hint">Open or close form</span></summary>
    <div class="admin-create-body">
    <?php if ($missingFlightSetup): ?>
        <p class="admin-prerequisite" role="status">Before scheduling a flight, you need an active airline and two different active airports. <a href="/admin/airlines#admin-create">Add an airline</a> or <a href="/admin/airports#admin-create">add an airport</a>, then return here.</p>
    <?php elseif ($missingSeats): ?>
        <p class="admin-prerequisite" role="status">Schedule a flight before creating its seats. <a href="/admin/flights#admin-create">Schedule your first flight</a>.</p>
    <?php endif ?>
    <p class="admin-form-instructions">Fields marked <span aria-hidden="true">*</span> are required. Your changes are saved only when you submit this form.</p>
    <form class="admin-form wide-form admin-guided-form" method="post" action="<?= $post ?>">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <input type="hidden" name="kind" value="<?= $section ?>">
        <input type="hidden" name="return_to" value="<?= $path($section) ?>">
        <?php if (in_array($section, ['airlines','airports'], true)): ?>
        <fieldset class="admin-form-group"><legend><?= $section === 'airlines' ? 'Airline details' : 'Airport details' ?></legend>
            <label><?= $section === 'airlines' ? 'Airline name' : 'Airport name' ?><input name="name" maxlength="<?= $section === 'airlines' ? 150 : 180 ?>" required placeholder="<?= $section === 'airlines' ? 'Example: AeroBook Air' : 'Example: Jinnah International Airport' ?>"></label>
            <label>IATA code<input name="iata_code" maxlength="<?= $section === 'airlines' ? 2 : 3 ?>" required aria-describedby="iata-help"><small id="iata-help">The <?= $section === 'airlines' ? 'two-character airline code, such as AB.' : 'three-letter airport code, such as KHI.' ?></small></label>
            <label>ICAO code (optional)<input name="icao_code" maxlength="<?= $section === 'airlines' ? 3 : 4 ?>" aria-describedby="icao-help"><small id="icao-help">The <?= $section === 'airlines' ? 'three-letter airline code.' : 'four-letter airport code, such as OPKC.' ?> Leave blank if unknown.</small></label>
            <?php if ($section === 'airports'): ?>
            <label>City<input name="city" maxlength="120" required placeholder="Example: Karachi"></label>
            <label>Country<input name="country" maxlength="120" required placeholder="Example: Pakistan"></label>
            <label>Local timezone<input name="timezone" maxlength="64" list="admin-timezones" placeholder="Asia/Karachi" required aria-describedby="timezone-help"><small id="timezone-help">Choose the airport's local timezone. Start typing a city or region.</small></label>
            <datalist id="admin-timezones"><?php foreach (DateTimeZone::listIdentifiers() as $timezone): ?><option value="<?= $e($timezone) ?>"></option><?php endforeach ?></datalist>
            <?php endif ?>
            <label>Availability<select name="status" aria-describedby="availability-help"><option value="active">Active</option><option value="inactive">Inactive</option></select><small id="availability-help">Active records can be selected when scheduling new flights.</small></label>
        </fieldset>
        <?php elseif ($section === 'flights'): ?>
        <fieldset class="admin-form-group"><legend>1. Choose the airline and route</legend>
            <label>Airline<select name="airline_id" required><option value="" selected disabled>Choose an airline</option><?php foreach ($options['airlines'] as $item): ?><option value="<?= (int)$item['id'] ?>"><?= $e($item['name']) ?></option><?php endforeach ?></select></label>
            <label>Flight number<input name="flight_number" maxlength="10" placeholder="Example: AB101" required></label>
            <?php foreach (['departure_airport_id'=>'Departure airport','arrival_airport_id'=>'Arrival airport'] as $name=>$label): ?>
            <label><?= $label ?><select name="<?= $name ?>" required><option value="" selected disabled>Choose an airport</option><?php foreach ($options['airports'] as $item): ?><option value="<?= (int)$item['id'] ?>"><?= $e($item['city'].' — '.$item['iata_code'].' · '.$item['name']) ?></option><?php endforeach ?></select></label>
            <?php endforeach ?>
        </fieldset>
        <fieldset class="admin-form-group"><legend>2. Set the schedule and fare</legend>
            <p class="admin-group-help" id="schedule-help">All schedule times use <?= $e($GLOBALS['config']['app']['timezone'] ?? date_default_timezone_get()) ?>. Arrival must be later than departure.</p>
            <label>Departure date and time<input type="datetime-local" name="departure_at" required aria-describedby="schedule-help"></label>
            <label>Arrival date and time<input type="datetime-local" name="arrival_at" required aria-describedby="schedule-help"></label>
            <label>Fare per passenger<input name="base_fare" type="number" min="0.01" step="0.01" required aria-describedby="fare-help"><small id="fare-help">Enter the price for one passenger, before the booking total is calculated.</small></label>
            <label>Currency code<input name="currency" value="PKR" maxlength="3" required aria-describedby="currency-help"><small id="currency-help">Three-letter code, such as PKR or USD.</small></label>
            <label>Flight status<select name="status"><?php foreach (['scheduled','boarding','departed','completed','cancelled'] as $status): ?><option value="<?= $status ?>"><?= ucfirst($status) ?></option><?php endforeach ?></select></label>
        </fieldset>
        <p class="admin-form-next">After saving: open <strong>Seats &amp; availability</strong> and create the flight's seat layout. A flight needs seats before customers can book it.</p>
        <?php else: ?>
        <fieldset class="admin-form-group"><legend>1. Choose a flight and cabin</legend>
            <label>Flight<select name="flight_id" required><option value="" selected disabled>Choose a flight</option><?php foreach ($options['flights'] as $item): ?><option value="<?= (int)$item['id'] ?>"><?= $e($item['flight_number']) ?></option><?php endforeach ?></select></label>
            <label>Cabin class<select name="cabin_class"><?php foreach (['economy'=>'Economy','premium_economy'=>'Premium economy','business'=>'Business','first'=>'First class'] as $value=>$label): ?><option value="<?= $value ?>"><?= $label ?></option><?php endforeach ?></select></label>
            <label>Number of rows<input name="rows" type="number" min="1" max="100" value="20" required aria-describedby="rows-help"><small id="rows-help">For example, 20 rows with letters A–F creates 120 seat positions.</small></label>
        </fieldset>
        <fieldset class="seat-letters"><legend>2. Choose seat letters for each row</legend><?php foreach (range('A','K') as $letter): ?><label><input type="checkbox" name="letters[]" value="<?= $letter ?>" <?= strpos('ABCDEF',$letter)!==false ? 'checked' : '' ?>><?= $letter ?></label><?php endforeach ?></fieldset>
        <p class="admin-form-next">Existing seat numbers and reservations are preserved. Check the availability list below after creating the layout.</p>
        <?php endif ?>
        <button class="admin-button" type="submit" <?= $missingFlightSetup || $missingSeats ? 'disabled' : '' ?>><?= $e(['airlines'=>'Save airline','airports'=>'Save airport','flights'=>'Save flight','seats'=>'Create seat layout'][$section]) ?></button>
    </form>
    </div>
</details>
