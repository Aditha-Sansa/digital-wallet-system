<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        DB::disableQueryLog();

        $total = 100000;
        $chunkSize = 500;
        $hashedPassword = Hash::make('password');
        $now = now();

        for ($offset = 0; $offset < $total; $offset += $chunkSize) {
            $users = [];

            for ($i = 0; $i < $chunkSize && ($offset + $i) < $total; $i++) {
                $index = $offset + $i;

                $users[] = [
                    'uuid' => (string) Str::orderedUuid(),
                    'name' => "FN User {$index}",
                    'email' => "user{$index}@example.com",
                    'email_verified_at' => $now,
                    'password' => $hashedPassword,
                    'remember_token' => Str::random(10),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('users')->insert($users);

            unset($users);
            gc_collect_cycles();
        }
    }
}
