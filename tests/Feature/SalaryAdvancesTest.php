<?php

namespace Tests\Feature;

use App\Models\SalaryAdvance;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaryAdvancesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_director_can_create_advance_and_record_partial_and_full_repayments(): void
    {
        $director = User::factory()->create();
        $director->assignRole('directeur');
        $staff = Staff::factory()->create(['user_id' => null]);

        $this->actingAs($director)
            ->post(route('staff.advances.store'), [
                'staff_id' => $staff->id,
                'issued_on' => now()->toDateString(),
                'amount' => 100000,
                'purpose' => 'Dépense urgente',
                'notes' => 'Avance validée par la direction',
            ])
            ->assertRedirect();

        $advance = SalaryAdvance::firstOrFail();
        $this->assertSame(100000, $advance->balance);

        $this->post(route('staff.advances.repayments.store', $advance), [
            'amount' => 30000,
            'repaid_on' => now()->toDateString(),
            'payment_method' => 'cash',
            'reference' => 'CAISSE-001',
        ])->assertRedirect();

        $this->assertSame(70000, $advance->fresh()->balance);
        $this->assertSame(1, $advance->repayments()->count());

        $this->from(route('staff.advances.show', $advance))
            ->post(route('staff.advances.repayments.store', $advance), [
                'amount' => 70001,
                'repaid_on' => now()->toDateString(),
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');

        $this->post(route('staff.advances.repayments.store', $advance), [
            'amount' => 70000,
            'repaid_on' => now()->toDateString(),
            'payment_method' => 'cash',
        ])->assertRedirect();

        $this->assertSame(0, $advance->fresh()->balance);
        $this->from(route('staff.advances.show', $advance))
            ->delete(route('staff.advances.destroy', $advance))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('salary_advances', ['id' => $advance->id]);
    }

    public function test_director_can_update_and_delete_advance_without_repayments(): void
    {
        $director = User::factory()->create();
        $director->assignRole('directeur');
        $staff = Staff::factory()->create(['user_id' => null]);
        $advance = SalaryAdvance::create([
            'staff_id' => $staff->id,
            'issued_on' => now()->toDateString(),
            'amount' => 50000,
            'purpose' => 'Initial',
        ]);

        $this->actingAs($director)
            ->get(route('staff.advances.edit', $advance))
            ->assertOk();

        $this->put(route('staff.advances.update', $advance), [
            'staff_id' => $staff->id,
            'issued_on' => now()->toDateString(),
            'amount' => 60000,
            'purpose' => 'Mise à jour',
        ])->assertRedirect();

        $this->assertSame(60000, $advance->fresh()->amount);

        $this->delete(route('staff.advances.destroy', $advance))
            ->assertRedirect(route('staff.advances.index'));
        $this->assertDatabaseMissing('salary_advances', ['id' => $advance->id]);
    }

    public function test_payslip_only_records_the_selected_partial_advance_repayment(): void
    {
        $director = User::factory()->create();
        $director->assignRole('directeur');
        $staff = Staff::factory()->create([
            'user_id' => null,
            'contract_type' => 'permanent',
            'monthly_salary' => 150000,
        ]);
        $advance = SalaryAdvance::create([
            'staff_id' => $staff->id,
            'issued_on' => now()->toDateString(),
            'amount' => 50000,
            'purpose' => 'Avance de salaire',
        ]);

        $this->actingAs($director)
            ->post(route('staff.pay-slip.store', $staff), ['period' => now()->format('Y-m')])
            ->assertOk();

        $this->assertDatabaseHas('staff_pay_slips', [
            'staff_id' => $staff->id,
            'period' => now()->format('Y-m'),
            'amount_received' => 150000,
        ]);
        $this->assertSame(50000, $advance->fresh()->balance);
        $this->assertDatabaseCount('salary_advance_repayments', 0);

        $previousPeriod = now()->copy()->subMonth()->format('Y-m');
        $this->post(route('staff.pay-slip.store', $staff), [
            'period' => $previousPeriod,
            'repay_advance' => '1',
            'repayment_amount' => 20000,
        ])->assertOk()->assertSee('20 000 FCFA');

        $repayment = \App\Models\SalaryAdvanceRepayment::query()
            ->where('salary_advance_id', $advance->id)
            ->where('amount', 20000)
            ->firstOrFail();
        $this->assertSame(today()->toDateString(), $repayment->repaid_on->toDateString());
        $this->assertSame('salary_deduction', $repayment->payment_method);
        $this->assertSame($director->id, $repayment->recorded_by);
        $this->assertSame(30000, $advance->fresh()->balance);
        $this->assertDatabaseHas('staff_pay_slips', [
            'staff_id' => $staff->id,
            'period' => $previousPeriod,
            'amount_received' => 130000,
        ]);
    }

    public function test_payslip_rejects_repayment_above_the_advance_or_gross_salary(): void
    {
        $director = User::factory()->create();
        $director->assignRole('directeur');
        $staff = Staff::factory()->create([
            'user_id' => null,
            'contract_type' => 'permanent',
            'monthly_salary' => 30000,
        ]);
        $advance = SalaryAdvance::create([
            'staff_id' => $staff->id,
            'issued_on' => now()->toDateString(),
            'amount' => 50000,
            'purpose' => 'Avance de salaire',
        ]);

        $this->actingAs($director)
            ->from(route('staff.pay-slip', $staff))
            ->post(route('staff.pay-slip.store', $staff), [
                'period' => now()->format('Y-m'),
                'repay_advance' => '1',
                'repayment_amount' => 40000,
            ])
            ->assertSessionHasErrors('repayment_amount');

        $this->assertSame(50000, $advance->fresh()->balance);
        $this->assertDatabaseCount('salary_advance_repayments', 0);
        $this->assertDatabaseCount('staff_pay_slips', 0);
    }

    public function test_econome_cannot_view_or_manage_salary_advances(): void
    {
        $econome = User::factory()->create();
        $econome->assignRole('econome');
        $staff = Staff::factory()->create(['user_id' => null]);

        $this->actingAs($econome)
            ->get(route('staff.advances.index'))
            ->assertForbidden();

        $this->post(route('staff.advances.store'), [
            'staff_id' => $staff->id,
            'issued_on' => now()->toDateString(),
            'amount' => 10000,
        ])->assertForbidden();
    }
}
