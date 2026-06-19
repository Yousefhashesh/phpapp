<?php

namespace Tests\Unit;

use App\Support\Services\FinancialFormulaService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialFormulaServiceTest extends TestCase
{
    public function test_it_calculates_supported_formula_variables(): void
    {
        $service = new FinancialFormulaService();

        $result = $service->calculateFormula('total_amount - shipping_fee - settlement_fees', [
            'total_amount' => 100,
            'shipping_fee' => 20,
            'commission_amount' => 5,
            'company_amount' => 15,
            'cod_amount' => 80,
            'settlement_fees' => 3,
        ]);

        $this->assertSame(77.0, $result);
    }

    public function test_it_rejects_unknown_variables(): void
    {
        $this->expectException(ValidationException::class);

        (new FinancialFormulaService())->calculateFormula('total_amount - unknown_value', [
            'total_amount' => 100,
        ]);
    }
}
