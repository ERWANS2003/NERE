<?php

use App\Models\Permission;
use App\Support\PermissionCatalog;
use App\Support\RoleProfileCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rubriques : les taux, plafonds et barèmes sont saisis par le service paie,
        // jamais codés dans l'application.
        Schema::create('payroll_rubrics', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('label');
            $table->string('kind', 20);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_contributory')->default(false);
            $table->string('base', 20)->nullable();
            $table->decimal('employee_rate', 7, 4)->nullable();
            $table->decimal('employer_rate', 7, 4)->nullable();
            $table->decimal('ceiling', 14, 2)->nullable();
            $table->json('tax_rules')->nullable();
            $table->unsignedInteger('position')->default(100);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('payroll_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('base_salary', 14, 2);
            $table->string('category', 50)->nullable();
            $table->string('contract_type', 50)->nullable();
            $table->string('social_security_number', 50)->nullable();
            $table->unsignedTinyInteger('dependents')->default(0);
            $table->string('payment_method', 20)->default('virement');
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('payroll_profile_rubrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_rubric_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->unique(['payroll_profile_id', 'payroll_rubric_id']);
        });

        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status', 20)->default('open')->index();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });

        Schema::create('payroll_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('unpaid_days', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_period_id', 'user_id']);
        });

        Schema::create('payroll_variables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_rubric_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['payroll_period_id', 'user_id']);
        });

        Schema::create('payslips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('employee');
            $table->decimal('gross', 14, 2);
            $table->decimal('contributory_base', 14, 2);
            $table->decimal('taxable_base', 14, 2);
            $table->decimal('employee_contributions', 14, 2);
            $table->decimal('employer_contributions', 14, 2);
            $table->decimal('tax', 14, 2);
            $table->decimal('deductions', 14, 2);
            $table->decimal('net', 14, 2);
            $table->boolean('stale')->default(false);
            $table->timestamps();

            $table->unique(['payroll_period_id', 'user_id']);
        });

        Schema::create('payslip_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('label');
            $table->string('kind', 20);
            $table->decimal('base', 14, 2)->nullable();
            $table->decimal('rate', 7, 4)->nullable();
            $table->decimal('employer_rate', 7, 4)->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('employer_amount', 14, 2)->default(0);
            $table->unsignedInteger('position')->default(0);
        });

        $permission = collect(PermissionCatalog::groupedDefinitions())->flatten(1)->firstWhere('slug', 'payroll.manage');
        Permission::updateOrCreate(['slug' => $permission['slug']], $permission);
        RoleProfileCatalog::seed();
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_lines');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_variables');
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('payroll_profile_rubrics');
        Schema::dropIfExists('payroll_profiles');
        Schema::dropIfExists('payroll_rubrics');

        $permissionId = DB::table('permissions')->where('slug', 'payroll.manage')->value('id');
        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
