<?php

namespace Tests\Feature\APIRequests;

use App\Enums\Status;
use App\Models\BookCopy;
use App\Models\BorrowRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BorrowAPIRequestTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function cannot_borrow_a_book_with_invalid_data()
    {
        $response = $this->postJson(route('borrows.store'), []);

        $response->assertStatus(400);
        $response->assertJsonStructure(['status', 'message']);
        $this->assertArrayHasKey('barcode', $response->json('message'));
        $this->assertArrayHasKey('membership_number', $response->json('message'));
    }

    /** @test */
    public function cannot_borrow_with_invalid_barcode()
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('borrows.store'), [
            'barcode' => 'invalid-barcode',
            'membership_number' => $user->membership_number,
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['barcode' => ['The selected barcode is invalid.']]);
    }

    /** @test */
    public function cannot_borrow_with_nonexistent_user()
    {
        $bookCopy = BookCopy::factory()->create();

        $response = $this->postJson(route('borrows.store'), [
            'barcode' => $bookCopy->barcode,
            'membership_number' => 'fake-member',
        ]);

        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => 'User not found.']);
    }

    /** @test */
    public function cannot_borrow_unavailable_book_copy()
    {
        $user = User::factory()->create();
        $bookCopy = BookCopy::factory()->create(['status' => Status::BORROWED]);

        $response = $this->postJson(route('borrows.store'), [
            'barcode' => $bookCopy->barcode,
            'membership_number' => $user->membership_number,
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['message' => 'Book copy not available for borrowing.']);
    }

    /** @test */
    public function can_borrow_book_with_valid_borrow_request()
    {
        $user = User::factory()->create();
        $bookCopy = BookCopy::factory()->create(['status' => Status::AVAILABLE]);

        BorrowRequest::factory()->create([
            'user_id' => $user->id,
            'book_copy_id' => $bookCopy->id,
            'requested_until' => now()->addDay(),
        ]);

        $response = $this->postJson(route('borrows.store'), [
            'barcode' => $bookCopy->barcode,
            'membership_number' => $user->membership_number,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Book borrowed successfully (fulfilling reservation).']);

        $this->assertDatabaseHas('borrows', [
            'user_id' => $user->id,
            'book_copy_id' => $bookCopy->id,
        ]);

        $this->assertDatabaseMissing('borrow_requests', [
            'user_id' => $user->id,
            'book_copy_id' => $bookCopy->id,
        ]);
    }
}
