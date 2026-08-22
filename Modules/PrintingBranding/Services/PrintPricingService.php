<?php

namespace Modules\PrintingBranding\Services;

use Modules\PrintingBranding\Models\PricingRule;

class PrintPricingService
{
    public function calculate(array $data): array
    {
        $costs = collect([
            'artwork_charges',
            'setup_charges',
            'machine_cost',
            'labor_cost',
            'material_cost',
            'outsourcing_cost',
            'delivery_cost',
        ])->sum(fn ($key) => (float) ($data[$key] ?? 0));

        $markup = (float) ($data['markup'] ?? $this->defaultMarkup($data));
        $discount = (float) ($data['discount'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $selling = round(max($costs + (($costs * $markup) / 100) - $discount + $tax, 0), 2);

        return [
            'total_cost' => round($costs, 2),
            'selling_price' => $selling,
            'estimated_profit' => round($selling - $costs, 2),
        ];
    }

    private function defaultMarkup(array $data): float
    {
        $rule = PricingRule::where('is_active', true)
            ->whereIn('rule_type', ['Material Markup', 'Quantity Pricing', 'Customer Pricing'])
            ->orderByDesc('id')
            ->first();

        return $rule ? (float) $rule->rate : (float) ($data['default_markup'] ?? 30);
    }
}
