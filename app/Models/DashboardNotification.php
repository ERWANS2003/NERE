<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DashboardNotification extends Model
{
    use HasFactory;
    protected $table = 'dashboard_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'icon',
        'color',
        'action_url',
        'read_at',
        'data',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'data' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: unread notifications only
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Create a ticket created notification
     */
    public static function ticketCreated(Ticket $ticket, User $user)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'ticket_created',
            'title' => 'Nouveau Ticket Créé',
            'message' => "Ticket {$ticket->reference}: {$ticket->titre}",
            'icon' => '🎫',
            'color' => 'blue',
            'action_url' => route('tickets.show', $ticket),
            'data' => ['ticket_id' => $ticket->id],
        ]);
    }

    /**
     * Create a ticket assigned notification
     */
    public static function ticketAssigned(Ticket $ticket, User $assignee)
    {
        return self::create([
            'user_id' => $assignee->id,
            'type' => 'ticket_assigned',
            'title' => 'Ticket Assigné',
            'message' => "Vous avez reçu: {$ticket->titre}",
            'icon' => '📋',
            'color' => 'primary',
            'action_url' => route('tickets.show', $ticket),
            'data' => ['ticket_id' => $ticket->id],
        ]);
    }

    /**
     * Create a SLA warning notification
     */
    public static function slaWarning(Ticket $ticket, User $user)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'sla_warning',
            'title' => '⚠️ Alerte SLA',
            'message' => "Ticket {$ticket->reference} approaching SLA limit",
            'icon' => '⏰',
            'color' => 'yellow',
            'action_url' => route('tickets.show', $ticket),
            'data' => ['ticket_id' => $ticket->id],
        ]);
    }

    /**
     * Create a SLA breached notification
     */
    public static function slaBreached(Ticket $ticket, User $user)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'sla_breached',
            'title' => '🚨 SLA Dépassé',
            'message' => "Ticket {$ticket->reference} SLA breached",
            'icon' => '🚨',
            'color' => 'red',
            'action_url' => route('tickets.show', $ticket),
            'data' => ['ticket_id' => $ticket->id],
        ]);
    }

    /**
     * Create a comment notification
     */
    public static function commentAdded(Comment $comment, User $user)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'comment_added',
            'title' => 'Nouveau Commentaire',
            'message' => "{$comment->auteur?->name} a commenté: " . Str::limit($comment->contenu, 50),
            'icon' => '💬',
            'color' => 'info',
            'action_url' => route('tickets.show', $comment->ticket),
            'data' => ['ticket_id' => $comment->ticket_id, 'comment_id' => $comment->id],
        ]);
    }

    /**
     * Create an incident notification
     */
    public static function incidentCreated(SafetyIncident $incident, User $user)
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'incident_created',
            'title' => 'Nouvel Incident Sécurité',
            'message' => "Sévérité: {$incident->severity} - {$incident->title}",
            'icon' => '⚠️',
            'color' => 'danger',
            'action_url' => route('safety.show', $incident),
            'data' => ['incident_id' => $incident->id],
        ]);
    }
}
