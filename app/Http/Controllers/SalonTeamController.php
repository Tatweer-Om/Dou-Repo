<?php

namespace App\Http\Controllers;

use App\Models\SalonTeam;
use App\Support\SalonPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SalonTeamController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];
        if (!SalonPermission::userHasAny($permissions, [SalonPermission::TEAM])) {
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        $teams = SalonTeam::query()->ordered()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('saloon.team', compact('teams'))->render();
        }

        return view('saloon.team', compact('teams'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/', 'unique:salon_teams,code'],
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $team = SalonTeam::create([
            'code' => strtolower($validated['code']),
            'name' => $validated['name'],
            'name_ar' => $validated['name_ar'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'team_id' => $team->id,
            'message' => 'Team created successfully.',
        ]);
    }

    public function show(SalonTeam $salonTeam)
    {
        return response()->json($salonTeam);
    }

    public function update(Request $request, SalonTeam $salonTeam)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('salon_teams', 'code')->ignore($salonTeam->id),
            ],
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $salonTeam->code = strtolower($validated['code']);
        $salonTeam->name = $validated['name'];
        $salonTeam->name_ar = $validated['name_ar'] ?? null;
        $salonTeam->sort_order = (int) ($validated['sort_order'] ?? 0);
        $salonTeam->is_active = $request->boolean('is_active', true);
        $salonTeam->save();

        return response()->json([
            'success' => true,
            'team' => $salonTeam,
            'message' => 'Team updated successfully.',
        ]);
    }

    public function destroy(SalonTeam $salonTeam)
    {
        if ($salonTeam->staff()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a team that has staff assigned.',
            ], 422);
        }
        if ($salonTeam->bookings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a team that has bookings.',
            ], 422);
        }

        $salonTeam->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team deleted successfully.',
        ]);
    }
}
