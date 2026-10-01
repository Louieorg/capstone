<?php

use App\Models\User;

function studentNavigationUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

test('the student sidebar shows the simplified navigation structure', function (): void {
    $student = studentNavigationUser(['name' => 'Rina Santos', 'role' => 'user']);

    $this->actingAs($student)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('class="sb-brand"', false)
        ->assertSeeText('LIKHA')
        ->assertSeeText('Main')
        ->assertSeeText('Home Feed')
        ->assertSeeText('Submit Problem')
        ->assertSeeText('Discover')
        ->assertSeeText('Capstone Opportunities')
        ->assertSeeText('Personal')
        ->assertSeeText('Saved Ideas')
        ->assertSeeText('My Contribution');
});

test('the student sidebar no longer links to priority problems or the category summary', function (): void {
    $student = studentNavigationUser();

    $this->actingAs($student)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSeeText('Priority Problems')
        ->assertDontSeeText('Category Summary');
});

test('the student sidebar stays expanded regardless of the stored sidebar state', function (): void {
    $student = studentNavigationUser();

    $this->actingAs($student)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('collapsed: false', false)
        ->assertDontSee("localStorage.getItem('sidebar')", false);
});

test('the student layout has no top header row and keeps the sidebar', function (): void {
    $student = studentNavigationUser();

    $this->actingAs($student)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('class="app-header', false)
        ->assertDontSee('class="h-hamburger', false)
        ->assertDontSee('class="h-search"', false)
        ->assertSee('class="sb-brand"', false);
});

test('the student notification control sits in the main content area', function (): void {
    $student = studentNavigationUser();

    $this->actingAs($student)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('class="app-topbar"', false)
        ->assertSee('class="h-icon-btn notif-trigger', false)
        ->assertSee(route('notifications.dropdown'), false)
        ->assertSee(route('notifications'), false)
        ->assertSee('x-ref="notificationList"', false);
});

test('the student account control carries the profile, theme, and logout actions', function (): void {
    $student = studentNavigationUser(['name' => 'Rina Santos']);

    $response = $this->actingAs($student)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('class="sb-account"', false)
        ->assertSee('class="sb-account-trigger"', false)
        ->assertSee('Rina Santos')
        ->assertSeeText('Profile')
        ->assertSeeText('Toggle theme')
        ->assertSeeText('Logout');

    expect(substr_count($response->getContent(), 'id="logoutForm"'))->toBe(1);
});

test('the student layout keeps the guest sign in and registration actions', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('class="app-topbar"', false)
        ->assertSeeText('Sign in')
        ->assertSeeText('Get started')
        ->assertDontSee('class="sb-account"', false)
        ->assertDontSee('class="sb-divider"', false)
        ->assertDontSeeText('Saved Ideas')
        ->assertDontSeeText('Personal');
});

test('the sidebar-driven main pages drop the redundant page title bar', function (): void {
    $student = studentNavigationUser();

    $this->actingAs($student)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('class="page-bar"', false)
        ->assertDontSee('class="page-lede"', false)
        ->assertSeeText('Campus friction, made visible.');

    foreach (['discover', 'capstone.opportunities', 'feedback.create'] as $routeName) {
        $this->actingAs($student)
            ->get(route($routeName))
            ->assertOk()
            ->assertDontSee('class="page-bar"', false)
            ->assertSee('class="page-lede"', false);
    }
});

test('the main pages keep the description that explains them', function (): void {
    $student = studentNavigationUser();

    $this->actingAs($student)
        ->get(route('discover'))
        ->assertOk()
        ->assertSeeText('Browse institutional problems by momentum, support, severity, and fresh capstone activity.');

    $this->actingAs($student)
        ->get(route('capstone.opportunities'))
        ->assertOk()
        ->assertSeeText('Institutional problems already identified as potential capstone projects.');

    $this->actingAs($student)
        ->get(route('feedback.create'))
        ->assertOk()
        ->assertSeeText('Report an issue that affects members of the campus community');
});

test('pages without a sidebar entry keep their page title bar', function (): void {
    $student = studentNavigationUser();

    $this->actingAs($student)
        ->get(route('notifications'))
        ->assertOk()
        ->assertSee('class="page-bar"', false)
        ->assertSeeText('Notifications');
});

test('the admin layout has no top header and keeps notifications in the main area', function (): void {
    $admin = studentNavigationUser(['name' => 'Admin Dela Cruz', 'role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        // The admin shell no longer renders a top header row at all.
        ->assertDontSee('class="app-header', false)
        ->assertDontSee('class="h-hamburger', false)
        ->assertDontSee('class="h-search"', false)
        // Notifications float in the top-right of the content area instead.
        ->assertSee('class="app-topbar"', false)
        ->assertSee('class="h-icon-btn notif-trigger', false)
        // The persistent expanded sidebar still carries branding and the account control.
        ->assertSee('class="app-sidebar"', false)
        ->assertSee('class="sb-brand"', false)
        ->assertSee('class="sb-account-trigger"', false);
});
