<?php

namespace App\Features\TicketManagement\Exceptions;

class TicketCannotBeDeletedException extends \RuntimeException
{
    protected $message = 'El ticket no puede ser eliminado en su estado actual.';
}
