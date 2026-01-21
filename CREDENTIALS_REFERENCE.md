# System Credentials Reference

## 🔐 System Users - Quick Reference

---

## Administrator

| Role | Name | Email | Password | Access |
|------|------|-------|----------|--------|
| **Admin** | System Administrator | admin@sfm.com | admin123 | Full system access |

**Can Access:**
- User management
- System settings
- All features
- All data

---

## Investors (Login Access)

| Name | Email | Password | Dashboard Access |
|------|-------|----------|------------------|
| **John Smith** | john.smith@investor.com | password | `/investor/dashboard` |
| **Maria Garcia** | maria.garcia@investor.com | password | `/investor/dashboard` |
| **Robert Johnson** | robert.johnson@investor.com | password | `/investor/dashboard` |
| **Ana Santos** | ana.santos@investor.com | password | `/investor/dashboard` |
| **Carlos Rodriguez** | carlos.rodriguez@investor.com | password | `/investor/dashboard` |

**Can Access:**
- Investor dashboard
- Their own cages data
- Their own farmers data
- Their own samplings and reports

---

## Farmers (Login Access)

### Farmers for John Smith

| Name | Email | Password | Investor |
|------|-------|----------|----------|
| Pedro Santos | pedro.santos@sfm.com | password | John Smith |
| Juan Dela Cruz | juan.delacruz@sfm.com | password | John Smith |

### Farmers for Maria Garcia

| Name | Email | Password | Investor |
|------|-------|----------|----------|
| Carmen Rivera | carmen.rivera@sfm.com | password | Maria Garcia |
| Sofia Mercado | sofia.mercado@sfm.com | password | Maria Garcia |

### Farmers for Robert Johnson

| Name | Email | Password | Investor |
|------|-------|----------|----------|
| Ricardo Gomez | ricardo.gomez@sfm.com | password | Robert Johnson |
| Fernando Lopez | fernando.lopez@sfm.com | password | Robert Johnson |
| Gabriel Cruz | gabriel.cruz@sfm.com | password | Robert Johnson |

### Farmers for Ana Santos

| Name | Email | Password | Investor |
|------|-------|----------|----------|
| Rosa Diaz | rosa.diaz@sfm.com | password | Ana Santos |
| Lucia Martinez | lucia.martinez@sfm.com | password | Ana Santos |

### Farmers for Carlos Rodriguez

| Name | Email | Password | Investor |
|------|-------|----------|----------|
| Miguel Ramos | miguel.ramos@sfm.com | password | Carlos Rodriguez |
| Andres Fernandez | andres.fernandez@sfm.com | password | Carlos Rodriguez |

### Farmers for Luz Cruz

| Name | Email | Password | Investor |
|------|-------|----------|----------|
| Elena Vargas | elena.vargas@sfm.com | password | Luz Cruz |

### Farmers for Miguel Torres

| Name | Email | Password | Investor |
|------|-------|----------|----------|
| Diego Morales | diego.morales@sfm.com | password | Miguel Torres |
| Antonio Herrera | antonio.herrera@sfm.com | password | Miguel Torres |

### Farmers for Isabel Reyes

| Name | Email | Password | Investor |
|------|-------|----------|----------|
| Isabella Castro | isabella.castro@sfm.com | password | Isabel Reyes |
| Valentina Ortiz | valentina.ortiz@sfm.com | password | Isabel Reyes |

### Farmers for Other Investors

| Name | Email | Password | Investor |
|------|-------|----------|----------|
| Eduardo Silva | eduardo.silva@sfm.com | password | Pedro Martinez |
| Catalina Mendoza | catalina.mendoza@sfm.com | password | Carmen Lopez |
| Francisco Ruiz | francisco.ruiz@sfm.com | password | Jose Santos |
| Mariana Flores | mariana.flores@sfm.com | password | Rosa Mendoza |
| Alberto Jimenez | alberto.jimenez@sfm.com | password | Antonio Flores |

**Can Access:**
- Standard farmer features
- Manage their assigned cages
- Create samplings
- View reports

---

## Legacy Test Users

| Role | Name | Email | Password | Note |
|------|------|-------|----------|------|
| Farmer | Test User | test@sfm.com | password | General testing |
| Farmer | Farm Manager | manager@sfm.com | manager123 | Not a special role |
| Farmer | John Doe | john@sfm.com | password | Additional test user |
| Farmer | Jane Smith | jane@sfm.com | password | Additional test user |
| Farmer | Bob Wilson | bob@sfm.com | password | Additional test user |
| Farmer | Alice Brown | alice@sfm.com | password | Additional test user |
| Farmer | Charlie Davis | charlie@sfm.com | password | Additional test user |

---

## Quick Login URLs

```
Admin Panel: /dashboard
Investor Dashboard: /investor/dashboard
```

---

## Access Matrix

| Feature | Admin | Investor | Farmer |
|---------|-------|----------|--------|
| System Dashboard | ✅ | ❌ | ✅ |
| Investor Dashboard | ❌ | ✅ | ❌ |
| User Management | ✅ | ❌ | ❌ |
| System Settings | ✅ | ❌ | ❌ |
| Investors | ✅ | ❌ | ✅ |
| Cages | ✅ | View Own | ✅ |
| Samplings | ✅ | View Own | ✅ |
| Reports | ✅ | View Own | ✅ |
| Feed Types | ✅ | ❌ | ✅ |

---

## Role Hierarchy

```
┌──────────────┐
│    Admin     │  ← Full system access
└──────┬───────┘
       │
    ┌──┴──┐
    │     │
┌───▼───┐ │
│Investor│ │  ← Own data only (dashboard, cages, farmers)
└───────┘ │
          │
      ┌───▼────┐
      │ Farmer │  ← Standard features
      └────────┘
```

---

## Testing Scenarios

### Scenario 1: Investor Login
```
1. Login: john.smith@investor.com / password
2. Navigate to: /investor/dashboard
3. Expected: See John Smith's dashboard with 2 farmers and their cages
```

### Scenario 2: Farmer Login
```
1. Login: pedro.santos@sfm.com / password
2. Expected: Standard farmer interface
3. Cannot access: /investor/dashboard (403 error)
```

### Scenario 3: Data Isolation
```
1. Login as John Smith investor
2. Note the cages shown
3. Logout
4. Login as Maria Garcia investor
5. Expected: See different cages (no overlap with John Smith's data)
```

### Scenario 4: Farmer-Investor Link
```
1. Login as Pedro Santos (farmer)
2. Check assigned cages
3. Expected: Cages are linked to John Smith investor
4. Samplings created by Pedro will show in John Smith's dashboard
```

---

## Password Reset

All passwords are currently set to default values for testing:
- Investors: `password`
- Farmers: `password`
- Admin: `admin123`
- Manager: `manager123`

**⚠️ SECURITY WARNING:** Change these passwords in production!

---

## Database Quick Checks

### Check User Roles
```sql
SELECT name, email, role FROM users WHERE role IN ('admin', 'investor');
```

### Check Investor Links
```sql
SELECT u.name as farmer_name, i.name as investor_name 
FROM users u 
JOIN investors i ON u.investor_id = i.id 
WHERE u.role = 'farmer';
```

### Check Cages by Investor
```sql
SELECT i.name as investor, COUNT(c.id) as cage_count 
FROM investors i 
LEFT JOIN cages c ON i.id = c.investor_id 
GROUP BY i.name;
```

---

## Support Information

- **Documentation:** See INVESTOR_QUICK_START.md
- **Full Guide:** See INVESTOR_DASHBOARD_GUIDE.md
- **Setup:** Run `php artisan db:seed --class=InvestorSeeder`
- **Logs:** Check `storage/logs/laravel.log`

---

**Last Updated:** 2026-01-21  
**Version:** 1.0  
**System:** SFM (Shrimp Farm Management)
