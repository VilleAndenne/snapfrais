<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApproveExpenseSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_sets_validated_at_timestamp(): void
    {
        Notification::fake();

        $department = Department::factory()->create();
        $head = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($head->id, ['is_head' => true]);

        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        $form = Form::factory()->create();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'is_draft' => false,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => null,
            'validated_at' => null,
        ]);

        $now = Carbon::parse('2026-04-15 10:30:00');
        Carbon::setTestNow($now);

        $response = $this->actingAs($head)->post("/expense-sheet/{$expenseSheet->id}/approve", [
            'approval' => 1,
        ]);

        $response->assertRedirect("/expense-sheet/{$expenseSheet->id}");

        $expenseSheet->refresh();
        $this->assertTrue((bool) $expenseSheet->approved);
        $this->assertEquals($head->id, $expenseSheet->validated_by);
        $this->assertNotNull($expenseSheet->validated_at);
        $this->assertInstanceOf(Carbon::class, $expenseSheet->validated_at);
        $this->assertTrue($now->equalTo($expenseSheet->validated_at));

        Carbon::setTestNow();
    }

    public function test_validated_at_is_exposed_on_show_page(): void
    {
        $department = Department::factory()->create();
        $head = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($head->id, ['is_head' => true]);

        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        $form = Form::factory()->create();

        $validatedAt = Carbon::parse('2026-04-10 14:25:00');

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'is_draft' => false,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => true,
            'validated_by' => $head->id,
            'validated_at' => $validatedAt,
            'status' => 'Approuvée',
        ]);

        $response = $this->actingAs($user)->get("/expense-sheet/{$expenseSheet->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenseSheet/Show')
            ->where('expenseSheet.validated_at', $validatedAt->toJson())
        );
    }
}
