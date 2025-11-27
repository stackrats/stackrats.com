<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed lookup tables first
        $this->call([
            InvoiceStatusSeeder::class,
            RecurringFrequencySeeder::class,
        ]);

        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->userSetting()->create([
            'timezone' => \App\Enums\Timezones::PACIFIC_AUCKLAND,
        ]);
    }
}
