<?php

use App\Models\Feedback;
use App\Models\User;

it('renders the admin dashboard when feedback contains null affected groups', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);

    Feedback::query()->create([
        'title' => 'Broken scheduling flow',
        'description' => 'Students cannot access the registration portal during peak hours.',
        'impact' => 'High impact for students.',
        'category' => 'System',
        'department' => 'Registrar',
        'frequency' => 'Often',
        'current_process' => 'Manual',
        'affected_users' => 'Students',
        'affected_group' => ['Students', null],
        'status' => 'approved',
        'is_flagged' => false,
    ]);

    Feedback::query()->create([
        'title' => 'Pending review item',
        'description' => 'Awaiting admin moderation.',
        'impact' => 'Needs review.',
        'category' => 'Operations',
        'department' => 'Office',
        'frequency' => 'Occasionally',
        'current_process' => 'Manual',
        'affected_users' => 'Staff',
        'affected_group' => null,
        'status' => 'pending',
        'is_flagged' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Admin Dashboard');
});
