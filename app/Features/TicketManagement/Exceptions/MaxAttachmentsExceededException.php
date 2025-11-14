<?php

namespace App\Features\TicketManagement\Exceptions;

class MaxAttachmentsExceededException extends \Exception
{
    protected $message = 'Se ha alcanzado el límite máximo de archivos adjuntos.';
}
