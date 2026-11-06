<?php

namespace Tests\Feature;

use App\Models\AccessRight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CI safety net: if these fail, nothing gets deployed. */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();   // demo users + reference data (APP_ENV=testing)
    }

    private function user(string $role): User
    {
        return User::where('email', "{$role}@lms.test")->firstOrFail();
    }

    public function test_public_pages_load(): void
    {
        foreach (['/', '/catalog', '/about', '/contact', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_readers_cannot_open_the_back_office(): void
    {
        $this->actingAs($this->user('reader'))->get('/admin')->assertForbidden();
    }

    public function test_admin_screens_load(): void
    {
        $admin = $this->user('admin');
        foreach (['/admin', '/admin/books', '/admin/books/create', '/admin/loans', '/admin/members', '/admin/authors', '/admin/categories',
                  '/admin/departments', '/admin/reports', '/admin/feedback', '/admin/access-rights', '/admin/approval-flows',
                  '/admin/approvals', '/admin/notifications', '/account/password'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_custom_access_rights_can_revoke_a_module(): void
    {
        $clerk = $this->user('user');
        $this->actingAs($clerk)->get('/admin/books')->assertOk();

        AccessRight::create(['user_id' => $clerk->id, 'resource' => 'books',
            'can_read' => false, 'can_create' => false, 'can_update' => false, 'can_delete' => false]);

        $this->actingAs($clerk->fresh())->get('/admin/books')->assertForbidden();
    }

    public function test_admin_can_reset_a_password_and_user_must_change_it(): void
    {
        $admin = $this->user('admin');
        $clerk = $this->user('user');

        $this->actingAs($admin)->post("/admin/access-rights/{$clerk->id}/password", ['password' => 'Temp-Pass-123', 'force_change' => 1])
            ->assertRedirect();

        $clerk = $clerk->fresh();
        $this->assertTrue(password_verify('Temp-Pass-123', $clerk->password));
        $this->assertTrue($clerk->must_change_password);

        // forced to the change-password screen until a new password is set
        $this->actingAs($clerk)->get('/admin')->assertRedirect('/account/password');
        $this->actingAs($clerk)->put('/account/password', [
            'current_password' => 'Temp-Pass-123', 'password' => 'Brand-New-456', 'password_confirmation' => 'Brand-New-456',
        ])->assertRedirect();
        $this->assertFalse($clerk->fresh()->must_change_password);
    }
}
