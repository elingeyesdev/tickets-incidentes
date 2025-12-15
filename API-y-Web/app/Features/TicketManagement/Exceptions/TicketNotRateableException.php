<?php

namespace App\Features\TicketManagement\Exceptions;

class TicketNotRateableException extends \Exception
{
    protected $message = 'El ticket no puede ser calificado en su estado actual.';
}
