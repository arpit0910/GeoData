<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! User::where('email', 'admin@geodata.com')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@geodata.com',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]);
        }
    }
}
