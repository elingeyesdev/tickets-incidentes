<?php

declare(strict_types=1);

namespace App\Features\Reports\Http\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Ticket Chat Export Controller
 * 
 * Exports ticket conversation to TXT format.
 */
class TicketChatExportController
{
    public function exportTxt(string $ticketCode)
    {
        // Verificar autenticación
        $user = JWTHelper::getAuthenticatedUser();
        if (!$user) {
            abort(401, 'No autenticado');
        }

        // Buscar el ticket
        $ticket = Ticket::where('ticket_code', $ticketCode)
            ->with(['creator.profile', 'ownerAgent.profile', 'company', 'category'])
            ->first();

        if (!$ticket) {
            abort(404, 'Ticket no encontrado');
        }

        // Obtener respuestas
        $responses = TicketResponse::where('ticket_id', $ticket->id)
            ->with(['author.profile'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Construir el contenido TXT
        $content = $this->buildTxtContent($ticket, $responses);

        // Generar nombre de archivo
        $filename = 'chat_' . $ticketCode . '_' . now()->format('Y-m-d_His') . '.txt';

        // Retornar como descarga
        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function buildTxtContent(Ticket $ticket, $responses): string
    {
        $lines = [];
        
        // Header
        $lines[] = str_repeat('=', 70);
        $lines[] = '                    EXPORTACIÓN DE CONVERSACIÓN';
        $lines[] = str_repeat('=', 70);
        $lines[] = '';
        
        // Información del Ticket
        $lines[] = 'INFORMACIÓN DEL TICKET';
        $lines[] = str_repeat('-', 40);
        $lines[] = 'Código:      ' . $ticket->ticket_code;
        $lines[] = 'Título:      ' . $ticket->title;
        $lines[] = 'Estado:      ' . $this->translateStatus($ticket->status?->value ?? $ticket->status);
        $lines[] = 'Prioridad:   ' . $this->translatePriority($ticket->priority?->value ?? $ticket->priority);
        $lines[] = 'Categoría:   ' . ($ticket->category?->name ?? 'Sin categoría');
        $lines[] = 'Empresa:     ' . ($ticket->company?->name ?? 'N/A');
        $lines[] = 'Creado por:  ' . ($ticket->creator?->profile?->display_name ?? $ticket->creator?->email ?? 'N/A');
        $lines[] = 'Agente:      ' . ($ticket->ownerAgent?->profile?->display_name ?? $ticket->ownerAgent?->email ?? 'Sin asignar');
        $lines[] = 'Fecha:       ' . ($ticket->created_at ? $ticket->created_at->format('d/m/Y H:i:s') : 'N/A');
        $lines[] = '';
        
        // Descripción
        $lines[] = 'DESCRIPCIÓN INICIAL';
        $lines[] = str_repeat('-', 40);
        $lines[] = wordwrap($ticket->description ?? 'Sin descripción', 70);
        $lines[] = '';
        
        // Conversación
        $lines[] = str_repeat('=', 70);
        $lines[] = '                         CONVERSACIÓN';
        $lines[] = str_repeat('=', 70);
        $lines[] = '';
        
        if ($responses->isEmpty()) {
            $lines[] = '(No hay mensajes en esta conversación)';
        } else {
            foreach ($responses as $index => $response) {
                $authorName = $response->author?->profile?->display_name 
                    ?? $response->author?->email 
                    ?? 'Usuario';
                
                $dateTime = $response->created_at 
                    ? $response->created_at->format('d/m/Y H:i:s') 
                    : 'Fecha desconocida';
                
                $lines[] = '┌' . str_repeat('─', 68) . '┐';
                $lines[] = '│ ' . str_pad($authorName, 45) . ' ' . $dateTime . ' │';
                $lines[] = '├' . str_repeat('─', 68) . '┤';
                
                // Wrap content
                $content = $response->content ?? '';
                $wrappedContent = wordwrap($content, 66, "\n", true);
                foreach (explode("\n", $wrappedContent) as $line) {
                    $lines[] = '│ ' . str_pad($line, 66) . ' │';
                }
                
                // Attachments
                if ($response->attachments && $response->attachments->count() > 0) {
                    $lines[] = '│ ' . str_pad('', 66) . ' │';
                    $lines[] = '│ 📎 Adjuntos:' . str_pad('', 54) . ' │';
                    foreach ($response->attachments as $att) {
                        $attLine = '   • ' . ($att->file_name ?? 'archivo');
                        $lines[] = '│ ' . str_pad($attLine, 66) . ' │';
                    }
                }
                
                $lines[] = '└' . str_repeat('─', 68) . '┘';
                $lines[] = '';
            }
        }
        
        // Footer
        $lines[] = str_repeat('=', 70);
        $lines[] = 'Exportado el: ' . now()->format('d/m/Y H:i:s');
        $lines[] = 'Total mensajes: ' . $responses->count();
        $lines[] = str_repeat('=', 70);
        
        return implode("\n", $lines);
    }

    private function translateStatus(?string $status): string
    {
        return match (strtoupper($status ?? '')) {
            'OPEN' => 'Abierto',
            'PENDING' => 'Pendiente',
            'RESOLVED' => 'Resuelto',
            'CLOSED' => 'Cerrado',
            default => $status ?? 'N/A',
        };
    }

    private function translatePriority(?string $priority): string
    {
        return match (strtoupper($priority ?? '')) {
            'HIGH' => 'Alta',
            'MEDIUM' => 'Media',
            'LOW' => 'Baja',
            default => $priority ?? 'N/A',
        };
    }
}
