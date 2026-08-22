<?php

namespace Modules\Agriculture\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DocumentTemplate;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Support\ActiveTenant;
use Modules\Agriculture\Models\AgricultureDocument;
use Modules\Agriculture\Models\AgricultureInput;
use Modules\Agriculture\Models\Animal;
use Modules\Agriculture\Models\BreedingEvent;
use Modules\Agriculture\Models\BudgetLine;
use Modules\Agriculture\Models\ComplianceRecord;
use Modules\Agriculture\Models\Crop;
use Modules\Agriculture\Models\CropPlan;
use Modules\Agriculture\Models\Equipment;
use Modules\Agriculture\Models\EquipmentMaintenance;
use Modules\Agriculture\Models\Farm;
use Modules\Agriculture\Models\FarmActivity;
use Modules\Agriculture\Models\Farmer;
use Modules\Agriculture\Models\FarmSeason;
use Modules\Agriculture\Models\FarmWorker;
use Modules\Agriculture\Models\Field;
use Modules\Agriculture\Models\Harvest;
use Modules\Agriculture\Models\Herd;
use Modules\Agriculture\Models\InputUsage;
use Modules\Agriculture\Models\IrrigationSchedule;
use Modules\Agriculture\Models\PestDiseaseIncident;
use Modules\Agriculture\Models\Plot;
use Modules\Agriculture\Models\ProduceBatch;
use Modules\Agriculture\Models\ProduceSale;
use Modules\Agriculture\Models\ProduceWarehouse;
use Modules\Agriculture\Models\VeterinaryRecord;
use Modules\Agriculture\Models\WeatherRecord;
use Modules\Agriculture\Services\AgricultureDashboardService;

class AgricultureDashboardController extends Controller
{
    public function __invoke(AgricultureDashboardService $dashboard)
    {
        $tenant = ActiveTenant::current();
        $package = require base_path('Modules/Agriculture/module.php');
        $subIndustry = collect($package['sub_industries'] ?? [])->firstWhere('slug', $tenant?->sub_industry);

        return view('agriculture.dashboard', [
            'subIndustryName' => $subIndustry['name'] ?? 'Agriculture',
            'activeModules' => $subIndustry['modules'] ?? $package['features'],
            'metrics' => $dashboard->metrics(),
            'panels' => $dashboard->panels(),
            'alerts' => $dashboard->alerts(),
            'farms' => Farm::with('manager')->latest()->limit(50)->get(),
            'fields' => Field::with('farm')->latest()->limit(50)->get(),
            'plots' => Plot::with('field')->latest()->limit(30)->get(),
            'seasons' => FarmSeason::with('farm')->latest()->limit(30)->get(),
            'crops' => Crop::latest()->limit(50)->get(),
            'cropPlans' => CropPlan::with('farm', 'field', 'crop')->latest()->limit(50)->get(),
            'activities' => FarmActivity::with('farm', 'field', 'cropPlan.crop', 'worker')->latest()->limit(80)->get(),
            'harvests' => Harvest::with('farm', 'field', 'crop')->latest()->limit(50)->get(),
            'batches' => ProduceBatch::with('farm', 'harvest.crop')->latest()->limit(50)->get(),
            'herds' => Herd::with('farm')->latest()->limit(30)->get(),
            'animals' => Animal::with('farm', 'herd')->latest()->limit(60)->get(),
            'vetRecords' => VeterinaryRecord::with('animal', 'herd')->latest()->limit(50)->get(),
            'breedingEvents' => BreedingEvent::with('animal', 'herd')->latest()->limit(30)->get(),
            'inputs' => AgricultureInput::with('product', 'supplier')->latest()->limit(50)->get(),
            'inputUsages' => InputUsage::with('input', 'farm', 'field', 'cropPlan.crop')->latest()->limit(50)->get(),
            'incidents' => PestDiseaseIncident::with('farm', 'field', 'crop')->latest()->limit(30)->get(),
            'irrigation' => IrrigationSchedule::with('farm', 'field')->latest()->limit(30)->get(),
            'equipment' => Equipment::with('farm', 'operator')->latest()->limit(50)->get(),
            'maintenance' => EquipmentMaintenance::with('equipment')->latest()->limit(30)->get(),
            'workers' => FarmWorker::with('farm', 'field')->latest()->limit(50)->get(),
            'warehouses' => ProduceWarehouse::with('farm')->latest()->limit(30)->get(),
            'sales' => ProduceSale::with('client', 'produceBatch')->latest()->limit(50)->get(),
            'farmers' => Farmer::with('client')->latest()->limit(50)->get(),
            'compliance' => ComplianceRecord::with('farm')->latest()->limit(30)->get(),
            'budgets' => BudgetLine::with('farm', 'field')->latest()->limit(30)->get(),
            'weatherRecords' => WeatherRecord::with('farm')->latest()->limit(30)->get(),
            'documents' => AgricultureDocument::with('farm', 'documentable', 'documentTemplate')->latest()->limit(50)->get(),
            'users' => User::where('role', '!=', 'client_portal')->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'clients' => Client::orderBy('name')->limit(100)->get(),
            'documentTemplates' => DocumentTemplate::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
