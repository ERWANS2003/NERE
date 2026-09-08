<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowAutomation extends Model
{
    protected $fillable = [
        'name',
        'description',
        'trigger_event',
        'conditions',
        'actions',
        'is_active',
        'execution_count',
        'last_executed_at',
        'created_by',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'last_executed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class, 'automation_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where('trigger_event', $event);
    }

    /**
     * Vérifie si les conditions sont remplies
     */
    public function evaluateConditions(array $context): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $context)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Évalue une condition individuelle
     */
    protected function evaluateCondition(array $condition, array $context): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if (!$field || !isset($context[$field])) {
            return false;
        }

        $contextValue = $context[$field];

        return match ($operator) {
            '=' => $contextValue == $value,
            '!=' => $contextValue != $value,
            '>' => $contextValue > $value,
            '<' => $contextValue < $value,
            '>=' => $contextValue >= $value,
            '<=' => $contextValue <= $value,
            'contains' => str_contains($contextValue, $value),
            'starts_with' => str_starts_with($contextValue, $value),
            'ends_with' => str_ends_with($contextValue, $value),
            'in' => in_array($contextValue, (array)$value),
            'not_in' => !in_array($contextValue, (array)$value),
            default => false,
        };
    }

    /**
     * Exécute les actions de l'automation
     */
    public function executeActions(array $context): array
    {
        $results = [];

        foreach ($this->actions as $action) {
            try {
                $results[] = $this->executeAction($action, $context);
            } catch (\Exception $e) {
                $results[] = [
                    'action' => $action,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Incrémenter le compteur
        $this->increment('execution_count');
        $this->update(['last_executed_at' => now()]);

        return $results;
    }

    /**
     * Exécute une action individuelle
     */
    protected function executeAction(array $action, array $context): array
    {
        $type = $action['type'] ?? null;

        return match ($type) {
            'assign_ticket' => $this->assignTicket($action, $context),
            'change_status' => $this->changeStatus($action, $context),
            'send_notification' => $this->sendNotification($action, $context),
            'create_ticket' => $this->createTicket($action, $context),
            'add_comment' => $this->addComment($action, $context),
            'escalate' => $this->escalateTicket($action, $context),
            default => ['status' => 'skipped', 'reason' => 'Unknown action type'],
        };
    }

    protected function assignTicket(array $action, array $context): array
    {
        // Logique d'assignation
        return ['status' => 'success', 'action' => 'assign_ticket'];
    }

    protected function changeStatus(array $action, array $context): array
    {
        // Logique changement statut
        return ['status' => 'success', 'action' => 'change_status'];
    }

    protected function sendNotification(array $action, array $context): array
    {
        // Logique notification
        return ['status' => 'success', 'action' => 'send_notification'];
    }

    protected function createTicket(array $action, array $context): array
    {
        // Logique création ticket
        return ['status' => 'success', 'action' => 'create_ticket'];
    }

    protected function addComment(array $action, array $context): array
    {
        // Logique ajout commentaire
        return ['status' => 'success', 'action' => 'add_comment'];
    }

    protected function escalateTicket(array $action, array $context): array
    {
        // Logique escalade
        return ['status' => 'success', 'action' => 'escalate'];
    }
}
