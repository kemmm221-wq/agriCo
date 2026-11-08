<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\Admin\Application\UploadApplication;

class UploadApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_mount_upload_application_even_with_zero_applications(): void
    {
        Application::query()->delete();
        $this->assertEquals(0, Application::count());

        $superAdmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'agricosite77@gmail.com',
        ]);

        $this->actingAs($superAdmin);

        Livewire::test(UploadApplication::class)
            ->assertStatus(200)
            ->assertSee('Upload Application');

        // Verify it seeded a default version without throwing 500
        $this->assertGreaterThan(0, Application::count());
    }

    public function test_superadmin_can_access_upload_application_screen_via_route(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'admin@agrico.com',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('application.upload'));

        $response->assertStatus(200);
        $response->assertSee('Upload Application');
    }

    public function test_regular_admin_without_superadmin_privileges_is_forbidden(): void
    {
        $admin = User::factory()->create([
            'user_id' => 'ADM-002',
            'role' => 'admin',
            'email' => 'staff@agrico.com',
        ]);

        $response = $this->actingAs($admin)->get(route('application.upload'));

        $response->assertStatus(403);
    }

    public function test_guest_can_download_application_directly(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('applications/agrico-app-v1.0.6.apk', 'PK-dummy-apk-content');

        Application::create([
            'application_name' => 'AgriCo Mobile App',
            'version'          => 'v1.0.6',
            'filename'         => 'agrico-app-v1.0.6.apk',
            'file_size'        => '62.5 MB',
            'release_notes'    => 'Test release',
            'status'           => 'ACTIVE',
            'uploaded_by'      => 'Admin',
            'file_path'        => 'applications/agrico-app-v1.0.6.apk',
        ]);

        $response = $this->get(route('website.download'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.android.package-archive');
    }

    public function test_approved_user_download_route_handles_download(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('applications/agrico-app-v1.0.6.apk', 'PK-dummy-apk-content');

        Application::create([
            'application_name' => 'AgriCo Mobile App',
            'version'          => 'v1.0.6',
            'filename'         => 'agrico-app-v1.0.6.apk',
            'file_size'        => '62.5 MB',
            'release_notes'    => 'Test release',
            'status'           => 'ACTIVE',
            'uploaded_by'      => 'Admin',
            'file_path'        => 'applications/agrico-app-v1.0.6.apk',
        ]);

        $user = User::factory()->create([
            'role'   => 'user',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('website.download'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.android.package-archive');
    }

    public function test_download_redirect_mode_redirects_to_canonical_github_url(): void
    {
        Application::create([
            'application_name' => 'AgriCo Mobile App',
            'version'          => 'v1.0.6',
            'filename'         => 'agrico-app-v1.0.6.apk',
            'file_size'        => '62.5 MB',
            'release_notes'    => 'Test release',
            'status'           => 'ACTIVE',
            'uploaded_by'      => 'Admin',
            'file_path'        => 'https://github.com/agricosite77-oss/agrico/releases/download/v1.0.6/agrico-app-v1.0.6.apk',
        ]);

        $user = User::factory()->create([
            'role'   => 'user',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('website.download', ['mode' => 'redirect']));
        $response->assertRedirect('https://github.com/agricosite77-oss/agrico/releases/download/v1.0.6/agrico-app-v1.0.6.apk');
    }
}

