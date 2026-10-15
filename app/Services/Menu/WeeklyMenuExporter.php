<?php

namespace App\Services\Menu;

use App\Models\User;
use App\Models\WeeklyMenu;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Génère le fichier Excel des choix de menu, dans l'esprit de la liste
 * administrative : une feuille par jour, lignes regroupées par département,
 * puis un récapitulatif des totaux (Menu 1 / Menu 2 / Aucun repas / Non répondu).
 */
class WeeklyMenuExporter
{
    private const LABELS = [
        'menu_1' => 'MENU 1',
        'menu_2' => 'MENU 2',
        'no_meal' => 'AUCUN REPAS',
    ];

    /**
     * @param  Collection<int, User>  $eligible
     */
    public function download(WeeklyMenu $menu, Collection $eligible): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($menu->days as $day) {
            $this->buildDaySheet($spreadsheet, $menu, $day, $eligible);
        }

        $this->buildSummarySheet($spreadsheet, $menu, $eligible);
        $spreadsheet->setActiveSheetIndex(0);

        $locationCode = str($menu->location->name)->slug('_')->upper();
        $filename = sprintf('MENU_%s_%s.xlsx', $locationCode, $menu->week_start->format('Y_m_d'));

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildDaySheet(Spreadsheet $spreadsheet, WeeklyMenu $menu, $day, Collection $eligible): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($day->day_name);

        $sheet->setCellValue('A1', 'CHOIX DU MENU — '.mb_strtoupper($menu->location->name));
        $sheet->setCellValue('A2', $day->day_name.' '.$day->menu_date->format('d/m/Y'));
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $headerRow = 4;
        foreach (['N°', 'Département', 'Nom et prénom', 'Choix', 'Observation'] as $i => $title) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$headerRow, $title);
        }
        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:E{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C9A227');

        // Choix du jour indexés par utilisateur.
        $choices = $day->choices->keyBy('user_id');

        $grouped = $eligible->sortBy([
            fn ($a, $b) => strcmp($this->departmentOf($a), $this->departmentOf($b)),
            fn ($a, $b) => strcmp($a->name, $b->name),
        ])->values();

        $row = $headerRow + 1;
        $order = 1;
        $currentDept = null;

        foreach ($grouped as $user) {
            $dept = $this->departmentOf($user);
            if ($dept !== $currentDept) {
                $currentDept = $dept;
                $sheet->setCellValue("A{$row}", '');
                $sheet->setCellValue("B{$row}", $dept);
                $sheet->mergeCells("B{$row}:E{$row}");
                $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:E{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EDE7D6');
                $row++;
                $order = 1;
            }

            $choice = $choices->get($user->id);
            $label = $choice ? (self::LABELS[$choice->choice] ?? 'NON RÉPONDU') : 'NON RÉPONDU';

            $sheet->setCellValue("A{$row}", $order);
            $sheet->setCellValue("B{$row}", $dept);
            $sheet->setCellValue("C{$row}", $user->name);
            $sheet->setCellValue("D{$row}", $label);
            $sheet->setCellValue("E{$row}", $choice?->comment ?? '');
            $row++;
            $order++;
        }

        $sheet->getStyle("A{$headerRow}:E".($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (['A' => 6, 'B' => 24, 'C' => 34, 'D' => 16, 'E' => 24] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Totaux du jour.
        $counts = ['menu_1' => 0, 'menu_2' => 0, 'no_meal' => 0, 'no_response' => 0];
        foreach ($eligible as $user) {
            $choice = $choices->get($user->id);
            $key = $choice && isset($counts[$choice->choice]) ? $choice->choice : 'no_response';
            $counts[$key]++;
        }

        $row += 1;
        $sheet->setCellValue("B{$row}", 'TOTAUX');
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        foreach ([
            'Menu 1' => $counts['menu_1'],
            'Menu 2' => $counts['menu_2'],
            'Aucun repas' => $counts['no_meal'],
            'Non répondu' => $counts['no_response'],
        ] as $libelle => $valeur) {
            $row++;
            $sheet->setCellValue("B{$row}", $libelle);
            $sheet->setCellValue("C{$row}", $valeur);
        }
    }

    private function buildSummarySheet(Spreadsheet $spreadsheet, WeeklyMenu $menu, Collection $eligible): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Récapitulatif');

        $sheet->setCellValue('A1', 'RÉCAPITULATIF — '.mb_strtoupper($menu->location->name));
        $sheet->setCellValue('A2', 'Semaine du '.$menu->week_start->format('d/m/Y').' au '.$menu->week_end->format('d/m/Y'));
        $sheet->setCellValue('A3', 'Personnel concerné : '.$eligible->count());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $row = 5;
        foreach (['Jour', 'Menu 1', 'Menu 2', 'Aucun repas', 'Non répondu'] as $i => $title) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$row, $title);
        }
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:E{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C9A227');

        $eligibleIds = $eligible->pluck('id');
        foreach ($menu->days as $day) {
            $row++;
            $counts = ['menu_1' => 0, 'menu_2' => 0, 'no_meal' => 0];
            foreach ($day->choices as $choice) {
                if ($eligibleIds->contains($choice->user_id) && isset($counts[$choice->choice])) {
                    $counts[$choice->choice]++;
                }
            }
            $noResponse = max(0, $eligible->count() - array_sum($counts));
            $sheet->setCellValue("A{$row}", $day->day_name);
            $sheet->setCellValue("B{$row}", $counts['menu_1']);
            $sheet->setCellValue("C{$row}", $counts['menu_2']);
            $sheet->setCellValue("D{$row}", $counts['no_meal']);
            $sheet->setCellValue("E{$row}", $noResponse);
        }

        $sheet->getStyle('A5:E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A5:E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (['A' => 14, 'B' => 12, 'C' => 12, 'D' => 14, 'E' => 14] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
    }

    private function departmentOf($user): string
    {
        return $user->departments->first()?->name ?? 'Non affecté';
    }
}
