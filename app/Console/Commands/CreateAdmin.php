<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'shop:admin {email} {--name=Store owner}';

    protected $description = 'Create the first owner securely. Password input is hidden.';

    public function handle(): int
    {
        $data = ['name' => $this->option('name'), 'email' => $this->argument('email'), 'password' => $this->secret('Choose a password (12+ characters, upper/lowercase, number, symbol)')];
        $validator = Validator::make($data, ['name' => 'required|string|max:120', 'email' => 'required|email|max:190|unique:users', 'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        if ($this->secret('Confirm password') !== $data['password']) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }
        User::create([...$data, 'role' => 'owner', 'is_active' => true]);
        $this->info('Owner account created. Login path: /'.config('shop.admin_path').'/login');

        return self::SUCCESS;
    }
}
