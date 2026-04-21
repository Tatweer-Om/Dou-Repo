<?php

namespace App\Http\Controllers;

use App\Models\SalonStaff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(1, $permissions)) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $staffForUserForm = SalonStaff::query()
            ->with('salonTeam')
            ->orderBy('name')
            ->get(['id', 'name', 'team_id', 'user_id']);

        return view('users.user', compact('staffForUserForm'));
    }

    public function getusers()
    {
        return User::query()
            ->with(['salonStaff:id,name'])
            ->orderByDesc('id')
            ->paginate(10);
    }

    /**
     * Salon staff rows for the user form dropdown (all active records).
     * Must use the same permission rule as index() — permissions may be stored as int or string in JSON.
     */
    public function staffOptionsForUsers()
    {
        if (!Auth::check()) {
            abort(401);
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!in_array(1, $permissions)) {
            abort(403);
        }

        $loc = session('locale', 'en');
        $list = SalonStaff::query()
            ->with('salonTeam')
            ->orderBy('name')
            ->get();

        return response()->json($list->map(static function (SalonStaff $s) use ($loc) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'user_id' => $s->user_id,
                'team_label' => $s->salonTeam?->displayName($loc) ?? '',
            ];
        })->values());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'user_phone' => 'required|string|max:50',
            'user_email' => 'nullable|email|max:255',
            'user_password' => 'required|string|min:4',
            'notes' => 'nullable|string',
            'user_scope' => ['nullable', Rule::in(['boutique', 'saloon'])],
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer',
            'user_type' => ['required', Rule::in(['admin', 'user'])],
            'salon_staff_id' => [
                'nullable',
                'integer',
                'exists:salon_staff,id',
                Rule::requiredIf(fn () => $request->input('user_type') === 'user'),
            ],
        ]);

        $user = new User;
        $user->user_name = $validated['user_name'];
        $user->user_phone = $validated['user_phone'];
        $user->user_email = $validated['user_email'] ?? '';
        $user->password = Hash::make($validated['user_password']);
        $user->notes = $validated['notes'] ?? null;
        $user->user_scope = $validated['user_scope'] ?? 'boutique';
        $user->user_type = $validated['user_type'];
        $permissions = $validated['permissions'] ?? [];
        $user->permissions = array_map('intval', $permissions);
        $user->added_by = 'system';
        $user->user_id = (string) (auth()->id() ?? 1);
        $user->salon_staff_id = null;

        DB::transaction(function () use ($user, $validated) {
            $user->save();
            $this->syncUserStaffAssignment($user, $validated['user_type'], $validated['salon_staff_id'] ?? null);
        });

        $user->load('salonStaff:id,name');

        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'user_name' => 'required|string|max:255',
            'user_phone' => 'required|string|max:50',
            'user_email' => 'nullable|email|max:255',
            'user_password' => 'nullable|string|min:4',
            'notes' => 'nullable|string',
            'user_scope' => ['nullable', Rule::in(['boutique', 'saloon'])],
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer',
            'user_type' => ['required', Rule::in(['admin', 'user'])],
            'salon_staff_id' => [
                'nullable',
                'integer',
                'exists:salon_staff,id',
                Rule::requiredIf(fn () => $request->input('user_type') === 'user'),
            ],
        ]);

        $user->user_name = $validated['user_name'];
        $user->user_phone = $validated['user_phone'];
        $user->user_email = $validated['user_email'] ?? '';
        $user->notes = $validated['notes'] ?? null;
        $user->user_scope = $validated['user_scope'] ?? 'boutique';
        $user->user_type = $validated['user_type'];
        $permissions = $validated['permissions'] ?? [];
        $user->permissions = array_map('intval', $permissions);
        $user->updated_by = auth()->user()->user_name ?? 'system_update';

        if (!empty($validated['user_password'])) {
            $user->password = Hash::make($validated['user_password']);
        }

        DB::transaction(function () use ($user, $validated) {
            $user->save();
            $this->syncUserStaffAssignment($user, $validated['user_type'], $validated['salon_staff_id'] ?? null);
        });

        $user->load('salonStaff:id,name');

        return response()->json($user);
    }

    /**
     * Keeps users.salon_staff_id and salon_staff.user_id in sync (1:1). Admins have no staff link.
     */
    protected function syncUserStaffAssignment(User $user, string $userType, mixed $salonStaffIdInput): void
    {
        $staffId = $salonStaffIdInput !== null && $salonStaffIdInput !== ''
            ? (int) $salonStaffIdInput
            : null;

        SalonStaff::query()->where('user_id', $user->id)->update(['user_id' => null]);

        if ($userType === 'admin' || !$staffId) {
            User::query()->whereKey($user->id)->update(['salon_staff_id' => null]);
            $user->salon_staff_id = null;

            return;
        }

        User::query()
            ->where('salon_staff_id', $staffId)
            ->where('id', '!=', $user->id)
            ->update(['salon_staff_id' => null]);

        $staff = SalonStaff::query()->lockForUpdate()->find($staffId);
        if (!$staff) {
            User::query()->whereKey($user->id)->update(['salon_staff_id' => null]);
            $user->salon_staff_id = null;

            return;
        }

        if ($staff->user_id && (int) $staff->user_id !== (int) $user->id) {
            User::query()->whereKey($staff->user_id)->update(['salon_staff_id' => null]);
        }

        $staff->user_id = $user->id;
        $staff->save();

        User::query()->whereKey($user->id)->update(['salon_staff_id' => $staffId]);
        $user->salon_staff_id = $staffId;
    }

    public function show(User $user)
    {
        $user->load('salonStaff:id,name');

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        DB::transaction(function () use ($user) {
            SalonStaff::query()->where('user_id', $user->id)->update(['user_id' => null]);
            $user->delete();
        });

        return response()->json(['message' => 'Deleted']);
    }

    public function login_page(Request $request)
    {
        return view('login.login_page');
    }

    public function login_user(Request $request)
    {
        $request->validate([
            'user_phone' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('user_name', $request->user_phone)
            ->orWhere('user_phone', $request->user_phone)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'اسم المستخدم أو رقم الهاتف غير صحيح',
            ]);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'كلمة المرور غير صحيحة',
            ]);
        }

        auth()->login($user);

        session(['locale' => 'en']);

        $scope = $user->user_scope ?? 'boutique';
        $redirectUrl = $scope === 'saloon'
            ? route('saloon_dashboard')
            : route('dashboard');

        if ($scope === 'saloon' && !empty($user->salon_staff_id)) {
            $staffPk = (int) $user->salon_staff_id;
            if ($staffPk > 0 && SalonStaff::query()->whereKey($staffPk)->exists()) {
                $redirectUrl = route('salonstaff.profile', ['salonstaff' => $staffPk]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الدخول بنجاح',
            'redirect_url' => $redirectUrl,
        ]);
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login_page')->with('success', 'تم تسجيل الخروج بنجاح');
    }
}
