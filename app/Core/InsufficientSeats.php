<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class InsufficientSeats extends RuntimeException
{
    public function __construct(public readonly int $availableSeats)
    {
        parent::__construct('There are not enough available seats for the passenger count. Please search again.');
    }
}
