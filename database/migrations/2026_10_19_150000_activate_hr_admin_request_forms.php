<?php

use App\Support\DepartmentRequestCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $catalog = DepartmentRequestCatalog::forms();
        $serviceForms = DepartmentRequestCatalog::serviceForms();

        foreach ($catalog as $departmentCode => $forms) {
            $departmentId = DB::table('departments')->where('code', $departmentCode)->value('id');
            if ($departmentId === null) {
                continue;
            }

            foreach ($forms as $name => $form) {
                DB::table('department_forms')->updateOrInsert(
                    ['department_id' => $departmentId, 'name' => $name],
                    [
                        'description' => $form['description'],
                        'form_schema' => json_encode(['fields' => $form['fields']], JSON_THROW_ON_ERROR),
                        'is_active' => true,
                        'order' => array_search($name, array_keys($forms), true),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            foreach ($serviceForms[$departmentCode] ?? [] as $serviceName => $formName) {
                $formId = DB::table('department_forms')
                    ->where('department_id', $departmentId)
                    ->where('name', $formName)
                    ->value('id');

                if ($formId !== null) {
                    DB::table('department_services')
                        ->where('department_id', $departmentId)
                        ->where('name', $serviceName)
                        ->update([
                            'link_type' => 'page',
                            'link_url' => '/intranet/forms/'.$formId,
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        $hrDepartmentId = DB::table('departments')->where('code', 'RH')->value('id');
        if ($hrDepartmentId !== null) {
            DB::table('department_services')
                ->where('department_id', $hrDepartmentId)
                ->where('name', 'Demande de congé')
                ->update([
                    'link_type' => 'page',
                    'link_url' => '/intranet/time-off/demande',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        foreach (['RH', 'ADMIN'] as $departmentCode) {
            $departmentId = DB::table('departments')->where('code', $departmentCode)->value('id');
            if ($departmentId === null) {
                continue;
            }

            $serviceNames = array_keys(DepartmentRequestCatalog::serviceForms()[$departmentCode] ?? []);
            if ($departmentCode === 'RH') {
                $serviceNames[] = 'Demande de congé';
            }

            DB::table('department_services')
                ->where('department_id', $departmentId)
                ->whereIn('name', $serviceNames)
                ->update([
                    'link_type' => 'form',
                    'link_url' => null,
                    'updated_at' => now(),
                ]);
        }
    }
};
