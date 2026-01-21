# Brevo Email Configuration Guide

This guide will help you configure Brevo (formerly Sendinblue) for sending emails in the Smart Fish Management System.

## Prerequisites

1. A Brevo account (sign up at https://www.brevo.com/)
2. Brevo SMTP credentials

## Getting Your Brevo SMTP Credentials

1. Log in to your Brevo account
2. Go to **Settings** (top right) → **SMTP & API**
3. You'll find your SMTP credentials:
   - **SMTP Server**: `smtp-relay.brevo.com`
   - **Port**: `587` (recommended) or `465` (SSL)
   - **Login**: Your Brevo login email
   - **SMTP Key**: Generate/copy your SMTP key (NOT your account password)

## Configuration Steps

### Step 1: Update Your `.env` File

Add or update these lines in your `.env` file:

```env
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your-brevo-email@example.com
MAIL_PASSWORD=your-brevo-smtp-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 2: Replace the Values

Replace the following with your actual credentials:

- `MAIL_USERNAME`: Your Brevo account email
- `MAIL_PASSWORD`: Your Brevo SMTP key (NOT your account password)
- `MAIL_FROM_ADDRESS`: The email address you want to send from (must be verified in Brevo)
- `MAIL_FROM_NAME`: The name that will appear as the sender (optional, defaults to your app name)

### Step 3: Verify Sender Email in Brevo

⚠️ **Important**: You must verify your sender email in Brevo:

1. Go to **Settings** → **Senders & IP**
2. Add your sender email address
3. Verify it through the confirmation email Brevo sends

### Step 4: Clear Config Cache

After updating your `.env` file, clear the configuration cache:

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 5: Test Email Sending

You can test if emails are working by creating a new user in the admin panel. The system will automatically send a welcome email with login credentials.

## Common Ports

- **587** (TLS/STARTTLS) - Recommended
- **465** (SSL)
- **25** (Not recommended for security reasons)

## Troubleshooting

### Email Not Sending

1. **Check your SMTP key**: Make sure you're using the SMTP key, not your account password
2. **Verify sender email**: Ensure your `MAIL_FROM_ADDRESS` is verified in Brevo
3. **Check Brevo dashboard**: Log in to Brevo and check the "Statistics" section to see if emails were sent
4. **Review Laravel logs**: Check `storage/logs/laravel.log` for error messages

### Authentication Failed

- Double-check your `MAIL_USERNAME` and `MAIL_PASSWORD`
- Make sure you're using the SMTP key from Brevo's SMTP & API section

### Sender Not Verified

- Go to Brevo → Settings → Senders & IP
- Add and verify your sender email address

## Example Configuration

Here's a complete example:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=admin@smartfish.com
MAIL_PASSWORD=xkeysib-1234567890abcdef-xxxxxxxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@smartfish.com
MAIL_FROM_NAME="Smart Fish Management"
```

## Features Using Email

Currently, the following features send emails:

1. **User Creation** - New users receive their login credentials via email
   - Welcome message
   - Account details (email and role)
   - Auto-generated temporary password
   - Login link

## Email Templates

All email notifications are located in:
- `app/Notifications/UserCreatedNotification.php`

You can customize the email content by editing the notification classes.

## Daily Sending Limits

Brevo free tier typically includes:
- 300 emails per day
- Unlimited contacts

For higher volume, consider upgrading your Brevo plan.

## Additional Resources

- [Brevo Documentation](https://developers.brevo.com/)
- [Laravel Mail Documentation](https://laravel.com/docs/mail)
- [Laravel Notifications](https://laravel.com/docs/notifications)
