<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users');
        
        $user = new User();
        $user->role = 1;
        $user->name = 'Kevin Ringo';
        $user->email = 'kevin@gmail.com';
        $user->phone = 81234567890;
        $user->password = Hash::make('1234qweR!');
        $user->save();
    }
}
