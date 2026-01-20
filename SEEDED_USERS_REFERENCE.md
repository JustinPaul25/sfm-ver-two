# Seeded Users Quick Reference

## Overview
After running `php artisan migrate:fresh --seed`, you'll have **13 users** created.

## ⚠️ Important: Only 2 Roles Exist
- **Admin** - Full system access (1 user)
- **Farmer** - Can manage assigned cages (12 users)

**There is NO "manager" role.** The user `manager@sfm.com` is just a regular farmer with the same privileges as `test@sfm.com`, `john@sfm.com`, etc. See `MANAGER_USER_CLARIFICATION.md` for details.

---

## Admin Users (1)

| Email | Password | Role | Status | Verified |
|-------|----------|------|--------|----------|
| admin@sfm.com | admin123 | Admin | Active | ✅ Yes |

**Capabilities**: Full system access, user management, can see all cages

---

## Active Farmers (8)

| Email | Password | Name | Status | Verified |
|-------|----------|------|--------|----------|
| test@sfm.com | password | Test User | Active | ✅ Yes |
| manager@sfm.com | manager123 | Farm Manager | Active | ✅ Yes |
| john@sfm.com | password | John Doe | Active | ✅ Yes |
| jane@sfm.com | password | Jane Smith | Active | ✅ Yes |
| bob@sfm.com | password | Bob Wilson | Active | ✅ Yes |
| alice@sfm.com | password | Alice Brown | Active | ✅ Yes |
| charlie@sfm.com | password | Charlie Davis | Active | ✅ Yes |

**Capabilities**: Can manage their assigned cages, samplings, and feeding schedules

**⚠️ Important Note**: The "Farm Manager" user (`manager@sfm.com`) is **NOT** a special role. It's just a regular farmer account with a different name and password for easy identification during testing. It has the exact same privileges as `test@sfm.com`, `john@sfm.com`, etc.

---

## Unverified Farmers (2)

| Email | Password | Name | Status | Verified |
|-------|----------|------|--------|----------|
| unverified1@sfm.com | password | Unverified User 1 | Active | ❌ No |
| unverified2@sfm.com | password | Unverified User 2 | Active | ❌ No |

**Purpose**: Test email verification feature
**Note**: These users are active but have not verified their email addresses

---

## Inactive Farmers (2)

| Email | Password | Name | Status | Verified |
|-------|----------|------|--------|----------|
| inactive1@sfm.com | password | Inactive Farmer 1 | Inactive | ✅ Yes |
| inactive2@sfm.com | password | Inactive Farmer 2 | Inactive | ✅ Yes |

**Purpose**: Test user activation/deactivation feature
**Note**: These users CANNOT log in (blocked by middleware)

---

## Summary Statistics

| Metric | Count |
|--------|-------|
| **Total Users** | 13 |
| **Active** | 11 |
| **Inactive** | 2 |
| **Admins** | 1 |
| **Farmers** | 12 |
| **Email Verified** | 11 |
| **Unverified** | 2 |

---

## Common Testing Scenarios

### Test User Management (Admin)
```
Login: admin@sfm.com / admin123
Navigate to: /users
```

### Test Farmer Access
```
Login: test@sfm.com / password
Can see: Only assigned cages
Can do: Create/edit own cages and samplings
```

### Test Activation/Deactivation
```
1. Login as admin
2. Deactivate a user (e.g., test@sfm.com)
3. Try logging in with that user → Should be blocked
4. Reactivate the user
5. Login should work again
```

### Test Inactive User Login
```
Try login: inactive1@sfm.com / password
Result: Should be blocked with message about inactive account
```

---

## Role Capabilities

### Admin
- ✅ Full system access
- ✅ User management (create, edit, delete, activate/deactivate)
- ✅ See all cages (regardless of assignment)
- ✅ Manage investors, feed types, feeding schedules
- ✅ Access all reports

### Farmer
- ✅ Manage assigned cages
- ✅ Create and manage samplings
- ✅ Manage feeding schedules
- ❌ Cannot access user management
- ❌ Cannot see cages not assigned to them

---

## Notes

- All passwords are securely hashed
- Inactive users are blocked by middleware (`EnsureUserIsActive`)
- Admin cannot deactivate their own account
- Users are created with `firstOrCreate()` to prevent duplicates
- Only 2 roles exist: **Admin** and **Farmer**
- ⚠️ **"Farm Manager" is NOT a special role** - it's just a farmer user with role='farmer'

---

## Quick Commands

```bash
# Reset database and seed all data
php artisan migrate:fresh --seed

# Seed only users
php artisan db:seed --class=UserSeeder

# Check user count
php artisan tinker
>>> User::count()
>>> User::where('is_active', true)->count()
>>> User::where('role', 'admin')->count()
```

---

## Related Files

- `database/seeders/UserSeeder.php` - User seeder code
- `app/Models/User.php` - User model
- `app/Http/Middleware/EnsureUserIsActive.php` - Active user middleware
- `app/Http/Middleware/EnsureUserIsAdmin.php` - Admin middleware
- `ADMIN_FEATURES.md` - Full admin features documentation
- `SEEDER_UPDATES.md` - All seeder updates documentation
