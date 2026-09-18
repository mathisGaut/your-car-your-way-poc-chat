<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->resetDemoTables();

        $user1 = User::forceCreate([
            'id' => 1,
            'name' => 'Utilisateur 1',
            'email' => 'utilisateur1@ycyw.test',
            'password' => 'password',
        ]);

        $user2 = User::forceCreate([
            'id' => 2,
            'name' => 'Utilisateur 2',
            'email' => 'utilisateur2@ycyw.test',
            'password' => 'password',
        ]);

        $conversation = Conversation::forceCreate([
            'id' => 1,
            'status' => 'open',
            'user_id' => $user1->id,
        ]);

        $conversation->participants()->sync([$user1->id, $user2->id]);

        Message::forceCreate([
            'id' => 1,
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->id,
            'content' => 'Bonjour',
            'sent_at' => now()->subMinutes(2),
        ]);

        Message::forceCreate([
            'id' => 2,
            'conversation_id' => $conversation->id,
            'sender_id' => $user2->id,
            'content' => 'Bonjour, comment allez-vous ?',
            'sent_at' => now()->subMinute(),
        ]);

        $this->syncPostgresSequences();
    }

    private function resetDemoTables(): void
    {
        $tables = ['messages', 'conversation_user', 'conversations', 'users'];

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('TRUNCATE TABLE '.implode(', ', $tables).' RESTART IDENTITY CASCADE');

            return;
        }

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            DB::table($table)->delete();
        }

        Schema::enableForeignKeyConstraints();
    }

    private function syncPostgresSequences(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['users', 'conversations', 'conversation_user', 'messages'] as $table) {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1))"
            );
        }
    }
}
