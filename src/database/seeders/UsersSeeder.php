<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Fernando Bonfim',
            'email' => 'fernando@gmail.com',
            'password' => '$2y$10$L0qqlrbN6/sHh2YUFnyWg.5khsOMgYv2amKOS08ZQesiWmNpXSCPu' //the password is 123
        ]);
    }
}
