<?php

namespace Tests\Feature;

use App\Exceptions\LmsException;
use App\Models\ApprovalStep;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeBook(Department $dept): Book
    {
        return Book::create([
            'name' => 'Test Policy', 'publication_year' => 2025, 'version' => '1.0', 'state' => 'draft',
            'author_id' => Author::first()->id, 'category_id' => Category::first()->id,
            'department_id' => $dept->id, 'copies_total' => 1,
        ]);
    }

    public function test_approval_follows_the_assigned_sequence(): void
    {
        $this->seed();
        $qa = User::where('email', 'qa@lms.test')->first();
        $manager = User::where('email', 'manager@lms.test')->first();
        $admin = User::where('email', 'admin@lms.test')->first();
        $dept = Department::where('name', 'HR')->first();

        ApprovalStep::create(['department_id' => $dept->id, 'sequence' => 1, 'title' => 'QA review', 'role' => 'qa']);
        ApprovalStep::create(['department_id' => $dept->id, 'sequence' => 2, 'title' => 'Manager approval', 'role' => 'manager']);

        $book = $this->makeBook($dept);
        $book->submitForReview($qa);
        $book = $book->fresh();
        $this->assertSame('review', $book->state);

        // step 1: only QA (and the admin override) - NOT the manager yet
        $this->assertTrue($book->canBeApprovedBy($qa));
        $this->assertFalse($book->canBeApprovedBy($manager));
        $this->assertTrue($book->canBeApprovedBy($admin));

        try {
            $book->approve($manager);
            $this->fail('Manager must not approve before QA.');
        } catch (LmsException $e) {
            $this->assertStringContainsString('not your turn', $e->getMessage());
        }

        $book->approve($qa);
        $book = $book->fresh();
        $this->assertSame('review', $book->state);                 // still waiting for step 2
        $this->assertFalse($book->canBeApprovedBy($qa));           // QA's turn is over
        $this->assertTrue($book->canBeApprovedBy($manager));       // now it's the manager's turn
        $this->assertTrue($manager->notifications()->whereNull('read_at')->exists());   // manager was told

        $book->approve($manager);
        $this->assertSame('approved', $book->fresh()->state);
    }

    public function test_a_named_person_can_be_the_only_approver(): void
    {
        $this->seed();
        $qa = User::where('email', 'qa@lms.test')->first();
        $other = User::create(['name' => 'Second QA', 'email' => 'qa2@lms.test', 'password' => 'password', 'role' => 'qa']);
        $dept = Department::where('name', 'Finance')->first();

        ApprovalStep::create(['department_id' => $dept->id, 'sequence' => 1, 'title' => 'Finance review', 'user_id' => $other->id]);

        $book = $this->makeBook($dept);
        $book->submitForReview($qa);
        $book = $book->fresh();

        $this->assertFalse($book->canBeApprovedBy($qa));           // same role, but not the named person
        $this->assertTrue($book->canBeApprovedBy($other));
    }

    public function test_rejection_returns_the_book_to_draft_and_notifies_the_requester(): void
    {
        $this->seed();
        $qa = User::where('email', 'qa@lms.test')->first();
        $dept = Department::where('name', 'HR')->first();

        $book = $this->makeBook($dept);
        $book->submitForReview($qa);
        $book->fresh()->reject($qa, 'Needs a revised introduction');

        $this->assertSame('draft', $book->fresh()->state);
        $this->assertTrue($qa->notifications()->where('data', 'like', '%rejected%')->exists());
    }

    public function test_opening_a_notification_marks_it_read(): void
    {
        $this->seed();
        $qa = User::where('email', 'qa@lms.test')->first();
        $book = $this->makeBook(Department::where('name', 'HR')->first());
        $book->submitForReview($qa);

        $note = $qa->notifications()->whereNull('read_at')->firstOrFail();
        $this->actingAs($qa)->get("/admin/notifications/{$note->id}/open")->assertRedirect();
        $this->assertNotNull($note->fresh()->read_at);
    }
}
