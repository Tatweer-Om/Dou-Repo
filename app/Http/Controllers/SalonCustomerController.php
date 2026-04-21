<?php

namespace App\Http\Controllers;

use App\Models\SalonCustomer;
use App\Models\SaloonBooking;
use App\Models\SaloonBookingPayment;
use App\Support\SalonPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalonCustomerController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!SalonPermission::userHasAny($permissions, [SalonPermission::CUSTOMER])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $customer = SalonCustomer::latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('saloon.customer', compact('customer'))->render();
        }

        return view('saloon.customer', compact('customer'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'phone'      => 'required', 
            'notes'    => 'nullable|string',
         ]);

        $userId = Auth::id();
        $user = Auth::user();
        $userName = $user->user_name ?? 'system';

        

        $customer = SalonCustomer::create([
            'name'        => $request->name,
            'phone'       => $request->phone, 
            'notes'     => $request->notes,
            'added_by'    => $userName,
            'user_id'     => $userId,
        ]);

        return response()->json([
            'success'  => true,
            'customer_id' => $customer->id,
            'message'  => 'customer added successfully',
        ]);
    }

    public function show(SalonCustomer $saloncustomer)
    {
        return response()->json($saloncustomer);
    }

    public function profile(Request $request, SalonCustomer $saloncustomer)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::CUSTOMER])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $activeTab = (string) $request->query('tab', 'bookings');
        if (!in_array($activeTab, ['bookings', 'payments'], true)) {
            $activeTab = 'bookings';
        }

        $bookingsQuery = SaloonBooking::query()
            ->with(['salonTeam', 'detail', 'payments.account'])
            ->where('customer_id', $saloncustomer->id)
            ->orderByDesc('booking_date')
            ->orderByDesc('id');

        $bookings = (clone $bookingsQuery)
            ->paginate(10, ['*'], 'bookings_page')
            ->appends($request->except('bookings_page'));

        $bookingIds = (clone $bookingsQuery)->pluck('id');
        $paymentsQuery = SaloonBookingPayment::query()
            ->with(['booking', 'account'])
            ->when($bookingIds->isNotEmpty(), function ($q) use ($bookingIds) {
                $q->whereIn('saloon_booking_id', $bookingIds->all());
            }, function ($q) {
                $q->whereRaw('1 = 0');
            })
            ->orderByDesc('payment_at')
            ->orderByDesc('id');

        $payments = (clone $paymentsQuery)
            ->paginate(10, ['*'], 'payments_page')
            ->appends($request->except('payments_page'));

        $totals = [
            'bookings' => (clone $bookingsQuery)->count(),
            'paid' => round((float) (clone $bookingsQuery)->sum('total_paid'), 3),
            'remaining' => round((float) (clone $bookingsQuery)->sum('total_remaining'), 3),
        ];
        $staffById = SaloonBooking::staffByIdMapForBookings($bookings);

        return view('saloon.customer_profile', [
            'customer' => $saloncustomer,
            'bookings' => $bookings,
            'payments' => $payments,
            'totals' => $totals,
            'activeTab' => $activeTab,
            'staffById' => $staffById,
        ]);
    }

    public function update(Request $request, SalonCustomer $saloncustomer)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'phone'      => 'required', 
            'notes'    => 'nullable|string',
         ]);

        $user = Auth::user();
        $userName = $user->user_name ?? 'system';

        

        $saloncustomer->name = $request->name;
        $saloncustomer->phone = $request->phone; 
        $saloncustomer->notes = $request->notes;
        $saloncustomer->updated_by = $userName;
        $saloncustomer->save();

        return response()->json([
            'success' => true,
            'customer'   => $saloncustomer,
            'message' => 'customer updated successfully',
        ]);
    }

    public function destroy(SalonCustomer $saloncustomer)
    {
         

        $saloncustomer->delete();

        return response()->json([
            'success' => true,
            'message' => 'customer deleted successfully',
        ]);
    }
}