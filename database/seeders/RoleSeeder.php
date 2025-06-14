<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run()
    {
        DB::table('roles')->insert([
            [
                'name' => 'Admin',
                'guard_name' => 'web',
                'created_at' => now()
            ],
            [
                'name' => 'Farmer',
                'guard_name' => 'web',
                'created_at' => now()
            ]
        ]);
    }
}
