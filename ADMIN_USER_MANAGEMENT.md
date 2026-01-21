# Admin User Management System

## Overview

The system has been updated to implement admin-managed account creation for investors and farmers. Only administrators can create user accounts and assign farmers to cages.

## Key Features Implemented

### 1. **Admin-Managed User Creation**

Administrators can now create three types of users through the User Management interface:

#### Creating Investor Accounts
When creating an investor account, the admin must provide:
- Name
- Email
- Password
- **Address** (required for investors)
- **Phone** (required for investors)

The system automatically:
- Creates an `Investor` business entity with the provided information
- Creates a `User` account with role `investor`
- Links the user account to the investor entity via `investor_id`

#### Creating Farmer Accounts
When creating a farmer account, the admin must provide:
- Name
- Email
- Password
- **Investor Selection** (required for farmers)

The system automatically:
- Creates a `User` account with role `farmer`
- Links the farmer to the selected investor via `investor_id`
- Only farmers belonging to the same investor can be assigned to that investor's cages

#### Creating Admin Accounts
Standard admin account creation with name, email, and password.

### 2. **Farmer Assignment to Cages**

Only administrators can assign farmers to cages through the Cage Management interface:

- When creating or editing a cage, admins can select a farmer from a dropdown
- The farmer dropdown is **filtered by investor** - only farmers belonging to the selected investor are shown
- The system validates that the farmer belongs to the same investor as the cage
- Farmers are automatically assigned to cages they create
- Farmers cannot reassign themselves to different cages (admin-only)

### 3. **Public Registration Disabled**

Public registration has been disabled. The registration routes are now commented out in `routes/auth.php`:
- Users cannot self-register
- All accounts must be created by administrators through the User Management interface

## Technical Implementation

### Backend Changes

#### 1. **UserController** (`app/Http/Controllers/UserController.php`)

**New Features:**
- `store()` method now handles investor account creation (creates both User and Investor records in a transaction)
- `store()` method links farmers to investors via `investor_id`
- Added `getFarmersByInvestor()` endpoint to fetch farmers belonging to a specific investor
- `list()` method now loads investor relationships for display

**API Endpoints:**
```php
GET  /users/farmers-by-investor?investor_id={id}  // Get farmers for an investor
POST /users                                         // Create user (with investor/farmer logic)
```

#### 2. **CageController** (`app/Http/Controllers/CageController.php`)

**New Features:**
- `store()` method accepts optional `farmer_id` parameter
- `update()` method accepts optional `farmer_id` parameter (admin-only for reassignment)
- Validation ensures farmer belongs to the same investor as the cage
- Farmers creating cages are automatically assigned as the farmer
- Farmers cannot change farmer assignments (admin-only)

**Validation:**
```php
'farmer_id' => 'nullable|exists:users,id'
```

#### 3. **Routes** (`routes/web.php`)

**New Route:**
```php
Route::get('users/farmers-by-investor', [UserController::class, 'getFarmersByInvestor'])
    ->name('users.farmers-by-investor');
```

#### 4. **Authentication Routes** (`routes/auth.php`)

**Disabled Routes:**
```php
// Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
// Route::post('register', [RegisteredUserController::class, 'store']);
```

### Frontend Changes

#### 1. **Users Management** (`resources/js/pages/Users/Index.vue`)

**New Features:**
- Dynamic form fields based on selected role:
  - **Investor role**: Shows Address and Phone fields
  - **Farmer role**: Shows Investor selection dropdown
  - **Admin role**: Standard fields only
- User table displays investor association for farmers/investors
- Fetches investor list for farmer assignment

**UI Elements:**
- New "Investor" column in user table
- Conditional form fields in create dialog
- Real-time role selection changes form layout

#### 2. **Cages Management** (`resources/js/pages/Cages/Index.vue`)

**New Features:**
- Farmer assignment dropdown (admin-only)
- Dynamic farmer list filtered by selected investor
- "Farmer" column in cage table
- Real-time investor selection updates farmer dropdown

**UI Flow:**
1. Admin selects an investor
2. System fetches farmers belonging to that investor
3. Admin can optionally assign a farmer from the filtered list
4. Validation ensures farmer-investor relationship

## Usage Guide

### Creating an Investor Account

1. Navigate to **User Management** (`/users`)
2. Click **Create User**
3. Select **Role: Investor**
4. Fill in:
   - Name
   - Email
   - Address
   - Phone
   - Password
   - Password Confirmation
5. Click **Create**

The system creates both the investor business entity and the user account automatically.

### Creating a Farmer Account

1. Navigate to **User Management** (`/users`)
2. Click **Create User**
3. Select **Role: Farmer**
4. Fill in:
   - Name
   - Email
   - **Select Investor** (required)
   - Password
   - Password Confirmation
5. Click **Create**

The farmer is now linked to the selected investor.

### Assigning a Farmer to a Cage

1. Navigate to **Cages** (`/cages`)
2. Click **Create Cage** or **Edit** an existing cage
3. Select the **Investor**
4. The farmer dropdown automatically populates with farmers belonging to that investor
5. Optionally select a **Farmer**
6. Fill in other cage details
7. Click **Create** or **Update**

### User Management Features

- **View Users**: See all users with their roles and investor associations
- **Filter**: Filter by role (farmer/investor/admin) and status (active/inactive)
- **Search**: Search by name or email
- **Change Role**: Update user roles via dropdown (except your own)
- **Toggle Status**: Activate/deactivate users (except yourself)
- **Edit**: Update user name and email
- **Delete**: Remove users (except yourself)

## Database Structure

### Users Table
```
- id
- name
- email
- password
- role (farmer|investor|admin)
- is_active
- investor_id (foreign key to investors table)
- email_verified_at
- created_at
- updated_at
```

### Investors Table
```
- id
- name
- address
- phone
- created_at
- updated_at
- deleted_at (soft deletes)
```

### Cages Table
```
- id
- number_of_fingerlings
- feed_types_id
- investor_id (foreign key to investors table)
- farmer_id (foreign key to users table)
- created_at
- updated_at
```

## Relationships

```
Investor (1) ─── (many) Users (farmers and investors)
Investor (1) ─── (many) Cages
User/Farmer (1) ─── (many) Cages
```

## Security & Permissions

### Admin Only
- Create any user account
- Assign farmers to cages
- Reassign farmers between cages
- Access User Management interface

### Farmers
- View their own cages
- Create cages (automatically assigned to themselves)
- Update their own cages (cannot change farmer assignment)
- Cannot create other user accounts

### Investors
- View-only access to their data
- Cannot create or modify cages
- Cannot create user accounts

## API Response Examples

### Create Investor User
```json
POST /users
{
  "name": "John Smith",
  "email": "john@investor.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "investor",
  "address": "123 Main St, Davao City",
  "phone": "09123456789"
}

Response:
{
  "message": "User created successfully",
  "user": {
    "id": 1,
    "name": "John Smith",
    "email": "john@investor.com",
    "role": "investor",
    "investor_id": 15,
    "is_active": true,
    "investor": {
      "id": 15,
      "name": "John Smith",
      "address": "123 Main St, Davao City",
      "phone": "09123456789"
    }
  }
}
```

### Create Farmer User
```json
POST /users
{
  "name": "Pedro Santos",
  "email": "pedro@farmer.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "farmer",
  "investor_id": 15
}

Response:
{
  "message": "User created successfully",
  "user": {
    "id": 2,
    "name": "Pedro Santos",
    "email": "pedro@farmer.com",
    "role": "farmer",
    "investor_id": 15,
    "is_active": true,
    "investor": {
      "id": 15,
      "name": "John Smith"
    }
  }
}
```

### Get Farmers by Investor
```json
GET /users/farmers-by-investor?investor_id=15

Response:
[
  {
    "id": 2,
    "name": "Pedro Santos",
    "email": "pedro@farmer.com",
    "role": "farmer",
    "investor_id": 15
  },
  {
    "id": 3,
    "name": "Juan Dela Cruz",
    "email": "juan@farmer.com",
    "role": "farmer",
    "investor_id": 15
  }
]
```

## Testing the Implementation

### Test Case 1: Create Investor Account
1. Login as admin
2. Navigate to User Management
3. Create investor with address and phone
4. Verify investor entity is created in database
5. Verify user can login with investor role
6. Verify investor_id is linked

### Test Case 2: Create Farmer Account
1. Login as admin
2. Navigate to User Management
3. Create farmer and select an investor
4. Verify farmer user is created
5. Verify farmer is linked to investor via investor_id
6. Verify farmer can login

### Test Case 3: Assign Farmer to Cage
1. Login as admin
2. Navigate to Cages
3. Create a cage and select an investor
4. Verify farmer dropdown shows only farmers from that investor
5. Assign a farmer
6. Verify farmer_id is saved correctly
7. Verify farmer can see the cage in their list

### Test Case 4: Farmer Cannot Reassign
1. Login as farmer
2. Edit a cage assigned to you
3. Verify farmer dropdown is not shown
4. Verify farmer_id cannot be changed

### Test Case 5: Cross-Investor Validation
1. Login as admin
2. Try to assign a farmer from Investor A to a cage owned by Investor B
3. Verify system shows validation error
4. Verify assignment is rejected

## Migration Notes

If you have existing data:

1. **Existing Investors**: Already have records in the `investors` table
2. **Existing Users**: 
   - Update any investor users to have correct `investor_id`
   - Update any farmer users to have correct `investor_id`
3. **Existing Cages**: 
   - `farmer_id` can be null (optional field)
   - Update cages to assign farmers as needed

## Troubleshooting

### Issue: "Farmer must belong to the same investor"
**Solution**: Ensure the farmer you're assigning belongs to the same investor as the cage.

### Issue: "You are not associated with any investor account"
**Solution**: 
- For investors: Ensure the user has a valid `investor_id` linked to an Investor record
- For farmers: Ensure the user has a valid `investor_id` linked to an Investor record
- Admin should recreate the account properly through User Management

### Issue: Farmer dropdown is empty
**Solution**: 
- Verify farmers exist for the selected investor
- Check that farmers have `role = 'farmer'` and valid `investor_id`
- Ensure farmers are active (`is_active = true`)

### Issue: Cannot create investor without address/phone
**Solution**: This is expected behavior. Investors require address and phone for business records.

## Future Enhancements

Potential improvements:
1. Bulk farmer assignment to multiple cages
2. Farmer reassignment history/audit log
3. Email notifications when farmer is assigned to cage
4. Farmer workload dashboard (number of cages assigned)
5. Investor dashboard showing all their farmers
6. Password reset functionality for admin-created accounts
