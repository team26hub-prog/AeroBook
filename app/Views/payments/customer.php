<?php
declare(strict_types=1);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$latest=$history[0]??null;
$allSeatsSelected=(int)$booking['passenger_count']>0&&(int)$booking['assigned_count']===(int)$booking['passenger_count'];
$canSubmit=$booking['status']==='pending'&&$allSeatsSelected&&(!$latest||!in_array($latest['status'],['pending','submitted','verified'],true));
$rejected=$latest&&$latest['status']==='rejected';
$defaultDate=date('Y-m-d\TH:i');
$minimumDate=date('Y-m-d\TH:i',strtotime($booking['booked_at']));
$formMethod=$old['method']??($rejected?$latest['method']:'bank_transfer');
$formSender=$old['sender_name']??($rejected?$latest['sender_name']:'');
$formReference=$old['transaction_reference']??'';
$formPaidAt=$old['paid_at']??$defaultDate;
$formAmount=$old['amount']??($rejected?$latest['amount']:$booking['total_amount']);
$formRemarks=$old['remarks']??($rejected?($latest['remarks']??''):'');
require BASE_PATH.'/app/Views/layouts/customer-start.php';
?>
<link rel="stylesheet" href="/assets/css/payments.css">
<div class="payment-page">
    <a class="payment-back" href="/payments">&larr; All payments</a>
    <div class="payment-heading"><p class="customer-eyebrow">PNR <?= $e($booking['pnr']) ?> &middot; <?= $e($booking['flight_number']) ?></p><h1>Payment details</h1><p><?= $e($booking['departure_code'].' &rarr; '.$booking['arrival_code']) ?> &middot; <?= $e(date('D, M j, Y H:i',strtotime($booking['departure_at']))) ?></p></div>
    <?php if($success): ?><div class="customer-notice" role="status"><?= $e($success) ?></div><?php endif ?><?php if($errors): ?><div class="alert alert-error" role="alert"><ul><?php foreach($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach ?></ul></div><?php endif ?>
    <section class="payment-summary"><div class="payment-summary-heading"><div><p class="customer-eyebrow">Booking summary</p><h2><?= $e($booking['airline_name'].' '.$booking['flight_number']) ?></h2></div><span class="payment-status <?= $e($latest['status']??$booking['status']) ?>"><?= $e($latest?ucwords(str_replace('_',' ',$latest['status'])):ucwords($booking['status'])) ?></span></div>
        <div class="payment-summary-grid"><div><span>Route</span><strong><?= $e($booking['departure_city'].' ('.$booking['departure_code'].') &rarr; '.$booking['arrival_city'].' ('.$booking['arrival_code'].')') ?></strong></div><div><span>Departure</span><strong><?= $e(date('D, M j, Y H:i',strtotime($booking['departure_at']))) ?></strong></div><div><span>PNR</span><strong><?= $e($booking['pnr']) ?></strong></div><div><span>Total due</span><strong><?= $e($booking['currency']) ?> <?= $e(number_format((float)$booking['total_amount'],2)) ?></strong></div></div>
        <div class="payment-passengers"><h3>Passengers and seats</h3><?php foreach($passengers as $person): ?><p><span><?= $e($person['first_name'].' '.$person['last_name']) ?></span><strong><?= $e($person['seat_number']??'Seat not selected') ?></strong></p><?php endforeach ?></div>
    </section>
    <?php if($latest&&in_array($latest['status'],['pending','submitted'],true)): ?><div class="payment-review-notice"><strong>Payment submitted &mdash; pending admin verification</strong><span>Your booking will be updated after an administrator reviews these details. You do not need to submit again while this payment is under review.</span></div>
    <?php elseif($latest&&$latest['status']==='verified'): ?><div class="payment-review-notice verified"><strong>Payment verified</strong><span>Your booking is now <?= $e($booking['status']) ?>. Payment verification is complete. <a href="/account?section=tickets">View your e-ticket</a></span></div>
    <?php elseif($latest&&$latest['status']==='rejected'): ?><div class="payment-review-notice rejected"><strong>Payment rejected</strong><span>Review the previous submission below and enter corrected payment details to resubmit.</span></div>
    <?php elseif($booking['status']!=='pending'): ?><div class="payment-review-notice"><strong>Booking <?= $e(ucwords($booking['status'])) ?></strong><span>This booking is not accepting a new payment submission.</span></div>
    <?php endif ?>
    <?php if(!$allSeatsSelected&&$booking['status']==='pending'): ?><section class="payment-empty"><h2>Seat selection required</h2><p>Choose and save one seat for every passenger before submitting payment.</p><a class="payment-button" href="/seat-selection?booking_id=<?= (int)$booking['id'] ?>">Continue to seat selection</a></section>
    <?php elseif($canSubmit): ?>
        <section class="payment-methods"><h2>Manual payment methods</h2><p>Use payment instructions and account details supplied by AeroBook. Include your PNR in the transfer note where possible.</p><div class="payment-method-grid"><article><h3>Bank transfer</h3><p>Transfer the total due to the AeroBook bank account shared with you. Enter the account holder name and bank transaction reference.</p></article><article><h3>Bank deposit</h3><p>Deposit the total due using the account details supplied by AeroBook. Enter the depositor name and deposit reference.</p></article><article><h3>Mobile wallet</h3><p>Send the total due to the official AeroBook wallet details provided to you. Enter the wallet sender name and transaction ID.</p></article><article><h3>Cash / other</h3><p>Pay through an AeroBook authorized counter or method and enter the receipt or payment reference number.</p></article></div><p class="payment-instruction-note">For current destination account or wallet details, use the instructions provided by AeroBook support. Never send payment to unverified account details.</p></section>
        <section class="payment-form-card"><div class="payment-form-heading"><h2><?= $rejected?'Resubmit payment details':'Submit payment details' ?></h2><p>Admin verification is required before the booking is confirmed.</p></div>
            <form class="payment-form" method="post" action="/payments/submit"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                <label>Payment method<select name="method" required><?php foreach(['bank_transfer'=>'Bank transfer','bank_deposit'=>'Bank deposit','mobile_wallet'=>'Mobile wallet','cash'=>'Cash / counter','other'=>'Other manual method'] as $value=>$label): ?><option value="<?= $e($value) ?>" <?= $formMethod===$value?'selected':'' ?>><?= $e($label) ?></option><?php endforeach ?></select></label>
                <label>Sender / account name<input name="sender_name" maxlength="150" required autocomplete="name" value="<?= $e($formSender) ?>"></label>
                <label>Transaction / reference ID<input name="transaction_reference" maxlength="100" required autocomplete="off" placeholder="Enter the ID shown by your bank or wallet" value="<?= $e($formReference) ?>"></label>
                <label>Payment date and time<input type="datetime-local" name="paid_at" required min="<?= $e($minimumDate) ?>" max="<?= $e($defaultDate) ?>" value="<?= $e($formPaidAt) ?>"></label>
                <label>Paid amount (<?= $e($booking['currency']) ?>)<input name="amount" type="number" min="0.01" max="99999999.99" step="0.01" required value="<?= $e($formAmount) ?>"></label>
                <label class="payment-remarks">Remarks <span>(optional)</span><textarea name="remarks" rows="3" maxlength="2000" placeholder="Add any details that may help verify this payment"><?= $e($formRemarks) ?></textarea></label>
                <button class="payment-button" type="submit"><?= $rejected?'Resubmit for verification':'Submit for verification' ?></button>
            </form>
        </section>
    <?php endif ?>
    <?php if($history): ?><section class="payment-history"><h2>Payment submissions</h2><?php foreach($history as $payment): ?><article class="payment-history-card"><div class="payment-history-heading"><strong><?= $e(ucwords(str_replace('_',' ',$payment['method']))) ?> &middot; <?= $e($payment['currency']) ?> <?= $e(number_format((float)$payment['amount'],2)) ?></strong><span class="payment-status <?= $e($payment['status']) ?>"><?= $e(ucwords($payment['status'])) ?></span></div><dl><div><dt>Sender / account</dt><dd><?= $e($payment['sender_name']) ?></dd></div><div><dt>Reference</dt><dd><?= $e($payment['transaction_reference']??'—') ?></dd></div><div><dt>Paid at</dt><dd><?= $e($payment['paid_at']?date('M j, Y H:i',strtotime($payment['paid_at'])):'—') ?></dd></div><?php if($payment['remarks']): ?><div><dt>Remarks</dt><dd><?= nl2br($e($payment['remarks'])) ?></dd></div><?php endif ?></dl><small>Submitted <?= $e(date('M j, Y H:i',strtotime($payment['created_at']))) ?></small></article><?php endforeach ?></section><?php endif ?>
</div>
<?php require BASE_PATH.'/app/Views/layouts/customer-end.php'; ?>
