<?php

namespace Tests\Feature;

use App\Models\LocationMapping;
use App\Models\Soil;
use App\Models\SoilScan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteScanPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_scan_page_renders_successfully_with_action_buttons(): void
    {
        $response = $this->get(route('website.scan'));
        $response->assertStatus(200);
        $response->assertSee('Save Copy');
        $response->assertSee('Pin Location');
        $response->assertSee('resActionButtonsRow');
        $response->assertSee('btnSaveCopyScan');
        $response->assertSee('btnPinLocationScan');
        $response->assertSee('pinSoilLocationModal');
        $response->assertSee('cameraLightingPopup');
        $response->assertSee('Good Lighting Condition');
        $response->assertSee('not super dark');
        $response->assertSee('not super light');
        $response->assertSee('dismissCameraLightingCenterPopup');
        $response->assertSee('cameraLightingTimerCount');
    }

    public function test_pin_location_endpoint_creates_pending_record_for_regular_user(): void
    {
        $soil = Soil::create([
            'soil_id' => 1,
            'soil_name' => 'Yellowish Brown',
            'full_code' => '10YR 5/4',
            'hue' => '10YR',
            'hex_color' => '#92744F',
            'description' => '10YR 5/4 - Yellowish Brown',
        ]);

        $user = User::create([
            'user_id' => 'USR-TEST-01',
            'name' => 'Regular User',
            'email' => 'regular@agrico.test',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->postJson(route('website.scan.pin'), [
            'latitude' => 18.1972,
            'longitude' => 121.6612,
            'barangay' => 'Bagumbayan',
            'soil_id' => $soil->soil_id,
            'scan_note' => 'Field sample testing',
            'coverage_area' => '500m - 1km (Medium)',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'location' => [
                'status' => 'Pending',
                'barangay' => 'Bagumbayan',
            ],
        ]);

        $this->assertDatabaseHas('location_mappings', [
            'barangay' => 'Bagumbayan',
            'status' => 'Pending',
        ]);

        $this->assertDatabaseHas('user_requests', [
            'user_email' => 'regular@agrico.test',
            'type' => 'Soil Coordinates',
            'status' => 'Pending',
        ]);
    }

    public function test_pin_location_endpoint_creates_active_record_for_admin(): void
    {
        $soil = Soil::create([
            'soil_id' => 2,
            'soil_name' => 'Brown Clay Loam',
            'full_code' => '7.5YR 5/3',
            'hue' => '7.5YR',
            'hex_color' => '#8F694E',
            'description' => '7.5YR 5/3 - Brown Clay Loam',
        ]);

        $admin = User::create([
            'user_id' => 'ADM-TEST-01',
            'name' => 'Admin User',
            'email' => 'admin@agrico.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->postJson(route('website.scan.pin'), [
            'latitude' => 18.2284,
            'longitude' => 121.6701,
            'barangay' => 'Bangag',
            'soil_id' => $soil->soil_id,
            'scan_note' => 'Admin verified site survey',
            'coverage_area' => '1km+ (Wide)',
            'direct_publish' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'location' => [
                'status' => 'Active',
                'barangay' => 'Bangag',
            ],
        ]);

        $this->assertDatabaseHas('location_mappings', [
            'barangay' => 'Bangag',
            'status' => 'Active',
        ]);
    }

    public function test_admin_approval_activates_pin_and_displays_on_website_and_admin_map(): void
    {
        $soil = Soil::create([
            'soil_id' => 3,
            'soil_name' => 'Dark Loam Specimen',
            'full_code' => '10YR 3/2',
            'hue' => '10YR',
            'hex_color' => '#443428',
            'description' => '10YR 3/2 - Very Dark Grayish Brown',
        ]);

        $user = User::create([
            'user_id' => 'USR-TEST-02',
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@agrico.test',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $admin = \App\Models\Admin::create([
            'admin_id' => 'ADM-APPROVAL-01',
            'full_name' => 'System SuperAdmin',
            'email' => 'superadmin@agrico.test',
            'password' => bcrypt('password'),
        ]);

        // 1. Regular user pins a location
        $this->actingAs($user)->postJson(route('website.scan.pin'), [
            'latitude' => 18.2115,
            'longitude' => 121.6624,
            'barangay' => 'Maxingal',
            'soil_id' => $soil->soil_id,
            'scan_note' => 'Near river bank sample',
        ]);

        $userRequest = \App\Models\UserRequest::where('user_email', 'juan@agrico.test')->first();
        $this->assertNotNull($userRequest);
        $this->assertEquals('Pending', $userRequest->status);

        // Before approval: Website Map should NOT show Pending locations
        $mapBefore = $this->get(route('website.map'));
        $mapBefore->assertStatus(200);
        $mapBefore->assertDontSee('Maxingal');

        // 2. Admin approves the pin request
        $approveResponse = $this->actingAs($admin, 'admin')
            ->postJson(route('requests.approve', $userRequest->user_request_id));

        $approveResponse->assertStatus(200);
        $approveResponse->assertJson(['success' => true, 'status' => 'Approved']);

        // Check LocationMapping status updated to Active
        $this->assertDatabaseHas('location_mappings', [
            'barangay' => 'Maxingal',
            'status' => 'Active',
        ]);

        // 3. Website Map now displays the approved location!
        $mapAfter = $this->get(route('website.map'));
        $mapAfter->assertStatus(200);
        $mapAfter->assertSee('Maxingal');
    }

    public function test_website_soil_record_details_has_pin_map_structure(): void
    {
        $response = $this->get(route('website.scan'));
        $response->assertStatus(200);
        $response->assertSee('modalSoilLocationCard');
        $response->assertSee('modalSoilDetailMap');
        $response->assertSee('modalBeforeAfterCard');
    }

    public function test_admin_soil_detail_modal_renders_map_preview_when_location_pinned(): void
    {
        $user = User::create([
            'user_id' => 'USR-TEST-MAP',
            'name' => 'Map Test User',
            'email' => 'mapuser@agrico.test',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $soil = Soil::create([
            'soil_id' => 88,
            'soil_name' => 'Mapped Soil Specimen',
            'full_code' => '5YR 4/6',
            'hue' => '5YR',
            'hex_color' => '#7A3B26',
            'description' => '5YR 4/6 - Test Mapped',
        ]);

        $scan = SoilScan::create([
            'soil_scan_id' => 99,
            'soil_id' => $soil->soil_id,
            'user_id' => $user->user_id,
            'platform' => 'website',
            'scan_date' => now(),
            'scan_note' => 'Field sample [Covered Area: 500 m²]',
        ]);

        $loc = LocationMapping::create([
            'soil_scan_id' => $scan->soil_scan_id,
            'soil_id' => $soil->soil_id,
            'latitude' => 18.2001,
            'longitude' => 121.6622,
            'barangay' => 'Centro (Pob.)',
            'status' => 'Active',
            'timestamp' => now(),
        ]);

        \Livewire\Livewire::test(\App\Livewire\Admin\Soil\SoilDetailModal::class)
            ->call('openModal', $scan->soil_scan_id)
            ->assertSet('showModal', true)
            ->assertSet('location.lat', 18.2001)
            ->assertSet('location.lng', 121.6622)
            ->assertSet('location.barangay', 'Centro (Pob.)')
            ->assertSet('coverage', '500 m²')
            ->assertSee('SOIL PIN LOCATION &amp; COVERED AREA', false)
            ->assertSee('admin-soil-detail-map-99')
            ->assertSee('Centro (Pob.)');
    }

    public function test_pin_location_accepts_valid_lallo_target_point_dagupan(): void
    {
        $soil = Soil::create([
            'soil_id' => 101,
            'soil_name' => 'Pale Yellow',
            'full_code' => '5YR 7/6',
            'hue' => '5YR',
            'hex_color' => '#E5C07B',
            'description' => '5YR 7/6 - Pale Yellow Specimen',
        ]);

        $user = User::create([
            'user_id' => 'USR-TEST-DAGUPAN',
            'name' => 'Lal-lo Farmer',
            'email' => 'farmer@agrico.test',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        // Target point from Problem 1 (18.1746, 121.7260)
        $response = $this->actingAs($user)->postJson(route('website.scan.pin'), [
            'latitude' => 18.1746,
            'longitude' => 121.7260,
            'barangay' => 'Dagupan',
            'soil_id' => $soil->soil_id,
            'scan_note' => 'Soil sample classified as Pale Yellow (5YR 7/6).',
            'coverage_area' => '500 m²',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'location' => [
                'status' => 'Pending',
                'barangay' => 'Dagupan',
            ],
        ]);

        $this->assertDatabaseHas('location_mappings', [
            'barangay' => 'Dagupan',
            'latitude' => 18.1746,
            'longitude' => 121.7260,
            'status' => 'Pending',
        ]);
    }

    public function test_pin_location_rejects_coordinates_outside_lallo(): void
    {
        $soil = Soil::create([
            'soil_id' => 102,
            'soil_name' => 'Outside Soil',
            'full_code' => '10YR 5/6',
            'hue' => '10YR',
            'hex_color' => '#8B5A2B',
            'description' => 'Test outside point',
        ]);

        $user = User::create([
            'user_id' => 'USR-OUTSIDE-TEST',
            'name' => 'Outside User',
            'email' => 'outside@agrico.test',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        // Coordinates in Gattaran (outside Lal-lo)
        $response = $this->actingAs($user)->postJson(route('website.scan.pin'), [
            'latitude' => 18.0800,
            'longitude' => 121.6500,
            'barangay' => 'Outside Lal-lo',
            'soil_id' => $soil->soil_id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Selected location is outside the official Lal-lo, Cagayan municipal coverage area (35 Barangays).'
        ]);
    }

    public function test_lallo_boundary_service_accepts_all_35_barangay_centroids(): void
    {
        foreach (\App\Services\LalloBoundaryService::BARANGAYS_35 as $b) {
            $isInside = \App\Services\LalloBoundaryService::isInsideLallo($b['lat'], $b['lng']);
            $this->assertTrue($isInside, "Failed for barangay centroid: {$b['name']} ({$b['lat']}, {$b['lng']})");

            $detected = \App\Services\LalloBoundaryService::findBarangayAt($b['lat'], $b['lng']);
            $this->assertEquals($b['name'], $detected, "Mismatch for barangay centroid: {$b['name']}");
        }
    }
}
