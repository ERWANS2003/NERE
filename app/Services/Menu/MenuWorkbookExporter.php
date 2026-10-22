<?php

namespace App\Services\Menu;

use App\Models\MenuCampaign;
use App\Models\MenuChoice;
use App\Models\MenuDay;
use App\Models\MenuRegistration;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MenuWorkbookExporter
{
    private const MENU_ONE_FILL = 'FFFFFF00';

    private const MENU_TWO_FILL = 'FF92D050';

    private const HEADER_FILL = 'FFED7D31';

    private const DEPARTMENT_FILLS = ['FFFFF2CC', 'FFDFEBF7', 'FFC5E0B4', 'FFF8CBAD'];

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
        $campaign->load(['registrations', 'days.choices.registration']);
        $spreadsheet = new Spreadsheet;
        foreach ($campaign->days as $index => $day) {
            $sheet = $index === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle($this->sheetTitle($day));
            $this->writeDailySheet($sheet, $campaign, $day);
        }

        $spreadsheet->getProperties()
            ->setTitle($campaign->title)
            ->setSubject('Choix des menus et commandes du lundi au vendredi')
            ->setDescription('Une feuille par jour de la semaine, avec les plats choisis et les totaux à commander.');

        return $spreadsheet;
    }

    private function sheetTitle(MenuDay $day): string
    {
        return mb_convert_case(
            $day->date->locale('fr')->translatedFormat('l d M Y'),
            MB_CASE_TITLE,
            'UTF-8',
        );
    }

    private function writeDailySheet(Worksheet $sheet, MenuCampaign $campaign, MenuDay $day): void
    {
        $lastDate = $day->date->locale('fr')->translatedFormat('l d F Y');
        $lastDate = mb_convert_case($lastDate, MB_CASE_TITLE, 'UTF-8');
        $sheet->mergeCells('B1:D1');
        $this->writeString($sheet, 'B1', 'CHOIX DU MENU — '.$lastDate.' — SOGA');
        $sheet->getStyle('B1:D1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B1:D1')->getAlignment()->setHorizontal('center');

        $sheet->fromArray([["N° D'ORDRE", 'NOM ET PRÉNOM(S)', 'OBSERVATIONS']], null, 'B2');
        $sheet->getStyle('B2:D2')->getFont()->setBold(true);
        $sheet->getStyle('B2:D2')->getFill()->setFillType('solid')->getStartColor()->setARGB(self::HEADER_FILL);

        $choicesByRegistration = $day->choices->keyBy('registration_id');
        $registrationsByDepartment = $campaign->registrations
            ->sortBy(fn (MenuRegistration $registration): string => mb_strtolower($registration->department)."\0".mb_strtolower($registration->employee_name))
            ->groupBy(fn (MenuRegistration $registration): string => $registration->department);

        $row = 3;
        foreach ($registrationsByDepartment->values() as $departmentIndex => $registrations) {
            $this->writeString($sheet, "C{$row}", $registrations->first()->department);
            $sheet->setCellValue("D{$row}", $registrations->count());
            $departmentFill = self::DEPARTMENT_FILLS[$departmentIndex % count(self::DEPARTMENT_FILLS)];
            $sheet->getStyle("B{$row}:D{$row}")->getFill()->setFillType('solid')->getStartColor()->setARGB($departmentFill);
            $sheet->getStyle("B{$row}:D{$row}")->getFont()->setBold(true);
            $row++;

            foreach ($registrations->values() as $employeeIndex => $registration) {
                $this->writeString($sheet, "B{$row}", (string) ($employeeIndex + 1));
                $this->writeString($sheet, "C{$row}", $registration->employee_name);

                $choice = $choicesByRegistration->get($registration->id);
                if ($choice !== null) {
                    $menu = $choice->selected_menu === 1 ? $day->menu_one : $day->menu_two;
                    $this->writeString($sheet, "D{$row}", $menu);
                    $fill = $choice->selected_menu === 1 ? self::MENU_ONE_FILL : self::MENU_TWO_FILL;
                    $sheet->getStyle("D{$row}")->getFill()->setFillType('solid')->getStartColor()->setARGB($fill);
                }

                $row++;
            }
        }

        $menuOneCount = $this->choicesForMenu($day, 1)->count();
        $menuTwoCount = $this->choicesForMenu($day, 2)->count();
        $registeredMeals = $menuOneCount + $menuTwoCount;
        $row++;
        $this->writeString($sheet, "B{$row}", 'TOTAL = '.$campaign->registrations->count());
        $this->writeString($sheet, "C{$row}", 'PLATS = '.$registeredMeals);
        $sheet->getStyle("B{$row}:D{$row}")->getFont()->setBold(true);
        $row++;
        $this->writeString($sheet, "B{$row}", 'MENU 1 : '.$menuOneCount);
        $this->writeString($sheet, "C{$row}", $day->menu_one);
        $sheet->getStyle("C{$row}")->getFill()->setFillType('solid')->getStartColor()->setARGB(self::MENU_ONE_FILL);
        $row++;
        $this->writeString($sheet, "B{$row}", 'MENU 2 : '.$menuTwoCount);
        $this->writeString($sheet, "C{$row}", $day->menu_two);
        $sheet->getStyle("C{$row}")->getFill()->setFillType('solid')->getStartColor()->setARGB(self::MENU_TWO_FILL);
        $row++;
        $this->writeString($sheet, "B{$row}", 'IMPRÉVUS : 02 PLATS');
        $sheet->getStyle("B{$row}:D{$row}")->getFont()->setBold(true);
        $row++;
        $this->writeString($sheet, "B{$row}", '01 PLAT — MENU 1');
        $this->writeString($sheet, "C{$row}", $day->menu_one);
        $sheet->setCellValue("D{$row}", $menuOneCount + 1);
        $sheet->getStyle("C{$row}")->getFill()->setFillType('solid')->getStartColor()->setARGB(self::MENU_ONE_FILL);
        $row++;
        $this->writeString($sheet, "B{$row}", '01 PLAT — MENU 2');
        $this->writeString($sheet, "C{$row}", $day->menu_two);
        $sheet->setCellValue("D{$row}", $menuTwoCount + 1);
        $sheet->getStyle("C{$row}")->getFill()->setFillType('solid')->getStartColor()->setARGB(self::MENU_TWO_FILL);
        $row++;
        $this->writeString($sheet, "B{$row}", 'TOTAL À COMMANDER');
        $sheet->setCellValue("D{$row}", $registeredMeals + 2);
        $sheet->getStyle("B{$row}:D{$row}")->getFont()->setBold(true);

        $this->styleTable($sheet, 'B2:D2', "B2:D{$row}");
        $sheet->getColumnDimension('A')->setWidth(3);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(42);
        $sheet->getColumnDimension('D')->setWidth(58);
        $sheet->getStyle("B1:D{$row}")->getAlignment()->setVertical('center')->setWrapText(true);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->freezePane('B3');
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
