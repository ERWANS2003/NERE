<?php

namespace App\Models;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $table = 'it_support_tickets';

    protected $fillable = [
        'number',
        'department_id',
        'requester_id',
        'assignee_id',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'first_response_due_at',
        'first_responded_at',
        'response_warning_at',
        'response_warning_sent_at',
        'response_breached_at',
        'resolution_due_at',
        'resolution_warning_at',
        'resolution_warning_sent_at',
        'resolution_breached_at',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'first_response_due_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'response_warning_at' => 'datetime',
            'response_warning_sent_at' => 'datetime',
            'response_breached_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'resolution_warning_at' => 'datetime',
            'resolution_warning_sent_at' => 'datetime',
            'resolution_breached_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class);
    }
}
