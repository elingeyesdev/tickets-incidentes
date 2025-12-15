<?php

namespace App\Features\TicketManagement\Exceptions;

class TicketNotFoundException extends \Exception
{
    protected $message = 'Ticket not found.';
}
