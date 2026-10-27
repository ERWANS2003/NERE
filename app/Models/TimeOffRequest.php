<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeOffRequest extends Model
{
    protected $fillable = [
        'user_id',
        'policy_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'approval_comment',
        'is_half_day',
        'emergency_contact',
        'manager_reminder_sent_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:1',
        'is_half_day' => 'boolean',
        'approved_at' => 'datetime',
        'manager_reminder_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(TimeOffPolicy::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Demandes que cet utilisateur peut traiter : toutes pour l'admin ; sinon
     * celles de ses collaborateurs directs (manager_id) et des membres des
     * departements qu'il dirige, jamais les siennes.
     */
    public function scopeApprovableBy(Builder $query, User $approver): Builder
    {
        if ($approver->is_super_admin) {
            return $query;
        }

        $departmentIds = $approver->directedDepartments()->pluck('id')->all();

        return $query
            ->where('user_id', '!=', $approver->getKey())
            ->whereHas('user', fn (Builder $users) => $users->where(fn (Builder $scope) => $scope
                ->where('manager_id', $approver->getKey())
                ->when($departmentIds !== [], fn (Builder $q) => $q->orWhereHas(
                    'departments',
                    fn (Builder $departments) => $departments->whereIn('departments.id', $departmentIds)
                ))));
    }
}
