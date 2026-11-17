<?php

namespace App\Services;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TicketAiTriage
{
    public static function enabled(): bool
    {
        return filled(config('services.ticket_ai.url'));
    }

    /**
     * @return array{category: string, priority: string, summary: string, reply: string}|null
     */
    public function suggest(Ticket $ticket): ?array
    {
        $response = Http::timeout(config('services.ticket_ai.timeout'))
            ->post(rtrim(config('services.ticket_ai.url'), '/').'/api/chat', [
                'model' => config('services.ticket_ai.model'),
                'stream' => false,
                'keep_alive' => '30m',
                'options' => ['temperature' => 0, 'num_ctx' => 8192],
                'format' => [
                    'type' => 'object',
                    'properties' => [
                        'category' => ['type' => 'string', 'enum' => array_column(TicketCategory::cases(), 'value')],
                        'priority' => ['type' => 'string', 'enum' => array_column(TicketPriority::cases(), 'value')],
                        'summary' => ['type' => 'string'],
                        'reply' => ['type' => 'string'],
                    ],
                    'required' => ['category', 'priority', 'summary', 'reply'],
                ],
                'messages' => [
                    ['role' => 'system', 'content' => $this->instructions()],
                    ['role' => 'user', 'content' => "Sujet : {$ticket->subject}\n\nDescription :\n".($ticket->description ?: '(aucune)')],
                ],
            ])
            ->throw();

        $data = json_decode((string) $response->json('message.content'), true);

        if (! is_array($data)
            || TicketCategory::tryFrom($data['category'] ?? '') === null
            || TicketPriority::tryFrom($data['priority'] ?? '') === null
            || ! is_string($data['summary'] ?? null) || trim($data['summary']) === ''
            || ! is_string($data['reply'] ?? null) || trim($data['reply']) === '') {
            return null;
        }

        return [
            'category' => $data['category'],
            'priority' => $data['priority'],
            'summary' => Str::limit(trim($data['summary']), 500),
            'reply' => Str::limit(trim($data['reply']), 3000),
        ];
    }

    private function instructions(): string
    {
        $categories = collect(TicketCategory::cases())
            ->map(fn (TicketCategory $category) => "- {$category->value} : {$category->label()}")
            ->join("\n");

        return <<<TXT
        Tu aides le support informatique de NERE Mining à trier les tickets.
        À partir du sujet et de la description, réponds uniquement en JSON avec :
        - category : la catégorie la plus adaptée parmi :
        {$categories}
          (un problème de courrier électronique ou d'Outlook relève de « email ».)
        - priority : low (gêne mineure, contournement possible), normal (une personne gênée),
          high (une personne bloquée dans son travail), urgent (plusieurs personnes ou un service
          entier bloqués, ou un risque de sécurité).
        - summary : une seule phrase en français qui résume le problème.
        - reply : un court premier message en français au demandeur, poli et au vouvoiement :
          accuse réception, pose au besoin une ou deux questions utiles ou donne une vérification
          simple qu'il peut faire lui-même. Ne promets aucun délai et n'invente aucune information.
          Ne signe pas.
        TXT;
    }
}
