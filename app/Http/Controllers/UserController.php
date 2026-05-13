<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Models\User;
use App\Notifications\UserCreatedNotification;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

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
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
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
            'filters' => $request->only(['search', 'role', 'status', 'page']),
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
        $emailIn = $request->input('email');
        $usernameIn = $request->input('username');
        $request->merge([
            'email' => ($emailIn !== null && trim((string) $emailIn) !== '') ? Str::lower(trim((string) $emailIn)) : null,
            'username' => ($usernameIn !== null && trim((string) $usernameIn) !== '') ? Str::lower(trim((string) $usernameIn)) : null,
        ]);

        $rules = [
            'name' => 'required|string|max:255',
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
        ];

        if ($request->role === 'farmer') {
            $rules['email'] = ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email', 'required_without:username'];
            $rules['username'] = ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:users,username', 'required_without:email'];
        } else {
            $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'];
            $rules['username'] = ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:users,username'];
        }

        $request->validate($rules, [
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
                    $phone = '63'.$phone; // 9XXXXXXXXX -> 639XXXXXXXXX
                } elseif (strlen($phone) === 11 && $phone[0] === '0') {
                    $phone = '63'.substr($phone, 1); // 09XXXXXXXXX -> 639XXXXXXXXX
                }
                $normalizedPhone = '+'.$phone;
            }

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'phone' => $normalizedPhone,
                'password' => Hash::make($generatedPassword),
                'role' => $request->role,
                'is_active' => true,
                'email_verified_at' => $request->email ? now() : null,
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

        if ($user->email) {
            $user->notify(new UserCreatedNotification($generatedPassword, $user->role));
        }

        $user->load('investor');

        $message = $user->email
            ? 'User created successfully. Login credentials have been sent to their email.'
            : 'User created successfully. Share their username and temporary password with them in person; no email was sent because no address was provided.';

        return response()->json([
            'message' => $message,
            'user' => $user,
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
                'message' => 'You cannot change your own role',
            ], 403);
        }

        $request->validate([
            'role' => 'required|in:farmer,investor,admin',
        ]);

        $user->update([
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => $user,
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
                'message' => 'You cannot deactivate your own account',
            ], 403);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "User {$status} successfully",
            'user' => $user,
        ]);
    }

    public function validateAccount(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Your own account is already validated',
            ], 403);
        }

        $user->update([
            'is_active' => true,
            'email_verified_at' => $user->email ? ($user->email_verified_at ?? now()) : null,
        ]);

        return response()->json([
            'message' => 'User account validated successfully',
            'user' => $user->load('investor'),
        ]);
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'User password updated successfully',
        ]);
    }

    /**
     * Update user details (admin only).
     */
    public function update(Request $request, User $user)
    {
        $emailIn = $request->input('email');
        $request->merge([
            'email' => ($emailIn !== null && trim((string) $emailIn) !== '') ? Str::lower(trim((string) $emailIn)) : null,
        ]);

        if ($user->role === 'farmer') {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'nullable',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                    function (string $attribute, mixed $value, \Closure $fail) use ($user) {
                        $emailEmpty = $value === null || $value === '';
                        if ($emailEmpty && empty($user->username)) {
                            $fail('Either an email or an existing username is required for this user.');
                        }
                    },
                ],
                'investor_id' => 'required|exists:investors,id',
                'is_active' => ['required', 'boolean'],
            ]);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'investor_id' => $request->investor_id,
                'is_active' => $request->boolean('is_active'),
            ]);
        } elseif ($user->role === 'investor') {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'investor_id' => 'required|exists:investors,id',
                'is_active' => ['required', 'boolean'],
            ]);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'investor_id' => $request->investor_id,
                'is_active' => $request->boolean('is_active'),
            ]);
        } else {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'is_active' => ['required', 'boolean'],
            ]);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'is_active' => $request->boolean('is_active'),
            ]);
        }

        $user->load('investor');

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
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
                'message' => 'You cannot delete your own account',
            ], 403);
        }

        // Delete related data first (samples must be deleted before samplings,
        // because samples.sampling_id lacks an ON DELETE CASCADE constraint)
        foreach ($user->cages as $cage) {
            foreach ($cage->samplings as $sampling) {
                $sampling->samples()->delete();
            }
            $cage->delete();
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
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
            ],
        ]);
    }

    /**
     * Download the current database as a SQL backup (admin only).
     */
    public function downloadDatabase()
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            return response()->json([
                'message' => "Database downloads are not supported for the {$driver} driver.",
            ], 422);
        }

        $databaseName = config("database.connections.{$connection->getName()}.database", config('app.name', 'database'));
        $safeDatabaseName = Str::slug((string) pathinfo((string) $databaseName, PATHINFO_FILENAME)) ?: 'database';
        $filename = $safeDatabaseName.'-backup-'.now()->format('Y-m-d-His').'.sql';

        return response()->streamDownload(function () use ($connection, $driver, $databaseName) {
            echo "-- SFM database backup\n";
            echo '-- Database: '.(string) $databaseName."\n";
            echo '-- Generated at: '.now()->toDateTimeString()."\n\n";

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
            }

            foreach ($this->databaseTables($connection, $driver) as $table) {
                $this->writeTableDump($connection, $driver, $table);
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                echo "SET FOREIGN_KEY_CHECKS=1;\n";
            }
        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    private function databaseTables(Connection $connection, string $driver): array
    {
        if ($driver === 'sqlite') {
            return collect($connection->select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
            ))->pluck('name')->all();
        }

        return collect($connection->select('SHOW FULL TABLES'))
            ->filter(function (object $row) {
                $values = array_values((array) $row);

                return ($values[1] ?? null) === 'BASE TABLE';
            })
            ->map(function (object $row) {
                return array_values((array) $row)[0];
            })
            ->all();
    }

    private function writeTableDump(Connection $connection, string $driver, string $table): void
    {
        $quotedTable = $this->quoteIdentifier($table, $driver);

        echo "-- --------------------------------------------------------\n";
        echo "-- Table structure for {$table}\n";
        echo "DROP TABLE IF EXISTS {$quotedTable};\n";
        echo $this->tableCreateStatement($connection, $driver, $table).";\n\n";

        $rows = $connection->table($table)->orderByRaw('1')->cursor();
        $hasRows = false;

        foreach ($rows as $row) {
            $hasRows = true;
            $data = (array) $row;
            $columns = collect(array_keys($data))
                ->map(fn (string $column) => $this->quoteIdentifier($column, $driver))
                ->implode(', ');
            $values = collect(array_values($data))
                ->map(fn (mixed $value) => $this->quoteValue($connection, $value))
                ->implode(', ');

            echo "INSERT INTO {$quotedTable} ({$columns}) VALUES ({$values});\n";
        }

        if ($hasRows) {
            echo "\n";
        }
    }

    private function tableCreateStatement(Connection $connection, string $driver, string $table): string
    {
        if ($driver === 'sqlite') {
            $result = $connection->selectOne(
                "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$table]
            );

            return $result->sql;
        }

        $result = $connection->selectOne('SHOW CREATE TABLE '.$this->quoteIdentifier($table, $driver));

        return (array_values((array) $result)[1] ?? '');
    }

    private function quoteIdentifier(string $identifier, string $driver): string
    {
        if ($driver === 'sqlite') {
            return '"'.str_replace('"', '""', $identifier).'"';
        }

        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function quoteValue(Connection $connection, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $connection->getPdo()->quote((string) $value);
    }

    /**
     * Link investor user to investor record (admin only).
     * This fixes investor users that don't have investor_id set.
     */
    public function linkInvestor(Request $request, User $user)
    {
        if ($user->role !== 'investor') {
            return response()->json([
                'message' => 'User is not an investor',
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
            'user' => $user->load('investor'),
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
            'total_checked' => $investorUsers->count(),
        ]);
    }
}
