<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\BoutiqueInvo;
use Illuminate\Http\Request;

class BoutiqueController extends Controller
{
 public function index() {
        return view('boutique.boutique');
    }


     public function boutique_profile($id) {
        $boutique = Boutique::findOrFail($id);
        
        // Get transfers to this boutique (shipments)
        $boutiqueLocation = 'boutique-' . $id;
        $transfers = \App\Models\Transfer::where('to', $boutiqueLocation)->get();
        
        // Calculate total abayas sent (sum of quantities from transfers)
        $totalAbayas = $transfers->sum('quantity');
        
        // Count number of shipments
        $numberOfShipments = $transfers->count();
        
        // Get unpaid rent invoice status
        $unpaidRentInvoice = ($boutique->rent_invoice_status == '5') ? 1 : 0;
        
        // Get last 6 months sales data from settlements
        $last6Months = [];
        $monthNames = [];
        $salesData = [];
        
        // Month name mapping for translations
        $monthTranslationKeys = [
            'January' => 'january',
            'February' => 'february',
            'March' => 'march',
            'April' => 'april',
            'May' => 'may',
            'June' => 'june',
            'July' => 'july',
            'August' => 'august',
            'September' => 'september',
            'October' => 'october',
            'November' => 'november',
            'December' => 'december'
        ];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $monthNameEn = $date->format('F');
            $translationKey = $monthTranslationKeys[$monthNameEn] ?? strtolower($monthNameEn);
            
            // Get settlement for this month and boutique
            $settlement = \App\Models\Settlement::where('boutique_id', $id)
                ->where('month', $monthKey)
                ->first();
            
            $salesAmount = $settlement ? (float)$settlement->total_sales : 0;
            
            $last6Months[] = [
                'month' => $monthKey,
                'name' => $monthNameEn,
                'translation_key' => $translationKey,
                'sales' => $salesAmount
            ];
            $monthNames[] = $translationKey;
            $salesData[] = $salesAmount;
        }
        
        // Calculate total sales from all settlements
        $totalSales = \App\Models\Settlement::where('boutique_id', $id)
            ->sum('total_sales');
        
        // Calculate most selling color and most requested size from settlements
        $colorCounts = [];
        $sizeCounts = [];
        
        $settlements = \App\Models\Settlement::where('boutique_id', $id)->get();
        
        foreach ($settlements as $settlement) {
            if ($settlement->items_data) {
                $itemsData = is_string($settlement->items_data) 
                    ? json_decode($settlement->items_data, true) 
                    : $settlement->items_data;
                
                if (is_array($itemsData)) {
                    foreach ($itemsData as $item) {
                        // Count colors
                        if (isset($item['color']) && !empty($item['color'])) {
                            $colorName = $item['color'];
                            if (!isset($colorCounts[$colorName])) {
                                $colorCounts[$colorName] = 0;
                            }
                            // Add sold quantity if available, otherwise add 1
                            $colorCounts[$colorName] += isset($item['sold']) ? (int)$item['sold'] : 1;
                        }
                        
                        // Count sizes
                        if (isset($item['size']) && !empty($item['size'])) {
                            $sizeName = $item['size'];
                            if (!isset($sizeCounts[$sizeName])) {
                                $sizeCounts[$sizeName] = 0;
                            }
                            // Add sold quantity if available, otherwise add 1
                            $sizeCounts[$sizeName] += isset($item['sold']) ? (int)$item['sold'] : 1;
                        }
                    }
                }
            }
        }
        
        // Get most selling color
        $bestSellingColor = '-';
        if (!empty($colorCounts)) {
            arsort($colorCounts);
            $bestSellingColor = array_key_first($colorCounts);
        }
        
        // Get most requested size
        $mostRequestedSize = '-';
        if (!empty($sizeCounts)) {
            arsort($sizeCounts);
            $mostRequestedSize = array_key_first($sizeCounts);
        }
        
        $monthlyProfit = 0;
        
        // Get all invoices from boutique_invos table (paid and unpaid)
        $allInvoices = BoutiqueInvo::where('boutique_id', (string)$id)
            ->orderBy('month', 'asc')
            ->get();
        
        // Get unpaid invoices separately for count
        $unpaidInvoices = BoutiqueInvo::where('boutique_id', (string)$id)
            ->where('status', '5') // Status 5 = Unpaid
            ->orderBy('month', 'asc')
            ->get();
        
        // Count unpaid invoices
        $unpaidInvoicesCount = $unpaidInvoices->count();
        
        // Calculate income report metrics
        $incomeReport = $this->calculateIncomeReport($id, $boutiqueLocation);
        
        // Get sales data grouped by transfer_code
        $salesByTransfer = [];
        $transfersWithItems = \App\Models\Transfer::with(['items' => function($query) {
            $query->with('stock');
        }])
            ->where('to', $boutiqueLocation)
            ->orderBy('date', 'desc')
            ->get();
        
        foreach ($transfersWithItems as $transfer) {
            // Get all settlements for this boutique (to find sold items)
            $allSettlements = \App\Models\Settlement::where('boutique_id', $id)->get();
            
            // Calculate items sent in this transfer
            $itemsSent = 0;
            $totalAmountSent = 0;
            $transferItemsData = [];
            
            foreach ($transfer->items as $transferItem) {
                $qty = (int)$transferItem->quantity;
                $itemsSent += $qty;
                
                // Get stock for price calculation
                $stock = \App\Models\Stock::where('abaya_code', $transferItem->abaya_code)->first();
                $itemPrice = $stock ? (float)($stock->sales_price ?? 0) : 0;
                $itemAmount = $itemPrice * $qty;
                $totalAmountSent += $itemAmount;
                
                // Store item details for detail popup
                $transferItemsData[] = [
                    'code' => $transferItem->abaya_code,
                    'color' => $transferItem->color_name ?? '',
                    'size' => $transferItem->size_name ?? '',
                    'quantity' => $qty,
                    'price' => $itemPrice
                ];
            }
            
            // Calculate sold amount and profit from settlements
            $soldAmount = 0;
            $profit = 0;
            $totalItemsSold = 0;
            
            // Create a map of transfer items for quick lookup
            $transferItemsMap = [];
            foreach ($transferItemsData as $item) {
                $key = $item['code'] . '|' . ($item['color'] ?? '') . '|' . ($item['size'] ?? '');
                if (!isset($transferItemsMap[$key])) {
                    $transferItemsMap[$key] = $item;
                }
            }
            
            foreach ($allSettlements as $settlement) {
                if ($settlement->items_data) {
                    $itemsData = is_string($settlement->items_data) 
                        ? json_decode($settlement->items_data, true) 
                        : $settlement->items_data;
                    
                    if (is_array($itemsData)) {
                        foreach ($itemsData as $item) {
                            $itemCode = $item['code'] ?? '';
                            $itemColor = $item['color'] ?? '';
                            $itemSize = $item['size'] ?? '';
                            $key = $itemCode . '|' . $itemColor . '|' . $itemSize;
                            
                            // Check if this item matches any item in the transfer
                            if (isset($transferItemsMap[$key])) {
                                $soldQty = (int)($item['sold'] ?? 0);
                                if ($soldQty > 0) {
                                    $totalItemsSold += $soldQty;
                                    $itemPrice = (float)($item['price'] ?? $transferItemsMap[$key]['price']);
                                    $soldAmount += $itemPrice * $soldQty;
                                    
                                    // Calculate profit: (sales_price - cost_price - tailor_charges) * sold_quantity
                                    $stock = \App\Models\Stock::where('abaya_code', $itemCode)->first();
                                    if ($stock) {
                                        $salesPrice = (float)($stock->sales_price ?? 0);
                                        $costPrice = (float)($stock->cost_price ?? 0);
                                        $tailorCharges = (float)($stock->tailor_charges ?? 0);
                                        $itemProfit = ($salesPrice - $costPrice - $tailorCharges) * $soldQty;
                                        $profit += $itemProfit;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // Determine status: compare total items sent vs total items sold
            $status = 'not_paid';
            if ($totalItemsSold == $itemsSent && $itemsSent > 0) {
                $status = 'fully_paid';
            } elseif ($totalItemsSold > 0 && $totalItemsSold < $itemsSent) {
                $status = 'partially_paid';
            }
            
            $salesByTransfer[] = [
                'transfer_code' => $transfer->transfer_code,
                'transfer_id' => $transfer->id,
                'date' => $transfer->date->format('Y-m-d'),
                'items_sent' => $itemsSent,
                'total_amount_sent' => $totalAmountSent,
                'sold_amount' => $soldAmount,
                'profit' => $profit,
                'status' => $status,
                'items' => $transferItemsData
            ];
        }
        
        // Get shipments data (transfers) for shipments tab
        $shipmentsData = [];
        foreach ($transfersWithItems as $transfer) {
            $itemsCount = 0;
            $totalAmount = 0;
            $shipmentItems = [];
            
            foreach ($transfer->items as $transferItem) {
                $qty = (int)$transferItem->quantity;
                $itemsCount += $qty;
                
                // Get stock for price calculation
                $stock = \App\Models\Stock::where('abaya_code', $transferItem->abaya_code)->first();
                $itemPrice = $stock ? (float)($stock->sales_price ?? 0) : 0;
                $itemAmount = $itemPrice * $qty;
                $totalAmount += $itemAmount;
                
                // Store item details for detail popup
                $shipmentItems[] = [
                    'code' => $transferItem->abaya_code,
                    'color' => $transferItem->color_name ?? '',
                    'size' => $transferItem->size_name ?? '',
                    'quantity' => $qty,
                    'price' => $itemPrice
                ];
            }
            
            $shipmentsData[] = [
                'transfer_code' => $transfer->transfer_code,
                'transfer_id' => $transfer->id,
                'date' => $transfer->date->format('Y-m-d'),
                'total_items' => $itemsCount,
                'total_amount' => $totalAmount,
                'items' => $shipmentItems
            ];
        }
        
        return view('boutique.boutique_profile', compact(
            'boutique',
            'totalAbayas',
            'numberOfShipments',
            'unpaidRentInvoice',
            'unpaidInvoices',
            'unpaidInvoicesCount',
            'allInvoices',
            'totalSales',
            'bestSellingColor',
            'mostRequestedSize',
            'monthlyProfit',
            'last6Months',
            'monthNames',
            'salesData',
            'salesByTransfer',
            'shipmentsData'
        ));
    }


public function getboutiques() {
    $boutiques = Boutique::orderBy('id', 'DESC')->paginate(10);
    
    // Add unpaid months info to each boutique
    foreach ($boutiques->items() as $boutique) {
        $unpaidInvoices = BoutiqueInvo::where('boutique_id', (string)$boutique->id)
            ->where('status', '5') // Unpaid
            ->orderBy('month', 'asc')
            ->get();
        
        $unpaidMonths = [];
        foreach ($unpaidInvoices as $invoice) {
            $unpaidMonths[] = $invoice->month;
        }
        
        $boutique->unpaid_months = $unpaidMonths;
        $boutique->unpaid_count = count($unpaidMonths);
    }
    
    return $boutiques;
}

public function show($id) {
    $boutique = Boutique::findOrFail($id);
    return response()->json($boutique);
}

  public function add_boutique(Request $request)
    {
        $boutique = new Boutique();

        $boutique->boutique_name = $request->boutique_name;
        $boutique->shelf_no = $request->shelf_no;
        $boutique->monthly_rent = $request->monthly_rent;
        $boutique->rent_date = $request->rent_date;
        $boutique->status = $request->status; // Active/Inactive status
        $boutique->rent_invoice_status = '5'; // Always set to unpaid (5) when adding new boutique
        $boutique->boutique_address = $request->boutique_address;
        $boutique->added_by_by = 'system';
        $boutique->user_id = 1;

        $boutique->save();
        
        // Create invoice record in boutique_invos table
        if ($boutique->rent_date && $boutique->monthly_rent) {
            $rentDate = \Carbon\Carbon::parse($boutique->rent_date);
            $month = $rentDate->format('m-Y'); // Format: MM-YYYY (e.g., 02-2025)
            
            $invoice = new BoutiqueInvo();
            $invoice->boutique_id = (string)$boutique->id;
            $invoice->boutique_name = $boutique->boutique_name;
            $invoice->month = $month;
            $invoice->payment_date = $boutique->rent_date;
            $invoice->status = '5'; // Status 4 = Paid
            $invoice->total_amount = $boutique->monthly_rent;
            $invoice->added_by = 'system';
            $invoice->user_id = 1;
            $invoice->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Boutique saved successfully!'
        ]);
    }

    public function destroy($id)
{

    $boutique = Boutique::find($id);

    if (!$boutique) {
        return response()->json([
            'success' => false,
            'message' => __('messages.not_found')
        ]);
    }

    try {
        $boutique->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.boutique_deleted')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => __('messages.delete_error')
        ]);
    }
}

    
    public function boutique_list(Request $request)
    {
        // Generate missing invoice records for all boutiques
        $this->generateMissingInvoices();
        
        return view ('boutique.view_boutique');
    }
    
    /**
     * Generate missing invoice records for all boutiques
     * Creates invoice records from rent_date to current month
     */
    private function generateMissingInvoices()
    {
        $currentDate = now();
        $currentMonth = $currentDate->format('m-Y'); // Format: MM-YYYY
        
        // Get all boutiques with rent_date
        $boutiques = Boutique::whereNotNull('rent_date')
            ->whereNotNull('monthly_rent')
            ->get();
        
        foreach ($boutiques as $boutique) {
            if (!$boutique->rent_date || !$boutique->monthly_rent) {
                continue;
            }
            
            $rentDate = \Carbon\Carbon::parse($boutique->rent_date);
            $rentMonth = $rentDate->format('m-Y'); // Format: MM-YYYY
            
            // Generate all months from rent_date to current month
            $startDate = $rentDate->copy()->startOfMonth();
            $endDate = $currentDate->copy()->startOfMonth();
            
            // Loop through each month
            $currentMonthDate = $startDate->copy();
            while ($currentMonthDate->lte($endDate)) {
                $monthKey = $currentMonthDate->format('m-Y'); // Format: MM-YYYY
                
                // Check if invoice already exists for this boutique and month
                $existingInvoice = BoutiqueInvo::where('boutique_id', (string)$boutique->id)
                    ->where('month', $monthKey)
                    ->first();
                
                if (!$existingInvoice) {
                    // Create new invoice record
                    $invoice = new BoutiqueInvo();
                    $invoice->boutique_id = (string)$boutique->id;
                    $invoice->boutique_name = $boutique->boutique_name;
                    $invoice->month = $monthKey;
                    
                    // If this is the rent_date month, set status to 4 (paid) and use rent_date as payment_date
                    // Otherwise, set status to 5 (unpaid) and payment_date to null
                    if ($monthKey === $rentMonth) {
                        $invoice->status = '4'; // Paid
                        $invoice->payment_date = $boutique->rent_date;
                    } else {
                        $invoice->status = '5'; // Unpaid
                        $invoice->payment_date = null;
                    }
                    
                    $invoice->total_amount = $boutique->monthly_rent;
                    $invoice->added_by = 'system';
                    $invoice->user_id = 1;
                    $invoice->save();
                }
                
                // Move to next month
                $currentMonthDate->addMonth();
            }
        }
    }

      public function edit_boutique($id)
    {
       
        $boutique = Boutique::find($id);

    return view('boutique.edit_boutique', compact('boutique'));
    }


  public function update_boutique(Request $request)
{
    $boutique_id = $request->boutique_id;
    $boutique = Boutique::find($boutique_id);

    $boutique->boutique_name = $request->boutique_name;
    $boutique->shelf_no = $request->shelf_no;
    $boutique->monthly_rent = $request->monthly_rent;
    $boutique->rent_date = $request->rent_date;
    $boutique->status = $request->status;
    $boutique->boutique_address = $request->boutique_address;
    $boutique->updated_by = 'system_update';

    $boutique->save();

    return response()->json([
        'success' => true,
        'message' => $request->boutique_id 
            ? __('messages.update_boutique') 
            : __('messages.add_boutique'),
        'boutique' => $boutique // ✅ include this
    ]);
}

public function get_boutique_invoices(Request $request)
{
    $boutique_id = $request->input('boutique_id');
    
    if (!$boutique_id) {
        return response()->json([
            'success' => false,
            'message' => 'Boutique ID is required'
        ], 400);
    }
    
    $boutique = Boutique::find($boutique_id);
    
    if (!$boutique) {
        return response()->json([
            'success' => false,
            'message' => __('messages.not_found')
        ], 404);
    }
    
    // Get all invoices for this boutique, ordered by month
    $invoices = BoutiqueInvo::where('boutique_id', (string)$boutique_id)
        ->orderBy('month', 'asc')
        ->get();
    
    return response()->json([
        'success' => true,
        'boutique' => [
            'id' => $boutique->id,
            'name' => $boutique->boutique_name,
            'monthly_rent' => $boutique->monthly_rent
        ],
        'invoices' => $invoices
    ]);
}

public function update_invoice_payment(Request $request)
{
    // Handle both single invoice and array of invoices
    $invoices = $request->input('invoices');
    
    if ($invoices && is_array($invoices)) {
        // Handle array of invoices
        foreach ($invoices as $invoiceData) {
            $invoiceId = $invoiceData['id'] ?? null;
            $amount = $invoiceData['total_amount'] ?? null;
            $paymentDate = $invoiceData['payment_date'] ?? null;
            
            if (!$invoiceId) {
                continue;
            }
            
            $invoice = BoutiqueInvo::find($invoiceId);
            
            if (!$invoice) {
                continue;
            }
            
            // Update invoice
            if ($amount !== null) {
                $invoice->total_amount = $amount;
            }
            
            if ($paymentDate !== null) {
                $invoice->payment_date = $paymentDate;
            }
            
            // Set status to paid (4) when payment is made
            $invoice->status = '4';
            $invoice->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => __('messages.payments_updated_successfully')
        ]);
    }
    
    // Handle single invoice (backward compatibility)
    $invoice_id = $request->input('invoice_id');
    $amount = $request->input('amount');
    $payment_date = $request->input('payment_date');
    
    if (!$invoice_id) {
        return response()->json([
            'success' => false,
            'message' => 'Invoice ID is required'
        ], 400);
    }
    
    $invoice = BoutiqueInvo::find($invoice_id);
    
    if (!$invoice) {
        return response()->json([
            'success' => false,
            'message' => __('messages.not_found')
        ], 404);
    }
    
    // Update invoice
    if ($amount !== null) {
        $invoice->total_amount = $amount;
    }
    if ($payment_date !== null) {
        $invoice->payment_date = $payment_date;
    }
    
    // Set status to paid (4) when payment is made
    $invoice->status = '4';
    $invoice->save();
    
    return response()->json([
        'success' => true,
        'message' => __('messages.payment_updated_successfully'),
        'invoice' => $invoice
    ]);
}

public function update_rent_invoice_status(Request $request)
{
    $boutique_id = $request->boutique_id;
    $status = $request->status; // 4 for paid, 5 for unpaid
    
    $boutique = Boutique::find($boutique_id);
    
    if (!$boutique) {
        return response()->json([
            'success' => false,
            'message' => __('messages.not_found')
        ]);
    }
    
    $boutique->rent_invoice_status = $status;
    $boutique->save();
    
    return response()->json([
        'success' => true,
        'message' => __('messages.rent_invoice_status_updated'),
        'boutique' => $boutique
    ]);
}

/**
 * Calculate income report for boutique
 */
private function calculateIncomeReport($boutiqueId, $boutiqueLocation)
{
    // Get all transfers TO this boutique
    $transfersTo = \App\Models\Transfer::with('items.stock')
        ->where('to', $boutiqueLocation)
        ->get();
    
    // Get all transfers FROM this boutique (pulled items)
    $transfersFrom = \App\Models\Transfer::with('items.stock')
        ->where('from', $boutiqueLocation)
        ->get();
    
    // Calculate total items sent
    $totalItemsSent = 0;
    $totalPriceSent = 0;
    
    foreach ($transfersTo as $transfer) {
        foreach ($transfer->items as $transferItem) {
            $qty = (int)$transferItem->quantity;
            $totalItemsSent += $qty;
            
            // Get stock for price calculation
            $stock = \App\Models\Stock::where('abaya_code', $transferItem->abaya_code)->first();
            if ($stock && $stock->sales_price) {
                $totalPriceSent += (float)$stock->sales_price * $qty;
            }
        }
    }
    
    // Calculate total items pulled
    $totalItemsPulled = 0;
    foreach ($transfersFrom as $transfer) {
        $totalItemsPulled += (int)$transfer->quantity;
    }
    
    // Calculate total sellable (from transfers table where to = boutique)
    $totalSellable = \App\Models\Transfer::where('to', $boutiqueLocation)
        ->sum('sellable');
    
    // Calculate total sold and total profit from settlements
    $totalSold = 0;
    $totalProfit = 0;
    
    $allSettlements = \App\Models\Settlement::where('boutique_id', $boutiqueId)->get();
    
    foreach ($allSettlements as $settlement) {
        if ($settlement->items_data) {
            $itemsData = is_string($settlement->items_data) 
                ? json_decode($settlement->items_data, true) 
                : $settlement->items_data;
            
            if (is_array($itemsData)) {
                foreach ($itemsData as $item) {
                    $soldQty = (int)($item['sold'] ?? 0);
                    if ($soldQty > 0) {
                        $totalSold += $soldQty;
                        
                        // Calculate profit: (sales_price - cost_price - tailor_charges) * sold_quantity
                        $stock = \App\Models\Stock::where('abaya_code', $item['code'] ?? '')->first();
                        if ($stock) {
                            $salesPrice = (float)($stock->sales_price ?? 0);
                            $costPrice = (float)($stock->cost_price ?? 0);
                            $tailorCharges = (float)($stock->tailor_charges ?? 0);
                            $itemProfit = ($salesPrice - $costPrice - $tailorCharges) * $soldQty;
                            $totalProfit += $itemProfit;
                        }
                    }
                }
            }
        }
    }
    
    return [
        'total_items_sent' => $totalItemsSent,
        'total_items_pulled' => $totalItemsPulled,
        'total_sellable' => max(0, $totalSellable),
        'total_sold' => $totalSold,
        'total_profit' => $totalProfit,
        'total_price_sent' => $totalPriceSent
    ];
}

}
