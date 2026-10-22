<?php

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Ticket;
use App\Models\TimeOffBalance;
use App\Models\TimeOffPolicy;
use App\Models\TimeOffRequest;
use App\Models\User;
use App\Support\IntranetModuleRegistry;

it('restricts central module management to system administrators', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('admin.time-off.index'))->assertForbidden();
    $this->get(route('admin.resources.index'))->assertForbidden();
    $this->get(route('admin.tickets.index'))->assertForbidden();
});

it('keeps module administration usable while hiding inaccessible content shortcuts', function () {
    $admin = User::factory()->superAdmin()->create();
    $modules = app(IntranetModuleRegistry::class);
    $modules->setEnabled('resources', false);
    $modules->setEnabled('knowledge_base', false);
    $modules->setEnabled('forms', false);

    $this->actingAs($admin)
        ->get(route('admin.resources.index'))
        ->assertOk()
        ->assertSee('Activez le module pour ajouter une ressource')
        ->assertDontSee('/intranet/resources/ajouter', false);

    $this->get(route('admin.modules.index'))
        ->assertOk()
        ->assertSee('Base de connaissances désactivée')
        ->assertSee('Formulaires et services désactivés')
        ->assertDontSee('href="/intranet/forms"', false)
        ->assertDontSee('href="/intranet/base-de-connaissances"', false);
});

it('manages leave policies without deleting existing policies', function () {
    $admin = User::factory()->superAdmin()->create();
    $this->actingAs($admin);

    $this->post(route('admin.time-off.policies.store'), [
        'name' => 'Congé exceptionnel',
        'type' => 'special_leave',
        'description' => 'Politique créée depuis le hub.',
        'annual_allocation' => 3,
        'carry_over_limit' => 1,
        'advance_notice_days' => 2,
        'requires_approval' => '1',
        'is_active' => '1',
    ])->assertRedirect();

    $policy = TimeOffPolicy::where('type', 'special_leave')->firstOrFail();
    $this->put(route('admin.time-off.policies.update', $policy), [
        'name' => 'Congé exceptionnel ajusté',
        'type' => 'special_leave',
        'description' => 'Politique modifiée depuis le hub.',
        'annual_allocation' => 4,
        'carry_over_limit' => 1,
        'advance_notice_days' => 2,
        'requires_approval' => '1',
        'is_active' => '1',
    ])->assertRedirect();

    $this->delete(route('admin.time-off.policies.destroy', $policy))->assertRedirect();

    expect($policy->fresh()->is_active)->toBeFalse()
        ->and($policy->fresh()->name)->toBe('Congé exceptionnel ajusté')
        ->and($policy->fresh()->annual_allocation)->toBe(4);
});

it('adjusts leave balances while preserving usage and enforcing carry-over policy limits', function () {
    $admin = User::factory()->superAdmin()->create();
    $employee = User::factory()->create();
    $policy = TimeOffPolicy::create([
        'name' => 'Congé annuel',
        'type' => 'annual',
        'annual_allocation' => 25,
        'carry_over_limit' => 5,
        'requires_approval' => true,
        'advance_notice_days' => 0,
        'is_active' => true,
    ]);
    $balance = TimeOffBalance::create([
        'user_id' => $employee->id,
        'policy_id' => $policy->id,
        'year' => 2026,
        'total_days' => 20,
        'used_days' => 4,
        'remaining_days' => 16,
        'carry_over_days' => 0,
    ]);
    $this->actingAs($admin);

    $this->put(route('admin.time-off.balances.update', $balance), [
        'total_days' => 22,
        'carry_over_days' => 3,
    ])->assertRedirect();

    expect($balance->fresh()->used_days)->toBe('4.0')
        ->and($balance->fresh()->remaining_days)->toBe('21.0');

    $this->put(route('admin.time-off.balances.update', $balance), [
        'total_days' => 22,
        'carry_over_days' => 6,
    ])->assertSessionHasErrors('carry_over_days');

    $this->put(route('admin.time-off.balances.update', $balance), [
        'total_days' => 3,
        'carry_over_days' => 0,
    ])->assertSessionHasErrors('total_days');

    expect($balance->fresh()->total_days)->toBe('22.0')
        ->and($balance->fresh()->used_days)->toBe('4.0');

    $secondEmployee = User::factory()->create();
    $this->post(route('admin.time-off.balances.store'), [
        'user_id' => $secondEmployee->id,
        'policy_id' => $policy->id,
        'year' => 2026,
        'total_days' => 15,
        'carry_over_days' => 2,
    ])->assertRedirect();

    expect(TimeOffBalance::where('user_id', $secondEmployee->id)->firstOrFail()->remaining_days)->toBe('17.0');
});

it('includes carried days when an approved leave request updates a balance', function () {
    $admin = User::factory()->superAdmin()->create();
    $employee = User::factory()->create();
    $policy = TimeOffPolicy::create([
        'name' => 'Congé annuel',
        'type' => 'annual',
        'annual_allocation' => 20,
        'carry_over_limit' => 5,
        'requires_approval' => true,
        'advance_notice_days' => 0,
        'is_active' => true,
    ]);
    $balance = TimeOffBalance::create([
        'user_id' => $employee->id,
        'policy_id' => $policy->id,
        'year' => now()->year,
        'total_days' => 20,
        'used_days' => 2,
        'remaining_days' => 21,
        'carry_over_days' => 3,
    ]);
    $request = TimeOffRequest::create([
        'user_id' => $employee->id,
        'policy_id' => $policy->id,
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(11)->toDateString(),
        'total_days' => 2,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->put(route('intranet.time-off.approvals.update', $request), ['decision' => 'approve'])
        ->assertRedirect(route('intranet.time-off.approvals'));

    expect($balance->fresh()->used_days)->toBe('4.0')
        ->and($balance->fresh()->remaining_days)->toBe('19.0');
});

it('manages resources and refuses to delete categories that still contain resources', function () {
    $admin = User::factory()->superAdmin()->create();
    $category = ResourceCategory::create([
        'name' => 'Guides',
        'slug' => 'guides',
        'order' => 1,
    ]);
    $resource = Resource::create([
        'title' => 'Guide d’accueil',
        'description' => 'Description initiale',
        'category_id' => $category->id,
        'file_type' => 'link',
        'file_path' => '/intranet/forms',
        'tags' => ['ancien'],
        'uploaded_by' => $admin->id,
        'is_active' => true,
    ]);
    $this->actingAs($admin);

    $this->get(route('admin.resources.index'))
        ->assertOk()
        ->assertSee('Bibliothèque de ressources')
        ->assertSee('Guide d’accueil');

    $this->put(route('admin.resources.update', $resource), [
        'title' => 'Guide mis à jour',
        'description' => 'Nouvelle description',
        'category_id' => $category->id,
        'department_id' => '',
        'tags' => 'guide, accueil',
        'is_active' => '0',
    ])->assertRedirect();

    expect($resource->fresh()->title)->toBe('Guide mis à jour')
        ->and($resource->fresh()->tags)->toBe(['guide', 'accueil'])
        ->and($resource->fresh()->is_active)->toBeFalse()
        ->and($resource->fresh()->file_path)->toBe('/intranet/forms');

    $this->delete(route('admin.resources.categories.destroy', $category))
        ->assertSessionHasErrors('category');
    expect($category->fresh())->not->toBeNull();

    $this->post(route('admin.resources.categories.store'), [
        'name' => 'Archives',
        'slug' => 'archives',
        'description' => 'Documents archivés',
        'icon' => 'archive',
        'color' => 'gray',
        'order' => 3,
    ])->assertRedirect();

    $emptyCategory = ResourceCategory::where('slug', 'archives')->firstOrFail();
    $this->put(route('admin.resources.categories.update', $emptyCategory), [
        'name' => 'Archives internes',
        'slug' => 'archives-internes',
        'description' => 'Documents conservés',
        'icon' => 'archive',
        'color' => 'gray',
        'order' => 4,
    ])->assertRedirect();
    $this->delete(route('admin.resources.categories.destroy', $emptyCategory))->assertRedirect();
    expect(ResourceCategory::whereKey($emptyCategory->id)->exists())->toBeFalse();
});

it('provides filtered global ticket visibility in the administration hub', function () {
    $admin = User::factory()->superAdmin()->create();
    $department = Department::factory()->create(['name' => 'Informatique']);
    $requester = User::factory()->create();
    $ticket = Ticket::create([
        'number' => 'IT-009999',
        'department_id' => $department->id,
        'requester_id' => $requester->id,
        'subject' => 'Accès au réseau',
        'description' => 'Demande de diagnostic.',
        'category' => TicketCategory::Network,
        'priority' => TicketPriority::High,
        'status' => TicketStatus::InProgress,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.tickets.index', ['status' => 'in_progress', 'search' => 'IT-009999']))
        ->assertOk()
        ->assertSee('Suivi global du support')
        ->assertSee('IT-009999')
        ->assertSee('Accès au réseau');

    $this->get(route('admin.tickets.index', ['status' => 'closed']))
        ->assertOk()
        ->assertDontSee('IT-009999');
});
