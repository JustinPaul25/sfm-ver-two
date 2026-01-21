# Investor Feature - Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Run Migration
```bash
php artisan migrate
```

This creates the `investor_id` column in the `users` table.

### Step 2: Run Seeder
```bash
php artisan db:seed --class=InvestorSeeder
```

This creates:
- 21 investors
- 5 investor user accounts
- 20 farmer users linked to investors

### Step 3: Test Login
Open your browser and login with:
```
Email: john.smith@investor.com
Password: password
```

### Step 4: Access Dashboard
Navigate to: `/investor/dashboard`

You should see John Smith's investor dashboard! 🎉

---

## 🔑 Investor Login Credentials

| Name | Email | Password |
|------|-------|----------|
| John Smith | john.smith@investor.com | password |
| Maria Garcia | maria.garcia@investor.com | password |
| Robert Johnson | robert.johnson@investor.com | password |
| Ana Santos | ana.santos@investor.com | password |
| Carlos Rodriguez | carlos.rodriguez@investor.com | password |

---

## 👨‍🌾 Farmer Login Credentials (Sample)

| Name | Email | Password | Linked to Investor |
|------|-------|----------|-------------------|
| Pedro Santos | pedro.santos@sfm.com | password | John Smith |
| Juan Dela Cruz | juan.delacruz@sfm.com | password | John Smith |
| Carmen Rivera | carmen.rivera@sfm.com | password | Maria Garcia |
| Ricardo Gomez | ricardo.gomez@sfm.com | password | Robert Johnson |

*See INVESTOR_FARMER_RELATIONSHIP.md for complete list*

---

## 📊 What You'll See on the Dashboard

### Summary Cards
- Total Cages
- Total Farmers
- Samplings This Period
- Average Sample Weight

### Charts & Analytics
- Sampling Trends Chart
- Weight Statistics
- Cage Performance Table
- Feed Type Usage
- Recent Samplings
- Growth Metrics

### Filters
- **Time Period:** Today, This Week, Last 30 Days, This Month, Custom
- **Cage Filter:** Filter by specific cage or view all

---

## 🔒 Security Features

- ✅ Only users with `role = 'investor'` can access
- ✅ Automatic data filtering by investor_id
- ✅ No manual investor selection needed
- ✅ Data isolation (investors only see their own data)

---

## 🛠️ Troubleshooting

### "Access denied. Investor privileges required."
**Solution:** User needs `role = 'investor'` in database

```sql
UPDATE users SET role = 'investor' WHERE email = 'your.email@example.com';
```

### "You are not associated with any investor account"
**Solution:** User needs `investor_id` set

```php
php artisan tinker

$user = User::where('email', 'your.email@example.com')->first();
$investor = Investor::where('name', 'Investor Name')->first();
$user->investor_id = $investor->id;
$user->save();
```

### Dashboard shows no data
**Solution:** Ensure investor has linked cages

```php
php artisan tinker

$investor = Investor::where('name', 'John Smith')->first();
$investor->cages; // Check if cages exist
$investor->farmers; // Check if farmers exist
```

---

## 📖 Full Documentation

For comprehensive documentation, see:
- **INVESTOR_FARMER_RELATIONSHIP.md** - Database structure and relationships
- **INVESTOR_DASHBOARD_GUIDE.md** - Complete feature guide
- **INVESTOR_FEATURE_SUMMARY.md** - Implementation details

---

## 🎯 Quick Test Checklist

- [ ] Migration ran successfully
- [ ] Seeder completed without errors
- [ ] Can login as investor
- [ ] Can access `/investor/dashboard`
- [ ] Dashboard shows summary cards
- [ ] Can change time period filters
- [ ] Can filter by cage
- [ ] Charts are displaying
- [ ] Recent samplings table shows data

---

## 📞 Need Help?

1. Check the troubleshooting section above
2. Review logs: `storage/logs/laravel.log`
3. Verify database: Check `users`, `investors`, `cages` tables
4. Contact development team

---

## 🎨 Customization Tips

### Add Navigation Link
Edit your navigation component to add a link to the investor dashboard:

```vue
<template>
  <nav v-if="$page.props.auth.user?.role === 'investor'">
    <Link href="/investor/dashboard">
      My Dashboard
    </Link>
  </nav>
</template>
```

### Change Dashboard Route
Edit `routes/web.php`:

```php
Route::get('my-investments', [InvestorDashboardController::class, 'index'])
    ->name('investor.dashboard');
```

### Customize Summary Cards
Edit `InvestorDashboard/Index.vue` and modify the `summaryCards` computed property.

---

## ✨ That's It!

You now have a fully functional investor dashboard with:
- Automated data filtering
- Real-time analytics
- Interactive charts
- Performance metrics
- Growth comparisons

Happy investing! 🚀
