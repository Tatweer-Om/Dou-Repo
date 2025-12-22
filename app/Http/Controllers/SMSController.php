<?php

namespace App\Http\Controllers;

use App\Models\SMS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SMSController extends Controller
{
    public function index()
    {
        // Get all SMS messages ordered by status
        $smsMessages = SMS::orderBy('sms_status', 'ASC')->get();

        return view('modules.sms', compact('smsMessages'));
    }

    public function getSMS(Request $request)
    {
        $status = $request->input('status');

        $sms = SMS::where('sms_status', $status)
            ->first();

        if ($sms) {
            return response()->json([
                'success' => true,
                'sms' => base64_decode($sms->sms),
                'id' => $sms->id
            ]);
        }

        return response()->json([
            'success' => false,
            'sms' => ''
        ]);
    }

    public function store(Request $request)
    {
        $user_id = Auth::id();
        $user = Auth::user();
        $user_name = $user->user_name ?? 'system';

        $sms_status = $request->input('status');
        $sms_text = $request->input('sms');

        // Validation
        if (empty($sms_text)) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.enter_sms_text', [], session('locale'))
            ], 422);
        }

        if (empty($sms_status)) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.select_message_type', [], session('locale'))
            ], 422);
        }

        // Check if SMS with this status exists
        $check_status = SMS::where('sms_status', $sms_status)
            ->first();

        if ($check_status) {
            // Update existing
            $check_status->sms = base64_encode($sms_text);
            $check_status->sms_status = $sms_status;
            $check_status->updated_by = $user_name;
            $check_status->user_id = $user_id;
            $check_status->save();

            return response()->json([
                'success' => true,
                'message' => trans('messages.message_updated_successfuly_lang', [], session('locale')),
                'sms' => $check_status
            ]);
        } else {
            // Create new
            $sms_data = new SMS();
            $sms_data->sms = base64_encode($sms_text);
            $sms_data->sms_status = $sms_status;
            $sms_data->added_by = $user_name;
            $sms_data->user_id = $user_id;
            $sms_data->save();

            return response()->json([
                'success' => true,
                'message' => trans('messages.message_added_successfuly_lang', [], session('locale')),
                'sms' => $sms_data
            ]);
        }
    }
}

