<?php

use App\Models\CategoryAssignment;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\QueryException;

function adminOfficeUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function adminOfficeCreatePayload(array $attributes = []): array
{
    return array_merge([
        'name' => 'Registrar',
        'representative_user_id' => adminOfficeUser()->id,
    ], $attributes);
}

function adminOfficeCreate(array $attributes = []): Office
{
    return Office::query()->create(adminOfficeCreatePayload($attributes));
}

test('an admin can view offices and their representative names', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    $representative = adminOfficeUser(['name' => 'Jordan Santos']);
    adminOfficeCreate(['representative_user_id' => $representative->id]);

    $this->actingAs($admin)
        ->get(route('admin.offices.index'))
        ->assertOk()
        ->assertSeeText('Office Directory')
        ->assertSeeText('Registrar')
        ->assertSeeText('Jordan Santos')
        ->assertSeeText('Active');
});

test('a non-admin cannot manage offices', function (): void {
    $user = adminOfficeUser(['role' => 'user']);

    $this->actingAs($user)
        ->get(route('admin.offices.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('admin.offices.store'), adminOfficeCreatePayload())
        ->assertForbidden();
});

test('an admin can create an office with any valid user as its representative', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    $representative = adminOfficeUser(['role' => 'user']);

    $this->actingAs($admin)
        ->post(route('admin.offices.store'), [
            'name' => 'Guidance Office',
            'representative_user_id' => $representative->id,
        ])
        ->assertRedirect(route('admin.offices.index'));

    $this->assertDatabaseHas('offices', [
        'name' => 'Guidance Office',
        'representative_user_id' => $representative->id,
        'is_active' => true,
    ]);
});

test('an admin can create an office with its official contact email', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    $representative = adminOfficeUser(['role' => 'user']);

    $this->actingAs($admin)
        ->post(route('admin.offices.store'), [
            'name' => 'Student Services Office',
            'representative_user_id' => $representative->id,
            'contact_email' => 'student-services@example.edu',
        ])
        ->assertRedirect(route('admin.offices.index'));

    $this->assertDatabaseHas('offices', [
        'name' => 'Student Services Office',
        'representative_user_id' => $representative->id,
        'contact_email' => 'student-services@example.edu',
    ]);
});

test('an admin can update the official office contact email', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    $office = adminOfficeCreate();

    $this->actingAs($admin)
        ->patch(route('admin.offices.update', $office), [
            'name' => $office->name,
            'representative_user_id' => $office->representative_user_id,
            'contact_email' => 'registrar-contact@example.edu',
        ])
        ->assertRedirect(route('admin.offices.index'));

    expect($office->fresh()->contact_email)->toBe('registrar-contact@example.edu');
});

test('omitted and blank official contact emails remain nullable', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    $office = adminOfficeCreate();

    $this->assertDatabaseHas('offices', [
        'id' => $office->id,
        'contact_email' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.offices.update', $office), [
            'name' => $office->name,
            'representative_user_id' => $office->representative_user_id,
            'contact_email' => '',
        ])
        ->assertRedirect(route('admin.offices.index'));

    expect($office->fresh()->contact_email)->toBeNull();
});

test('an invalid official office contact email is rejected', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    $representative = adminOfficeUser();

    $this->actingAs($admin)
        ->post(route('admin.offices.store'), [
            'name' => 'Invalid Contact Office',
            'representative_user_id' => $representative->id,
            'contact_email' => 'not-an-email',
        ])
        ->assertSessionHasErrors('contact_email');

    $this->assertDatabaseMissing('offices', ['name' => 'Invalid Contact Office']);
});

test('the admin office directory displays the official contact email', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    $office = adminOfficeCreate(['contact_email' => 'registrar-official@example.edu']);

    $this->actingAs($admin)
        ->get(route('admin.offices.index'))
        ->assertOk()
        ->assertSeeText('registrar-official@example.edu');
});

test('an office requires a representative', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.offices.store'), ['name' => 'Library'])
        ->assertSessionHasErrors('representative_user_id');
});

test('an invalid representative is rejected', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.offices.store'), [
            'name' => 'Library',
            'representative_user_id' => 999999,
        ])
        ->assertSessionHasErrors('representative_user_id');
});

test('an office name is required', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.offices.store'), [
            'representative_user_id' => adminOfficeUser()->id,
        ])
        ->assertSessionHasErrors('name');
});

test('an office name must be unique', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    adminOfficeCreate(['name' => 'Registrar']);

    $this->actingAs($admin)
        ->post(route('admin.offices.store'), adminOfficeCreatePayload(['name' => 'Registrar']))
        ->assertSessionHasErrors('name');
});

test('an admin can edit an office name', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    $office = adminOfficeCreate();

    $this->actingAs($admin)
        ->patch(route('admin.offices.update', $office), [
            'name' => 'Office of the Registrar',
            'representative_user_id' => $office->representative_user_id,
        ])
        ->assertRedirect(route('admin.offices.index'));

    expect($office->fresh()->name)->toBe('Office of the Registrar');
});

test('an admin can change an office representative', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    $office = adminOfficeCreate();
    $newRepresentative = adminOfficeUser(['role' => 'adviser']);

    $this->actingAs($admin)
        ->patch(route('admin.offices.update', $office), [
            'name' => $office->name,
            'representative_user_id' => $newRepresentative->id,
        ])
        ->assertRedirect(route('admin.offices.index'));

    expect($office->fresh()->representative_user_id)->toBe($newRepresentative->id);
});

test('an admin can deactivate and reactivate an office', function (): void {
    $admin = adminOfficeUser(['role' => 'admin']);
    $office = adminOfficeCreate();

    $this->actingAs($admin)
        ->patch(route('admin.offices.status', $office), ['is_active' => '0'])
        ->assertRedirect(route('admin.offices.index'));

    expect($office->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->patch(route('admin.offices.status', $office), ['is_active' => '1'])
        ->assertRedirect(route('admin.offices.index'));

    expect($office->fresh()->is_active)->toBeTrue();
});

test('the same user can represent multiple offices', function (): void {
    $representative = adminOfficeUser();
    $firstOffice = adminOfficeCreate(['name' => 'First Office', 'representative_user_id' => $representative->id]);
    $secondOffice = adminOfficeCreate(['name' => 'Second Office', 'representative_user_id' => $representative->id]);

    expect($representative->representedOffices()->pluck('id')->all())
        ->toEqualCanonicalizing([$firstOffice->id, $secondOffice->id]);
});

test('office management does not change reviewer roles or category assignments', function (): void {
    $reviewer = adminOfficeUser(['role' => 'office_academic', 'is_office_head' => true]);
    CategoryAssignment::query()->create([
        'category' => 'Enrollment',
        'office' => 'office_academic',
    ]);

    adminOfficeCreate(['name' => 'Registrar']);

    expect($reviewer->isOfficeReviewer())->toBeTrue()
        ->and($reviewer->officeLabel())->toBe('Academic Affairs')
        ->and($reviewer->is_office_head)->toBeTrue()
        ->and($reviewer->reviewableCategories())->toBe(['Enrollment'])
        ->and(CategoryAssignment::query()->where('category', 'Enrollment')->value('office'))
        ->toBe('office_academic');
});

test('a user assigned as representative cannot be deleted until reassigned', function (): void {
    $representative = adminOfficeUser();
    adminOfficeCreate(['representative_user_id' => $representative->id]);

    expect(fn () => $representative->delete())->toThrow(QueryException::class);
});
