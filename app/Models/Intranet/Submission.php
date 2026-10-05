<?php

namespace App\Models\Intranet;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submission extends Model
{
    use SoftDeletes;

    protected $table = 'intranet_submissions';

    protected $fillable = [
        'reference', 'form_id', 'requester_id', 'assignee_id',
        'current_step_id', 'status', 'priority', 'due_at', 'closed_at',
    ];

    protected $casts = [
        'due_at'    => 'datetime',
        'closed_at' => 'datetime',
    ];

    /* ── Statuts ────────────────────────────────────────────── */

    public const STATUS_DRAFT       = 'brouillon';
    public const STATUS_SUBMITTED   = 'soumise';
    public const STATUS_VALIDATING  = 'en_validation';
    public const STATUS_ASSIGNED    = 'assignee';
    public const STATUS_IN_PROGRESS = 'en_cours';
    public const STATUS_WAITING     = 'en_attente';
    public const STATUS_ESCALATED   = 'escaladee';
    public const STATUS_RESOLVED    = 'resolue';
    public const STATUS_CLOSED      = 'cloturee';
    public const STATUS_REJECTED    = 'rejetee';
    public const STATUS_CANCELLED   = 'annulee';

    public const TERMINAL_STATUSES = [
        self::STATUS_CLOSED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    /* ── Relations ─────────────────────────────────────────── */

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(SubmissionValue::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubmissionEvent::class)->latest('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->oldest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /* ── Helpers ────────────────────────────────────────────── */

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES);
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && ! $this->isTerminal();
    }

    /** Valeur d'un champ par son id. */
    public function valueOf(int $fieldId): mixed
    {
        return $this->values->firstWhere('form_field_id', $fieldId)?->value;
    }

    /* ── Scopes ─────────────────────────────────────────────── */

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    public function scopeOverdue($query)
    {
        return $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
    }
}
