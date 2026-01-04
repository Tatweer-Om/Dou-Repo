<?php

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use Carbon\Carbon;
use App\Models\SMS;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\SessionData;
use App\Models\Staff;
use App\Models\PosOrders;
use App\Models\PosOrdersDetail;
use App\Models\SpecialOrder;
use App\Models\SpecialOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;



// app/helpers.php
function genUuid()
{
    return sprintf(
        '%04x%04x%04x%04x%04x%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),
        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,
        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,
        // 48 bits for "node"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

function get_sms($params)
{

    // Default variables
  $customer_name = "";
$pos_order_no = "";
$special_order_no = "";
$customer_phone_number = "";
$abaya_name = "";
$abaya_code = "";
$abaya_category = "";
$color = "";
$size = "";
$abaya_length = "";
$bust = "";
$sleeves_length = "";
$buttons = "";
$special_order_number = "";
$quantity = "";
$total_amount = "";
    $remaining_amount = "";
$paid_amount = "";
$discount = "";
$delivery_charges = "";
$tailor_name = "";
$delivery_date = "";
$pos_order_status = "";
$special_order_status = "";


    // Template fetch
    $sms_text = SMS::where('sms_status', $params['sms_status'])->first();

    // If no template found, return empty string
    if (!$sms_text || !$sms_text->sms) {
        return '';
    }

    // Case: POS Order (sms_status == 1)
    if ($params['sms_status'] == 1) {
        $orderId = $params['order_id'] ?? $params['patient_id'] ?? null;
        
        if ($orderId) {
            // Fetch the POS order with all relationships
            $order = PosOrders::with([
                'customer',
                'details.stock.category',
                'details.stock',
                'details.color',
                'details.size'
            ])->find($orderId);
            
            if ($order) {
                // Customer details
                $customer_name = $order->customer ? ($order->customer->name ?? '') : '';
                $customer_phone_number = $order->customer ? ($order->customer->phone ?? '') : '';
                
                // Order details
                $pos_order_no = $order->order_no ?? '';
                $total_amount = number_format($order->total_amount ?? 0, 3);
                $discount = number_format($order->total_discount ?? 0, 3);
                $paid_amount = number_format($order->paid_amount ?? 0, 3);
                $remaining_amount = number_format(($order->total_amount ?? 0) - ($order->paid_amount ?? 0), 3);
                $delivery_charges = number_format($order->delivery_charges ?? 0, 3);
                
                // Get order status
                $pos_order_status = '';
                if ($order->delivery_status) {
                    $pos_order_status = $order->delivery_status;
                } elseif ($order->return_status) {
                    $pos_order_status = 'Returned';
                } else {
                    $pos_order_status = 'Pending';
                }
                
                // Get first item details (or combine if multiple items)
                $firstDetail = $order->details->first();
                if ($firstDetail) {
                    $stock = $firstDetail->stock;
                    if ($stock) {
                        $abaya_name = $stock->design_name ?? $stock->abaya_code ?? '';
                        $abaya_code = $stock->abaya_code ?? '';
                        
                        // Get category name based on locale
                        $locale = session('locale', 'en');
                        if ($stock->category) {
                            if ($locale === 'ar') {
                                $abaya_category = $stock->category->category_name_ar ?? $stock->category->category_name ?? '';
                            } else {
                                $abaya_category = $stock->category->category_name ?? $stock->category->category_name_ar ?? '';
                            }
                        } else {
                            $abaya_category = '';
                        }
                    }
                    
                    // Get color and size from first detail
                    $locale = session('locale', 'en');
                    if ($firstDetail->color) {
                        if ($locale === 'ar') {
                            $color = $firstDetail->color->color_name_ar ?? $firstDetail->color->color_name_en ?? '';
                        } else {
                            $color = $firstDetail->color->color_name_en ?? $firstDetail->color->color_name_ar ?? '';
                        }
                    } else {
                        $color = '';
                    }
                    
                    if ($firstDetail->size) {
                        if ($locale === 'ar') {
                            $size = $firstDetail->size->size_name_ar ?? $firstDetail->size->size_name_en ?? '';
                        } else {
                            $size = $firstDetail->size->size_name_en ?? $firstDetail->size->size_name_ar ?? '';
                        }
                    } else {
                        $size = '';
                    }
                    
                    // Calculate total quantity from all order details
                    $quantity = $order->details->sum('item_quantity') ?? 1;
                } else {
                    $abaya_name = '';
                    $abaya_code = '';
                    $abaya_category = '';
                    $color = '';
                    $size = '';
                    $quantity = 1;
                }
            }
        }
    }

    // Case: Special Order (sms_status == 2)
    else if ($params['sms_status'] == 2) {
        // Get special_order_id from params
        $specialOrderId = $params['special_order_id'] ?? null;
        
        if ($specialOrderId) {
            // Fetch the Special Order with all relationships
            $specialOrder = SpecialOrder::with([
                'customer.area',
                'customer.city',
                'items.stock.category',
                'items.stock',
                'items.tailor'
            ])->find($specialOrderId);
            
            // Refresh items relationship to ensure data is loaded
            if ($specialOrder) {
                $specialOrder->load('items');
            }
            
            if ($specialOrder) {
                // Customer details
                $customer_name = $specialOrder->customer ? ($specialOrder->customer->name ?? '') : '';
                $customer_phone_number = $specialOrder->customer ? ($specialOrder->customer->phone ?? '') : '';
                
                // Generate order number: YYYY-00ID (e.g., 2025-0001)
                $orderDate = \Carbon\Carbon::parse($specialOrder->created_at);
                $special_order_no = $orderDate->format('Y') . '-' . str_pad($specialOrder->id, 4, '0', STR_PAD_LEFT);
                $special_order_number = $special_order_no; // Alias
                
                // Order details
                $totalAmountValue = (float)($specialOrder->total_amount ?? 0);
                $paidAmountValue = (float)($specialOrder->paid_amount ?? 0);
                $remainingAmountValue = $totalAmountValue - $paidAmountValue;
                
                $total_amount = number_format($totalAmountValue, 3);
                $paid_amount = number_format($paidAmountValue, 3);
                $remaining_amount = number_format($remainingAmountValue, 3);
                $delivery_charges = number_format($specialOrder->shipping_fee ?? 0, 3);
                $special_order_status = $specialOrder->status ?? 'new';
                
                // Calculate delivery date: 2 weeks after order creation date
                $delivery_date = $orderDate->copy()->addWeeks(2)->format('Y-m-d');
                
                // Get first item details (or combine if multiple items)
                $firstItem = $specialOrder->items->first();
                if ($firstItem) {
                    $stock = $firstItem->stock;
                    if ($stock) {
                        $abaya_name = $firstItem->design_name ?? $stock->design_name ?? $stock->abaya_code ?? '';
                        $abaya_code = $firstItem->abaya_code ?? $stock->abaya_code ?? '';
                        
                        // Get category name based on locale
                        $locale = session('locale', 'en');
                        if ($stock->category) {
                            if ($locale === 'ar') {
                                $abaya_category = $stock->category->category_name_ar ?? $stock->category->category_name ?? '';
                            } else {
                                $abaya_category = $stock->category->category_name ?? $stock->category->category_name_ar ?? '';
                            }
                        } else {
                            $abaya_category = '';
                        }
                    } else {
                        $abaya_name = $firstItem->design_name ?? $firstItem->abaya_code ?? '';
                        $abaya_code = $firstItem->abaya_code ?? '';
                        $abaya_category = '';
                    }
                    
                    // Get abaya measurements (check if value exists and is numeric, including 0)
                    $abayaLengthValue = $firstItem->abaya_length;
                    if ($abayaLengthValue !== null && $abayaLengthValue !== '' && is_numeric($abayaLengthValue)) {
                        $abaya_length = number_format((float)$abayaLengthValue, 2);
                    } else {
                        $abaya_length = '';
                    }
                    
                    $bustValue = $firstItem->bust;
                    if ($bustValue !== null && $bustValue !== '' && is_numeric($bustValue)) {
                        $bust = number_format((float)$bustValue, 2);
                    } else {
                        $bust = '';
                    }
                    
                    $sleevesLengthValue = $firstItem->sleeves_length;
                    if ($sleevesLengthValue !== null && $sleevesLengthValue !== '' && is_numeric($sleevesLengthValue)) {
                        $sleeves_length = number_format((float)$sleevesLengthValue, 2);
                    } else {
                        $sleeves_length = '';
                    }
                    
                    $buttons = $firstItem->buttons ? (session('locale', 'en') === 'ar' ? 'نعم' : 'Yes') : (session('locale', 'en') === 'ar' ? 'لا' : 'No');
                    
                    // Get tailor name
                    if ($firstItem->tailor) {
                        $tailor_name = $firstItem->tailor->tailor_name ?? '';
                    } elseif ($specialOrder->notes) {
                        $tailor_name = $specialOrder->notes;
                    } else {
                        $tailor_name = '';
                    }
                    
                    // Calculate total quantity from all items
                    $quantity = $specialOrder->items->sum('quantity') ?? 1;
                    
                    // Note: Special orders use custom measurements (abaya_length, bust, sleeves_length)
                    // instead of standard sizes, so $size remains empty for special orders
                    $size = '';
                } else {
                    $abaya_name = '';
                    $abaya_code = '';
                    $abaya_category = '';
                    $abaya_length = '';
                    $bust = '';
                    $sleeves_length = '';
                    $buttons = '';
                    $tailor_name = '';
                    $size = '';
                    $quantity = 1;
                    // Ensure payment values are set even if no items
                    $total_amount = number_format($specialOrder->total_amount ?? 0, 3);
                    $paid_amount = number_format($specialOrder->paid_amount ?? 0, 3);
                    $remaining_amount = number_format(($specialOrder->total_amount ?? 0) - ($specialOrder->paid_amount ?? 0), 3);
                    $delivery_date = $orderDate->copy()->addWeeks(2)->format('Y-m-d');
                }
            }
        }
    }

    // Case: Session done
    else if ($params['sms_status'] == 5) {
       
    }


    // Define template replacement variables
    $variables = [
    'customer_name'        => $customer_name,
    'pos_order_no'         => $pos_order_no,
    'special_order_no'     => $special_order_no,
    'customer_phone_number'=> $customer_phone_number,
    'abaya_name'           => $abaya_name,
    'abaya_code'           => $abaya_code,
    'abaya_category'       => $abaya_category,
    'color'                => $color,
    'size'                 => $size,
    'abaya_length'         => $abaya_length,
    'bust'                 => $bust,
    'sleeves_length'       => $sleeves_length,
    'buttons'              => $buttons,
    'special_order_number' => $special_order_number,
    'quantity'             => $quantity,
    'total_amount'         => $total_amount,
    'remaining_amount'     => $remaining_amount,
    'paid_amount'          => $paid_amount,
    'discount'             => $discount,
    'delivery_charges'     => $delivery_charges,
    'tailor_name'          => $tailor_name,
    'delivery_date'        => $delivery_date,
    'pos_order_status'     => $pos_order_status,
    'special_order_status' => $special_order_status,
];


    // Replace placeholders in base64 decoded template
    $string = base64_decode($sms_text->sms);
    foreach ($variables as $key => $value) {
        $string = str_replace('{' . $key . '}', $value, $string);
    }

    return $string;
}



function sms_module($contact, $sms)
{
    if (!empty($contact)) {
        $url = "http://myapp3.com/whatsapp_admin_latest/Api_pos/send_request";

        $form_data = [
            'status' => 1,
            'sender_contact' => $contact,
            'customer_id' => 'tatweeersoftweb',
            'instance_id' => '1xwaxr8k',
            'sms' => base64_encode($sms),
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $form_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($resp, true);
    }
}
