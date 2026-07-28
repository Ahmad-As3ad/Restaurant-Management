<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(Request $request)
    {
        $query = Table::query();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->capacity) {
            $query->where('capacity', '>=', $request->capacity);
        }

        $tables = $query->orderBy('table_number')->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $tables,
        ]);
    }

    public function available(Request $request)
    {
        $date = $request->date ?: now()->toDateString();
        $time = $request->time ?: now()->format('H:i');

        $tables = Table::where('status', 'available')
            ->orWhere('status', 'reserved')
            ->get()
            ->filter(function ($table) use ($date, $time) {
                return $table->isAvailable($date, $time);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $tables,
        ]);
    }

    public function show($id)
    {
        $table = Table::with('bookings')->find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $table,
        ]);
    }

    public function checkAvailability(Request $request, $id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found',
            ], 404);
        }

        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
        ]);

        $isAvailable = $table->isAvailable($request->date, $request->time);

        return response()->json([
            'success' => true,
            'data' => [
                'table_id' => $table->id,
                'table_number' => $table->table_number,
                'date' => $request->date,
                'time' => $request->time,
                'is_available' => $isAvailable,
            ],
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'table_number' => 'required|string|unique:tables,table_number',
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'price_per_person' => 'required|numeric|min:0',
        ]);

        $table = Table::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Table created successfully',
            'data' => $table,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found',
            ], 404);
        }

        $request->validate([
            'table_number' => "sometimes|string|unique:tables,table_number,{$id}",
            'capacity' => 'sometimes|integer|min:1',
            'location' => 'nullable|string|max:255',
            'price_per_person' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:available,occupied,reserved',
        ]);

        $table->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Table updated successfully',
            'data' => $table,
        ]);
    }

    public function destroy($id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found',
            ], 404);
        }

        if ($table->bookings()->whereIn('status', ['pending', 'confirmed'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete table with active bookings',
            ], 400);
        }

        $table->delete();

        return response()->json([
            'success' => true,
            'message' => 'Table deleted successfully',
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found',
            ], 404);
        }

        $request->validate([
            'status' => 'required|in:available,occupied,reserved',
        ]);

        $table->status = $request->status;
        $table->save();

        return response()->json([
            'success' => true,
            'message' => 'Table status updated',
            'data' => $table,
        ]);
    }
}
