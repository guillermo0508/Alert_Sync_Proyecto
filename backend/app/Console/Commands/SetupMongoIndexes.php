<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupMongoIndexes extends Command
{
    protected $signature = 'mongodb:setup-indexes';

    protected $description = 'Crea los índices necesarios en MongoDB para ALERT SYNC';

    public function handle(): int
    {
        $db = DB::connection('mongodb')->getMongoDB();

        // email is intentionally NOT unique at the DB level: multiple admin accounts are
        // allowed to share one contact email. Regular-user email uniqueness is enforced in
        // AuthController::register() / Admin\UserController::store()-update() at the app level.
        $db->selectCollection('users')->createIndex(['email' => 1]);
        $db->selectCollection('users')->createIndex(['username' => 1], ['unique' => true, 'sparse' => true]);
        $db->selectCollection('contacts')->createIndex(['user_id' => 1, 'priority' => 1]);
        $db->selectCollection('alerts')->createIndex(['user_id' => 1, 'created_at' => -1]);
        $db->selectCollection('payments')->createIndex(['user_id' => 1, 'created_at' => -1]);
        $db->selectCollection('plans')->createIndex(['plan_id' => 1], ['unique' => true]);
        $db->selectCollection('personal_access_tokens')->createIndex(['token' => 1]);

        $this->info('Índices de MongoDB creados correctamente.');

        return self::SUCCESS;
    }
}
