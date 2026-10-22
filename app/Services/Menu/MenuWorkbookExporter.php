<?php

namespace App\Services\Menu;

use App\Models\MenuCampaign;
use App\Models\MenuChoice;
use App\Models\MenuDay;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MenuWorkbookExporter
{
    public function download(MenuCampaign $campaign): StreamedResponse
    {
        $campaign->load(['days.choices.registration']);
        $spreadsheet = $this->createWorkbook($campaign);
        $filename = 'menus-'.str($campaign->title)->slug().'-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(
            static function () use ($spreadsheet): void {
                try {
                    (new Xlsx($spreadsheet))->save('php://output');
                } finally {
                    $spreadsheet->disconnectWorksheets();
                }
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    private function createWorkbook(MenuCampaign $campaign): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Récapitulatif');
        $this->writeSummary($summary, $campaign);

        foreach ($campaign->days as $day) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($day->date->format('Y-m-d'));
            $this->writeDailySheet($sheet, $campaign, $day);
        }

        $spreadsheet->getProperties()
            ->setTitle($campaign->title)
            ->setSubject('Choix des menus et commandes par jour')
            ->setDescription('Liste des inscrits, totaux et deux repas imprévus par jour.');

        return $spreadsheet;
    }

    private function writeSummary(Worksheet $sheet, MenuCampaign $campaign): void
    {
        $sheet->mergeCells('A1:F1');
        $this->writeString($sheet, 'A1', $campaign->title);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true)->setSize(16);
        $sheet->fromArray(
            [['Date', 'Type', 'Menu / dessert', 'Inscrits', 'Imprévus', 'Total à prévoir']],
            null,
            'A3',
        );

        $row = 4;
        foreach ($campaign->days as $day) {
            $menuOneCount = $this->choicesForMenu($day, 1)->count();
            $menuTwoCount = $this->choicesForMenu($day, 2)->count();
            $registeredMeals = $menuOneCount + $menuTwoCount;
            $this->writeString($sheet, "A{$row}", $day->date->format('d/m/Y'));
            $this->writeString($sheet, "B{$row}", 'Menu 1');
            $this->writeString($sheet, "C{$row}", $day->menu_one);
            $sheet->setCellValue("D{$row}", $menuOneCount);
            $sheet->setCellValue("E{$row}", 1);
            $sheet->setCellValue("F{$row}", $menuOneCount + 1);
            $row++;

            $this->writeString($sheet, "A{$row}", $day->date->format('d/m/Y'));
            $this->writeString($sheet, "B{$row}", 'Menu 2');
            $this->writeString($sheet, "C{$row}", $day->menu_two);
            $sheet->setCellValue("D{$row}", $menuTwoCount);
            $sheet->setCellValue("E{$row}", 1);
            $sheet->setCellValue("F{$row}", $menuTwoCount + 1);
            $row++;

            $this->writeString($sheet, "A{$row}", $day->date->format('d/m/Y'));
            $this->writeString($sheet, "B{$row}", 'Desserts');
            $this->writeString($sheet, "C{$row}", $day->dessert);
            $sheet->setCellValue("D{$row}", $registeredMeals);
            $sheet->setCellValue("E{$row}", 2);
            $sheet->setCellValue("F{$row}", $registeredMeals + 2);
            $row++;

            $this->writeString($sheet, "A{$row}", $day->date->format('d/m/Y'));
            $this->writeString($sheet, "B{$row}", 'Total des plats');
            $this->writeString($sheet, "C{$row}", '');
            $sheet->setCellValue("D{$row}", $registeredMeals);
            $sheet->setCellValue("E{$row}", 2);
            $sheet->setCellValue("F{$row}", $registeredMeals + 2);
            $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
            $row++;
        }

        $this->styleTable($sheet, 'A3:F3', 'A3:F'.max(3, $row - 1));
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getColumnDimension('C')->setWidth(48);
        $sheet->getStyle('C4:C'.max(4, $row - 1))->getAlignment()->setWrapText(true);
        $sheet->freezePane('A4');
    }

    private function writeDailySheet(Worksheet $sheet, MenuCampaign $campaign, MenuDay $day): void
    {
        $sheet->mergeCells('A1:D1');
        $this->writeString($sheet, 'A1', $campaign->title);
        $sheet->getStyle('A1:D1')->getFont()->setBold(true)->setSize(16);
        $sheet->mergeCells('A2:D2');
        $this->writeString($sheet, 'A2', 'CHOIX DU MENU — '.$day->date->format('d/m/Y'));
        $sheet->getStyle('A2:D2')->getFont()->setBold(true)->setSize(13);
        $this->writeString($sheet, 'A4', 'Menu 1');
        $this->writeString($sheet, 'B4', $day->menu_one);
        $this->writeString($sheet, 'C4', 'Menu 2');
        $this->writeString($sheet, 'D4', $day->menu_two);
        $this->writeString($sheet, 'A5', 'Dessert du jour');
        $sheet->mergeCells('B5:D5');
        $this->writeString($sheet, 'B5', $day->dessert);
        $sheet->getStyle('A4:D5')->getAlignment()->setWrapText(true)->setVertical('center');
        $sheet->getStyle('A4:D5')->getFont()->setBold(true);
        $sheet->getRowDimension(4)->setRowHeight(48);

        $sheet->fromArray([['N° d’ordre', 'Nom et prénom(s)', 'Menu choisi', 'Dessert']], null, 'A7');
        $sheet->getStyle('A7:D7')->getFont()->setBold(true);
        $sheet->getStyle('A7:D7')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD9E2F3');

        $row = 8;
        $departmentChoices = $day->choices
            ->filter(fn (MenuChoice $choice): bool => $choice->registration !== null)
            ->sortBy(fn (MenuChoice $choice): string => mb_strtolower($choice->registration->department))
            ->groupBy(fn (MenuChoice $choice): string => $choice->registration->department);

        foreach ($departmentChoices as $department => $choices) {
            $this->writeString($sheet, "A{$row}", (string) $department);
            $this->writeString($sheet, "D{$row}", $choices->count().' employé(s)');
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD9EAD3');
            $row++;

            foreach ($choices->values() as $index => $choice) {
                $this->writeString($sheet, "A{$row}", (string) ($index + 1));
                $this->writeString($sheet, "B{$row}", $choice->registration->employee_name);
                $this->writeString($sheet, "C{$row}", $choice->selected_menu === 1 ? $day->menu_one : $day->menu_two);
                $this->writeString($sheet, "D{$row}", $day->dessert);
                $row++;
            }
        }

        $menuOneCount = $this->choicesForMenu($day, 1)->count();
        $menuTwoCount = $this->choicesForMenu($day, 2)->count();
        $registeredMeals = $menuOneCount + $menuTwoCount;
        $row++;
        $this->writeString($sheet, "A{$row}", 'TOTAL PLATS INSCRITS');
        $sheet->setCellValue("D{$row}", $registeredMeals);
        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $row++;
        $this->writeString($sheet, "A{$row}", 'Menu 1 : inscrits');
        $sheet->setCellValue("C{$row}", $menuOneCount);
        $row++;
        $this->writeString($sheet, "A{$row}", 'Menu 2 : inscrits');
        $sheet->setCellValue("C{$row}", $menuTwoCount);
        $row++;
        $this->writeString($sheet, "A{$row}", 'IMPRÉVUS : 02 PLATS');
        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $row++;
        $this->writeString($sheet, "A{$row}", '01 plat supplémentaire — Menu 1');
        $sheet->setCellValue("C{$row}", 1);
        $sheet->setCellValue("D{$row}", $menuOneCount + 1);
        $row++;
        $this->writeString($sheet, "A{$row}", '01 plat supplémentaire — Menu 2');
        $sheet->setCellValue("C{$row}", 1);
        $sheet->setCellValue("D{$row}", $menuTwoCount + 1);
        $row++;
        $this->writeString($sheet, "A{$row}", 'TOTAL À COMMANDER');
        $sheet->setCellValue("D{$row}", $registeredMeals + 2);
        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $row++;
        $this->writeString($sheet, "A{$row}", 'Desserts à prévoir');
        $this->writeString($sheet, "C{$row}", $day->dessert);
        $sheet->setCellValue("D{$row}", $registeredMeals + 2);

        $this->styleTable($sheet, 'A7:D7', "A7:D{$row}");
        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(32);
        $sheet->getColumnDimension('C')->setWidth(50);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getStyle("A1:D{$row}")->getAlignment()->setVertical('center');
        $sheet->getStyle("A1:D{$row}")->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(7)->setRowHeight(28);
        $sheet->freezePane('A8');
        $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setFitToPage(true);
    }

    private function choicesForMenu(MenuDay $day, int $menu): Collection
    {
        return $day->choices->filter(
            fn (MenuChoice $choice): bool => $choice->selected_menu === $menu
        );
    }

    private function styleTable(Worksheet $sheet, string $headerRange, string $tableRange): void
    {
        $sheet->getStyle($headerRange)->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD9E2F3');
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle('thin');
    }

    private function writeString(Worksheet $sheet, string $coordinate, string $value): void
    {
        $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
    }
}
