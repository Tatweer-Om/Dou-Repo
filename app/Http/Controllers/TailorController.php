<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Tailor;
use App\Models\History;
use App\Models\SpecialOrderItem;
use App\Models\SpecialOrder;
use App\Models\Stock;
use App\Models\TailorMaterial;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TailorController extends Controller
{
    public function index()
    {
        return view('tailors.tailor');
    }

    public function gettailors()
    {
        return Tailor::orderBy('id', 'DESC')->paginate(10);
    }


    public function store(Request $request)
    {

        $tailor = new Tailor();
        $tailor->tailor_name = $request->tailor_name;
        $tailor->tailor_phone = $request->tailor_phone;
        $tailor->tailor_address = $request->tailor_address;
        $tailor->added_by = 'system';
        $tailor->user_id = 1;
        $tailor->save();

        return response()->json($tailor);
    }

    public function update(Request $request, tailor $tailor)
    {
        $tailor->tailor_name = $request->tailor_name;
        $tailor->tailor_phone = $request->tailor_phone;
        $tailor->tailor_address = $request->tailor_address;
        $tailor->updated_by = 'system_update';
        $tailor->save();

        return response()->json($tailor);
    }


    public function show(tailor $tailor)
    {
        return response()->json($tailor);
    }

    public function destroy(tailor $tailor)
    {
        $tailor->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function tailor_profile($id)
    {
        $tailor = Tailor::findOrFail($id);
        
        // Get real special orders data for this tailor
        $specialOrderItems = SpecialOrderItem::with(['specialOrder.customer'])
            ->where('tailor_id', $id)
            ->orderBy('sent_to_tailor_at', 'DESC')
            ->get();
        
        // Calculate totals
        $totalSent = $specialOrderItems->whereNotNull('sent_to_tailor_at')->count();
        $totalReceived = $specialOrderItems->where('tailor_status', 'received')->count();
        $pending = $specialOrderItems->where('tailor_status', 'processing')->count();
        
        // Format items for display
        $items = $specialOrderItems->map(function($item) {
            $order = $item->specialOrder;
            return [
                'order_id' => 'SO-' . str_pad($order->id ?? 0, 4, '0', STR_PAD_LEFT),
                'customer_name' => $order->customer->name ?? 'N/A',
                'abaya_code' => $item->abaya_code ?? '-',
                'design_name' => $item->design_name ?? $item->abaya_code ?? '-',
                'quantity' => $item->quantity ?? 0,
                'status' => $item->tailor_status === 'received' ? 'received' : ($item->tailor_status === 'processing' ? 'processing' : 'new'),
                'sent_date' => $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at)->format('Y-m-d') : null,
                'received_date' => $item->received_from_tailor_at ? Carbon::parse($item->received_from_tailor_at)->format('Y-m-d') : null,
            ];
        })->toArray();
        
        $specialOrdersData = [
            'total_sent' => $totalSent,
            'total_received' => $totalReceived,
            'pending' => $pending,
            'items' => $items
        ];

        // Get real stock received data - items received from this tailor
        $receivedItems = SpecialOrderItem::with(['stock.colorSizes.color', 'stock.colorSizes.size', 'stock'])
            ->where('tailor_id', $id)
            ->where('tailor_status', 'received')
            ->whereNotNull('received_from_tailor_at')
            ->orderBy('received_from_tailor_at', 'DESC')
            ->get();
        
        $totalItems = 0;
        $totalValue = 0;
        $items = [];
        $processedStocks = []; // Track processed stock+color+size combinations
        
        foreach ($receivedItems as $item) {
            $stock = $item->stock;
            $receivedDate = $item->received_from_tailor_at ? Carbon::parse($item->received_from_tailor_at)->format('Y-m-d') : null;
            
            if ($stock && $stock->colorSizes->count() > 0) {
                // If stock has color/size combinations, create entries for each
                foreach ($stock->colorSizes as $colorSize) {
                    $colorName = session('locale') === 'ar' 
                        ? ($colorSize->color->color_name_ar ?? '-')
                        : ($colorSize->color->color_name_en ?? '-');
                    
                    $sizeName = session('locale') === 'ar'
                        ? ($colorSize->size->size_name_ar ?? '-')
                        : ($colorSize->size->size_name_en ?? '-');
                    
                    // Create unique key for stock+color+size combination
                    $key = $stock->id . '_' . ($colorSize->color_id ?? '0') . '_' . ($colorSize->size_id ?? '0');
                    
                    if (!isset($processedStocks[$key])) {
                        $qty = $colorSize->qty ?? 0;
                        $totalItems += $qty;
                        
                        // Calculate value (using sales_price from stock)
                        $itemValue = ($stock->sales_price ?? 0) * $qty;
                        $totalValue += $itemValue;
                        
                        $items[] = [
                            'abaya_code' => $stock->abaya_code ?? $item->abaya_code ?? '-',
                            'design_name' => $stock->design_name ?? $item->design_name ?? '-',
                            'color' => $colorName,
                            'size' => $sizeName,
                            'quantity' => $qty,
                            'received_date' => $receivedDate,
                        ];
                        
                        $processedStocks[$key] = true;
                    }
                }
            } else {
                // If no color/size data or no stock, show item without color/size
                $qty = $item->quantity ?? 0;
                $totalItems += $qty;
                
                // Calculate value
                $itemValue = $stock ? (($stock->sales_price ?? 0) * $qty) : 0;
                $totalValue += $itemValue;
                
                $items[] = [
                    'abaya_code' => $stock ? ($stock->abaya_code ?? $item->abaya_code ?? '-') : ($item->abaya_code ?? '-'),
                    'design_name' => $stock ? ($stock->design_name ?? $item->design_name ?? '-') : ($item->design_name ?? '-'),
                    'color' => '-',
                    'size' => '-',
                    'quantity' => $qty,
                    'received_date' => $receivedDate,
                ];
            }
        }
        
        $stockReceivedData = [
            'total_items' => $totalItems,
            'total_value' => $totalValue,
            'items' => $items
        ];

        // Get real materials sent data for this tailor
        $tailorMaterials = TailorMaterial::with('material')
            ->where('tailor_id', $id)
            ->orderBy('sent_date', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->get();
        
        $totalMaterials = $tailorMaterials->count();
        $totalAbayasExpected = $tailorMaterials->sum('abayas_expected');
        
        $items = $tailorMaterials->map(function($tm) {
            $material = $tm->material;
            return [
                'material_code' => 'MAT-' . str_pad($material->id ?? 0, 4, '0', STR_PAD_LEFT),
                'material_name' => $material->material_name ?? '-',
                'quantity' => $tm->quantity ?? 0,
                'abayas_expected' => $tm->abayas_expected ?? 0,
                'sent_date' => $tm->sent_date ? Carbon::parse($tm->sent_date)->format('Y-m-d') : null,
                'status' => $tm->status ?? 'pending',
            ];
        })->toArray();
        
        $materialsSentData = [
            'total_materials' => $totalMaterials,
            'total_abayas_expected' => $totalAbayasExpected,
            'items' => $items
        ];

        return view('tailors.tailor_profile', compact('tailor', 'specialOrdersData', 'stockReceivedData', 'materialsSentData'));
    }

    public function send_material_to_tailor(Request $request)
    {
        DB::beginTransaction();
        
        try {
            // Validate the request
            $validated = $request->validate([
                'tailor_id' => 'required|exists:tailors,id',
                'material_id' => 'required|exists:materials,id',
                'quantity' => 'required|numeric|min:0.01',
                'abayas_expected' => 'required|integer|min:1',
            ], [
                'tailor_id.required' => 'Tailor ID is required',
                'tailor_id.exists' => 'Selected tailor does not exist',
                'material_id.required' => 'Material ID is required',
                'material_id.exists' => 'Selected material does not exist',
                'quantity.required' => 'Quantity is required',
                'quantity.numeric' => 'Quantity must be a number',
                'quantity.min' => 'Quantity must be greater than 0',
                'abayas_expected.required' => 'Expected abayas is required',
                'abayas_expected.integer' => 'Expected abayas must be a whole number',
                'abayas_expected.min' => 'Expected abayas must be at least 1',
            ]);

            $user = Auth::user();

            // Create new TailorMaterial record
            $tailorMaterial = TailorMaterial::create([
                'tailor_id' => $validated['tailor_id'],
                'material_id' => $validated['material_id'],
                'quantity' => $validated['quantity'],
                'abayas_expected' => $validated['abayas_expected'],
                'status' => 'pending',
                'sent_date' => now()->format('Y-m-d'),
                'added_by' => $user->name ?? 'system',
                'user_id' => $user->id ?? 1,
            ]);

            // Log the action
            \Log::info('Material sent to tailor', [
                'tailor_id' => $tailorMaterial->tailor_id,
                'material_id' => $tailorMaterial->material_id,
                'quantity' => $tailorMaterial->quantity,
                'abayas_expected' => $tailorMaterial->abayas_expected,
                'user_id' => $user->id ?? 1,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.material_sent_successfully', [], session('locale', 'en')),
                'data' => [
                    'id' => $tailorMaterial->id,
                    'tailor_id' => $tailorMaterial->tailor_id,
                    'material_id' => $tailorMaterial->material_id,
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Validation error sending material to tailor', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.validation_error', [], session('locale', 'en')),
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error sending material to tailor: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.error_saving_material', [], session('locale', 'en')) . ': ' . $e->getMessage()
            ], 500);
        }
    }
}
