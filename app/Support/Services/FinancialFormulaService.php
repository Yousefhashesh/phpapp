<?php

namespace App\Support\Services;

use App\Models\Setting;
use Illuminate\Validation\ValidationException;

class FinancialFormulaService
{
    /**
     * @param  array<string, float|int|null>  $variables
     */
    public function calculate(string $settingKey, array $variables): float
    {
        $formula = trim((string) Setting::getValue($settingKey, $this->defaultFormula($settingKey)));

        if ($formula === '') {
            $formula = $this->defaultFormula($settingKey);
        }

        return $this->calculateFormula($formula, $variables);
    }

    /**
     * @param  array<string, float|int|null>  $variables
     */
    public function calculateFormula(string $formula, array $variables): float
    {
        return round($this->evaluate($formula, $variables), 2);
    }

    public function defaultFormula(string $settingKey): string
    {
        return (string) (Setting::getDefaults()[$settingKey] ?? '0');
    }

    /**
     * @param  array<string, float|int|null>  $variables
     */
    // private function evaluate(string $formula, array $variables): float
    // {
    //     if (! preg_match('/^[a-zA-Z0-9_\s+\-*\/().]+$/', $formula)) {
    //         throw ValidationException::withMessages([
    //             'formula' => ['Formula contains unsupported characters. Use numbers, variables, +, -, *, /, and parentheses only.'],
    //         ]);
    //     }

    //     $expression = preg_replace_callback('/\b[a-zA-Z_][a-zA-Z0-9_]*\b/', function (array $matches) use ($variables): string {
    //         $name = $matches[0];

    //         if (! array_key_exists($name, $variables)) {
    //             throw ValidationException::withMessages([
    //                 'formula' => ["Unknown formula variable: {$name}."],
    //             ]);
    //         }

    //         return (string) ((float) ($variables[$name] ?? 0));
    //     }, $formula);

    //     if ($expression === null || preg_match('/\/\s*0(?:\.0+)?(?![0-9.])/', $expression)) {
    //         throw ValidationException::withMessages([
    //             'formula' => ['Formula cannot be evaluated safely.'],
    //         ]);
    //     }

    //     try {
    //         /** @var float|int $result */
    //         $result = eval("return {$expression};");
    //     } catch (\Throwable $exception) {
    //         throw ValidationException::withMessages([
    //             'formula' => ['Formula could not be calculated: '.$exception->getMessage()],
    //         ]);
    //     }

    //     if (! is_numeric($result) || ! is_finite((float) $result)) {
    //         throw ValidationException::withMessages([
    //             'formula' => ['Formula result must be a valid number.'],
    //         ]);
    //     }

    //     return (float) $result;
    // }
    private function evaluate(string $formula, array $variables): float
{
    $formula = trim($formula);

  
    if ($formula === '') {
        return 0;
    }

    if (! preg_match('/^[a-zA-Z0-9_\s+\-*\/().]+$/', $formula)) {
   
        return 0;
    }

    $expression = preg_replace_callback('/\b[a-zA-Z_][a-zA-Z0-9_]*\b/', function (array $matches) use ($variables): string {
        $name = $matches[0];

        return (string) ((float) ($variables[$name] ?? 0));
    }, $formula);

    if ($expression === null) {
        return 0;
    }

    try {
        /** @var float|int $result */
        $result = eval("return {$expression};");
    } catch (\Throwable $exception) {
        return 0;
    }

    if (! is_numeric($result) || ! is_finite((float) $result)) {
        return 0;
    }

    return (float) $result;
}
}
