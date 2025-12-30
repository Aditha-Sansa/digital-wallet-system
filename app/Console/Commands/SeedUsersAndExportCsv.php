<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use League\Csv\Writer;
use SplFileObject;

class SeedUsersAndExportCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:seed-and-export 
                            {--total=100000 : Number of users to seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed users and export UUIDs with fixed amount to CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::disableQueryLog();

        $total = (int) $this->option('total');
        $chunkSize = 500;
        $amount = '100.00';

        $hashedPassword = Hash::make('password');
        $now = now();

        $path = storage_path('app/exports');
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $filePath = $path.'/users_wallet_seed.csv';

        $csv = Writer::createFromFileObject(
            new SplFileObject($filePath, 'w')
        );

        $csv->insertOne(['uuid', 'amount']);

        $this->info("Seeding {$total} users...");
        $this->info("Writing CSV to: {$filePath}");

        for ($offset = 0; $offset < $total; $offset += $chunkSize) {
            $users = [];
            $csvRows = [];

            for ($i = 0; $i < $chunkSize && ($offset + $i) < $total; $i++) {
                $uuid = (string) Str::orderedUuid();

                $users[] = [
                    'uuid' => $uuid,
                    'name' => "User {$offset}{$i}",
                    'email' => "user{$offset}{$i}@example.com",
                    'email_verified_at' => $now,
                    'password' => $hashedPassword,
                    'remember_token' => Str::random(10),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $csvRows[] = [$uuid, $amount];
            }

            DB::table('users')->insert($users);

            $csv->insertAll($csvRows);

            unset($users, $csvRows);
            gc_collect_cycles();
            usleep(1000);

            if ($offset % 5000 === 0) {
                $this->info("Processed {$offset} users");
            }
        }

        $this->info('Seeding and CSV export completed');

        return Command::SUCCESS;
    }
}
