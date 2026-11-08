<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $newHash = Hash::make('Password123!');
        $kentHash = Hash::make('Kent12345*');
        $superEmails = ['agricosite77@gmail.com', 'admin@agrico.com'];
        $userEmails = ['delacruzwinwin57@gmail.com'];

        DB::table('users')
            ->whereIn(DB::raw('LOWER(email)'), $superEmails)
            ->update([
                'password' => $newHash,
                'role'     => 'superadmin',
                'status'   => 'Active',
            ]);

        DB::table('users')
            ->whereIn(DB::raw('LOWER(email)'), $userEmails)
            ->update([
                'password' => $newHash,
                'status'   => 'Active',
            ]);

        DB::table('users')
            ->where(DB::raw('LOWER(email)'), 'kent@gmail.com')
            ->update([
                'password' => $kentHash,
                'status'   => 'Active',
            ]);

        DB::table('admins')
            ->whereIn(DB::raw('LOWER(email)'), array_merge($superEmails, $userEmails))
            ->update([
                'password' => $newHash,
            ]);

        DB::table('admins')
            ->where(DB::raw('LOWER(email)'), 'kent@gmail.com')
            ->update([
                'password' => $kentHash,
            ]);
    }

    public function down(): void
    {
        // No down migration needed
    }
};

