<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Create the site administrator, or promote the existing account.
     */
    public function run(): void
    {
        $email = config('chareharch.admin_email');

        $admin = User::where('email', $email)->first();

        if (! $admin) {
            $password = config('chareharch.admin_password');

            if (blank($password)) {
                $password = Str::password(20);
                $this->command?->warn("ADMIN_PASSWORD is not set. Generated password for {$email}: {$password}");
            }

            $admin = new User([
                'name' => 'Admin',
                'email' => $email,
                'password' => $password,
            ]);
            $admin->email_verified_at = now();
        }

        $admin->role = Role::Admin;
        $admin->can_edit = true;
        $admin->save();
    }
}
