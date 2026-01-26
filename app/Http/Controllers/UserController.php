<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Investor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;
use App\Notifications\UserCreatedNotification;

class UserController extends Controller
{
    /**
     * Display a listing of users (admin only).
     */
    public function index(Request $request)
    {
        return Inertia::render('Users/Index');
    }

    /**
     * Get paginated list of users.
     */
    public function list(Request $request)
    {
        $query = User::with('investor');

        // Search functionality
        if ($request->has('search') && $request->get('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->get('role')) {
            $query->where('role', $request->get('role'));
        }

        // Filter by status
        if ($request->has('status') && $request->get('status')) {
            $isActive = $request->get('status') === 'active';
            $query->where('is_active', $isActive);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'users' => [
                'data' => $users->items(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'filters' => $request->only(['search', 'role', 'status', 'page'])
        ]);
    }

    /**
     * Get farmers list by investor (admin only).
     */
    public function getFarmersByInvestor(Request $request)
    {
        $investorId = $request->get('investor_id');
        
        $query = User::where('role', 'farmer')
                     ->where('is_active', true);

        if ($investorId) {
            $query->where('investor_id', $investorId);
        }

        $farmers = $query->get();

        return response()->json($farmers);
    }

    /**
     * Create a new user (admin only).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'role' => 'required|in:farmer,investor,admin',
            // Required for investor role
            'address' => 'nullable|required_if:role,investor|string|max:255',
            // Required for farmer and investor roles
            'phone' => [
                'nullable',
                'required_if:role,farmer|required_if:role,investor',
                'string',
                'regex:/^(\+63|0)?9\d{9}$/',
            ],
            // Required for farmer role
            'investor_id' => 'nullable|required_if:role,farmer|exists:investors,id',
        ], [
            'phone.regex' => 'The phone number must be a valid Philippine mobile number (e.g., +639123456789, 09123456789, or 9123456789).',
        ]);

        // Generate a random secure password
        $generatedPassword = Str::password(12, true, true, false, false);

        $user = DB::transaction(function () use ($request, $generatedPassword) {
            // Normalize phone number format to +63XXXXXXXXXX (if phone is provided)
            $normalizedPhone = null;
            if ($request->has('phone') && $request->phone) {
                $phone = $request->phone;
                $phone = preg_replace('/\D/', '', $phone); // Remove non-digits
                if (strlen($phone) === 10) {
                    $phone = '63' . $phone; // 9XXXXXXXXX -> 639XXXXXXXXX
                } else if (strlen($phone) === 11 && $phone[0] === '0') {
                    $phone = '63' . substr($phone, 1); // 09XXXXXXXXX -> 639XXXXXXXXX
                }
                $normalizedPhone = '+' . $phone;
            }

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $normalizedPhone,
                'password' => Hash::make($generatedPassword),
                'role' => $request->role,
                'is_active' => true,
                'email_verified_at' => now(),
            ];

            // If creating an investor, create the Investor record first
            if ($request->role === 'investor') {
                $investor = Investor::create([
                    'name' => $request->name,
                    'address' => $request->address,
                    'phone' => $normalizedPhone,
                ]);
                $userData['investor_id'] = $investor->id;
            }

            // If creating a farmer, link to the specified investor
            if ($request->role === 'farmer') {
                $userData['investor_id'] = $request->investor_id;
            }

            return User::create($userData);
        });

        // Send email notification with the generated password
        $user->notify(new UserCreatedNotification($generatedPassword, $user->role));

        $user->load('investor');

        return response()->json([
            'message' => 'User created successfully. Login credentials have been sent to their email.',
            'user' => $user
        ]);
    }

    /**
     * Update user role (admin only).
     */
    public function updateRole(Request $request, User $user)
    {
        // Prevent admin from changing their own role
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot change your own role'
            ], 403);
        }

        $request->validate([
            'role' => 'required|in:farmer,investor,admin',
        ]);

        $user->update([
            'role' => $request->role
        ]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Toggle user active status (admin only).
     */
    public function toggleStatus(Request $request, User $user)
    {
        // Prevent admin from deactivating themselves
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot deactivate your own account'
            ], 403);
        }

        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "User {$status} successfully",
            'user' => $user
        ]);
    }

    /**
     * Update user details (admin only).
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only(['name', 'email']));

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete a user (admin only).
     */
    public function destroy(Request $request, User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        // Delete related data first
        $user->cages()->delete();
        
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get user statistics (admin only).
     */
    public function statistics()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();
        
        $usersByRole = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get()
            ->pluck('count', 'role');

        return response()->json([
            'statistics' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'farmers' => $usersByRole['farmer'] ?? 0,
                'investors' => $usersByRole['investor'] ?? 0,
                'admins' => $usersByRole['admin'] ?? 0,
            ]
        ]);
    }

    /**
     * Link investor user to investor record (admin only).
     * This fixes investor users that don't have investor_id set.
     */
    public function linkInvestor(Request $request, User $user)
    {
        if ($user->role !== 'investor') {
            return response()->json([
                'message' => 'User is not an investor'
            ], 422);
        }

        $request->validate([
            'investor_id' => 'required|exists:investors,id',
        ]);

        // Verify the investor name matches (optional check)
        $investor = Investor::find($request->investor_id);
        if ($investor && $investor->name !== $user->name) {
            // Allow but warn
            \Log::warning("Linking investor user {$user->name} (ID: {$user->id}) to investor {$investor->name} (ID: {$investor->id}) - names don't match");
        }

        $user->investor_id = $request->investor_id;
        $user->save();

        return response()->json([
            'message' => 'Investor user linked successfully',
            'user' => $user->load('investor')
        ]);
    }

    /**
     * Auto-fix investor users missing investor_id (admin only).
     * Attempts to match investor users to investor records by name.
     */
    public function fixInvestorLinks()
    {
        $investorUsers = User::where('role', 'investor')
            ->whereNull('investor_id')
            ->get();

        $fixed = 0;
        $notFound = [];

        foreach ($investorUsers as $user) {
            $investor = Investor::where('name', $user->name)->first();
            if ($investor) {
                $user->investor_id = $investor->id;
                $user->save();
                $fixed++;
            } else {
                $notFound[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                ];
            }
        }

        return response()->json([
            'message' => 'Investor link fix completed',
            'fixed' => $fixed,
            'not_found' => $notFound,
            'total_checked' => $investorUsers->count()
        ]);
    }
}
