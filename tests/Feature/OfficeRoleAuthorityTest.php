<?php

use App\Models\CategoryAssignment;
use App\Models\Feedback;
use App\Models\User;

test('office identity and review scope come from the role, not from office_department', function (): void {
    $reviewer = User::factory()->create([
        'role' => 'office_academic',
        'is_office_head' => true,
        // Deliberately contradicts the authoritative role-based office.
        'office_department' => 'Registrar',
    ]);

    CategoryAssignment::query()->create([
        'category' => 'Role Authority Facilities',
        'office' => 'office_academic',
    ]);

    expect($reviewer->isOfficeReviewer())->toBeTrue()
        ->and($reviewer->officeLabel())->toBe('Academic Affairs')
        ->and($reviewer->reviewableCategories())->toBe(['Role Authority Facilities'])
        ->and($reviewer->office_department)->toBe('Registrar');
});

test('a stale office_department cannot turn a non-office role into an office reviewer', function (): void {
    $user = User::factory()->create([
        'role' => 'user',
        'is_office_head' => true,
        'office_department' => 'CICS',
    ]);

    CategoryAssignment::query()->create([
        'category' => 'Role Authority Facilities',
        'office' => 'office_academic',
    ]);

    expect($user->isOfficeReviewer())->toBeFalse()
        ->and($user->officeLabel())->toBeNull()
        ->and($user->reviewableCategories())->toBe([]);
});

test('a non-office role cannot obtain office submission eligibility from a leftover office_department', function (): void {
    $user = User::factory()->create([
        'role' => 'user',
        'is_office_head' => true,
        'office_department' => 'CICS',
    ]);

    CategoryAssignment::query()->create([
        'category' => 'Role Authority Facilities',
        'office' => 'office_academic',
    ]);

    $this->actingAs($user)->post(route('feedback.store'), [
        'title' => 'Laboratory equipment status is tracked on paper',
        'category' => 'Role Authority Facilities',
        'description' => 'Laboratory personnel inspect every workstation by hand to learn which computers are functional.',
        'impact' => 'Students spend class time locating working computers while staff repeat manual inspections.',
        'frequency' => 'Often',
        'current_process' => 'Manual or paper-based process',
        'affected_users' => '50-200',
        'affected_group' => ['Students'],
        'force_submit' => '1',
    ])->assertRedirect(route('feedback.submitted', absolute: false));

    $feedback = Feedback::query()
        ->where('category', 'Role Authority Facilities')
        ->latest('id')
        ->firstOrFail();

    expect((bool) $feedback->is_capstone_worthy)->toBeFalse()
        ->and($feedback->capstone_marked_by)->toBeNull()
        ->and($feedback->status)->toBe('pending');
});

test('the office head toggle still persists is_office_head without touching office_department', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $reviewer = User::factory()->create([
        'role' => 'office_academic',
        'is_office_head' => false,
        'office_department' => 'Registrar',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->patch(route('admin.users.office-head', $reviewer->id), ['is_office_head' => '1'])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');

    expect($reviewer->refresh()->is_office_head)->toBeTrue()
        ->and($reviewer->office_department)->toBe('Registrar');
});

test('the office head toggle can still be turned off', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $reviewer = User::factory()->create([
        'role' => 'office_academic',
        'is_office_head' => true,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->patch(route('admin.users.office-head', $reviewer->id))
        ->assertRedirect(route('admin.users.index'));

    expect($reviewer->refresh()->is_office_head)->toBeFalse();
});

test('the admin users table no longer offers an office_department control', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $other = User::factory()->create(['role' => 'office_academic', 'office_department' => 'Registrar']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee('name="office_department"', false)
        ->assertSee('name="is_office_head"', false);
});
