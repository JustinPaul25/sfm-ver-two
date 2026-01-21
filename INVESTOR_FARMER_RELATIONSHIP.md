# Investor-Farmer Relationship Implementation

## Overview
This document describes the implementation of the one-to-many relationship between Investors and Farmers in the SFM system.

## Relationship Structure

### Database Schema
- **Investor** can link to **many Farmers** (one-to-many)
- **Farmer** can only be linked to **one Investor** (belongs to)

### Implementation Details

#### 1. Migration
**File:** `database/migrations/2026_01_21_000000_add_investor_id_to_users_table.php`

Adds `investor_id` foreign key to the `users` table:
- Column: `investor_id` (nullable, foreign key to investors table)
- Constraint: `onDelete('set null')` - if investor is deleted, farmer's investor_id becomes null
- Position: Added after the `role` column

#### 2. Model Relationships

**User Model** (`app/Models/User.php`)
- Added `investor_id` to `$fillable` array
- Added `investor()` method: `belongsTo(Investor::class)`
- This allows accessing: `$farmer->investor`

**Investor Model** (`app/Models/Investor.php`)
- Added `farmers()` method: `hasMany(User::class)->where('role', 'farmer')`
- This allows accessing: `$investor->farmers`

#### 3. Seeder Implementation

**InvestorSeeder** (`database/seeders/InvestorSeeder.php`)

The seeder now creates:
- 8 specific investors
- 12 additional investors
- 1 archived investor (soft-deleted)
- **20 farmer users** linked to various investors

### Farmer Distribution by Investor

| Investor | Number of Farmers | Farmer Names |
|----------|-------------------|--------------|
| John Smith | 2 | Pedro Santos, Juan Dela Cruz |
| Maria Garcia | 2 | Carmen Rivera, Sofia Mercado |
| Robert Johnson | 3 | Ricardo Gomez, Fernando Lopez, Gabriel Cruz |
| Ana Santos | 2 | Rosa Diaz, Lucia Martinez |
| Carlos Rodriguez | 2 | Miguel Ramos, Andres Fernandez |
| Luz Cruz | 1 | Elena Vargas |
| Miguel Torres | 2 | Diego Morales, Antonio Herrera |
| Isabel Reyes | 2 | Isabella Castro, Valentina Ortiz |
| Pedro Martinez | 1 | Eduardo Silva |
| Carmen Lopez | 1 | Catalina Mendoza |
| Jose Santos | 1 | Francisco Ruiz |
| Rosa Mendoza | 1 | Mariana Flores |
| Antonio Flores | 1 | Alberto Jimenez |

### Sample Farmer Credentials

All farmers use the password: `password`

Example logins:
- pedro.santos@sfm.com / password
- juan.delacruz@sfm.com / password
- carmen.rivera@sfm.com / password
- ricardo.gomez@sfm.com / password

## Usage Examples

### Accessing Farmer's Investor
```php
$farmer = User::where('email', 'pedro.santos@sfm.com')->first();
$investor = $farmer->investor; // Returns the Investor model
echo $investor->name; // "John Smith"
```

### Accessing Investor's Farmers
```php
$investor = Investor::where('name', 'John Smith')->first();
$farmers = $investor->farmers; // Collection of User models with role='farmer'

foreach ($farmers as $farmer) {
    echo $farmer->name; // "Pedro Santos", "Juan Dela Cruz"
}
```

### Creating a New Farmer with Investor
```php
$investor = Investor::find(1);

$farmer = User::create([
    'name' => 'New Farmer',
    'email' => 'new.farmer@sfm.com',
    'password' => Hash::make('password'),
    'role' => 'farmer',
    'is_active' => true,
    'investor_id' => $investor->id,
]);
```

### Query Farmers by Investor
```php
// Get all farmers for a specific investor
$farmers = User::where('role', 'farmer')
    ->where('investor_id', $investorId)
    ->get();

// Get count of farmers per investor
$investorWithFarmersCount = Investor::withCount('farmers')->get();
```

## Running the Seeder

To run the migration and seeder:

```bash
# Run the migration
php artisan migrate

# Run all seeders (includes InvestorSeeder)
php artisan db:seed

# Or run InvestorSeeder specifically
php artisan db:seed --class=InvestorSeeder
```

## Notes

1. **Farmer Role**: Farmers are Users with `role = 'farmer'`
2. **Nullable Relationship**: The `investor_id` is nullable, allowing farmers without an investor
3. **Existing Data**: Existing users and farmers remain unaffected (their `investor_id` will be null)
4. **Cascade Behavior**: When an investor is deleted, the farmer's `investor_id` is set to null (not deleted)

## Testing

You can test the relationship using:

```php
// In tinker (php artisan tinker)
$farmer = User::where('email', 'pedro.santos@sfm.com')->first();
$farmer->investor; // Should return John Smith investor

$investor = Investor::where('name', 'John Smith')->first();
$investor->farmers; // Should return 2 farmers
$investor->farmers->count(); // Should return 2
```
