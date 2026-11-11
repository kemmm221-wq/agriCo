<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Application;

return new class extends Migration
{
    public function up(): void
    {
        Application::where('status', 'ACTIVE')->update(['status' => 'ARCHIVED']);

        Application::create([
            'application_name' => 'AgriCo Mobile App',
            'version'          => 'v1.0.7',
            'file_path'        => 'https://github.com/kemmm221-wq/agriCo/releases/download/v1.0.7/agrico-app-v1.0.7.apk',
            'filename'         => 'agrico-app-v1.0.7.apk',
            'file_size'        => '84.3 MB',
            'release_notes'    => "• 100% Offline Mode (⚡) on-device spectral matching in under 2 seconds\n• Bundled Real Soil (10R, 10YR, 2.5Y, 2.5YR, 5YR, 7.5YR) and Fake Soil reference datasets\n• Fixed Delta E = Infinity bug with 207 pre-cached Munsell soil centroids\n• Calibrated skin and granular texture filters to prevent false rejections on real soil",
            'status'           => 'ACTIVE',
            'uploaded_by'      => 'kemmm221-wq',
            'is_maintenance'   => false,
        ]);
    }

    public function down(): void
    {
        Application::where('version', 'v1.0.7')->delete();
        Application::where('version', 'v1.0.6')->update(['status' => 'ACTIVE']);
    }
};

