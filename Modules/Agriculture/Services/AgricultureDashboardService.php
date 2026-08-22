<?php

namespace Modules\Agriculture\Services;

use Modules\Agriculture\Models\Animal;
use Modules\Agriculture\Models\BudgetLine;
use Modules\Agriculture\Models\ComplianceRecord;
use Modules\Agriculture\Models\CropPlan;
use Modules\Agriculture\Models\Equipment;
use Modules\Agriculture\Models\Farm;
use Modules\Agriculture\Models\FarmActivity;
use Modules\Agriculture\Models\Field;
use Modules\Agriculture\Models\Harvest;
use Modules\Agriculture\Models\InputUsage;
use Modules\Agriculture\Models\ProduceBatch;
use Modules\Agriculture\Models\ProduceSale;
use Modules\Agriculture\Models\VeterinaryRecord;

class AgricultureDashboardService
{
    public function metrics(): array
    {
        $farmArea = (float) Farm::sum('total_area');
        $inputCost = (float) InputUsage::sum('cost');
        $operatingCost = $inputCost + (float) FarmActivity::sum('cost') + (float) VeterinaryRecord::sum('treatment_cost');
        $salesRevenue = (float) ProduceSale::sum('total');
        $expectedHarvest = (float) CropPlan::whereNotIn('status', ['Closed', 'Cancelled'])->sum('expected_yield');
        $harvestCompleted = (float) Harvest::sum('quantity');

        return [
            'total_farms' => Farm::count(),
            'total_area' => $farmArea,
            'active_fields' => Field::whereIn('status', ['Available', 'Preparing', 'Planted', 'Growing', 'Harvesting'])->count(),
            'crops_planted' => CropPlan::whereIn('status', ['Planted', 'Growing', 'Harvest Ready'])->count(),
            'expected_harvest' => $expectedHarvest,
            'harvest_completed' => $harvestCompleted,
            'livestock_count' => Animal::where('status', 'Active')->count(),
            'farm_input_cost' => $inputCost,
            'farm_operating_cost' => $operatingCost,
            'sales_revenue' => $salesRevenue,
            'gross_farm_margin' => $salesRevenue - $operatingCost,
            'equipment_availability' => Equipment::count() > 0 ? round((Equipment::where('status', 'Available')->count() / Equipment::count()) * 100, 1) : 0,
            'active_farm_workers' => \Modules\Agriculture\Models\FarmWorker::where('status', 'Active')->count(),
            'pending_farm_activities' => FarmActivity::whereIn('status', ['Planned', 'Assigned', 'In Progress', 'Overdue'])->count(),
        ];
    }

    public function panels(): array
    {
        $fieldCount = Field::count();
        $cultivated = Field::whereIn('status', ['Planted', 'Growing', 'Harvesting'])->count();

        return [
            'farm' => [
                'farms' => Farm::count(),
                'total_area' => (float) Farm::sum('total_area'),
                'active_fields' => Field::where('status', '!=', 'Maintenance')->count(),
                'cultivated_fields' => $cultivated,
                'idle_fields' => Field::whereIn('status', ['Available', 'Fallow'])->count(),
                'utilization' => $fieldCount ? round(($cultivated / $fieldCount) * 100, 1) : 0,
            ],
            'livestock' => [
                'total' => Animal::where('status', 'Active')->count(),
                'by_category' => Animal::selectRaw('species, count(*) as total')->groupBy('species')->pluck('total', 'species')->all(),
                'births_this_month' => \Modules\Agriculture\Models\BreedingEvent::whereMonth('birth_date', now()->month)->whereYear('birth_date', now()->year)->sum('offspring_count'),
                'deaths_this_month' => Animal::where('status', 'Deceased')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
                'under_treatment' => VeterinaryRecord::whereNotIn('recovery_status', ['Recovered', 'Closed'])->count(),
                'upcoming_vaccinations' => VeterinaryRecord::whereIn('record_type', ['Vaccination', 'Deworming'])->whereDate('next_due_date', '<=', now()->addDays(30))->count(),
                'production' => (float) \Modules\Agriculture\Models\LivestockProduction::sum('quantity'),
            ],
            'crop' => [
                'active_plans' => CropPlan::whereNotIn('status', ['Closed', 'Cancelled'])->count(),
                'upcoming_planting' => CropPlan::whereDate('planting_date', '>=', now())->whereDate('planting_date', '<=', now()->addDays(30))->count(),
                'upcoming_fertilization' => FarmActivity::where('activity_type', 'Fertilizer Application')->whereDate('scheduled_date', '>=', now())->count(),
                'upcoming_spraying' => FarmActivity::whereIn('activity_type', ['Pesticide Application', 'Herbicide Application'])->whereDate('scheduled_date', '>=', now())->count(),
                'expected_harvest_dates' => CropPlan::with('crop', 'field')->whereDate('expected_harvest_date', '>=', now())->orderBy('expected_harvest_date')->limit(6)->get(),
            ],
            'harvest' => [
                'expected' => (float) CropPlan::sum('expected_yield'),
                'completed' => (float) Harvest::sum('quantity'),
                'yield_per_area' => Farm::sum('total_area') > 0 ? round(Harvest::sum('quantity') / Farm::sum('total_area'), 2) : 0,
                'harvest_value' => (float) Harvest::sum('value'),
                'losses' => (float) Harvest::sum('waste_quantity'),
                'produce_in_storage' => (float) ProduceBatch::whereIn('stage', ['Storage', 'Processing'])->sum('quantity'),
                'produce_sold' => (float) ProduceSale::sum('quantity'),
            ],
        ];
    }

    public function alerts(): array
    {
        return collect([
            CropPlan::where('status', 'Harvest Ready')->exists() ? ['type' => 'Crop Ready for Harvest', 'severity' => 'High'] : null,
            FarmActivity::whereDate('scheduled_date', '<', today())->whereNotIn('status', ['Completed', 'Cancelled'])->exists() ? ['type' => 'Overdue Farm Activity', 'severity' => 'High'] : null,
            \Modules\Agriculture\Models\AgricultureInput::whereColumn('quantity_on_hand', '<=', 'reorder_level')->exists() ? ['type' => 'Low Input Stock', 'severity' => 'Moderate'] : null,
            \Modules\Agriculture\Models\AgricultureInput::whereDate('expiry_date', '<=', now()->addDays(30))->exists() ? ['type' => 'Expiring Chemicals', 'severity' => 'Moderate'] : null,
            VeterinaryRecord::whereDate('next_due_date', '<=', now()->addDays(14))->exists() ? ['type' => 'Upcoming Vaccination', 'severity' => 'Moderate'] : null,
            \Modules\Agriculture\Models\EquipmentMaintenance::whereDate('next_service_date', '<=', now()->addDays(14))->exists() ? ['type' => 'Equipment Maintenance Due', 'severity' => 'Moderate'] : null,
            ComplianceRecord::whereDate('expiry_date', '<=', now()->addDays(45))->exists() ? ['type' => 'Compliance Certificate Expiring', 'severity' => 'High'] : null,
            \Modules\Agriculture\Models\PestDiseaseIncident::whereIn('severity', ['High', 'Critical'])->whereNotIn('status', ['Resolved', 'Closed'])->exists() ? ['type' => 'Crop Disease Alert', 'severity' => 'Critical'] : null,
            BudgetLine::whereColumn('actual_amount', '>=', 'budget_amount')->where('budget_amount', '>', 0)->exists() ? ['type' => 'Budget Threshold Exceeded', 'severity' => 'High'] : null,
        ])->filter()->values()->all();
    }
}
