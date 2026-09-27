<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Authorise the current user to act on a ticket bound from the route.
     *
     * Route model binding will happily resolve any ticket id, so every
     * single-ticket endpoint has to ask this question. It answers it with the
     * same scope the listings use (Ticket::visibleA) so a user cannot read or
     * mutate a ticket that does not appear on their own index page.
     */
    protected function authorizeTicket(Ticket $ticket): void
    {
        abort_unless(
            Ticket::visibleA(auth()->user())->whereKey($ticket->getKey())->exists(),
            403,
            'Ce ticket est hors de votre périmètre.'
        );
    }

    /**
     * Authorise an operation that assigns or reassigns a ticket.
     *
     * Assignment is a privileged action: without this check any authenticated
     * user could reassign arbitrary tickets by posting to an endpoint.
     */
    protected function authorizeAssignment(): void
    {
        abort_unless(
            auth()->user()->can('view_all_tickets'),
            403,
            "Vous n'êtes pas autorisé à affecter un ticket."
        );
    }
}
