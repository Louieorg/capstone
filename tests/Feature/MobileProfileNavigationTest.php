<?php

use App\Models\Office;
use App\Models\User;

/**
 * The student mobile shell is intentionally: Home | Discover | Submit | Capstone |
 * Profile. Profile IS the personal/account hub, so the student shell must not
 * also render a floating account control. (The admin shell still needs its own,
 * because it has no Profile slot in its bottom bar.)
 */
function mobileNavOffice(array $attributes = []): Office
{
    return Office::query()->create(array_merge([
        'name' => 'Mobile Nav Office',
        'representative_user_id' => User::factory()->create(['role' => 'user'])->id,
        'contact_email' => 'mobile-nav@example.edu',
        'is_active' => true,
    ], $attributes));
}

it('renders exactly the five intended mobile destinations', function (): void {
    $user = User::factory()->create(['role' => 'user']);

    $content = (string) $this->actingAs($user)->get(route('home'))->assertOk()->getContent();

    // Four plain items plus the centre Submit CTA.
    expect(substr_count($content, 'class="bn-item'))->toBe(4)
        ->and(substr_count($content, 'class="bn-center"'))->toBe(1)
        ->and(substr_count($content, 'class="bn-item') + substr_count($content, 'class="bn-center"'))->toBe(5);

    // And they are the intended five, in order.
    expect($content)
        ->toContain('<span>Home</span>')
        ->toContain('<span>Discover</span>')
        ->toContain('<span>Submit</span>')
        ->toContain('<span>Capstone</span>')
        ->toContain('<span>Profile</span>');
});

it('does not render a floating account control in the student shell', function (): void {
    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)->get(route('home'))->assertOk()
        // The sidebar account control still exists for desktop.
        ->assertSee('sb-account-trigger', false)
        // ...but no second, floating mobile instance sits beside the bottom nav.
        // Matched on the markup attribute, because the shared stylesheet also
        // defines the .sb-account--mobile rules.
        ->assertDontSee('class="sb-account sb-account--mobile"', false)
        // The student shell no longer reserves the floating pill's band either.
        ->assertDontSee('class="has-mobile-account"', false);
});

it('keeps every account action reachable from the profile hub', function (): void {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    $response->assertSeeText('Saved Ideas')
        ->assertSeeText('My Contribution')
        ->assertSeeText('Profile Information')
        ->assertSeeText('Change Password')
        ->assertSeeText('Light / Dark Mode')
        ->assertSeeText('Logout')
        // The real forms are still on the page.
        ->assertSee('name="name"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="current_password"', false)
        // confirmLogout() still has exactly one form to submit.
        ->assertSee('id="logoutForm"', false);

    expect(substr_count((string) $response->getContent(), 'id="logoutForm"'))->toBe(1);
});

it('offers confirmation requests to a representative through the profile hub', function (): void {
    $representative = User::factory()->create(['role' => 'user']);
    mobileNavOffice(['representative_user_id' => $representative->id]);

    $this->actingAs($representative)->get(route('profile.edit'))->assertOk()
        ->assertSeeText('Confirmation Requests')
        ->assertSee(route('office.confirmations.index'), false)
        ->assertSeeText('Saved Ideas')
        ->assertSeeText('My Contribution')
        ->assertSeeText('Logout');
});

it('does not offer confirmation requests to a normal user', function (): void {
    mobileNavOffice();

    $user = User::factory()->create(['role' => 'user']);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertDontSee(route('office.confirmations.index'), false);
});
