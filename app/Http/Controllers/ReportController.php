<?php

namespace App\Http\Controllers;

use App\Models\PosOrders;
use App\Models\SpecialOrder;
use App\Models\SpecialOrderItem;
use App\Models\Stock;
use App\Models\User;
use App\Models\Settlement;
use App\Models\Boutique;
use App\Models\Channel;
use App\Models\TailorPayment;
use App\Models\Expense;
use App\Models\BoutiqueInvo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Show POS Income Report page
     */
    public function posIncomeReport()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];
        // You can add permission check here if needed
        // if (!in_array(X, $permissions)) {
        //     return redirect()->route('login_page')->with('error', 'Permission denied');
        // }

        return view('reports.pos_income_report');
    }

    /**
     * Get POS Income Report data with pagination
     */
    public function getPosIncomeReport(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $page = $request->input('page', 1);

            $query = PosOrders::with(['details', 'customer']);

            // Apply date filters
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            // Order by created_at descending
            $query->orderBy('created_at', 'DESC');

            // Paginate results - 10 entries per page
            $orders = $query->paginate(10, ['*'], 'page', $page);

            $formattedOrders = $orders->map(function($order) {
                // Format order number
                $orderNo = str_pad($order->order_no ?? $order->id, 6, '0', STR_PAD_LEFT);
                
                // Get customer information
                $customerName = $order->customer ? ($order->customer->name ?? '-') : '-';
                $customerPhone = $order->customer ? ($order->customer->phone ?? '-') : '-';
                
                // Get delivery charges (separate from income/profit)
                $deliveryCharges = (float)($order->delivery_charges ?? 0);
                
                // Get user who added the order
                $addedBy = $order->added_by ?? '-';
                // Try to get user name from user_id if available
                if ($order->user_id) {
                    $user = User::find($order->user_id);
                    if ($user && $user->user_name) {
                        $addedBy = $user->user_name;
                    }
                }
                
                // Format date
                $createdAt = $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '-';
                
                // Calculate profit from order details (sum of all item profits)
                // Profit = sum of (item_profit) for each item in the order
                // Note: delivery charges are NOT included in profit
                $orderProfit = 0;
                if ($order->details && $order->details->count() > 0) {
                    $orderProfit = (float)$order->details->sum('item_profit');
                } else {
                    // Fallback to stored profit if details are not loaded
                    $orderProfit = (float)($order->profit ?? 0);
                }
                
                // Total amount excluding delivery charges for income calculation
                $totalAmountExcludingDelivery = (float)($order->total_amount ?? 0) - $deliveryCharges;
                
                return [
                    'id' => $order->id,
                    'order_no' => $orderNo,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'total_amount' => $totalAmountExcludingDelivery,
                    'paid_amount' => (float)($order->paid_amount ?? 0),
                    'discount' => (float)($order->total_discount ?? 0),
                    'profit' => $orderProfit,
                    'delivery_charges' => $deliveryCharges,
                    'added_by' => $addedBy,
                    'created_at' => $createdAt,
                ];
            });

            // Calculate totals - recalculate profit from order details
            $totalQuery = PosOrders::with(['details', 'customer']);
            if ($fromDate) {
                $totalQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $totalQuery->whereDate('created_at', '<=', $toDate);
            }
            
            $allOrdersForTotals = $totalQuery->get();
            
            // Calculate profit from order details (sum of item_profit for all orders)
            // Note: delivery charges are NOT included in profit
            $totalProfit = 0;
            $totalDeliveryCharges = 0;
            $totalAmountExcludingDelivery = 0;
            
            foreach ($allOrdersForTotals as $order) {
                $deliveryCharges = (float)($order->delivery_charges ?? 0);
                $totalDeliveryCharges += $deliveryCharges;
                
                // Total amount excluding delivery charges
                $totalAmountExcludingDelivery += (float)($order->total_amount ?? 0) - $deliveryCharges;
                
                if ($order->details && $order->details->count() > 0) {
                    $totalProfit += (float)$order->details->sum('item_profit');
                } else {
                    // Fallback to stored profit
                    $totalProfit += (float)($order->profit ?? 0);
                }
            }
            
            $totals = [
                'total_amount' => $totalAmountExcludingDelivery,
                'paid_amount' => (float)$allOrdersForTotals->sum('paid_amount'),
                'discount' => (float)$allOrdersForTotals->sum('total_discount'),
                'profit' => $totalProfit,
                'delivery_charges' => $totalDeliveryCharges,
                'count' => $allOrdersForTotals->count(),
            ];

            return response()->json([
                'success' => true,
                'orders' => $formattedOrders,
                'totals' => $totals,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export POS Income Report to Excel
     */
    public function exportPosIncomeExcel(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            $query = PosOrders::with(['details', 'customer']);

            // Apply date filters
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $orders = $query->orderBy('created_at', 'DESC')->get();

            // Prepare data for export
            $data = [];
            
            // Headers
            $locale = session('locale', 'en');
            if ($locale === 'ar') {
                $data[] = ['رقم الطلب', 'اسم العميل', 'رقم الهاتف', 'المبلغ الإجمالي', 'المبلغ المدفوع', 'الخصم', 'الربح', 'رسوم التوصيل', 'أضيف بواسطة', 'تاريخ الإنشاء'];
            } else {
                $data[] = ['Order Number', 'Customer Name', 'Phone', 'Total Amount', 'Paid Amount', 'Discount', 'Profit', 'Delivery Charges', 'Added By', 'Created At'];
            }

            // Calculate totals - recalculate profit from order details
            $totalProfit = 0;
            $totalDeliveryCharges = 0;
            $totalAmountExcludingDelivery = 0;
            $dateObjects = []; // Store Carbon date objects for Excel formatting
            
            // Data rows
            foreach ($orders as $order) {
                $orderNo = str_pad($order->order_no ?? $order->id, 6, '0', STR_PAD_LEFT);
                
                // Get customer information
                $customerName = $order->customer ? ($order->customer->name ?? '-') : '-';
                $customerPhone = $order->customer ? ($order->customer->phone ?? '-') : '-';
                
                // Get delivery charges (separate from income/profit)
                $deliveryCharges = (float)($order->delivery_charges ?? 0);
                $totalDeliveryCharges += $deliveryCharges;
                
                $addedBy = $order->added_by ?? '-';
                if ($order->user_id) {
                    $user = User::find($order->user_id);
                    if ($user && $user->user_name) {
                        $addedBy = $user->user_name;
                    }
                }
                
                $createdAt = $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '-';
                // Store Carbon instance for Excel formatting
                $dateObjects[] = $order->created_at;
                
                // Calculate profit from order details (sum of all item profits)
                // Note: delivery charges are NOT included in profit
                $orderProfit = 0;
                if ($order->details && $order->details->count() > 0) {
                    $orderProfit = (float)$order->details->sum('item_profit');
                } else {
                    // Fallback to stored profit
                    $orderProfit = (float)($order->profit ?? 0);
                }
                
                $totalProfit += $orderProfit;
                
                // Total amount excluding delivery charges for income calculation
                $totalAmountExcludingDelivery += (float)($order->total_amount ?? 0) - $deliveryCharges;
                
                $data[] = [
                    $orderNo,
                    $customerName,
                    $customerPhone,
                    (float)($order->total_amount ?? 0) - $deliveryCharges,
                    (float)($order->paid_amount ?? 0),
                    (float)($order->total_discount ?? 0),
                    $orderProfit,
                    $deliveryCharges,
                    $addedBy,
                    $createdAt,
                ];
            }
            
            $totals = [
                'total_amount' => $totalAmountExcludingDelivery,
                'paid_amount' => (float)$orders->sum('paid_amount'),
                'discount' => (float)$orders->sum('total_discount'),
                'profit' => $totalProfit,
                'delivery_charges' => $totalDeliveryCharges,
            ];

            if ($locale === 'ar') {
                $data[] = ['الإجمالي', '', '', $totals['total_amount'], $totals['paid_amount'], $totals['discount'], $totals['profit'], $totals['delivery_charges'], '', ''];
            } else {
                $data[] = ['Total', '', '', $totals['total_amount'], $totals['paid_amount'], $totals['discount'], $totals['profit'], $totals['delivery_charges'], '', ''];
            }

            // Use PhpSpreadsheet if available
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                
                // Set sheet title
                $sheet->setTitle('POS Income Report');
                
                // Prepare date range text
                $dateRangeText = '';
                if ($fromDate && $toDate) {
                    if ($locale === 'ar') {
                        $dateRangeText = 'من ' . Carbon::parse($fromDate)->format('Y-m-d') . ' إلى ' . Carbon::parse($toDate)->format('Y-m-d');
                    } else {
                        $dateRangeText = 'From ' . Carbon::parse($fromDate)->format('Y-m-d') . ' To ' . Carbon::parse($toDate)->format('Y-m-d');
                    }
                } elseif ($fromDate) {
                    if ($locale === 'ar') {
                        $dateRangeText = 'من ' . Carbon::parse($fromDate)->format('Y-m-d');
                    } else {
                        $dateRangeText = 'From ' . Carbon::parse($fromDate)->format('Y-m-d');
                    }
                } elseif ($toDate) {
                    if ($locale === 'ar') {
                        $dateRangeText = 'إلى ' . Carbon::parse($toDate)->format('Y-m-d');
                    } else {
                        $dateRangeText = 'To ' . Carbon::parse($toDate)->format('Y-m-d');
                    }
                } else {
                    if ($locale === 'ar') {
                        $dateRangeText = 'جميع التواريخ';
                    } else {
                        $dateRangeText = 'All Dates';
                    }
                }
                
                // Report title
                $reportTitle = $locale === 'ar' ? 'تقرير دخل نقاط البيع' : 'POS Income Report';
                
                // Write report title (Row 1)
                $sheet->setCellValue('A1', $reportTitle);
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2E75B6'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(35);
                
                // Write date range (Row 2)
                $sheet->setCellValue('A2', $dateRangeText);
                $sheet->mergeCells('A2:G2');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '5B9BD5'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(25);
                
                // Write data row by row to handle date formatting properly
                $rowIndex = 3; // Start from row 3 (after title and date range)
                
                // Write headers
                $headers = $data[0];
                foreach ($headers as $colIndex => $header) {
                    $cell = $sheet->getCellByColumnAndRow($colIndex + 1, $rowIndex);
                    $cell->setValue($header);
                }
                $rowIndex++;
                
                // Write data rows
                $dataRowCount = count($data) - 1; // Exclude header and totals
                for ($i = 1; $i <= $dataRowCount; $i++) {
                    $rowData = $data[$i];
                    $dateIndex = $i - 1; // Index for dateObjects array
                    foreach ($rowData as $colIndex => $value) {
                        $cell = $sheet->getCellByColumnAndRow($colIndex + 1, $rowIndex);
                        
                        // Column G (index 6) is Created At - format as date/time
                        if ($colIndex == 6 && isset($dateObjects[$dateIndex]) && $dateObjects[$dateIndex]) {
                            try {
                                // Use Carbon instance directly to convert to Excel timestamp
                                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dateObjects[$dateIndex]);
                                $cell->setValue($excelDate);
                                $cell->getStyle()->getNumberFormat()
                                    ->setFormatCode('yyyy-mm-dd hh:mm:ss');
                            } catch (\Exception $e) {
                                // If conversion fails, just set as string
                                $cell->setValue($value);
                            }
                        } 
                        // Columns B, C, D, E (indices 1-4) are numbers - format with 3 decimals
                        elseif (in_array($colIndex, [1, 2, 3, 4]) && is_numeric($value)) {
                            $cell->setValue((float)$value);
                            $cell->getStyle()->getNumberFormat()
                                ->setFormatCode('#,##0.000');
                        } else {
                            $cell->setValue($value);
                        }
                    }
                    $rowIndex++;
                }
                
                // Write totals row
                $totalsRow = $data[count($data) - 1];
                foreach ($totalsRow as $colIndex => $value) {
                    $cell = $sheet->getCellByColumnAndRow($colIndex + 1, $rowIndex);
                    if (in_array($colIndex, [1, 2, 3, 4]) && is_numeric($value)) {
                        $cell->setValue((float)$value);
                        $cell->getStyle()->getNumberFormat()
                            ->setFormatCode('#,##0.000');
                    } else {
                        $cell->setValue($value);
                    }
                }
                
                $lastRow = $rowIndex;
                
                // Auto-size columns
                foreach (range('A', 'G') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    // Set minimum width
                    $sheet->getColumnDimension($col)->setWidth(max(12, $sheet->getColumnDimension($col)->getWidth()));
                }
                
                // Apply borders to title and date range rows
                $sheet->getStyle('A1:G2')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                
                // Apply borders to all data cells (from row 3 onwards)
                $sheet->getStyle('A3:G' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                
                // Style column header row (row 3)
                $headerRow = 3;
                $sheet->getStyle('A' . $headerRow . ':G' . $headerRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                // Set column header row height
                $sheet->getRowDimension($headerRow)->setRowHeight(25);
                
                // Style data rows - center align numbers and dates
                if ($lastRow > $headerRow + 1) {
                    $dataStartRow = $headerRow + 1;
                    // Center align number columns (B, C, D, E)
                    $sheet->getStyle('B' . $dataStartRow . ':E' . ($lastRow - 1))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);
                    
                    // Center align date column (G)
                    $sheet->getStyle('G' . $dataStartRow . ':G' . ($lastRow - 1))->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);
                    
                    // Alternate row colors for better readability (starting from data rows)
                    for ($row = $dataStartRow; $row < $lastRow; $row++) {
                        if (($row - $dataStartRow) % 2 == 0) {
                            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F2F2F2'],
                                ],
                            ]);
                        }
                    }
                }
                
                // Style totals row
                $sheet->getStyle('A' . $lastRow . ':G' . $lastRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E1F2'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                
                // Freeze column header row (row 3) and title rows
                $sheet->freezePane('A4');
                
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $filename = 'pos_income_report_' . ($fromDate ?? 'all') . '_' . ($toDate ?? 'all') . '_' . date('Y-m-d_His') . '.xlsx';
                
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
                header('Cache-Control: max-age=0');
                
                $writer->save('php://output');
                exit;
            } else {
                // Fallback: CSV export with UTF-8 BOM for Excel
                $filename = 'pos_income_report_' . ($fromDate ?? 'all') . '_' . ($toDate ?? 'all') . '_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                foreach ($data as $row) {
                    $row = array_map(function($value) {
                        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }, $row);
                    fputcsv($output, $row);
                }
                fclose($output);
                exit;
            }
        } catch (\Exception $e) {
            \Log::error('Error in exportPosIncomeExcel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export POS Income Report to PDF
     */
    public function exportPosIncomePdf(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            $query = PosOrders::with(['details', 'customer']);

            // Apply date filters
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $orders = $query->orderBy('created_at', 'DESC')->get();

            // Format orders for PDF
            $formattedOrders = $orders->map(function($order) {
                $orderNo = str_pad($order->order_no ?? $order->id, 6, '0', STR_PAD_LEFT);
                
                // Get customer information
                $customerName = $order->customer ? ($order->customer->name ?? '-') : '-';
                $customerPhone = $order->customer ? ($order->customer->phone ?? '-') : '-';
                
                // Get delivery charges (separate from income/profit)
                $deliveryCharges = (float)($order->delivery_charges ?? 0);
                
                $addedBy = $order->added_by ?? '-';
                if ($order->user_id) {
                    $user = User::find($order->user_id);
                    if ($user && $user->user_name) {
                        $addedBy = $user->user_name;
                    }
                }
                
                // Calculate profit from order details (sum of all item profits)
                // Note: delivery charges are NOT included in profit
                $orderProfit = 0;
                if ($order->details && $order->details->count() > 0) {
                    $orderProfit = (float)$order->details->sum('item_profit');
                } else {
                    // Fallback to stored profit
                    $orderProfit = (float)($order->profit ?? 0);
                }
                
                // Total amount excluding delivery charges for income calculation
                $totalAmountExcludingDelivery = (float)($order->total_amount ?? 0) - $deliveryCharges;
                
                return [
                    'order_no' => $orderNo,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'total_amount' => $totalAmountExcludingDelivery,
                    'paid_amount' => (float)($order->paid_amount ?? 0),
                    'discount' => (float)($order->total_discount ?? 0),
                    'profit' => $orderProfit,
                    'delivery_charges' => $deliveryCharges,
                    'added_by' => $addedBy,
                    'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '-',
                ];
            });

            // Calculate totals - recalculate profit from order details
            $totalProfit = 0;
            $totalDeliveryCharges = 0;
            $totalAmountExcludingDelivery = 0;
            
            foreach ($orders as $order) {
                $deliveryCharges = (float)($order->delivery_charges ?? 0);
                $totalDeliveryCharges += $deliveryCharges;
                
                // Total amount excluding delivery charges
                $totalAmountExcludingDelivery += (float)($order->total_amount ?? 0) - $deliveryCharges;
                
                if ($order->details && $order->details->count() > 0) {
                    $totalProfit += (float)$order->details->sum('item_profit');
                } else {
                    $totalProfit += (float)($order->profit ?? 0);
                }
            }
            
            $totals = [
                'total_amount' => $totalAmountExcludingDelivery,
                'paid_amount' => (float)$orders->sum('paid_amount'),
                'discount' => (float)$orders->sum('total_discount'),
                'profit' => $totalProfit,
                'delivery_charges' => $totalDeliveryCharges,
                'count' => $orders->count(),
            ];

            $html = view('reports.pos_income_pdf', compact('formattedOrders', 'totals', 'fromDate', 'toDate'))->render();
            
            // Use dompdf if available
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $filename = 'pos_income_report_' . ($fromDate ?? 'all') . '_' . ($toDate ?? 'all') . '_' . date('Y-m-d_His') . '.pdf';
                return $pdf->download($filename);
            } else {
                // Fallback: return HTML view for printing
                return view('reports.pos_income_pdf', compact('formattedOrders', 'totals', 'fromDate', 'toDate'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Show Special Orders Income Report page
     */
    public function specialOrdersIncomeReport()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        return view('reports.special_orders_income_report');
    }

    /**
     * Get Special Orders Income Report data with pagination
     */
    public function getSpecialOrdersIncomeReport(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $page = $request->input('page', 1);

            $query = SpecialOrder::with(['customer', 'items.stock'])
                //
                ->whereNotNull('customer_id')
                ->where(function($q) {
                    $q->whereNull('source')
                      ->orWhere('source', '!=', 'stock');
                });

            // Apply date filters
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            // Order by created_at descending
            $query->orderBy('created_at', 'DESC');

            // Paginate results
            $orders = $query->paginate(15, ['*'], 'page', $page);

            $formattedOrders = $orders->map(function($order) {
                // Format special order number
                $orderNo = $order->special_order_no ?? ('SO-' . str_pad($order->id, 6, '0', STR_PAD_LEFT));
                
                // Customer info
                $customerName = $order->customer ? $order->customer->name : '-';
                $customerPhone = $order->customer ? $order->customer->phone : '-';
                
                // Calculate profit from order items
                $orderProfit = 0;
                if ($order->items && $order->items->count() > 0) {
                    foreach ($order->items as $item) {
                        $itemPrice = (float)($item->price ?? 0);
                        $quantity = (int)($item->quantity ?? 0);
                        
                        // Get cost price and tailor charges from stock
                        $costPrice = 0;
                        $tailorCharges = 0;
                        if ($item->stock) {
                            $costPrice = (float)($item->stock->cost_price ?? 0);
                            $tailorCharges = (float)($item->stock->tailor_charges ?? 0);
                        }
                        
                        // Profit = (Price - Cost Price - Tailor Charges) × Quantity
                        $itemProfit = ($itemPrice - $costPrice - $tailorCharges) * $quantity;
                        $orderProfit += $itemProfit;
                    }
                }
                
                // Format date
                $createdAt = $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '-';
                
                // Determine source type (whatsapp or direct/walkin)
                $source = $order->source ?? 'direct';
                $sourceLabel = '';
                if ($source === 'whatsapp') {
                    $sourceLabel = trans('messages.whatsapp', [], session('locale', 'en')) ?: 'WhatsApp';
                } elseif ($source === 'walkin') {
                    $sourceLabel = trans('messages.direct', [], session('locale', 'en')) ?: 'Direct';
                } else {
                    $sourceLabel = trans('messages.direct', [], session('locale', 'en')) ?: 'Direct';
                }
                
                // Calculate total amount and paid amount without delivery charges
                $shippingFee = (float)($order->shipping_fee ?? 0);
                $discount = (float)($order->discount ?? 0);
                $totalAmountWithShipping = (float)($order->total_amount ?? 0);
                $paidAmountWithShipping = (float)($order->paid_amount ?? 0);
                
                // total_amount is stored already discounted; add back discount to get true total before discount
                $trueTotal = $totalAmountWithShipping + $discount;
                // Subtract delivery charges to show items-only total
                $totalAmountWithoutShipping = max(0, $trueTotal - $shippingFee);
                $paidAmountWithoutShipping = max(0, $paidAmountWithShipping - $shippingFee);
                
                return [
                    'id' => $order->id,
                    'order_no' => $orderNo,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'source' => $source,
                    'source_label' => $sourceLabel,
                    'total_amount' => $totalAmountWithoutShipping,
                    'paid_amount' => $paidAmountWithoutShipping,
                    'delivery_charges' => $shippingFee,
                    'discount' => $discount,
                    'profit' => $orderProfit,
                    'created_at' => $createdAt,
                ];
            });

            // Calculate totals - recalculate profit from all orders
            $totalQuery = SpecialOrder::with(['items.stock'])
                //
                ->whereNotNull('customer_id')
                ->where(function($q) {
                    $q->whereNull('source')
                      ->orWhere('source', '!=', 'stock');
                });
            
            if ($fromDate) {
                $totalQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $totalQuery->whereDate('created_at', '<=', $toDate);
            }
            
            $allOrdersForTotals = $totalQuery->get();
            
            // Calculate total profit from all order items
            $totalProfit = 0;
            foreach ($allOrdersForTotals as $order) {
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
                        $totalProfit += $itemProfit;
                    }
                }
            }
            
            // Calculate totals excluding delivery charges
            $totalShippingFee = (float)$allOrdersForTotals->sum('shipping_fee');
            $totalDiscount = (float)$allOrdersForTotals->sum('discount');
            $totalAmountWithShipping = (float)$allOrdersForTotals->sum('total_amount');
            $paidAmountWithShipping = (float)$allOrdersForTotals->sum('paid_amount');
            
            // total_amount is stored after discount; add back discount to get true total before discount
            $totals = [
                'total_amount' => max(0, $totalAmountWithShipping + $totalDiscount - $totalShippingFee),
                'paid_amount' => max(0, $paidAmountWithShipping - $totalShippingFee),
                'delivery_charges' => $totalShippingFee,
                'total_discount' => $totalDiscount,
                'profit' => $totalProfit,
                'count' => $allOrdersForTotals->count(),
            ];

            return response()->json([
                'success' => true,
                'orders' => $formattedOrders,
                'totals' => $totals,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export Special Orders Income Report to Excel
     */
    public function exportSpecialOrdersIncomeExcel(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            $query = SpecialOrder::with(['customer', 'items.stock'])
               
                ->whereNotNull('customer_id')
                ->where(function($q) {
                    $q->whereNull('source')
                      ->orWhere('source', '!=', 'stock');
                });

            // Apply date filters
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $orders = $query->orderBy('created_at', 'DESC')->get();

            // Prepare data for export
            $data = [];
            
            // Headers
            $locale = session('locale', 'en');
            if ($locale === 'ar') {
                $data[] = ['رقم الطلب', 'اسم العميل', 'رقم العميل', 'المصدر', 'المبلغ الإجمالي', 'المبلغ المدفوع', 'رسوم التوصيل', 'الربح', 'تاريخ الإنشاء'];
            } else {
                $data[] = ['Order Number', 'Customer Name', 'Customer Phone', 'Source', 'Total Amount', 'Paid Amount', 'Delivery Charges', 'Profit', 'Created At'];
            }

            // Calculate totals
            $totalProfit = 0;
            
            // Data rows
            foreach ($orders as $order) {
                $orderNo = $order->special_order_no ?? ('SO-' . str_pad($order->id, 6, '0', STR_PAD_LEFT));
                $customerName = $order->customer ? $order->customer->name : '-';
                $customerPhone = $order->customer ? $order->customer->phone : '-';
                $createdAt = $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '-';
                
                // Determine source type (whatsapp or direct/walkin)
                $source = $order->source ?? 'direct';
                $sourceLabel = '';
                if ($source === 'whatsapp') {
                    $sourceLabel = trans('messages.whatsapp', [], $locale) ?: 'WhatsApp';
                } elseif ($source === 'walkin') {
                    $sourceLabel = trans('messages.direct', [], $locale) ?: 'Direct';
                } else {
                    $sourceLabel = trans('messages.direct', [], $locale) ?: 'Direct';
                }
                
                // Calculate profit from order items
                $orderProfit = 0;
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
                        $orderProfit += $itemProfit;
                    }
                }
                $totalProfit += $orderProfit;
                
                // Calculate total amount and paid amount without delivery charges
                $shippingFee = (float)($order->shipping_fee ?? 0);
                $totalAmountWithShipping = (float)($order->total_amount ?? 0);
                $paidAmountWithShipping = (float)($order->paid_amount ?? 0);
                
                // Subtract delivery charges from total and paid amounts
                $totalAmountWithoutShipping = max(0, $totalAmountWithShipping - $shippingFee);
                $paidAmountWithoutShipping = max(0, $paidAmountWithShipping - $shippingFee);
                
                $data[] = [
                    $orderNo,
                    $customerName,
                    $customerPhone,
                    $sourceLabel,
                    $totalAmountWithoutShipping,
                    $paidAmountWithoutShipping,
                    $shippingFee,
                    $orderProfit,
                    $createdAt,
                ];
            }
            
            // Calculate totals excluding delivery charges
            $totalShippingFee = (float)$orders->sum('shipping_fee');
            $totalAmountWithShipping = (float)$orders->sum('total_amount');
            $paidAmountWithShipping = (float)$orders->sum('paid_amount');
            
            $totals = [
                'total_amount' => max(0, $totalAmountWithShipping - $totalShippingFee),
                'paid_amount' => max(0, $paidAmountWithShipping - $totalShippingFee),
                'delivery_charges' => $totalShippingFee,
                'profit' => $totalProfit,
            ];

            if ($locale === 'ar') {
                $data[] = ['الإجمالي', '', '', '', $totals['total_amount'], $totals['paid_amount'], $totals['delivery_charges'], $totals['profit'], ''];
            } else {
                $data[] = ['Total', '', '', '', $totals['total_amount'], $totals['paid_amount'], $totals['delivery_charges'], $totals['profit'], ''];
            }

            // Use PhpSpreadsheet if available
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                
                $sheet->fromArray($data, null, 'A1');
                
                // Auto-size columns
                foreach (range('A', 'I') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Style header row
                $sheet->getStyle('A1:I1')->getFont()->setBold(true);
                $sheet->getStyle('A1:I1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');
                
                // Style totals row
                $lastRow = count($data);
                $sheet->getStyle('A' . $lastRow . ':I' . $lastRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $lastRow . ':I' . $lastRow)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF0F0F0');
                
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $filename = 'special_orders_income_report_' . ($fromDate ?? 'all') . '_' . ($toDate ?? 'all') . '_' . date('Y-m-d_His') . '.xlsx';
                
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
                header('Cache-Control: max-age=0');
                
                $writer->save('php://output');
                exit;
            } else {
                // Fallback: CSV export
                $filename = 'special_orders_income_report_' . ($fromDate ?? 'all') . '_' . ($toDate ?? 'all') . '_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                foreach ($data as $row) {
                    $row = array_map(function($value) {
                        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }, $row);
                    fputcsv($output, $row);
                }
                fclose($output);
                exit;
            }
        } catch (\Exception $e) {
            \Log::error('Error in exportSpecialOrdersIncomeExcel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export Special Orders Income Report to PDF
     */
    public function exportSpecialOrdersIncomePdf(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            $query = SpecialOrder::with(['customer', 'items.stock'])
               
                ->whereNotNull('customer_id')
                ->where(function($q) {
                    $q->whereNull('source')
                      ->orWhere('source', '!=', 'stock');
                });

            // Apply date filters
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $orders = $query->orderBy('created_at', 'DESC')->get();

            // Format orders for PDF
            $formattedOrders = $orders->map(function($order) {
                $orderNo = $order->special_order_no ?? ('SO-' . str_pad($order->id, 6, '0', STR_PAD_LEFT));
                $customerName = $order->customer ? $order->customer->name : '-';
                $customerPhone = $order->customer ? $order->customer->phone : '-';
                
                // Calculate profit
                $orderProfit = 0;
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
                        $orderProfit += $itemProfit;
                    }
                }
                
                // Determine source type (whatsapp or direct/walkin)
                $source = $order->source ?? 'direct';
                $sourceLabel = '';
                if ($source === 'whatsapp') {
                    $sourceLabel = trans('messages.whatsapp', [], session('locale', 'en')) ?: 'WhatsApp';
                } elseif ($source === 'walkin') {
                    $sourceLabel = trans('messages.direct', [], session('locale', 'en')) ?: 'Direct';
                } else {
                    $sourceLabel = trans('messages.direct', [], session('locale', 'en')) ?: 'Direct';
                }
                
                // Calculate total amount and paid amount without delivery charges
                $shippingFee = (float)($order->shipping_fee ?? 0);
                $totalAmountWithShipping = (float)($order->total_amount ?? 0);
                $paidAmountWithShipping = (float)($order->paid_amount ?? 0);
                
                // Subtract delivery charges from total and paid amounts
                $totalAmountWithoutShipping = max(0, $totalAmountWithShipping - $shippingFee);
                $paidAmountWithoutShipping = max(0, $paidAmountWithShipping - $shippingFee);
                
                return [
                    'order_no' => $orderNo,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'source' => $source,
                    'source_label' => $sourceLabel,
                    'total_amount' => $totalAmountWithoutShipping,
                    'paid_amount' => $paidAmountWithoutShipping,
                    'delivery_charges' => $shippingFee,
                    'profit' => $orderProfit,
                    'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '-',
                ];
            });

            // Calculate totals
            $totalProfit = 0;
            foreach ($orders as $order) {
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
                        $totalProfit += $itemProfit;
                    }
                }
            }
            
            // Calculate totals excluding delivery charges
            $totalShippingFee = (float)$orders->sum('shipping_fee');
            $totalAmountWithShipping = (float)$orders->sum('total_amount');
            $paidAmountWithShipping = (float)$orders->sum('paid_amount');
            
            $totals = [
                'total_amount' => max(0, $totalAmountWithShipping - $totalShippingFee),
                'paid_amount' => max(0, $paidAmountWithShipping - $totalShippingFee),
                'delivery_charges' => $totalShippingFee,
                'profit' => $totalProfit,
                'count' => $orders->count(),
            ];

            $html = view('reports.special_orders_income_pdf', compact('formattedOrders', 'totals', 'fromDate', 'toDate'))->render();
            
            // Use dompdf if available
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $filename = 'special_orders_income_report_' . ($fromDate ?? 'all') . '_' . ($toDate ?? 'all') . '_' . date('Y-m-d_His') . '.pdf';
                return $pdf->download($filename);
            } else {
                // Fallback: return HTML view for printing
                return view('reports.special_orders_income_pdf', compact('formattedOrders', 'totals', 'fromDate', 'toDate'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Show Settlement Profit Report page
     */
    public function settlementProfitReport()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        // Get all boutiques and channels for filter
        $boutiques = Boutique::orderBy('boutique_name', 'ASC')->get(['id', 'boutique_name']);
        $channels = Channel::orderBy('channel_name_ar', 'ASC')->get(['id', 'channel_name_ar', 'channel_name_en']);

        return view('reports.settlement_profit_report', compact('boutiques', 'channels'));
    }

    /**
     * Get Settlement Profit Report data with pagination
     */
    public function getSettlementProfitReport(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $boutiqueId = $request->input('boutique_id');
            $channelId = $request->input('channel_id');
            $page = $request->input('page', 1);

            $query = Settlement::query();

            // Apply date filters - use created_at instead of date_from/date_to
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            // Apply boutique filter
            if ($boutiqueId) {
                $query->where('boutique_id', $boutiqueId);
            }

            // Order by created_at descending
            $query->orderBy('created_at', 'DESC');

            // Paginate results
            $settlements = $query->paginate(10, ['*'], 'page', $page);

            $formattedSettlements = $settlements->map(function($settlement) {
                // Calculate profit from items_data
                $profit = 0;
                $itemsData = $settlement->items_data;
                $itemsCount = 0;
                
                // Handle JSON string if needed (for older records)
                if (is_string($itemsData)) {
                    $itemsData = json_decode($itemsData, true);
                    // Handle double-encoded JSON (string inside JSON)
                    if (is_string($itemsData)) {
                        $itemsData = json_decode($itemsData, true);
                    }
                }
                
                if (is_array($itemsData)) {
                    foreach ($itemsData as $item) {
                        $soldQty = (int)($item['sold'] ?? 0);
                        if ($soldQty > 0) {
                            $itemsCount += $soldQty;
                            $abayaCode = trim((string)($item['code'] ?? ''));
                            
                            if (empty($abayaCode)) {
                                continue; // Skip if no code
                            }
                            
                            // Get stock for cost and tailor charges
                            $stock = Stock::where('abaya_code', $abayaCode)
                                ->orWhere('barcode', $abayaCode)
                                ->first();
                            if ($stock) {
                                $salesPrice = (float)($stock->sales_price ?? 0);
                                $costPrice = (float)($stock->cost_price ?? 0);
                                $tailorCharges = (float)($stock->tailor_charges ?? 0);
                                
                                // Profit = (Sales Price - Cost Price - Tailor Charges) × Sold Quantity
                                $itemProfit = ($salesPrice - $costPrice - $tailorCharges) * $soldQty;
                                $profit += $itemProfit;
                                
                                // Debug logging if profit is zero but should have values
                                if ($itemProfit == 0 && $soldQty > 0) {
                                    \Log::warning("Settlement Profit: Zero profit for stock", [
                                        'settlement_id' => $settlement->id,
                                        'abaya_code' => $abayaCode,
                                        'sold_qty' => $soldQty,
                                        'sales_price' => $salesPrice,
                                        'cost_price' => $costPrice,
                                        'tailor_charges' => $tailorCharges,
                                    ]);
                                }
                            } else {
                                // Log when stock is not found
                                \Log::warning("Settlement Profit: Stock not found", [
                                    'settlement_id' => $settlement->id,
                                    'abaya_code' => $abayaCode,
                                    'sold_qty' => $soldQty,
                                ]);
                            }
                        }
                    }
                }
                
                // Format month display
                $monthDisplay = $settlement->month ?? '-';
                if ($monthDisplay && $monthDisplay !== '-') {
                    try {
                        $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $monthDisplay);
                        $locale = session('locale', 'en');
                        if ($locale === 'ar') {
                            $monthDisplay = $monthDate->format('Y-m');
                        } else {
                            $monthDisplay = $monthDate->format('M Y');
                        }
                    } catch (\Exception $e) {
                        // Keep original format if parsing fails
                    }
                }
                
                return [
                    'id' => $settlement->id,
                    'operation_number' => $settlement->settlement_code ?? '-',
                    'month' => $monthDisplay,
                    'boutique' => $settlement->boutique_name ?? '-',
                    // Recalculate from items_data (stored value may be 0 in older records)
                    'number_of_items' => (int)$itemsCount,
                    'sales' => (float)($settlement->total_sales ?? 0),
                    'profit' => $profit,
                ];
            });

            // Calculate totals - recalculate profit from all settlements
            $totalQuery = Settlement::query();
            if ($fromDate) {
                $totalQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $totalQuery->whereDate('created_at', '<=', $toDate);
            }
            if ($boutiqueId) {
                $totalQuery->where('boutique_id', $boutiqueId);
            }
            
            $allSettlementsForTotals = $totalQuery->get();
            
            // Calculate total profit from all settlements
            $totalProfit = 0;
            $totalItems = 0;
            foreach ($allSettlementsForTotals as $settlement) {
                $itemsData = $settlement->items_data;
                
                // Handle JSON string if needed (for older records)
                if (is_string($itemsData)) {
                    $itemsData = json_decode($itemsData, true);
                    // Handle double-encoded JSON (string inside JSON)
                    if (is_string($itemsData)) {
                        $itemsData = json_decode($itemsData, true);
                    }
                }
                
                if (is_array($itemsData)) {
                    foreach ($itemsData as $item) {
                        $soldQty = (int)($item['sold'] ?? 0);
                        if ($soldQty > 0) {
                            $totalItems += $soldQty;
                            $abayaCode = trim((string)($item['code'] ?? ''));
                            
                            if (empty($abayaCode)) {
                                continue; // Skip if no code
                            }
                            
                            $stock = Stock::where('abaya_code', $abayaCode)
                                ->orWhere('barcode', $abayaCode)
                                ->first();
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
            
            $totals = [
                // Recalculate items instead of summing stored column
                'number_of_items' => (int)$totalItems,
                'sales' => (float)$allSettlementsForTotals->sum('total_sales'),
                'profit' => $totalProfit,
                'count' => $allSettlementsForTotals->count(),
            ];

            return response()->json([
                'success' => true,
                'settlements' => $formattedSettlements,
                'totals' => $totals,
                'current_page' => $settlements->currentPage(),
                'last_page' => $settlements->lastPage(),
                'total' => $settlements->total(),
                'per_page' => $settlements->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export Settlement Profit Report to Excel
     */
    public function exportSettlementProfitExcel(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $boutiqueId = $request->input('boutique_id');
            $channelId = $request->input('channel_id');

            $query = Settlement::query();

            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
            if ($boutiqueId) {
                $query->where('boutique_id', $boutiqueId);
            }

            $settlements = $query->orderBy('created_at', 'DESC')->get();

            // Prepare data for export
            $data = [];
            
            // Headers
            $locale = session('locale', 'en');
            if ($locale === 'ar') {
                $data[] = ['رقم العملية', 'الشهر', 'البوتيك', 'عدد العناصر', 'المبيعات (ر.ع)', 'الربح'];
            } else {
                $data[] = ['Operation Number', 'Month', 'Boutique', 'Number of Items', 'Sales (OMR)', 'Profit'];
            }

            // Calculate totals
            $totalProfit = 0;
            
            // Data rows
            foreach ($settlements as $settlement) {
                // Calculate profit
                $profit = 0;
                $itemsData = $settlement->items_data;
                $itemsCount = 0;
                
                // Handle JSON string if needed (for older records)
                if (is_string($itemsData)) {
                    $itemsData = json_decode($itemsData, true);
                    // Handle double-encoded JSON (string inside JSON)
                    if (is_string($itemsData)) {
                        $itemsData = json_decode($itemsData, true);
                    }
                }
                
                if (is_array($itemsData)) {
                    foreach ($itemsData as $item) {
                        $soldQty = (int)($item['sold'] ?? 0);
                        if ($soldQty > 0) {
                            $itemsCount += $soldQty;
                            $abayaCode = trim((string)($item['code'] ?? ''));
                            
                            if (empty($abayaCode)) {
                                continue; // Skip if no code
                            }
                            
                            $stock = Stock::where('abaya_code', $abayaCode)
                                ->orWhere('barcode', $abayaCode)
                                ->first();
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
                $totalProfit += $profit;
                
                // Format month
                $monthDisplay = $settlement->month ?? '-';
                if ($monthDisplay && $monthDisplay !== '-') {
                    try {
                        $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $monthDisplay);
                        $monthDisplay = $monthDate->format('Y-m');
                    } catch (\Exception $e) {
                        // Keep original
                    }
                }
                
                $data[] = [
                    $settlement->settlement_code ?? '-',
                    $monthDisplay,
                    $settlement->boutique_name ?? '-',
                    (int)$itemsCount,
                    (float)($settlement->total_sales ?? 0),
                    $profit,
                ];
            }
            
            $totals = [
                // Sum from generated rows (column index 3) instead of stored column
                'number_of_items' => (int)collect($data)->slice(1)->sum(function ($row) {
                    return (int)($row[3] ?? 0);
                }),
                'sales' => (float)$settlements->sum('total_sales'),
                'profit' => $totalProfit,
            ];

            if ($locale === 'ar') {
                $data[] = ['الإجمالي', '', '', $totals['number_of_items'], $totals['sales'], $totals['profit']];
            } else {
                $data[] = ['Total', '', '', $totals['number_of_items'], $totals['sales'], $totals['profit']];
            }

            // Use PhpSpreadsheet if available
            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                
                $sheet->fromArray($data, null, 'A1');
                
                // Auto-size columns
                foreach (range('A', 'F') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Style header row
                $sheet->getStyle('A1:F1')->getFont()->setBold(true);
                $sheet->getStyle('A1:F1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');
                
                // Style totals row
                $lastRow = count($data);
                $sheet->getStyle('A' . $lastRow . ':F' . $lastRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $lastRow . ':F' . $lastRow)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF0F0F0');
                
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $filename = 'settlement_profit_report_' . ($fromDate ?? 'all') . '_' . ($toDate ?? 'all') . '_' . date('Y-m-d_His') . '.xlsx';
                
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
                header('Cache-Control: max-age=0');
                
                $writer->save('php://output');
                exit;
            } else {
                // Fallback: CSV export
                $filename = 'settlement_profit_report_' . ($fromDate ?? 'all') . '_' . ($toDate ?? 'all') . '_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                foreach ($data as $row) {
                    $row = array_map(function($value) {
                        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }, $row);
                    fputcsv($output, $row);
                }
                fclose($output);
                exit;
            }
        } catch (\Exception $e) {
            \Log::error('Error in exportSettlementProfitExcel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export Settlement Profit Report to PDF
     */
    public function exportSettlementProfitPdf(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $boutiqueId = $request->input('boutique_id');
            $channelId = $request->input('channel_id');

            $query = Settlement::query();

            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
            if ($boutiqueId) {
                $query->where('boutique_id', $boutiqueId);
            }

            $settlements = $query->orderBy('created_at', 'DESC')->get();

            // Format settlements for PDF
            $formattedSettlements = $settlements->map(function($settlement) {
                // Calculate profit
                $profit = 0;
                $itemsData = $settlement->items_data;
                $itemsCount = 0;
                
                // Handle JSON string if needed (for older records)
                if (is_string($itemsData)) {
                    $itemsData = json_decode($itemsData, true);
                }
                
                if (is_array($itemsData)) {
                    foreach ($itemsData as $item) {
                        $soldQty = (int)($item['sold'] ?? 0);
                        if ($soldQty > 0) {
                            $itemsCount += $soldQty;
                            $abayaCode = trim((string)($item['code'] ?? ''));
                            
                            if (empty($abayaCode)) {
                                continue; // Skip if no code
                            }
                            
                            $stock = Stock::where('abaya_code', $abayaCode)
                                ->orWhere('barcode', $abayaCode)
                                ->first();
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
                
                // Format month
                $monthDisplay = $settlement->month ?? '-';
                if ($monthDisplay && $monthDisplay !== '-') {
                    try {
                        $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $monthDisplay);
                        $locale = session('locale', 'en');
                        if ($locale === 'ar') {
                            $monthDisplay = $monthDate->format('Y-m');
                        } else {
                            $monthDisplay = $monthDate->format('M Y');
                        }
                    } catch (\Exception $e) {
                        // Keep original
                    }
                }
                
                return [
                    'operation_number' => $settlement->settlement_code ?? '-',
                    'month' => $monthDisplay,
                    'boutique' => $settlement->boutique_name ?? '-',
                    'number_of_items' => (int)$itemsCount,
                    'sales' => (float)($settlement->total_sales ?? 0),
                    'profit' => $profit,
                ];
            });

            // Calculate totals
            $totalProfit = 0;
            $totalItems = 0;
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
                            $totalItems += $soldQty;
                            $abayaCode = trim((string)($item['code'] ?? ''));
                            if (empty($abayaCode)) {
                                continue;
                            }
                            $stock = Stock::where('abaya_code', $abayaCode)
                                ->orWhere('barcode', $abayaCode)
                                ->first();
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
            
            $totals = [
                'number_of_items' => (int)$totalItems,
                'sales' => (float)$settlements->sum('total_sales'),
                'profit' => $totalProfit,
                'count' => $settlements->count(),
            ];

            $html = view('reports.settlement_profit_pdf', compact('formattedSettlements', 'totals', 'fromDate', 'toDate'))->render();
            
            // Use dompdf if available
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $filename = 'settlement_profit_report_' . ($fromDate ?? 'all') . '_' . ($toDate ?? 'all') . '_' . date('Y-m-d_His') . '.pdf';
                return $pdf->download($filename);
            } else {
                // Fallback: return HTML view for printing
                return view('reports.settlement_profit_pdf', compact('formattedSettlements', 'totals', 'fromDate', 'toDate'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    public function yearlyIncomeReport(){
        return view('reports.yearly_income_report');
    }

    /**
     * Show Profit & Expense Report page (by date: expense = tailor payments + expense module; profit = POS + Special Orders + Settlement)
     */
    public function profitExpenseReport()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        return view('reports.profit_expense_report');
    }

    /**
     * Get Profit & Expense Report data: for each date in range, expense (tailor + expense module) and profit (POS + Special + Settlement)
     */
    public function getProfitExpenseReport(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            if (!$fromDate || !$toDate) {
                return response()->json(['success' => false, 'message' => 'From date and to date are required'], 400);
            }
            if (strtotime($fromDate) > strtotime($toDate)) {
                return response()->json(['success' => false, 'message' => 'From date must be before or equal to to date'], 400);
            }

            $start = Carbon::parse($fromDate)->startOfDay();
            $end = Carbon::parse($toDate)->endOfDay();
            $rows = [];
            $totals = [
                'expense_module' => 0,
                'expense_boutique_rent' => 0,
                'expense_maintenance_company' => 0,
                'expense_total' => 0,
                'profit_pos' => 0,
                'profit_special' => 0,
                'profit_settlement' => 0,
                'profit_total' => 0,
                'net' => 0,
            ];

            // Pre-fetch all data for the range to avoid N+1 per date
            $expenseByDate = Expense::whereBetween('expense_date', [$fromDate, $toDate])
                ->get()
                ->groupBy(fn ($e) => $e->expense_date ? \Carbon\Carbon::parse($e->expense_date)->format('Y-m-d') : '');

            // Boutique rent payments (paid invoices) by payment_date
            $boutiqueRentByDate = BoutiqueInvo::where(function ($q) {
                    $q->where('status', '4')->orWhere('status', 4);
                })
                ->whereNotNull('payment_date')
                ->whereBetween('payment_date', [$start, $end])
                ->get(['payment_date', 'total_amount'])
                ->groupBy(fn ($i) => $i->payment_date ? \Carbon\Carbon::parse($i->payment_date)->format('Y-m-d') : '');

            // Maintenance (company bearer) expenses by delivered date
            $maintenanceCompanyItems = SpecialOrderItem::where('maintenance_status', 'delivered')
                ->where('maintenance_cost_bearer', 'company')
                ->whereNotNull('repaired_delivered_at')
                ->whereBetween('repaired_delivered_at', [$start, $end])
                ->get(['repaired_delivered_at', 'maintenance_delivery_charges', 'maintenance_repair_cost'])
                ->groupBy(fn ($i) => $i->repaired_delivered_at ? \Carbon\Carbon::parse($i->repaired_delivered_at)->format('Y-m-d') : '');

            $posOrders = PosOrders::with('details')
                ->whereBetween('created_at', [$start, $end])
                ->get();
            $posByDate = [];
            foreach ($posOrders as $o) {
                $d = $o->created_at ? $o->created_at->format('Y-m-d') : '';
                if (!isset($posByDate[$d])) $posByDate[$d] = 0;
                $posByDate[$d] += (float)($o->details ? $o->details->sum('item_profit') : ($o->profit ?? 0));
            }

            $specialOrders = SpecialOrder::with(['items.stock'])
               
                ->whereNotNull('customer_id')
                ->where(function ($q) { $q->whereNull('source')->orWhere('source', '!=', 'stock'); })
                ->whereBetween('created_at', [$start, $end])
                ->get();
            $specialByDate = [];
            foreach ($specialOrders as $o) {
                $d = $o->created_at ? $o->created_at->format('Y-m-d') : '';
                if (!isset($specialByDate[$d])) $specialByDate[$d] = 0;
                foreach ($o->items ?? [] as $item) {
                    $price = (float)($item->price ?? 0);
                    $qty = (int)($item->quantity ?? 0);
                    $cost = 0;
                    $tailor = 0;
                    if ($item->stock) {
                        $cost = (float)($item->stock->cost_price ?? 0);
                        $tailor = (float)($item->stock->tailor_charges ?? 0);
                    }
                    $specialByDate[$d] += ($price - $cost - $tailor) * $qty;
                }
            }

            $settlements = Settlement::whereBetween('created_at', [$start, $end])->get();
            $settlementByDate = [];
            foreach ($settlements as $s) {
                $d = $s->created_at ? $s->created_at->format('Y-m-d') : '';
                if (!isset($settlementByDate[$d])) $settlementByDate[$d] = 0;
                $items = $s->items_data;
                if (is_string($items)) {
                    $items = json_decode($items, true);
                    // Handle double-encoded JSON (string inside JSON)
                    if (is_string($items)) {
                        $items = json_decode($items, true);
                    }
                }
                $items = is_array($items) ? $items : [];
                foreach ($items as $it) {
                    $sold = (int)($it['sold'] ?? 0);
                    if ($sold <= 0) continue;
                    $code = $it['code'] ?? '';
                    $stock = Stock::where('abaya_code', $code)
                        ->orWhere('barcode', $code)
                        ->first();
                    if ($stock) {
                        $sales = (float)($stock->sales_price ?? 0);
                        $cost = (float)($stock->cost_price ?? 0);
                        $tailor = (float)($stock->tailor_charges ?? 0);
                        $settlementByDate[$d] += ($sales - $cost - $tailor) * $sold;
                    }
                }
            }

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $expenseModule = (float)($expenseByDate->get($dateStr, collect())->sum('amount'));
                $expenseBoutiqueRent = (float)($boutiqueRentByDate->get($dateStr, collect())->sum('total_amount'));
                $expenseMaintenanceCompany = 0;
                $mc = $maintenanceCompanyItems->get($dateStr, collect());
                if ($mc && $mc->count() > 0) {
                    $expenseMaintenanceCompany = (float)$mc->sum('maintenance_delivery_charges') + (float)$mc->sum('maintenance_repair_cost');
                }
                $expenseTotal = $expenseModule + $expenseBoutiqueRent + $expenseMaintenanceCompany;
                $profitPos = (float)($posByDate[$dateStr] ?? 0);
                $profitSpecial = (float)($specialByDate[$dateStr] ?? 0);
                $profitSettlement = (float)($settlementByDate[$dateStr] ?? 0);
                $profitTotal = $profitPos + $profitSpecial + $profitSettlement;
                $net = $profitTotal - $expenseTotal;

                $rows[] = [
                    'date' => $dateStr,
                    'expense_module' => $expenseModule,
                    'expense_boutique_rent' => $expenseBoutiqueRent,
                    'expense_maintenance_company' => $expenseMaintenanceCompany,
                    'expense_total' => $expenseTotal,
                    'profit_pos' => $profitPos,
                    'profit_special' => $profitSpecial,
                    'profit_settlement' => $profitSettlement,
                    'profit_total' => $profitTotal,
                    'net' => $net,
                ];

                $totals['expense_module'] += $expenseModule;
                $totals['expense_boutique_rent'] += $expenseBoutiqueRent;
                $totals['expense_maintenance_company'] += $expenseMaintenanceCompany;
                $totals['expense_total'] += $expenseTotal;
                $totals['profit_pos'] += $profitPos;
                $totals['profit_special'] += $profitSpecial;
                $totals['profit_settlement'] += $profitSettlement;
                $totals['profit_total'] += $profitTotal;
                $totals['net'] += $net;
            }

            return response()->json([
                'success' => true,
                'rows' => $rows,
                'totals' => $totals,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show Daily Sales Report: per day — Special Orders (delivered) orders/items/sales, POS orders/items/sales, Settlement items/sales; total items and total sales.
     */
    public function dailySalesReport()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }
        return view('reports.daily_sales_report');
    }

    /**
     * Get Daily Sales Report data: for each date in range: so_orders, so_items, so_sales, pos_orders, pos_items, pos_sales, settlement_items, settlement_sales, total_items, total_sales.
     */
    public function getDailySalesReport(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            if (!$fromDate || !$toDate) {
                return response()->json(['success' => false, 'message' => 'From date and to date are required'], 400);
            }
            if (strtotime($fromDate) > strtotime($toDate)) {
                return response()->json(['success' => false, 'message' => 'From date must be before or equal to to date'], 400);
            }

            $start = Carbon::parse($fromDate)->startOfDay();
            $end = Carbon::parse($toDate)->endOfDay();
            $rows = [];
            $totals = [
                'so_orders' => 0,
                'so_items' => 0,
                'so_sales' => 0,
                'so_profit' => 0,
                'pos_orders' => 0,
                'pos_items' => 0,
                'pos_sales' => 0,
                'pos_profit' => 0,
                'settlement_items' => 0,
                'settlement_sales' => 0,
                'settlement_profit' => 0,
                'total_items' => 0,
                'total_sales' => 0,
                'total_profit' => 0,
            ];

            // Special Orders: delivered only. Group by created_at date -> orders count, items (sum quantity), sales (sum total_amount)
            $soQuery = SpecialOrder::with(['items.stock'])
               
                ->whereBetween('created_at', [$start, $end])
                ->get();
            $soByDate = [];
            foreach ($soQuery as $o) {
                $d = $o->created_at ? $o->created_at->format('Y-m-d') : '';
                if (!isset($soByDate[$d])) {
                    $soByDate[$d] = ['orders' => 0, 'items' => 0, 'sales' => 0.0, 'profit' => 0.0];
                }
                $soByDate[$d]['orders'] += 1;
                $soByDate[$d]['items'] += (int)($o->items ? $o->items->sum('quantity') : 0);
                $soByDate[$d]['sales'] += (float)($o->total_amount ?? 0);

                // Profit for special orders: (Item Price - Stock Cost - Tailor Charges) × Quantity
                $orderProfit = 0.0;
                foreach ($o->items ?? [] as $item) {
                    $price = (float)($item->price ?? 0);
                    $qty = (int)($item->quantity ?? 0);
                    if ($qty <= 0) continue;
                    $cost = 0.0;
                    $tailor = 0.0;
                    if ($item->stock) {
                        $cost = (float)($item->stock->cost_price ?? 0);
                        $tailor = (float)($item->stock->tailor_charges ?? 0);
                    }
                    $orderProfit += ($price - $cost - $tailor) * $qty;
                }
                $soByDate[$d]['profit'] += $orderProfit;
            }

            // POS: orders, items (sum of detail item_quantity), sales (sum total_amount)
            $posQuery = PosOrders::with(['details'])->whereBetween('created_at', [$start, $end])->get();
            $posByDate = [];
            foreach ($posQuery as $o) {
                $d = $o->created_at ? $o->created_at->format('Y-m-d') : '';
                if (!isset($posByDate[$d])) {
                    $posByDate[$d] = ['orders' => 0, 'items' => 0, 'sales' => 0.0, 'profit' => 0.0];
                }
                $posByDate[$d]['orders'] += 1;
                $posByDate[$d]['items'] += (int)($o->details ? $o->details->sum('item_quantity') : 0);
                $posByDate[$d]['sales'] += (float)($o->total_amount ?? 0);
                // POS profit is stored per line in details.item_profit (already includes quantity)
                $posByDate[$d]['profit'] += (float)($o->details ? $o->details->sum('item_profit') : 0);
            }

            // Settlement: items (sum of items_data[].sold), sales (total_sales)
            $setQuery = Settlement::whereBetween('created_at', [$start, $end])->get();
            $setByDate = [];

            // Build a stock lookup for settlement items to avoid repeated DB queries
            $settlementCodes = [];
            foreach ($setQuery as $s) {
                $itemsDataTmp = $s->items_data;
                if (is_string($itemsDataTmp)) {
                    $itemsDataTmp = json_decode($itemsDataTmp, true);
                    // Handle double-encoded JSON (string inside JSON)
                    if (is_string($itemsDataTmp)) {
                        $itemsDataTmp = json_decode($itemsDataTmp, true);
                    }
                }
                if (is_array($itemsDataTmp)) {
                    foreach ($itemsDataTmp as $it) {
                        $code = trim((string)($it['code'] ?? ''));
                        if (!empty($code)) $settlementCodes[] = $code;
                    }
                }
            }
            $settlementStocks = [];
            if (!empty($settlementCodes)) {
                $uniqueCodes = array_values(array_unique($settlementCodes));
                $stocks = Stock::whereIn('abaya_code', $uniqueCodes)
                    ->orWhereIn('barcode', $uniqueCodes)
                    ->get();

                foreach ($stocks as $st) {
                    $arr = $st->toArray();
                    if (!empty($st->abaya_code)) {
                        $settlementStocks[$st->abaya_code] = $arr;
                    }
                    if (!empty($st->barcode)) {
                        $settlementStocks[$st->barcode] = $arr;
                    }
                }
            }

            foreach ($setQuery as $s) {
                $d = $s->created_at ? $s->created_at->format('Y-m-d') : '';
                if (!isset($setByDate[$d])) {
                    $setByDate[$d] = ['items' => 0, 'sales' => 0.0, 'profit' => 0.0];
                }
                $items = $s->items_data;
                if (is_string($items)) {
                    $items = json_decode($items, true);
                    // Handle double-encoded JSON (string inside JSON)
                    if (is_string($items)) {
                        $items = json_decode($items, true);
                    }
                }
                $items = is_array($items) ? $items : [];
                foreach ($items as $it) {
                    $sold = (int)($it['sold'] ?? 0);
                    if ($sold <= 0) continue;
                    $setByDate[$d]['items'] += $sold;

                    // Settlement profit: (Stock Sales Price - Stock Cost Price - Tailor Charges) × Sold Qty
                    $code = trim((string)($it['code'] ?? ''));
                    if (empty($code)) continue;
                    $stockArr = $settlementStocks[$code] ?? null;
                    if ($stockArr) {
                        $sales = (float)($stockArr['sales_price'] ?? 0);
                        $cost = (float)($stockArr['cost_price'] ?? 0);
                        $tailor = (float)($stockArr['tailor_charges'] ?? 0);
                        $setByDate[$d]['profit'] += ($sales - $cost - $tailor) * $sold;
                    }
                }
                $setByDate[$d]['sales'] += (float)($s->total_sales ?? 0);
            }

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $so = $soByDate[$dateStr] ?? ['orders' => 0, 'items' => 0, 'sales' => 0.0, 'profit' => 0.0];
                $pos = $posByDate[$dateStr] ?? ['orders' => 0, 'items' => 0, 'sales' => 0.0, 'profit' => 0.0];
                $set = $setByDate[$dateStr] ?? ['items' => 0, 'sales' => 0.0, 'profit' => 0.0];

                $soOrders = (int)$so['orders'];
                $soItems = (int)$so['items'];
                $soSales = (float)$so['sales'];
                $soProfit = (float)$so['profit'];
                $posOrders = (int)$pos['orders'];
                $posItems = (int)$pos['items'];
                $posSales = (float)$pos['sales'];
                $posProfit = (float)$pos['profit'];
                $setItems = (int)$set['items'];
                $setSales = (float)$set['sales'];
                $setProfit = (float)$set['profit'];
                $totalItems = $soItems + $posItems + $setItems;
                $totalSales = $soSales + $posSales + $setSales;
                $totalProfit = $soProfit + $posProfit + $setProfit;

                $rows[] = [
                    'date' => $dateStr,
                    'so_orders' => $soOrders,
                    'so_items' => $soItems,
                    'so_sales' => $soSales,
                    'so_profit' => $soProfit,
                    'pos_orders' => $posOrders,
                    'pos_items' => $posItems,
                    'pos_sales' => $posSales,
                    'pos_profit' => $posProfit,
                    'settlement_items' => $setItems,
                    'settlement_sales' => $setSales,
                    'settlement_profit' => $setProfit,
                    'total_items' => $totalItems,
                    'total_sales' => $totalSales,
                    'total_profit' => $totalProfit,
                ];

                $totals['so_orders'] += $soOrders;
                $totals['so_items'] += $soItems;
                $totals['so_sales'] += $soSales;
                $totals['so_profit'] += $soProfit;
                $totals['pos_orders'] += $posOrders;
                $totals['pos_items'] += $posItems;
                $totals['pos_sales'] += $posSales;
                $totals['pos_profit'] += $posProfit;
                $totals['settlement_items'] += $setItems;
                $totals['settlement_sales'] += $setSales;
                $totals['settlement_profit'] += $setProfit;
                $totals['total_items'] += $totalItems;
                $totals['total_sales'] += $totalSales;
                $totals['total_profit'] += $totalProfit;
            }

            $perPage = (int) $request->input('per_page', 15);
            $perPage = $perPage >= 5 && $perPage <= 100 ? $perPage : 15;
            $total = count($rows);
            $lastPage = (int) max(1, ceil($total / $perPage));
            $page = (int) $request->input('page', 1);
            $page = max(1, min($page, $lastPage));
            $rows = array_slice($rows, ($page - 1) * $perPage, $perPage);

            return response()->json([
                'success' => true,
                'rows' => array_values($rows),
                'totals' => $totals,
                'current_page' => $page,
                'last_page' => $lastPage,
                'total' => $total,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Export Daily Sales Report to Excel
     */
    public function exportDailySalesExcel(Request $request)
    {
        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            if (!$fromDate || !$toDate) {
                $fromDate = $fromDate ?: now()->format('Y-m-d');
                $toDate = $toDate ?: now()->format('Y-m-d');
            }
            if (strtotime($fromDate) > strtotime($toDate)) {
                return redirect()->back()->with('error', 'From date must be before or equal to to date');
            }

            $start = Carbon::parse($fromDate)->startOfDay();
            $end = Carbon::parse($toDate)->endOfDay();
            $rows = [];
            $totals = [
                'so_orders' => 0, 'so_items' => 0, 'so_sales' => 0, 'so_profit' => 0,
                'pos_orders' => 0, 'pos_items' => 0, 'pos_sales' => 0, 'pos_profit' => 0,
                'settlement_items' => 0, 'settlement_sales' => 0, 'settlement_profit' => 0,
                'total_items' => 0, 'total_sales' => 0, 'total_profit' => 0,
            ];

            $soQuery = SpecialOrder::with(['items.stock'])->where('status', 'delivered')->whereBetween('created_at', [$start, $end])->get();
            $soByDate = [];
            foreach ($soQuery as $o) {
                $d = $o->created_at ? $o->created_at->format('Y-m-d') : '';
                if (!isset($soByDate[$d])) $soByDate[$d] = ['orders' => 0, 'items' => 0, 'sales' => 0.0, 'profit' => 0.0];
                $soByDate[$d]['orders'] += 1;
                $soByDate[$d]['items'] += (int)($o->items ? $o->items->sum('quantity') : 0);
                $soByDate[$d]['sales'] += (float)($o->total_amount ?? 0);

                $orderProfit = 0.0;
                foreach ($o->items ?? [] as $item) {
                    $price = (float)($item->price ?? 0);
                    $qty = (int)($item->quantity ?? 0);
                    if ($qty <= 0) continue;
                    $cost = 0.0;
                    $tailor = 0.0;
                    if ($item->stock) {
                        $cost = (float)($item->stock->cost_price ?? 0);
                        $tailor = (float)($item->stock->tailor_charges ?? 0);
                    }
                    $orderProfit += ($price - $cost - $tailor) * $qty;
                }
                $soByDate[$d]['profit'] += $orderProfit;
            }
            $posQuery = PosOrders::with('details')->whereBetween('created_at', [$start, $end])->get();
            $posByDate = [];
            foreach ($posQuery as $o) {
                $d = $o->created_at ? $o->created_at->format('Y-m-d') : '';
                if (!isset($posByDate[$d])) $posByDate[$d] = ['orders' => 0, 'items' => 0, 'sales' => 0.0, 'profit' => 0.0];
                $posByDate[$d]['orders'] += 1;
                $posByDate[$d]['items'] += (int)($o->details ? $o->details->sum('item_quantity') : 0);
                $posByDate[$d]['sales'] += (float)($o->total_amount ?? 0);
                $posByDate[$d]['profit'] += (float)($o->details ? $o->details->sum('item_profit') : 0);
            }
            $setQuery = Settlement::whereBetween('created_at', [$start, $end])->get();
            $setByDate = [];
            foreach ($setQuery as $s) {
                $d = $s->created_at ? $s->created_at->format('Y-m-d') : '';
                if (!isset($setByDate[$d])) $setByDate[$d] = ['items' => 0, 'sales' => 0.0, 'profit' => 0.0];

                $items = $s->items_data;
                if (is_string($items)) {
                    $items = json_decode($items, true);
                    // Handle double-encoded JSON (string inside JSON)
                    if (is_string($items)) {
                        $items = json_decode($items, true);
                    }
                }
                $items = is_array($items) ? $items : [];
                foreach ($items as $it) {
                    $sold = (int)($it['sold'] ?? 0);
                    if ($sold <= 0) continue;
                    $setByDate[$d]['items'] += $sold;
                    $code = trim((string)($it['code'] ?? ''));
                    if (empty($code)) continue;
                    $stock = Stock::where('abaya_code', $code)
                        ->orWhere('barcode', $code)
                        ->first();
                    if ($stock) {
                        $sales = (float)($stock->sales_price ?? 0);
                        $cost = (float)($stock->cost_price ?? 0);
                        $tailor = (float)($stock->tailor_charges ?? 0);
                        $setByDate[$d]['profit'] += ($sales - $cost - $tailor) * $sold;
                    }
                }
                $setByDate[$d]['sales'] += (float)($s->total_sales ?? 0);
            }

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dateStr = $d->format('Y-m-d');
                $so = $soByDate[$dateStr] ?? ['orders' => 0, 'items' => 0, 'sales' => 0.0, 'profit' => 0.0];
                $pos = $posByDate[$dateStr] ?? ['orders' => 0, 'items' => 0, 'sales' => 0.0, 'profit' => 0.0];
                $set = $setByDate[$dateStr] ?? ['items' => 0, 'sales' => 0.0, 'profit' => 0.0];
                $soOrders = (int)$so['orders']; $soItems = (int)$so['items']; $soSales = (float)$so['sales']; $soProfit = (float)$so['profit'];
                $posOrders = (int)$pos['orders']; $posItems = (int)$pos['items']; $posSales = (float)$pos['sales']; $posProfit = (float)$pos['profit'];
                $setItems = (int)$set['items']; $setSales = (float)$set['sales']; $setProfit = (float)$set['profit'];
                $totalItems = $soItems + $posItems + $setItems;
                $totalSales = $soSales + $posSales + $setSales;
                $totalProfit = $soProfit + $posProfit + $setProfit;
                $rows[] = [
                    'date' => $dateStr,
                    'so_orders' => $soOrders, 'so_items' => $soItems, 'so_sales' => $soSales, 'so_profit' => $soProfit,
                    'pos_orders' => $posOrders, 'pos_items' => $posItems, 'pos_sales' => $posSales, 'pos_profit' => $posProfit,
                    'settlement_items' => $setItems, 'settlement_sales' => $setSales, 'settlement_profit' => $setProfit,
                    'total_items' => $totalItems, 'total_sales' => $totalSales, 'total_profit' => $totalProfit
                ];
                $totals['so_orders'] += $soOrders; $totals['so_items'] += $soItems; $totals['so_sales'] += $soSales; $totals['so_profit'] += $soProfit;
                $totals['pos_orders'] += $posOrders; $totals['pos_items'] += $posItems; $totals['pos_sales'] += $posSales; $totals['pos_profit'] += $posProfit;
                $totals['settlement_items'] += $setItems; $totals['settlement_sales'] += $setSales; $totals['settlement_profit'] += $setProfit;
                $totals['total_items'] += $totalItems; $totals['total_sales'] += $totalSales; $totals['total_profit'] += $totalProfit;
            }

            $locale = session('locale', 'en');
            $dateRangeText = ($fromDate && $toDate) ? ($locale === 'ar' ? 'من ' . $fromDate . ' إلى ' . $toDate : 'From ' . $fromDate . ' To ' . $toDate) : ($locale === 'ar' ? 'جميع التواريخ' : 'All Dates');
            $reportTitle = $locale === 'ar' ? 'تقرير المبيعات اليومية' : 'Daily Sales Report';

            if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Daily Sales');

                $sheet->setCellValue('A1', $reportTitle);
                $sheet->mergeCells('A1:O1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(32);

                $sheet->setCellValue('A2', $dateRangeText);
                $sheet->mergeCells('A2:O2');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '5B9BD5']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);

                $headers = $locale === 'ar'
                    ? ['التاريخ', 'طلبات', 'قطع', 'مبيعات', 'ربح', 'طلبات', 'قطع', 'مبيعات', 'ربح', 'قطع', 'مبيعات', 'ربح', 'قطع', 'مبيعات', 'ربح']
                    : ['Date', 'SO Ord', 'SO Itm', 'SO Sales', 'SO Profit', 'POS Ord', 'POS Itm', 'POS Sales', 'POS Profit', 'Set Itm', 'Set Sales', 'Set Profit', 'Total Itm', 'Total Sales', 'Total Profit'];
                $col = 'A';
                foreach ($headers as $h) {
                    $sheet->setCellValue($col . '3', $h);
                    $col++;
                }
                $sheet->getStyle('A3:O3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(24);

                $ri = 4;
                foreach ($rows as $r) {
                    $sheet->setCellValue('A' . $ri, $r['date']);
                    $sheet->setCellValue('B' . $ri, $r['so_orders']);
                    $sheet->setCellValue('C' . $ri, $r['so_items']);
                    $sheet->setCellValue('D' . $ri, round($r['so_sales'], 3));
                    $sheet->setCellValue('E' . $ri, round($r['so_profit'], 3));
                    $sheet->setCellValue('F' . $ri, $r['pos_orders']);
                    $sheet->setCellValue('G' . $ri, $r['pos_items']);
                    $sheet->setCellValue('H' . $ri, round($r['pos_sales'], 3));
                    $sheet->setCellValue('I' . $ri, round($r['pos_profit'], 3));
                    $sheet->setCellValue('J' . $ri, $r['settlement_items']);
                    $sheet->setCellValue('K' . $ri, round($r['settlement_sales'], 3));
                    $sheet->setCellValue('L' . $ri, round($r['settlement_profit'], 3));
                    $sheet->setCellValue('M' . $ri, $r['total_items']);
                    $sheet->setCellValue('N' . $ri, round($r['total_sales'], 3));
                    $sheet->setCellValue('O' . $ri, round($r['total_profit'], 3));

                    $sheet->getStyle('D' . $ri . ',E' . $ri . ',H' . $ri . ',I' . $ri . ',K' . $ri . ',L' . $ri . ',N' . $ri . ',O' . $ri)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.000');
                    if ($ri % 2 == 0) {
                        $sheet->getStyle('A' . $ri . ':O' . $ri)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8F9FA');
                    }
                    $ri++;
                }

                $sheet->setCellValue('A' . $ri, $locale === 'ar' ? 'الإجمالي' : 'Total');
                $sheet->setCellValue('B' . $ri, $totals['so_orders']);
                $sheet->setCellValue('C' . $ri, $totals['so_items']);
                $sheet->setCellValue('D' . $ri, round($totals['so_sales'], 3));
                $sheet->setCellValue('E' . $ri, round($totals['so_profit'], 3));
                $sheet->setCellValue('F' . $ri, $totals['pos_orders']);
                $sheet->setCellValue('G' . $ri, $totals['pos_items']);
                $sheet->setCellValue('H' . $ri, round($totals['pos_sales'], 3));
                $sheet->setCellValue('I' . $ri, round($totals['pos_profit'], 3));
                $sheet->setCellValue('J' . $ri, $totals['settlement_items']);
                $sheet->setCellValue('K' . $ri, round($totals['settlement_sales'], 3));
                $sheet->setCellValue('L' . $ri, round($totals['settlement_profit'], 3));
                $sheet->setCellValue('M' . $ri, $totals['total_items']);
                $sheet->setCellValue('N' . $ri, round($totals['total_sales'], 3));
                $sheet->setCellValue('O' . $ri, round($totals['total_profit'], 3));
                $sheet->getStyle('A' . $ri . ':O' . $ri)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F4FD']],
                ]);
                $sheet->getStyle('D' . $ri . ',E' . $ri . ',H' . $ri . ',I' . $ri . ',K' . $ri . ',L' . $ri . ',N' . $ri . ',O' . $ri)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.000');

                foreach (range('A', 'O') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
                $sheet->getStyle('A1:O' . $ri)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $filename = 'daily_sales_report_' . $fromDate . '_' . $toDate . '_' . date('Y-m-d_His') . '.xlsx';
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                $writer->save('php://output');
                exit;
            }

            // Fallback: CSV
            $filename = 'daily_sales_report_' . $fromDate . '_' . $toDate . '_' . date('Y-m-d_His') . '.csv';
            $out = fopen('php://output', 'w');
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            fputcsv($out, $locale === 'ar'
                ? ['التاريخ','طلبات SO','قطع SO','مبيعات SO','ربح SO','طلبات POS','قطع POS','مبيعات POS','ربح POS','قطع تسوية','مبيعات تسوية','ربح تسوية','إجمالي قطع','إجمالي مبيعات','إجمالي ربح']
                : ['Date','SO Orders','SO Items','SO Sales','SO Profit','POS Orders','POS Items','POS Sales','POS Profit','Settlement Items','Settlement Sales','Settlement Profit','Total Items','Total Sales','Total Profit']
            );
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['date'],
                    $r['so_orders'], $r['so_items'], round($r['so_sales'], 3), round($r['so_profit'], 3),
                    $r['pos_orders'], $r['pos_items'], round($r['pos_sales'], 3), round($r['pos_profit'], 3),
                    $r['settlement_items'], round($r['settlement_sales'], 3), round($r['settlement_profit'], 3),
                    $r['total_items'], round($r['total_sales'], 3), round($r['total_profit'], 3),
                ]);
            }
            fputcsv($out, [
                $locale === 'ar' ? 'الإجمالي' : 'Total',
                $totals['so_orders'], $totals['so_items'], round($totals['so_sales'], 3), round($totals['so_profit'], 3),
                $totals['pos_orders'], $totals['pos_items'], round($totals['pos_sales'], 3), round($totals['pos_profit'], 3),
                $totals['settlement_items'], round($totals['settlement_sales'], 3), round($totals['settlement_profit'], 3),
                $totals['total_items'], round($totals['total_sales'], 3), round($totals['total_profit'], 3),
            ]);
            fclose($out);
            exit;
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
}
