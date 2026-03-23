<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public static $startDate;
    public static $dbInsertBlockSize = 500;

    // public static $seedType = "small";
    //public static $seedType = "full";
    //public static $seedLanguage = "pt_PT";
    public static $seedLanguage = "en_US";

    public function run(): void
    {
        $this->command->info("-----------------------------------------------");
        $this->command->info("START of database seeder");
        $this->command->info("-----------------------------------------------");

        self::$startDate = Carbon::now()->subMonths(14);
        self::$seedLanguage = $this->command->choice('What is the language for users\' names?', ['pt_PT', 'en_US'], 0);

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            //DB::statement('SET foreign_key_checks=0');
            // No permissions to change global setting. Change the session setting only
            //DB::statement("SET @@global.time_zone = '+00:00'");
            //DB::statement("SET time_zone = '+00:00'");
            DB::statement("SET TIME ZONE 'UTC'");
        }

        // Limpeza das tabelas respeitando FKs
        if (DB::getDriverName() === 'pgsql') {
            // TRUNCATE em cascata evita conflitos de FK na ordem de deleção
            DB::statement('TRUNCATE TABLE coin_purchases, coin_transactions, games, matches, users, coin_transaction_types RESTART IDENTITY CASCADE');
        } else {
            // Ordem manual para drivers sem TRUNCATE CASCADE (ex.: sqlite, mysql)
            DB::table('coin_purchases')->delete();
            DB::table('coin_transactions')->delete();
            DB::table('games')->delete();
            DB::table('matches')->delete();
            DB::table('users')->delete();
            DB::table('coin_transaction_types')->delete();
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'users'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'matches'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'games'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'coin_purchases'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'coin_transactions'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name = 'coin_transaction_types'");
        } else {
            /*
            DB::statement('ALTER TABLE users AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE matches AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE games AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE coin_purchases AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE coin_transactions AUTO_INCREMENT = 0');
            DB::statement('ALTER TABLE coin_transaction_types AUTO_INCREMENT = 0');
            */
            DB::statement('ALTER SEQUENCE users_id_seq RESTART WITH 1');
            DB::statement('ALTER SEQUENCE matches_id_seq RESTART WITH 1');
            DB::statement('ALTER SEQUENCE games_id_seq RESTART WITH 1');
            DB::statement('ALTER SEQUENCE coin_purchases_id_seq RESTART WITH 1');
            DB::statement('ALTER SEQUENCE coin_transactions_id_seq RESTART WITH 1');
            DB::statement('ALTER SEQUENCE coin_transaction_types_id_seq RESTART WITH 1');

        }

        $this->command->info("-----------------------------------------------");

        $this->call(TransactionTypesSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(InitialTransactionsSeeder::class);
        $this->call(GamesSeeder::class);
        $this->call(GamesTransactionsSeeder::class);

        // Ajustar sequências do PostgreSQL para tabelas com IDs definidos manualmente nos seeders
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                SELECT setval('coin_transactions_id_seq',
                    COALESCE((SELECT MAX(id) FROM coin_transactions), 0)
                )
            ");

            DB::statement("
                SELECT setval('coin_purchases_id_seq',
                    COALESCE((SELECT MAX(id) FROM coin_purchases), 0)
                )
            ");
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            //DB::statement('SET foreign_key_checks=1');
        }



        $this->command->info("-----------------------------------------------");
        $this->command->info("END of database seeder");
        $this->command->info("-----------------------------------------------");
    }
}
