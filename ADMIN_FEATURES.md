# Admin Features Documentation

This document describes the admin and user management features implemented in the SFM application.

## Overview

The application now supports three distinct user roles:
- **Admin**: Full system access with user management capabilities
- **Farmer**: Can manage their own cages, samplings, and related data
- **Investor**: Read-only access to view cages and samplings

## Admin Credentials

**Default Admin Account:**
- Email: `admin@sfm.com`
- Password: `admin123`

**Other Test Accounts:**
- Test User (Farmer): `test@sfm.com` / `password`
- Manager (Farmer): `manager@sfm.com` / `manager123`

## Admin Features

### 1. User Management Dashboard

Admins have access to a comprehensive User Management page at `/users` with the following features:

#### Statistics Overview
- Total Users count
- Active/Inactive users count
- Role distribution (Farmers, Investors, Admins)

#### User List
- Search users by name or email
- Filter by role (farmer, investor, admin)
- Filter by status (active, inactive)
- Paginated results (10 users per page)

#### User Actions
1. **Create New User**
   - Add new users with name, email, role, and password
   - All new users are active by default

2. **Edit User**
   - Update user name and email
   - Cannot edit own admin account

3. **Change User Role**
   - Quickly change user role via dropdown in the table
   - Cannot change own role (security feature)
   - Available roles: Farmer, Investor, Admin

4. **Toggle User Status**
   - Activate/deactivate user accounts
   - Inactive users are immediately logged out
   - Cannot deactivate own account

5. **Delete User**
   - Permanently delete user accounts
   - Deletes related data (cages assigned to that user)
   - Cannot delete own account

### 2. Registration with Role Selection

New users can register and select their account type:
- **Farmer**: Can create and manage own cages
- **Investor**: View-only access

Note: Admin accounts can only be created by existing admins through the User Management interface.

### 3. Role-Based Navigation

The sidebar menu adapts based on user role:

**Farmer Menu:**
- Samplings
- Feed Types
- Cages
- Feeding Schedules

**Investor Menu:**
- Samplings
- Cages

**Admin Menu:**
- Dashboard
- **User Management** (admin-only)
- Investors
- Samplings
- Feed Types
- Cages
- Feeding Schedules
- Reports

### 4. Enhanced Security

#### Middleware Protection
- **Admin Middleware**: Protects admin-only routes (like `/users`)
- **Active User Middleware**: Prevents inactive users from accessing the application

#### User Account Status
- Users have an `is_active` status flag
- Inactive users are automatically logged out when they try to access the system
- Admins cannot deactivate or delete their own accounts

#### Role-Based Access Control
Throughout the application, controllers check user roles to enforce permissions:
- **Investors**: Cannot create, update, or delete any records
- **Farmers**: Can only manage their own cages and related data
- **Admins**: Have full access to all features

## Database Changes

### New Migration: `add_is_active_to_users_table`
Adds `is_active` boolean field to users table (default: true)

### Updated Users Table Structure
```php
- id
- name
- email
- email_verified_at
- password
- role (enum: 'farmer', 'investor', 'admin')
- is_active (boolean, default: true)
- remember_token
- created_at
- updated_at
```

## API Endpoints

### User Management (Admin Only)

All routes require authentication and admin role:

```
GET    /users                      - User management page
GET    /users/list                 - Get paginated user list
GET    /users/statistics           - Get user statistics
POST   /users                      - Create new user
PUT    /users/{user}               - Update user details
PUT    /users/{user}/role          - Update user role
POST   /users/{user}/toggle-status - Activate/deactivate user
DELETE /users/{user}               - Delete user
```

### Registration

```
POST /register - Register new user with role selection
```

## Testing the Features

### As Admin:
1. Log in with `admin@sfm.com` / `admin123`
2. Navigate to "User Management" in the sidebar
3. View user statistics and list
4. Try creating a new user
5. Change a user's role
6. Deactivate/activate a user
7. Test that you cannot deactivate yourself

### As Farmer:
1. Register a new account or use `test@sfm.com` / `password`
2. Notice the limited menu (no User Management)
3. Create and manage your own cages
4. Verify you can only see your own cages

### As Investor:
1. Register a new account with "Investor" role
2. Notice the read-only menu (Samplings, Cages only)
3. Verify you cannot create, update, or delete any records

### Test Account Deactivation:
1. As admin, deactivate a test user account
2. Try to log in with that account
3. Verify the account is rejected
4. Reactivate the account and try again

## Security Considerations

1. **Self-Protection**: Admins cannot modify or delete their own accounts
2. **Session Management**: Inactive users are logged out immediately
3. **Role Validation**: All admin routes are protected by middleware
4. **Password Requirements**: Uses Laravel's default password validation rules
5. **Activity Status**: Checked on every request for authenticated users

## Future Enhancements

Potential improvements for the admin system:
- User activity logs
- Email notifications for account status changes
- Bulk user operations
- Advanced user filtering and search
- User profile pictures
- Two-factor authentication for admins
- Password reset functionality for users (by admin)
- Audit trail for admin actions

## Troubleshooting

### Issue: Can't access User Management page
**Solution**: Ensure you're logged in as an admin user. Check the database to verify your user has `role = 'admin'`.

### Issue: Migration error
**Solution**: Run `php artisan migrate:fresh` and then `php artisan db:seed` to reset the database.

### Issue: Cannot see User Management in sidebar
**Solution**: Clear your browser cache and refresh. Ensure you're logged in as admin.

### Issue: Inactive user can still access
**Solution**: User needs to log out and log back in for the middleware to take effect.

## Support

For issues or questions about the admin features, please refer to:
- Laravel documentation: https://laravel.com/docs
- Vue.js documentation: https://vuejs.org/
- Inertia.js documentation: https://inertiajs.com/
