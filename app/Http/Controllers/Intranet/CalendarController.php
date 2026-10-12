<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Services\Intranet\CalendarFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /** Plage maximale demandée à l'API : une année et un peu de marge. */
    private const MAX_RANGE_DAYS = 400;

    public function __construct(private readonly CalendarFeed $feed)
    {
    }

    /**
     * Page calendrier. ?date=AAAA-MM-JJ ouvre le calendrier sur ce jour
     * (lien depuis la liste « À venir »).
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return view('intranet.calendar.index', [
            'initialDate' => $validated['date'] ?? null,
            'upcoming' => $this->feed->upcoming(4),
            'types' => config('intranet.calendar.event_types', []),
        ]);
    }

    /**
     * Flux JSON consommé par FullCalendar (paramètres start et end, fin exclusive).
     */
    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}/'],
            'end' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}/'],
        ]);

        // FullCalendar envoie une date ISO complète avec fuseau : seul le jour compte.
        $start = Carbon::parse(substr($validated['start'], 0, 10))->startOfDay();
        $end = Carbon::parse(substr($validated['end'], 0, 10))->startOfDay();

        if ($end->lte($start) || $start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            return response()->json(['message' => 'Plage de dates invalide.'], 422);
        }

        return response()->json($this->feed->between($start, $end));
    }
}
