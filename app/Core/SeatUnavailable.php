<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class SeatUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A selected seat is no longer available. Please choose another seat.');
    }
}
