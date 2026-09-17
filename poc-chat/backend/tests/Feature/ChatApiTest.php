<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_existing_messages_of_a_conversation(): void
    {
        [$conversation, $user1, $user2] = $this->seedConversation();

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->id,
            'content' => 'Bonjour',
            'sent_at' => now()->subMinute(),
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user2->id,
            'content' => 'Bonjour, comment allez-vous ?',
            'sent_at' => now(),
        ]);

        $response = $this->getJson("/api/conversations/{$conversation->id}/messages");

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.content', 'Bonjour')
            ->assertJsonPath('0.sender.name', 'Utilisateur 1')
            ->assertJsonPath('1.content', 'Bonjour, comment allez-vous ?')
            ->assertJsonPath('1.sender.name', 'Utilisateur 2');
    }

    public function test_it_stores_a_message_and_broadcasts_it(): void
    {
        Event::fake([MessageSent::class]);

        [$conversation, $user1] = $this->seedConversation();

        $response = $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'sender_id' => $user1->id,
            'content' => 'Très bien merci',
        ]);

        $response->assertCreated()
            ->assertJsonPath('content', 'Très bien merci')
            ->assertJsonPath('sender_id', $user1->id)
            ->assertJsonPath('sender.name', 'Utilisateur 1');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->id,
            'content' => 'Très bien merci',
        ]);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($conversation) {
            return $event->message->content === 'Très bien merci'
                && $event->message->conversation_id === $conversation->id;
        });
    }

    public function test_it_rejects_an_empty_message(): void
    {
        [$conversation, $user1] = $this->seedConversation();

        $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'sender_id' => $user1->id,
            'content' => '',
        ])->assertUnprocessable();
    }

    /**
     * @return array{0: Conversation, 1: User, 2: User}
     */
    private function seedConversation(): array
    {
        $user1 = User::factory()->create(['name' => 'Utilisateur 1']);
        $user2 = User::factory()->create(['name' => 'Utilisateur 2']);

        $conversation = Conversation::query()->create([
            'status' => 'open',
            'user_id' => $user1->id,
        ]);

        $conversation->participants()->attach([$user1->id, $user2->id]);

        return [$conversation, $user1, $user2];
    }
}
