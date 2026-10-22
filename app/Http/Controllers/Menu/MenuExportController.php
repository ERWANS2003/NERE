<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\MenuCampaign;
use App\Services\Menu\MenuWorkbookExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MenuExportController extends Controller
{
    public function __invoke(MenuCampaign $campaign, MenuWorkbookExporter $exporter): StreamedResponse
    {
        abort_unless($campaign->status !== 'draft', 404);

        return $exporter->download($campaign);
    }
}
