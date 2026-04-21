<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\BoutiqueInvo;
use App\Models\ColorSize;
use App\Models\Expense;
use App\Models\PosOrders;
use App\Models\PosOrdersDetail;
use App\Models\Settings;
use App\Models\Settlement;
use App\Models\SpecialOrder;
use App\Models\SpecialOrderItem;
use App\Models\Stock;
use App\Models\StockColor;
use App\Models\StockSize;
use App\Models\Tailor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{



    public function index(){




        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Calculate Revenue for Today
        $todayRevenue = $this->calculateTodayRevenue($today);
        
        // Calculate Expenses for Today (delivery charges if paid)
        $todayExpenses = $this->calculateTodayExpenses($today);
        
        // Calculate Net Profit for Today
        $todayNetProfit = $todayRevenue - $todayExpenses;
        
        // Calculate Revenue for This Month
        $monthRevenue = $this->calculateMonthRevenue($startOfMonth, $endOfMonth);
        
        // Calculate Expenses for This Month
        $monthExpenses = $this->calculateMonthExpenses($startOfMonth, $endOfMonth);
        
        // Calculate Net Profit for This Month
        $monthNetProfit = $monthRevenue - $monthExpenses;
        
        // Calculate Revenue - Expense (overall)
        $totalRevenue = $monthRevenue;
        $totalExpenses = $monthExpenses;
        $revenueMinusExpense = $totalRevenue - $totalExpenses;
        
        // Count orders with tailor (items with tailor_status = 'processing')
        $ordersWithTailor = SpecialOrderItem::where('tailor_status', 'processing')->count();
        
        // Calculate monthly revenue and expenses for the current year
        $currentYear = Carbon::now()->year;
        $monthlyData = $this->calculateMonthlyData($currentYear);

        // Gross sales (all time): POS + special orders + settlements only (matches POS income: goods excl. delivery; SO excludes POS-linked payments to avoid double count)
        $channelSalesTotals = $this->calculateTotalSalesPosSpecialSettlementAllTime();
        
        return view('dashboard.dashboard', compact(
            'todayNetProfit',
            'monthNetProfit',
            'revenueMinusExpense',
            'ordersWithTailor',
            'todayRevenue',
            'todayExpenses',
            'totalRevenue',
            'totalExpenses',
            'monthlyData',
            'currentYear',
            'channelSalesTotals'
        ));
    }

    /**
     * All-time gross sales from POS (standalone), special orders, and settlement records only.
     * POS rows tied to special_order_id are excluded; their value is counted on the special order.
     */
    private function calculateTotalSalesPosSpecialSettlementAllTime(): array
    {
        $posSales = (float) PosOrders::whereNull('special_order_id')
            ->selectRaw('COALESCE(SUM(COALESCE(total_amount, 0) - COALESCE(delivery_charges, 0)), 0) as aggregate')
            ->value('aggregate');

        $specialOrderSales = (float) SpecialOrder::query()->sum('total_amount');

        $settlementSales = (float) Settlement::query()->sum('total_sales');

        $combined = $posSales + $specialOrderSales + $settlementSales;

        return [
            'pos' => $posSales,
            'special_orders' => $specialOrderSales,
            'settlements' => $settlementSales,
            'combined' => $combined,
        ];
    }
    
    private function calculateTodayRevenue($date)
    {
        // Calculate actual profit, not revenue
        
        // POS Orders profit (sum of item_profit from details - matching POS income report)
        $posOrders = PosOrders::whereDate('created_at', $date)
            ->with('details')
            ->get();
        
        $posProfit = 0;
        foreach ($posOrders as $order) {
            if ($order->details && $order->details->count() > 0) {
                $posProfit += (float)$order->details->sum('item_profit');
            } else {
                // Fallback to stored profit if details not available
                $posProfit += (float)($order->profit ?? 0);
            }
        }
        
        // Special Orders profit (delivered orders only)
        // Profit = (Price - Cost Price - Tailor Charges) × Quantity
        // $specialOrders = SpecialOrder::where('status', 'delivered')
        //     ->whereDate('updated_at', $date)
        //     ->with(['items.stock'])
        //     ->get();
         $specialOrders = SpecialOrder::
            whereDate('created_at', $date)
            ->with(['items.stock'])
            ->get();
        
        $specialOrdersProfit = 0;
        foreach ($specialOrders as $order) {
            if ($order->items && $order->items->count() > 0) {
                foreach ($order->items as $item) {
                    $itemPrice = (float)($item->price ?? 0);
                    $quantity = (int)($item->quantity ?? 0);
                    
                    $costPrice = 0;
                    $tailorCharges = 0;
                    if ($item->stock) {
                        $costPrice = (float)($item->stock->cost_price ?? 0);
                        $tailorCharges = (float)($item->stock->tailor_charges ?? 0);
                    }
                    
                    $itemProfit = ($itemPrice - $costPrice - $tailorCharges) * $quantity;
                    $specialOrdersProfit += $itemProfit;
                }
            }
        }
        
        // Settlements profit
        $settlements = Settlement::whereDate('created_at', $date)->get();
        $settlementsProfit = 0;
        foreach ($settlements as $settlement) {
            $itemsData = $settlement->items_data;
            // Handle JSON string if needed (for older records)
            if (is_string($itemsData)) {
                $itemsData = json_decode($itemsData, true);
            }
            if (is_array($itemsData)) {
                foreach ($itemsData as $item) {
                    $soldQty = (int)($item['sold'] ?? 0);
                    if ($soldQty > 0) {
                        $abayaCode = $item['code'] ?? '';
                        $stock = Stock::where('abaya_code', $abayaCode)->first();
                        if ($stock) {
                            $salesPrice = (float)($stock->sales_price ?? 0);
                            $costPrice = (float)($stock->cost_price ?? 0);
                            $tailorCharges = (float)($stock->tailor_charges ?? 0);
                            $itemProfit = ($salesPrice - $costPrice - $tailorCharges) * $soldQty;
                            $settlementsProfit += $itemProfit;
                        }
                    }
                }
            }
        }
        
        return floatval($posProfit) + floatval($specialOrdersProfit) + floatval($settlementsProfit);
    }
    
    private function calculateTodayExpenses($date)
    {
        // POS Orders delivery charges (if delivery_paid is true or 1)
        // $posDeliveryCharges = PosOrders::whereDate('created_at', $date)
        //     ->where(function($q) {
        //         $q->where('delivery_paid', 1)
        //           ->orWhere('delivery_paid', true);
        //     })
        //     ->sum('delivery_charges');
        
        // Special Orders shipping fees (if order is delivered, consider shipping fee as expense)
        // $specialOrdersShipping = SpecialOrder::where('status', 'delivered')
        //     ->whereDate('updated_at', $date)
        //     ->sum('shipping_fee');
        
        // Expenses from Expense table
        $expensesFromTable = Expense::whereDate('expense_date', $date)
            ->sum('amount');
        
        // Boutique rent payments (status = '4' means paid, payment_date must not be null)
        $boutiqueRentPayments = BoutiqueInvo::where(function($q) {
                $q->where('status', '4')
                  ->orWhere('status', 4);
            })
            ->whereNotNull('payment_date')
            ->whereDate('payment_date', $date)
            ->get()
            ->sum(function($invoice) {
                return floatval($invoice->total_amount ?? 0);
            });

        // Maintenance expenses (company bearer): delivery + repair costs
        $maintenanceCompanyDelivery = SpecialOrderItem::where('maintenance_status', 'delivered')
            ->where('maintenance_cost_bearer', 'company')
            ->whereNotNull('repaired_delivered_at')
            ->whereDate('repaired_delivered_at', $date)
            ->sum('maintenance_delivery_charges');
        $maintenanceCompanyRepair = SpecialOrderItem::where('maintenance_status', 'delivered')
            ->where('maintenance_cost_bearer', 'company')
            ->whereNotNull('repaired_delivered_at')
            ->whereDate('repaired_delivered_at', $date)
            ->sum('maintenance_repair_cost');
        $maintenanceCompanyExpenses = floatval($maintenanceCompanyDelivery) + floatval($maintenanceCompanyRepair);
        
        return floatval($expensesFromTable) + floatval($boutiqueRentPayments) + floatval($maintenanceCompanyExpenses);
    }
    
    private function calculateMonthRevenue($startDate, $endDate)
    {
        // Calculate actual profit, not revenue
        
        // POS Orders profit (sum of item_profit from details - matching POS income report)
        $posOrders = PosOrders::whereBetween('created_at', [$startDate, $endDate])
            ->with('details')
            ->get();
        
        $posProfit = 0;
        foreach ($posOrders as $order) {
            if ($order->details && $order->details->count() > 0) {
                $posProfit += (float)$order->details->sum('item_profit');
            } else {
                // Fallback to stored profit if details not available
                $posProfit += (float)($order->profit ?? 0);
            }
        }
        
        // Special Orders profit (delivered orders only)
        // Profit = (Price - Cost Price - Tailor Charges) × Quantity
        // $specialOrders = SpecialOrder::where('status', 'delivered')
        //     ->whereBetween('created_at', [$startDate, $endDate])
        //     ->with(['items.stock'])
        //     ->get();

             $specialOrders = SpecialOrder::
            whereBetween('created_at', [$startDate, $endDate])
            ->with(['items.stock'])
            ->get();
        
        $specialOrdersProfit = 0;
        foreach ($specialOrders as $order) {
            if ($order->items && $order->items->count() > 0) {
                foreach ($order->items as $item) {
                    $itemPrice = (float)($item->price ?? 0);
                    $quantity = (int)($item->quantity ?? 0);
                    
                    $costPrice = 0;
                    $tailorCharges = 0;
                    if ($item->stock) {
                        $costPrice = (float)($item->stock->cost_price ?? 0);
                        $tailorCharges = (float)($item->stock->tailor_charges ?? 0);
                    }
                    
                    $itemProfit = ($itemPrice - $costPrice - $tailorCharges) * $quantity;
                    $specialOrdersProfit += $itemProfit;
                }
            }
        }
        
        // Settlements profit
        $settlements = Settlement::whereBetween('created_at', [$startDate, $endDate])->get();
        $settlementsProfit = 0;
        foreach ($settlements as $settlement) {
            $itemsData = $settlement->items_data;
            // Handle JSON string if needed (for older records)
            if (is_string($itemsData)) {
                $itemsData = json_decode($itemsData, true);
            }
            if (is_array($itemsData)) {
                foreach ($itemsData as $item) {
                    $soldQty = (int)($item['sold'] ?? 0);
                    if ($soldQty > 0) {
                        $abayaCode = $item['code'] ?? '';
                        $stock = Stock::where('abaya_code', $abayaCode)->first();
                        if ($stock) {
                            $salesPrice = (float)($stock->sales_price ?? 0);
                            $costPrice = (float)($stock->cost_price ?? 0);
                            $tailorCharges = (float)($stock->tailor_charges ?? 0);
                            $itemProfit = ($salesPrice - $costPrice - $tailorCharges) * $soldQty;
                            $settlementsProfit += $itemProfit;
                        }
                    }
                }
            }
        }
        
        return floatval($posProfit) + floatval($specialOrdersProfit) + floatval($settlementsProfit);
    }
    
    private function calculateMonthExpenses($startDate, $endDate)
    {
        // POS Orders delivery charges (if delivery_paid is true or 1)
        // $posDeliveryCharges = PosOrders::whereBetween('created_at', [$startDate, $endDate])
        //     ->where(function($q) {
        //         $q->where('delivery_paid', 1)
        //           ->orWhere('delivery_paid', true);
        //     })
        //     ->sum('delivery_charges');
        
        // Special Orders shipping fees (if order is delivered)
        // $specialOrdersShipping = SpecialOrder::where('status', 'delivered')
        //     ->whereBetween('updated_at', [$startDate, $endDate])
        //     ->sum('shipping_fee');
        
        // Expenses from Expense table
        $expensesFromTable = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');
        
        // Boutique rent payments (status = '4' means paid, payment_date must not be null)
        $boutiqueRentPayments = BoutiqueInvo::where(function($q) {
                $q->where('status', '4')
                  ->orWhere('status', 4);
            })
            ->whereNotNull('payment_date')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get()
            ->sum(function($invoice) {
                return floatval($invoice->total_amount ?? 0);
            });

        // Maintenance expenses (company bearer): delivery + repair costs
        $maintenanceCompanyDelivery = SpecialOrderItem::where('maintenance_status', 'delivered')
            ->where('maintenance_cost_bearer', 'company')
            ->whereNotNull('repaired_delivered_at')
            ->whereBetween('repaired_delivered_at', [$startDate, $endDate])
            ->sum('maintenance_delivery_charges');
        $maintenanceCompanyRepair = SpecialOrderItem::where('maintenance_status', 'delivered')
            ->where('maintenance_cost_bearer', 'company')
            ->whereNotNull('repaired_delivered_at')
            ->whereBetween('repaired_delivered_at', [$startDate, $endDate])
            ->sum('maintenance_repair_cost');
        $maintenanceCompanyExpenses = floatval($maintenanceCompanyDelivery) + floatval($maintenanceCompanyRepair);
        
        return floatval($expensesFromTable) + floatval($boutiqueRentPayments) + floatval($maintenanceCompanyExpenses);
    }
    
    private function calculateMonthlyData($year)
    {
        $monthlyRevenue = [];
        $monthlyExpenses = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
            
            // Calculate revenue for this month
            $revenue = $this->calculateMonthRevenue($startOfMonth, $endOfMonth);
            $monthlyRevenue[] = $revenue;
            
            // Calculate expenses for this month
            $expenses = $this->calculateMonthExpenses($startOfMonth, $endOfMonth);
            $monthlyExpenses[] = $expenses;
        }
        
        return [
            'revenue' => $monthlyRevenue,
            'expenses' => $monthlyExpenses
        ];
    }
    
    public function getMonthlyData(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $monthlyData = $this->calculateMonthlyData($year);
        
        return response()->json([
            'success' => true,
            'revenue' => $monthlyData['revenue'],
            'expenses' => $monthlyData['expenses']
        ]);
    }
    
    public function getAbayasUnderTailoring()
    {
        try {
            $items = SpecialOrderItem::with(['specialOrder.customer', 'tailor', 'stock.images'])
                ->where('tailor_status', 'processing')
                ->whereNotNull('sent_to_tailor_at')
                ->orderBy('sent_to_tailor_at', 'DESC')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    $order = $item->specialOrder;
                    $tailor = $item->tailor;
                    $stock = $item->stock;
                    
                    $orderDate = $order ? Carbon::parse($order->created_at) : null;
                    $orderNo = $orderDate ? $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT) : '—';
                    
                    $sentDate = $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at) : null;
                    $daysSinceSent = $sentDate ? $sentDate->diffInDays(Carbon::now()) : 0;
                    
                    return [
                        'id' => $item->id,
                        'order_no' => $orderNo,
                        'order_id' => $order->id ?? 0,
                        'customer_name' => $order->customer->name ?? 'N/A',
                        'abaya_code' => $item->abaya_code ?? 'N/A',
                        'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                        'tailor_name' => $tailor->tailor_name ?? 'N/A',
                        'tailor_id' => $item->tailor_id,
                        'sent_date' => $sentDate ? $sentDate->format('Y-m-d') : null,
                        'sent_date_formatted' => $sentDate ? $sentDate->format('d/m/Y') : null,
                        'days_since_sent' => $daysSinceSent,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'items' => $items
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching abayas under tailoring: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'items' => []
            ], 500);
        }
    }
    
    public function getLowStockItems()
    {
        try {
            $lowStockItems = [];
            $lowStockThreshold = 3; // Show items with quantity <= 3
            
            // Get all stocks
            $stocks = Stock::with(['colorSizes', 'colors', 'sizes'])
                ->get();
            
            foreach ($stocks as $stock) {
                $totalQty = 0;
                
                // Calculate total quantity based on mode
                if ($stock->mode === 'color_size') {
                    // Sum from color_sizes table
                    $totalQty = ColorSize::where('stock_id', $stock->id)->sum('qty');
                } elseif ($stock->mode === 'color') {
                    // Sum from stock_colors table
                    $totalQty = StockColor::where('stock_id', $stock->id)->sum('qty');
                } elseif ($stock->mode === 'size') {
                    // Sum from stock_sizes table
                    $totalQty = StockSize::where('stock_id', $stock->id)->sum('qty');
                } else {
                    // Fallback to stock quantity field
                    $totalQty = (int)($stock->quantity ?? 0);
                }
                
                // Check if quantity is less than or equal to 3
                if ($totalQty <= $lowStockThreshold) {
                    // Calculate percentage for progress bar (using threshold of 3 as 100%)
                    $percentage = $lowStockThreshold > 0 
                        ? ($totalQty / $lowStockThreshold) * 100 
                        : 0;
                    
                    $lowStockItems[] = [
                        'id' => $stock->id,
                        'abaya_code' => $stock->abaya_code ?? 'N/A',
                        'design_name' => $stock->design_name ?? $stock->abaya_code ?? 'N/A',
                        'remaining' => $totalQty,
                        'threshold' => $lowStockThreshold,
                        'percentage' => min($percentage, 100),
                    ];
                }
            }
            
            // Sort by remaining quantity (lowest first)
            usort($lowStockItems, function($a, $b) {
                return $a['remaining'] <=> $b['remaining'];
            });
            
            // Limit to top 10
            $lowStockItems = array_slice($lowStockItems, 0, 10);
            
            return response()->json([
                'success' => true,
                'items' => $lowStockItems
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching low stock items: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'items' => []
            ], 500);
        }
    }
    
    public function getBoutiqueRentReminders()
    {
        try {
            $reminders = [];
            $now = Carbon::now();
            $today = $now->copy()->startOfDay();
            $locale = session('locale', 'en');

            // Helpers for month normalization (boutique_invos has mixed formats historically)
            $normalizeMonthToYm = function (?string $month): ?string {
                $month = trim((string)$month);
                if ($month === '') return null;
                if (preg_match('/^\d{4}-\d{2}$/', $month)) return $month; // Y-m
                if (preg_match('/^\d{2}-\d{4}$/', $month)) { // m-Y
                    [$m, $y] = explode('-', $month);
                    return $y . '-' . $m;
                }
                try {
                    return Carbon::parse($month)->format('Y-m');
                } catch (\Throwable $e) {
                    return null;
                }
            };
            $formatYmToMY = function (string $ym): string {
                try {
                    return Carbon::createFromFormat('Y-m', $ym)->format('m-Y');
                } catch (\Throwable $e) {
                    return $ym;
                }
            };
            
            // Get all active boutiques
            $boutiques = Boutique::where('status', '1') // Active boutiques
                ->whereNotNull('monthly_rent')
                ->whereNotNull('rent_date')
                ->get();

            // Preload all invoices for these boutiques (reduce queries)
            $boutiqueIds = $boutiques->pluck('id')->map(fn($x) => (string)$x)->values()->all();
            $allInvoices = BoutiqueInvo::whereIn('boutique_id', $boutiqueIds)->get();
            $invoicesByBoutique = [];
            foreach ($allInvoices as $inv) {
                $bid = (string)($inv->boutique_id ?? '');
                if ($bid === '') continue;
                if (!isset($invoicesByBoutique[$bid])) $invoicesByBoutique[$bid] = collect();
                $invoicesByBoutique[$bid]->push($inv);
            }

            $unpaidItems = [];
            $upcomingItems = [];

            foreach ($boutiques as $boutique) {
                $rentStartDate = Carbon::parse($boutique->rent_date)->startOfDay();
                $monthlyRent = floatval($boutique->monthly_rent ?? 0);
                
                if ($monthlyRent <= 0) {
                    continue;
                }

                // Rent due dates start monthly AFTER the rent start date.
                // Example: rent start = 01-01-2026, next due = 01-02-2026.
                $dueDate = $rentStartDate->copy()->addMonthNoOverflow();
                while ($dueDate->lt($today)) {
                    // Keep same day-of-month where possible, otherwise use last valid day
                    $dueDate->addMonthNoOverflow();
                }

                $daysUntilDue = $today->diffInDays($dueDate, false);

                $boutiqueInvoiceList = $invoicesByBoutique[(string)$boutique->id] ?? collect();

                // Build map: ym => ['paid' => bool, 'unpaid' => bool, 'any' => Collection]
                $invoiceMap = [];
                foreach ($boutiqueInvoiceList as $inv) {
                    $ym = $normalizeMonthToYm($inv->month);
                    if (!$ym) continue;
                    if (!isset($invoiceMap[$ym])) {
                        $invoiceMap[$ym] = [
                            'paid' => false,
                            'unpaid' => false,
                            'list' => collect(),
                        ];
                    }
                    $invoiceMap[$ym]['list']->push($inv);
                    if ((string)($inv->status ?? '') === '4' || (int)($inv->status ?? 0) === 4) {
                        $invoiceMap[$ym]['paid'] = true;
                    } elseif ((string)($inv->status ?? '') === '5' || (int)($inv->status ?? 0) === 5) {
                        $invoiceMap[$ym]['unpaid'] = true;
                    }
                }

                // Ensure monthly invoices exist moving forward from rent start date (up to upcoming due month)
                // and collect any unpaid/overdue months.
                $checkDate = $rentStartDate->copy()->addMonthNoOverflow(); // first invoice month after start
                while ($checkDate->lte($dueDate)) {
                    $ym = $checkDate->format('Y-m');
                    $mY = $checkDate->format('m-Y');

                    // Create invoice if missing for this month (support both formats)
                    $exists = isset($invoiceMap[$ym]) && $invoiceMap[$ym]['list']->count() > 0;
                    if (!$exists) {
                        $newInv = new BoutiqueInvo();
                        $newInv->boutique_id = (string)$boutique->id;
                        $newInv->boutique_name = $boutique->boutique_name;
                        $newInv->month = $mY; // boutique module format
                        $newInv->payment_date = null;
                        $newInv->status = '5'; // unpaid
                        $newInv->total_amount = $monthlyRent;
                        $newInv->added_by = 'system';
                        $newInv->user_id = 1;
                        $newInv->save();

                        $invoiceMap[$ym] = [
                            'paid' => false,
                            'unpaid' => true,
                            'list' => collect([$newInv]),
                        ];
                    }

                    // If this due date month is in the past (overdue) and not paid, show as unpaid
                    if ($checkDate->lt($today)) {
                        $isPaid = $invoiceMap[$ym]['paid'] ?? false;
                        if (!$isPaid) {
                            $daysOverdue = $today->diffInDays($checkDate, false); // negative number
                            $unpaidItems[] = [
                                'id' => $boutique->id,
                                'boutique_name' => $boutique->boutique_name,
                                'amount' => $monthlyRent,
                                'month' => $mY,
                                'month_name' => $checkDate->format('F Y'),
                                'payment_date' => $checkDate->format('Y-m-d'), // due date
                                'payment_date_formatted' => $checkDate->format('d/m/Y'),
                                'notification_date' => $checkDate->copy()->subDays(10)->format('Y-m-d'),
                                'notification_date_formatted' => $checkDate->copy()->subDays(10)->format('d/m/Y'),
                                'days_remaining' => (int)$daysOverdue, // negative => overdue in UI
                                'status' => 'unpaid',
                                'status_class' => 'danger',
                                'status_text' => trans('messages.unpaid', [], $locale) ?: 'Unpaid',
                            ];
                        }
                    }

                    $checkDate->addMonthNoOverflow();
                }

                // Upcoming due reminder: only within 10 days window, and only if not paid
                if ($daysUntilDue >= 0 && $daysUntilDue <= 10) {
                    $ymDue = $dueDate->format('Y-m');
                    $mYDue = $dueDate->format('m-Y');
                    $isPaidUpcoming = isset($invoiceMap[$ymDue]) ? ($invoiceMap[$ymDue]['paid'] ?? false) : false;
                    if (!$isPaidUpcoming) {
                        $upcomingItems[] = [
                            'id' => $boutique->id,
                            'boutique_name' => $boutique->boutique_name,
                            'amount' => $monthlyRent,
                            'month' => $mYDue,
                            'month_name' => $dueDate->format('F Y'),
                            'payment_date' => $dueDate->format('Y-m-d'), // due date
                            'payment_date_formatted' => $dueDate->format('d/m/Y'),
                            'notification_date' => $dueDate->copy()->subDays(10)->format('Y-m-d'),
                            'notification_date_formatted' => $dueDate->copy()->subDays(10)->format('d/m/Y'),
                            'days_remaining' => (int)$daysUntilDue,
                            'status' => 'upcoming',
                            'status_class' => 'warn',
                            'status_text' => trans('messages.upcoming', [], $locale) ?: 'Upcoming',
                        ];
                    }
                }
            }

            // Recent 4 paid invoices (history) + always show any unpaid invoices
            $paidHistory = [];
            $recentPaid = BoutiqueInvo::where(function ($q) {
                    $q->where('status', '4')->orWhere('status', 4);
                })
                ->orderBy('payment_date', 'desc')
                ->orderBy('id', 'desc')
                ->limit(4)
                ->get();

            foreach ($recentPaid as $inv) {
                $ym = $normalizeMonthToYm($inv->month);
                $monthDisplay = $ym ? $formatYmToMY($ym) : ($inv->month ?? '—');
                $monthName = $ym ? Carbon::createFromFormat('Y-m', $ym)->format('F Y') : ($inv->month ?? '—');

                $paidDate = null;
                try {
                    $paidDate = $inv->payment_date ? Carbon::parse($inv->payment_date) : null;
                } catch (\Throwable $e) {
                    $paidDate = null;
                }

                $paidHistory[] = [
                    'id' => (int)($inv->boutique_id ?? 0),
                    'boutique_name' => $inv->boutique_name ?? '—',
                    'amount' => (float)($inv->total_amount ?? 0),
                    'month' => $monthDisplay,
                    'month_name' => $monthName,
                    'payment_date' => $paidDate ? $paidDate->format('Y-m-d') : null,
                    'payment_date_formatted' => $paidDate ? $paidDate->format('d/m/Y') : '—',
                    'notification_date' => null,
                    'notification_date_formatted' => null,
                    'days_remaining' => null,
                    'status' => 'paid',
                    'status_class' => 'ok',
                    'status_text' => trans('messages.paid', [], $locale) ?: 'Paid',
                ];
            }

            // Sort unpaid by due date ascending (oldest first), upcoming by due date ascending
            usort($unpaidItems, function ($a, $b) {
                return strtotime($a['payment_date']) <=> strtotime($b['payment_date']);
            });
            usort($upcomingItems, function ($a, $b) {
                return strtotime($a['payment_date']) <=> strtotime($b['payment_date']);
            });

            $reminders = array_merge($unpaidItems, $upcomingItems, $paidHistory);
            
            return response()->json([
                'success' => true,
                'reminders' => $reminders
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching boutique rent reminders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'reminders' => []
            ], 500);
        }
    }
    
    public function getRecentSpecialOrders()
    {
        try {
            $orders = SpecialOrder::with(['customer', 'items.stock.images'])
                ->orderBy('created_at', 'DESC')
                ->limit(6)
                ->get()
                ->map(function($order) {
                    $customer = $order->customer;
                    $governorate = optional($customer)->governorate ?? '';
                    $city = optional($customer)->area ?? '';
                    $location = trim($governorate . ($city ? ' - ' . $city : ''));
                    
                    // Generate order number: YYYY-00ID (e.g., 2025-0001)
                    $orderDate = Carbon::parse($order->created_at);
                    $orderNumber = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                    
                    // Get first item image or placeholder
                    $firstItem = $order->items->first();
                    $image = asset('images/placeholder.png');
                    if ($firstItem && $firstItem->stock && $firstItem->stock->images->first()) {
                        $imagePath = $firstItem->stock->images->first()->image_path;
                        if (strpos($imagePath, 'http') === 0) {
                            $image = $imagePath;
                        } else {
                            $image = asset($imagePath);
                        }
                    }
                    
                    // Format date
                    $createdAt = Carbon::parse($order->created_at);
                    $now = Carbon::now();
                    $dateFormatted = '';
                    
                    if ($createdAt->isToday()) {
                        $dateFormatted = trans('messages.today', [], session('locale')) . ' ' . $createdAt->format('h:i A');
                    } elseif ($createdAt->isYesterday()) {
                        $dateFormatted = trans('messages.yesterday', [], session('locale')) . ' ' . $createdAt->format('h:i A');
                    } else {
                        $dateFormatted = $createdAt->format('Y-m-d h:i A');
                    }
                    
                    // Get status badge info
                    $statusInfo = $this->getStatusInfo($order->status);
                    
                    // Get source badge info
                    $sourceInfo = $this->getSourceInfo($order->source);
                    
                    return [
                        'id' => $order->id,
                        'order_no' => $orderNumber,
                        'customer_name' => optional($customer)->name ?? 'N/A',
                        'customer_phone' => optional($customer)->phone ?? '—',
                        'location' => $location,
                        'status' => $order->status,
                        'status_info' => $statusInfo,
                        'source' => $order->source,
                        'source_info' => $sourceInfo,
                        'total' => floatval($order->total_amount ?? 0),
                        'paid' => floatval($order->paid_amount ?? 0),
                        'remaining' => floatval($order->total_amount ?? 0) - floatval($order->paid_amount ?? 0),
                        'date' => $order->created_at->format('Y-m-d'),
                        'date_formatted' => $dateFormatted,
                        'image' => $image,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'orders' => $orders
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching recent special orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'orders' => []
            ], 500);
        }
    }
    
    private function getStatusInfo($status)
    {
        $statusMap = [
            'new' => [
                'text' => trans('messages.new_orders', [], session('locale')),
                'class' => 'background: rgba(245,158,11,.14); color: var(--warn);',
                'icon' => 'hourglass_top'
            ],
            'processing' => [
                'text' => trans('messages.in_progress', [], session('locale')),
                'class' => 'background: rgba(109,91,208,.12); color: var(--primary2);',
                'icon' => 'content_cut'
            ],
            'ready' => [
                'text' => trans('messages.ready_for_delivery', [], session('locale')) ?? 'Ready',
                'class' => 'background: rgba(16,185,129,.12); color: var(--ok);',
                'icon' => 'check_circle'
            ],
            'delivered' => [
                'text' => trans('messages.delivered', [], session('locale')),
                'class' => 'background: rgba(16,185,129,.12); color: var(--ok);',
                'icon' => 'local_shipping'
            ],
            'partially_ready' => [
                'text' => trans('messages.partially_ready', [], session('locale')) ?? 'Partially Ready',
                'class' => 'background: rgba(109,91,208,.12); color: var(--primary2);',
                'icon' => 'hourglass_empty'
            ],
        ];
        
        return $statusMap[$status] ?? [
            'text' => ucfirst($status),
            'class' => 'background: rgba(107,114,128,.12); color: var(--muted);',
            'icon' => 'info'
        ];
    }
    
    private function getSourceInfo($source)
    {
        $sourceMap = [
            'whatsapp' => [
                'text' => trans('messages.whatsapp', [], session('locale')),
                'class' => 'background: rgba(179,75,138,.12); color: var(--primary);',
                'icon' => 'chat'
            ],
            'walkin' => [
                'text' => trans('messages.walk_in', [], session('locale')) ?? 'Walk-in',
                'class' => 'background: rgba(109,91,208,.12); color: var(--primary2);',
                'icon' => 'language'
            ],
        ];
        
        return $sourceMap[$source] ?? [
            'text' => ucfirst($source),
            'class' => 'background: rgba(107,114,128,.12); color: var(--muted);',
            'icon' => 'info'
        ];
    }
    
    public function getNotifications()
    {
        try {
            $notifications = [];
            $now = Carbon::now();
            
            // Get late delivery weeks from settings (default to 2 weeks)
            $settings = Settings::getSettings();
            $lateDeliveryWeeks = $settings->late_delivery_weeks ?? 2;
            $lateDeliveryDate = $now->copy()->subWeeks($lateDeliveryWeeks);
            
            // 1. Get stock items with quantity <= 2
            $lowStockItems = Stock::where('quantity', '<=', 2)
                ->where('quantity', '>', 0)
                ->with('category')
                ->get();
            
            foreach ($lowStockItems as $stock) {
                $categoryName = $stock->category ? 
                    (session('locale') === 'ar' ? $stock->category->category_name_ar : $stock->category->category_name_en) : 
                    'N/A';
                
                $stockName = $stock->design_name ?? $stock->abaya_code ?? 'N/A';
                $quantityText = str_replace(':quantity', (string)$stock->quantity, trans('messages.remaining_quantity_pieces', [], session('locale')));
                
                $notifications[] = [
                    'type' => 'low_stock',
                    'icon' => 'inventory',
                    'iconColor' => 'text-amber-500',
                    'title' => trans('messages.low_abaya_stock', [], session('locale')),
                    'message' => $stockName . ' (' . $categoryName . ') - ' . $quantityText,
                    'time' => $stock->updated_at ? $stock->updated_at->diffForHumans() : '',
                    'link' => url('view_stock')
                ];
            }
            
            // 2. Get special orders that are not delivered and created more than X weeks ago
            $delayedOrders = SpecialOrder::where('status', '!=', 'delivered')
                ->where('created_at', '<=', $lateDeliveryDate)
                ->with(['customer', 'items'])
                ->get();
            
            foreach ($delayedOrders as $order) {
                $orderDate = Carbon::parse($order->created_at);
                $daysAgo = $now->diffInDays($orderDate);
                $weeksAgo = floor($daysAgo / 7);
                $remainingDays = $daysAgo % 7;
                
                // Generate order number
                $orderNumber = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                
                $customerName = $order->customer ? $order->customer->name : 'N/A';
                
                // Build time ago text
                $timeAgoText = '';
                if ($weeksAgo > 0) {
                    $timeAgoText = $weeksAgo . ' ' . trans('messages.weeks', [], session('locale')) . ' ';
                }
                if ($remainingDays > 0) {
                    $timeAgoText .= $remainingDays . ' ' . trans('messages.days', [], session('locale')) . ' ';
                }
                $timeAgoText .= trans('messages.ago', [], session('locale'));
                
                $notifications[] = [
                    'type' => 'delayed_order',
                    'icon' => 'schedule',
                    'iconColor' => 'text-red-500',
                    'title' => trans('messages.abaya_delayed_delivery', [], session('locale')),
                    'message' => trans('messages.order', [], session('locale')) . ' #' . $orderNumber . ' - ' . $customerName . ' - ' . $timeAgoText,
                    'time' => $order->created_at->diffForHumans(),
                    'link' => url('view_special_order')
                ];
            }
            
            // Sort by creation time (newest first)
            usort($notifications, function($a, $b) {
                return strtotime($b['time']) <=> strtotime($a['time']);
            });
            
            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'notifications' => [],
                'count' => 0
            ]);
        }
    }
}
