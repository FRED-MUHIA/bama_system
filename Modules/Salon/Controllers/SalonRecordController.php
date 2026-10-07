<?php

namespace Modules\Salon\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Salon\Models as Models;
use Modules\Salon\Services\SalonRecords;
use Modules\Salon\Services\SalonBooking;

class SalonRecordController extends Controller
{
    private function definition(string $type): array
    {
        $definition = SalonRecords::definition($type);
        abort_unless(auth()->user()?->hasPermission('salon.'.$definition['permission'].'.manage'), 403);

        return $definition;
    }

    public function create(string $type)
    {
        $definition = $this->definition($type);
        abort_unless(in_array($type, ['resources', 'schedules', 'packages'], true), 404);

        return $this->form($type, $definition, new $definition['model']);
    }

    public function store(Request $request, string $type)
    {
        $definition = $this->definition($type);
        abort_unless(in_array($type, ['resources', 'schedules', 'packages'], true), 404);
        $data = $this->validated($request, $definition);
        DB::transaction(function () use ($type, $definition, $data) {
            $this->checkSchedule($type, $data);
            $definition['model']::create($data);
        });

        return redirect()->route('salon.'.$definition['index'].'.index')->with('success', $definition['label'].' created.');
    }

    public function edit(string $type, int $record)
    {
        $definition = $this->definition($type);
        $record = $definition['model']::findOrFail($record);
        $this->assertEditable($record);

        return $this->form($type, $definition, $record);
    }

    private function form(string $type, array $definition, $record)
    {
        $fields = $definition['fields'];
        foreach ($fields as &$field) {
            if (isset($field['model'])) {
                $query = $field['model']::query();
                if ($field['model'] === Models\ClientProfile::class) {
                    $query->with('client');
                }
                $field['options'] = $query->get()->mapWithKeys(fn ($row) => [$row->id => SalonRecords::label($row)])->all();
            }
        }
        unset($field);

        return view('salon.edit', compact('type', 'definition', 'record', 'fields'));
    }

    private function validated(Request $request, array $definition): array
    {
        $rules = array_map(fn ($field) => $field['rules'], $definition['fields']);
        if (isset($rules['ends_on'])) {
            $rules['ends_on'][] = 'after_or_equal:starts_on';
        }
        if (isset($rules['service_ids'])) {
            $rules['service_ids.*'] = ['required', 'integer', 'distinct', SalonRecords::exists(Models\Service::class)];
        }
        $data = $request->validate($rules);
        foreach ($definition['fields'] as $name => $field) {
            if ($field['lines'] ?? false) {
                $data[$name] = array_values(array_filter(array_map('trim', preg_split('/\R/', $data[$name] ?? ''))));
            }
        }
        if (! empty($data['salon_appointment_id']) && isset($data['salon_client_profile_id'])) {
            $appointment = Models\Appointment::findOrFail($data['salon_appointment_id']);
            if ((int) $appointment->salon_client_profile_id !== (int) $data['salon_client_profile_id']) {
                throw ValidationException::withMessages(['salon_appointment_id' => 'Choose an appointment belonging to this client.']);
            }
        }

        return $data;
    }

    public function update(Request $request, string $type, int $record)
    {
        $definition = $this->definition($type);
        $data = $this->validated($request, $definition);
        DB::transaction(function () use ($type, $record, $definition, $data) {
            $record = $definition['model']::whereKey($record)->lockForUpdate()->firstOrFail();
            $this->assertEditable($record);
            $this->checkSchedule($type, $data, $record->id);
            if ($record instanceof Models\ClientProfile) {
                $record->client()->firstOrFail()->update(collect($data)->only(['name', 'phone', 'email'])->all());
                unset($data['name'], $data['phone'], $data['email']);
            }
            if ($record instanceof Models\Appointment) {
                $duration = max(5, (int) $record->services()->sum('duration_minutes'));
                $data['ends_at'] = Carbon::parse($data['starts_at'])->addMinutes($duration);
                if (! in_array($data['status'], ['Cancelled', 'No Show'], true)) {
                    app(SalonBooking::class)->ensureAvailable($data, $record->id, $record->services()->pluck('salon_staff_profile_id')->filter()->all());
                }
            }
            if ($record instanceof Models\Commission) {
                $data['amount'] = round($data['base_amount'] * $data['rate'] / 100, 2);
            }
            if ($record instanceof Models\ProductConsumption) {
                $product = Product::whereKey($record->product_id)->lockForUpdate()->firstOrFail();
                $delta = (float) $data['quantity'] - (float) $record->quantity;
                if ($delta > (float) $product->stock_quantity) {
                    throw ValidationException::withMessages(['quantity' => 'There is not enough stock for this usage.']);
                }
                if ($delta > 0) {
                    app(StockService::class)->consume($product, $delta, $record, $record->reference, 'Salon usage correction.');
                } elseif ($delta < 0) {
                    app(StockService::class)->receive($product, -$delta, $record, $record->reference, 'Salon usage correction.');
                }
                $data['total_cost'] = round($data['quantity'] * $data['unit_cost'], 2);
            }
            if ($record instanceof Models\GiftCard && $record->status !== $data['status']) {
                $data['transactions'] = [...($record->transactions ?? []), ['type' => 'Status', 'from' => $record->status, 'to' => $data['status'], 'at' => now()->toISOString()]];
            }
            $record->update($data);
            if ($record instanceof Models\LoyaltyAccount) {
                $record->profile?->update(['loyalty_tier' => $data['tier']]);
            }
        });

        return redirect()->route('salon.'.$definition['index'].'.index')->with('success', $definition['label'].' updated.');
    }

    private function assertEditable($record): void
    {
        if ($record instanceof Models\Appointment && ($record->status === 'Completed' || $record->invoice_id || $record->pos_order_id || $record->payment_status === 'Paid')) {
            throw ValidationException::withMessages(['record' => 'Completed or settled appointments are locked to preserve their history.']);
        }
        if ($record instanceof Models\Commission && ($record->status === 'Paid' || $record->payment_id)) {
            throw ValidationException::withMessages(['record' => 'Paid commissions cannot be changed.']);
        }
    }

    private function checkSchedule(string $type, array $data, ?int $id = null): void
    {
        if ($type !== 'schedules') {
            return;
        }
        Models\StaffProfile::whereKey($data['salon_staff_profile_id'])->lockForUpdate()->firstOrFail();
        $query = Models\StaffSchedule::where('salon_staff_profile_id', $data['salon_staff_profile_id'])
            ->whereDate('work_date', $data['work_date'])->when($id, fn ($q) => $q->where('id', '!=', $id));
        if ((clone $query)->where('starts_at', $data['starts_at'])->exists()
            || ($data['status'] !== 'Cancelled' && $query->where('status', '!=', 'Cancelled')->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at'])->exists())) {
            throw ValidationException::withMessages(['starts_at' => 'This staff member already has a shift during that time.']);
        }
    }

    public function destroy(string $type, int $record)
    {
        $definition = $this->definition($type);
        DB::transaction(function () use ($definition, $record) {
            $record = $definition['model']::whereKey($record)->lockForUpdate()->firstOrFail();
            $this->assertEditable($record);
            $this->assertDeletable($record);
            if ($record instanceof Models\ProductConsumption) {
                $product = Product::whereKey($record->product_id)->lockForUpdate()->firstOrFail();
                app(StockService::class)->receive($product, (float) $record->quantity, $record, $record->reference, 'Deleted salon usage; stock restored.');
            }
            if ($record instanceof Models\Appointment) {
                $record->services()->delete();
            }
            if ($record instanceof Models\ClientProfile) {
                $record->loyaltyAccount()->delete();
            }
            $record->delete();
        });

        return redirect()->route('salon.'.$definition['index'].'.index')->with('success', $definition['label'].' deleted.'.($type === 'consumptions' ? ' Stock has been restored.' : ''));
    }

    private function assertDeletable($record): void
    {
        $references = match (true) {
            $record instanceof Models\ClientProfile => [
                [Models\Appointment::class, 'salon_client_profile_id'], [Models\Membership::class, 'salon_client_profile_id'],
                [Models\Consultation::class, 'salon_client_profile_id'], [Models\Treatment::class, 'salon_client_profile_id'], [Models\WellnessEnrollment::class, 'salon_client_profile_id'],
            ],
            $record instanceof Models\StaffProfile => [
                [Models\Appointment::class, 'salon_staff_profile_id'], [Models\AppointmentService::class, 'salon_staff_profile_id'],
                [Models\Commission::class, 'salon_staff_profile_id'], [Models\StaffSchedule::class, 'salon_staff_profile_id'],
                [Models\Consultation::class, 'salon_staff_profile_id'], [Models\Treatment::class, 'salon_staff_profile_id'],
            ],
            $record instanceof Models\Service => [[Models\AppointmentService::class, 'salon_service_id'], [Models\Treatment::class, 'salon_service_id'], [Models\ProductConsumption::class, 'salon_service_id']],
            $record instanceof Models\Resource => [[Models\Appointment::class, 'salon_resource_id']],
            $record instanceof Models\MembershipPlan => [[Models\Membership::class, 'salon_membership_plan_id']],
            $record instanceof Models\WellnessProgram => [[Models\WellnessEnrollment::class, 'salon_wellness_program_id']],
            $record instanceof Models\Appointment => [
                [Models\ProductConsumption::class, 'salon_appointment_id'], [Models\Commission::class, 'salon_appointment_id'],
                [Models\Consultation::class, 'salon_appointment_id'], [Models\Treatment::class, 'salon_appointment_id'],
            ],
            default => [],
        };
        $blocked = false;
        foreach ($references as [$model, $column]) {
            $blocked = $blocked || $model::where($column, $record->id)->exists();
        }
        if ($record instanceof Models\Service) {
            $blocked = $blocked || Models\Package::get()->contains(fn ($package) => in_array($record->id, $package->service_ids ?? []));
        }
        if ($record instanceof Models\ClientProfile) {
            $blocked = $blocked || $record->lifetime_visits > 0 || ($record->loyaltyAccount?->lifetime_points ?? 0) > 0;
        }
        if ($record instanceof Models\LoyaltyAccount) {
            $blocked = true;
        }
        if ($record instanceof Models\Membership) {
            $blocked = $blocked || (bool) $record->invoice_id;
        }
        if ($record instanceof Models\GiftCard) {
            $blocked = true;
        }
        if ($blocked) {
            throw ValidationException::withMessages(['record' => 'This record has linked history or balances and cannot be deleted. Use its status controls where available.']);
        }
    }
}
