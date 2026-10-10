<?php
declare(strict_types=1);
$help = [
    'dashboard'=>['Start here','Set up your directory, schedule a flight, then create its seats. For daily work, review payments and find bookings using the shortcuts below.'],
    'airlines'=>['Adding or updating an airline','Use the airline’s official codes. Select Edit beside an existing airline, change its details, then Save changes. Inactive airlines are excluded from the new-flight selector.'],
    'airports'=>['Adding or updating an airport','Use the airport’s official codes and local timezone. Select Edit to change one record. Inactive airports are excluded from the new-flight selector.'],
    'flights'=>['Make a flight ready for booking','Choose an active airline and two different active airports. Save the flight, then choose Create seats in its row. Use Edit for schedule, fare, or status changes.'],
    'seats'=>['Understanding seat availability','Available means customers can choose the seat. Blocked and unavailable seats cannot be chosen. A bulk update affects only seats without a reservation; existing reservations are preserved.'],
    'bookings'=>['Find a booking and choose the next step','Search by booking reference (PNR), customer, or flight. Open View passengers for traveler details. Use Find payment to review payment first: confirmation requires a verified payment and assigned seats. Cancelling releases seats and voids tickets; completed bookings require a past flight.'],
    'payments'=>['Review a payment safely','Choose Awaiting review, find the booking reference, and compare the amount, sender, and transaction reference against your payment records. Download proof when attached. Verify payment confirms the booking and prepares tickets; Reject payment asks the customer to submit corrected details.'],
][$section];
?>
<details class="admin-workflow-help"><summary>Help: <?= $e($help[0]) ?></summary><p><?= $e($help[1]) ?></p></details>
