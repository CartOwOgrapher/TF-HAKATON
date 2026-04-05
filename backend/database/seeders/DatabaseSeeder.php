<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Core\Auth\Models\User;
use Modules\Core\Enums\Role;
 

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
         dump('UserSeeder started');
        $users = [
            [
                'name' => 'Администратор',
                'email' => 'admin@test.com',
                'password' => 'password',
                'role' => Role::Admin,
            ],
            [
                'name' => 'HR Специалист',
                'email' => 'hr@test.com',
                'password' => 'password',
                'role' => Role::HR,
            ],
            [
                'name' => 'Центр обучения',
                'email' => 'training@test.com',
                'password' => 'password',
                'role' => Role::TrainingCenter,
            ],
            [
                'name' => 'Бухгалтерия',
                'email' => 'accounting@test.com',
                'password' => 'password',
                'role' => Role::Accounting,
            ],
        ];
 
        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
