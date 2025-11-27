<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SeedAdminUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-admin-user-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed an admin user into the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::firstOrCreate(
            ['email' => 'matt@stackrats.com'],
            [
                'name' => config('mail.from.name'),
                'password' => 'password',
            ]
        );

        $user->userSetting()->updateOrCreate(
            [],
            [
                'timezone' => \App\Enums\Timezones::ASIA_HO_CHI_MINH,
            ]
        );

    }
}
