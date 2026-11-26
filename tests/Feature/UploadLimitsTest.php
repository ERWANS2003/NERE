<?php

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('accepts ticket attachments up to 20 MB', function () {
    Notification::fake();
    Storage::fake('local');
    Department::factory()->create(['code' => 'IT', 'name' => 'Informatique']);
    $this->actingAs(User::factory()->create());
    $ticket = fn (int $kilobytes) => $this->post(route('intranet.tickets.store'), [
        'subject' => 'Plan scanné', 'description' => 'Voir pièce jointe.', 'category' => 'hardware', 'priority' => 'normal',
        'attachments' => [UploadedFile::fake()->create('plan.pdf', $kilobytes, 'application/pdf')],
    ]);

    $ticket(15 * 1024)->assertSessionHasNoErrors();
    $ticket(21 * 1024)->assertSessionHasErrors('attachments.0');
    expect(Ticket::count())->toBe(1);
});
