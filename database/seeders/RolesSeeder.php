<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Создаем роли
        $adminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
        $studentRole = Role::firstOrCreate(['name' => 'Student']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $curatorRole = Role::firstOrCreate(['name' => 'Curator']);

        // 2. Находим твоего пользователя (ID 1) и делаем Админом
        $user = User::find(1);
        if ($user) {
            $user->assignRole($adminRole);
        }
    }
}