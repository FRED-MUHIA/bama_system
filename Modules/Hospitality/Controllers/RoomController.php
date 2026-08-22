<?php

namespace Modules\Hospitality\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Hospitality\Models\Room;
use Modules\Hospitality\Models\RoomType;

class RoomController extends Controller
{
    public function index()
    {
        return view('hospitality.index', [
            'title' => 'Rooms',
            'section' => 'rooms',
            'records' => Room::with('roomType')->orderBy('floor')->orderBy('room_number')->paginate(60),
            'roomTypes' => RoomType::orderBy('name')->get(),
            'statuses' => Room::STATUSES,
            'defaultRoomTypes' => RoomType::DEFAULTS,
        ]);
    }

    public function storeType(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'capacity' => ['required', 'integer', 'min:1'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'amenities' => ['nullable', 'string'],
        ]);

        RoomType::create(array_merge($data, [
            'slug' => Str::slug($data['name']),
            'amenities' => $this->csv($data['amenities'] ?? null),
        ]));

        return back()->with('status', 'Room type created.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_type_id' => ['nullable', 'exists:hospitality_room_types,id'],
            'room_number' => ['required', 'string', 'max:40'],
            'status' => ['required', Rule::in(Room::STATUSES)],
            'capacity' => ['required', 'integer', 'min:1'],
            'floor' => ['nullable', 'string', 'max:40'],
            'view' => ['nullable', 'string', 'max:80'],
            'bed_type' => ['nullable', 'string', 'max:80'],
            'amenities' => ['nullable', 'string'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        Room::create(array_merge($data, ['amenities' => $this->csv($data['amenities'] ?? null)]));

        return back()->with('status', 'Room created.');
    }

    public function updateStatus(Request $request, Room $room)
    {
        $data = $request->validate(['status' => ['required', Rule::in(Room::STATUSES)]]);
        $room->update($data);

        return back()->with('status', 'Room status updated.');
    }

    private function csv(?string $value): array
    {
        return collect(explode(',', (string) $value))->map(fn ($item) => trim($item))->filter()->values()->all();
    }
}
