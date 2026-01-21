# Access Control Testing Guide

## Quick Test Scenarios

### Prerequisites
Make sure you have users with the following roles in your database:
1. An admin user
2. An investor user (with `role='investor'` and `investor_id` set)
3. A farmer user (with `role='farmer'`)
4. Multiple investors with different cages
5. Multiple farmers assigned to different cages

### Test Scenario 1: Investor Access Control

**Objective:** Verify investors can only see their own cages and samplings

#### Steps:
1. Log in as an **investor user** (e.g., investor_id = 1)
2. Navigate to the Cages page (`/cages`)
   - ✅ Should only see cages where `investor_id = 1`
   - ❌ Should NOT see cages from other investors
3. Navigate to the Samplings page (`/samplings`)
   - ✅ Should only see samplings where `investor_id = 1`
   - ❌ Should NOT see samplings from other investors
4. Navigate to the Dashboard (`/dashboard`)
   - ✅ Should only see statistics for investor_id = 1
5. Try to access the Feeding Report page (`/reports/feeding`)
   - ✅ Should only see feeding data for investor_id = 1 cages
6. Navigate to the Reports page (`/reports/overall`)
   - ✅ Should only see reports for investor_id = 1

#### Expected Behavior:
- All data displayed should be filtered to the logged-in investor's data
- No data from other investors should be visible
- Dropdown filters (if any) should only show the investor's own options

---

### Test Scenario 2: Farmer Access Control

**Objective:** Verify farmers can only see their own cages

#### Steps:
1. Log in as a **farmer user** (e.g., user_id = 5)
2. Navigate to the Cages page (`/cages`)
   - ✅ Should only see cages where `farmer_id = 5`
   - ❌ Should NOT see cages assigned to other farmers
3. Navigate to the Samplings page (`/samplings`)
   - ✅ Should only see samplings for cages where `farmer_id = 5`
   - ❌ Should NOT see samplings for other farmers' cages
4. Navigate to the Dashboard (`/dashboard`)
   - ✅ Should only see statistics for farmer_id = 5 cages
5. Navigate to the Cage Verification page (`/cages/verification`)
   - ✅ Should only see verification data for farmer_id = 5 cages
6. Navigate to the Feeding Report page (`/reports/feeding`)
   - ✅ Should only see feeding data for farmer_id = 5 cages

#### Expected Behavior:
- All data displayed should be filtered to the logged-in farmer's cages
- No data from other farmers' cages should be visible
- The farmer should be able to manage (create/edit/delete) their own cages

---

### Test Scenario 3: Admin Access (No Restrictions)

**Objective:** Verify admins can see all data

#### Steps:
1. Log in as an **admin user**
2. Navigate to all pages:
   - Cages (`/cages`)
   - Samplings (`/samplings`)
   - Dashboard (`/dashboard`)
   - Reports (`/reports`)
   - Investors (`/investors`)
3. Verify that you can see ALL data from ALL investors and farmers

#### Expected Behavior:
- Admin should see all cages from all investors
- Admin should see all samplings from all cages
- No filters should be applied automatically
- Admin should have full CRUD access to all resources

---

### Test Scenario 4: Direct Access Attempts (Security Test)

**Objective:** Verify users cannot access other users' data by manipulating URLs

#### Steps for Investor Users:

1. Log in as **Investor User 1** (investor_id = 1)
2. Note a cage ID that belongs to Investor 1 (e.g., cage_id = 5)
3. Try to access a cage that belongs to **Investor User 2** (e.g., cage_id = 10)
   - Try accessing `/cages/10/view` directly
   - ❌ Should receive a 403 Forbidden error
4. Try to access an investor report for another investor:
   - Try accessing `/investors/2/report` (where 2 is another investor's ID)
   - ❌ Should receive a 403 Forbidden error

#### Steps for Farmer Users:

1. Log in as **Farmer User 1** (user_id = 5)
2. Note a cage ID that belongs to Farmer 1 (e.g., cage_id = 3)
3. Try to access a cage that belongs to **Farmer User 2** (e.g., cage_id = 8)
   - Try accessing `/cages/8/view` directly
   - ❌ Should receive a 403 Forbidden error

#### Expected Behavior:
- Users should receive a 403 Forbidden error when trying to access resources they don't own
- The response should contain a message like:
  - "You can only view your own cages"
  - "You can only view your own investor report"

---

## Testing Checklist

### Investor Role Testing
- [ ] Can view only own cages
- [ ] Can view only own samplings
- [ ] Can view only own reports
- [ ] Cannot view other investors' cages
- [ ] Cannot view other investors' samplings
- [ ] Cannot access other investors' reports via direct URL
- [ ] Dashboard shows only own data
- [ ] Feeding reports show only own data

### Farmer Role Testing
- [ ] Can view only own cages
- [ ] Can view only samplings for own cages
- [ ] Cannot view other farmers' cages
- [ ] Cannot view samplings for other farmers' cages
- [ ] Cannot access other farmers' cages via direct URL
- [ ] Dashboard shows only own cage data
- [ ] Can create/edit/delete own cages
- [ ] Cannot create/edit/delete other farmers' cages

### Admin Role Testing
- [ ] Can view all cages
- [ ] Can view all samplings
- [ ] Can view all reports
- [ ] Can view all investors
- [ ] No access restrictions apply
- [ ] Can manage all resources

---

## Automated Testing (Optional)

If you want to create automated tests, here's an example test case structure:

```php
// tests/Feature/AccessControlTest.php

public function test_investor_can_only_see_own_cages()
{
    // Create two investors
    $investor1 = Investor::factory()->create();
    $investor2 = Investor::factory()->create();
    
    // Create investor user for investor 1
    $user = User::factory()->create([
        'role' => 'investor',
        'investor_id' => $investor1->id
    ]);
    
    // Create cages for both investors
    $cage1 = Cage::factory()->create(['investor_id' => $investor1->id]);
    $cage2 = Cage::factory()->create(['investor_id' => $investor2->id]);
    
    // Act as investor 1
    $response = $this->actingAs($user)->get('/cages/list');
    
    // Assert
    $response->assertStatus(200);
    $cages = $response->json('cages.data');
    
    // Should see own cage
    $this->assertTrue(collect($cages)->contains('id', $cage1->id));
    
    // Should NOT see other investor's cage
    $this->assertFalse(collect($cages)->contains('id', $cage2->id));
}

public function test_investor_cannot_view_other_investors_cage()
{
    $investor1 = Investor::factory()->create();
    $investor2 = Investor::factory()->create();
    
    $user = User::factory()->create([
        'role' => 'investor',
        'investor_id' => $investor1->id
    ]);
    
    $cage2 = Cage::factory()->create(['investor_id' => $investor2->id]);
    
    // Try to access other investor's cage
    $response = $this->actingAs($user)->get("/cages/{$cage2->id}/view");
    
    // Should be forbidden
    $response->assertStatus(403);
}
```

---

## Common Issues & Troubleshooting

### Issue 1: Investor sees no data
**Possible causes:**
- `user.investor_id` is not set correctly
- No cages assigned to that investor_id
- User role is not set to 'investor'

**Solution:** Check the users table and ensure `investor_id` field is set correctly.

### Issue 2: Farmer sees no data
**Possible causes:**
- No cages assigned to that farmer (farmer_id)
- User role is not set to 'farmer'

**Solution:** Check the cages table and ensure at least one cage has `farmer_id = user.id`.

### Issue 3: Admin restrictions applied incorrectly
**Possible causes:**
- User role is not set to 'admin'

**Solution:** Verify `user.role = 'admin'` in the database.

---

## Database Verification Queries

Run these queries to verify your data setup:

```sql
-- Check user roles and investor assignments
SELECT id, name, email, role, investor_id FROM users;

-- Check cage assignments
SELECT id, investor_id, farmer_id FROM cages;

-- Check samplings
SELECT id, investor_id, cage_no FROM samplings;

-- Verify investor-cage relationships
SELECT 
    i.id as investor_id, 
    i.name as investor_name,
    COUNT(c.id) as cage_count
FROM investors i
LEFT JOIN cages c ON i.id = c.investor_id
GROUP BY i.id, i.name;

-- Verify farmer-cage relationships
SELECT 
    u.id as farmer_id,
    u.name as farmer_name,
    COUNT(c.id) as cage_count
FROM users u
LEFT JOIN cages c ON u.id = c.farmer_id
WHERE u.role = 'farmer'
GROUP BY u.id, u.name;
```
