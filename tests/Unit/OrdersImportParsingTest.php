<?php

namespace Tests\Unit;

use App\Imports\OrdersImport;
use ReflectionClass;
use Tests\TestCase;

class OrdersImportParsingTest extends TestCase
{
    public function test_it_parses_order_amounts_safely(): void
    {
        $sheet = $this->makeImportSheet();
        $parse = (new ReflectionClass($sheet))->getMethod('parseMoneyValue');
        $parse->setAccessible(true);

        $this->assertSame(470.0, $parse->invoke($sheet, '470', 2));
        $this->assertSame(470.0, $parse->invoke($sheet, '٤٧٠', 2));
        $this->assertSame(20000.5, $parse->invoke($sheet, '20,000.50', 2));
        $this->assertSame(470.25, $parse->invoke($sheet, '470,25', 2));
    }

    public function test_it_rejects_empty_order_amounts(): void
    {
        $sheet = $this->makeImportSheet();
        $parse = (new ReflectionClass($sheet))->getMethod('parseMoneyValue');
        $parse->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);

        $parse->invoke($sheet, '', 2);
    }

    private function makeImportSheet(): object
    {
        $import = new OrdersImport(1);
        $sheetClass = new ReflectionClass('App\\Imports\\OrdersImportSheet');

        return $sheetClass->newInstance(1, $import);
    }
}
