# WhatsApp QR Testing - Start Here

## Your Bot is Ready! 🎉

Your bot code is fully set up for WhatsApp QR testing. Just 4 simple steps:

## Step 1: Start Development Server

```
php artisan serve
```

## Step 2: Expose with ngrok (new terminal)

```
ngrok http 8000
```
Copy the HTTPS URL shown (e.g., `https://abc123.ngrok.io`)

## Step 3: Configure Webhook in Meta

1. Go to https://developers.facebook.com/
2. Select your WhatsApp app
3. Settings → Webhook Configuration  
4. Set **Webhook URL**: `https://your-ngrok-url/api/webhook`
5. Set **Verify Token**: `my_secret_verify_token_2024`

## Step 4: Test via QR

1. In your app: WhatsApp → Sandbox
2. Scan QR code with WhatsApp
3. Send: `hello`
4. Bot responds! ✅

## View Logs

```
Get-Content storage/logs/laravel.log -Tail 20 -Wait
```

## Bot Commands

- `hello` or `hi` - Start conversation
- `menu` - Show menu
- `cancel` - Cancel order

## Test Restaurants Already Set Up

- **Taste of Bahawalpur** (Phone ID: YOUR_PHONE_NUMBER_ID_HERE)
- **ZFC** (Phone ID: 03241679919)

Just use one of these phone IDs in your ngrok webhook setup!

---

## Verify Everything is Ready

```
php artisan qr:verify
```

This will check database, tables, restaurants, and configuration.

---

**That's it!** Scan the QR and start testing. 🚀
