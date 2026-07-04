<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '9998887777',
        ]);

        $response->assertRedirect();
        $this->assertSame('Updated Name', $user->fresh()->name);
        $this->assertSame('9998887777', $user->fresh()->phone);
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.password.update'), [
            'current_password' => 'password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('new-strong-password', $user->fresh()->password));
    }

    public function test_correct_current_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertSessionHasErrors('current_password');
    }
}
