# 🚀 **Quick Fix for Shopify OAuth Error**

## 🔧 **Immediate Solution**

Add these lines to your `.env` file:

```env
# Shopify OAuth Configuration
SHOPIFY_API_KEY=7793b6863d3303fe7f295b8f19c6b4c4
SHOPIFY_API_SECRET=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
SHOPIFY_REDIRECT_URI=http://localhost:8000/app/shopify/callback
APP_URL=http://localhost:8000
```

## 📋 **Steps to Fix:**

### **Step 1: Update .env File**
Add the configuration above to your `.env` file.

### **Step 2: Update Shopify Partner Dashboard**
1. Go to [Shopify Partner Dashboard](https://partners.shopify.com/)
2. Find your app
3. Go to **App setup**
4. Update these URLs:
   - **App URL:** `http://localhost:8000`
   - **Allowed redirection URLs:** `http://localhost:8000/app/shopify/callback`

### **Step 3: Restart Your Server**
```bash
# Stop current server (Ctrl+C)
# Then restart:
php artisan serve --host=127.0.0.1 --port=8000
```

### **Step 4: Test Again**
1. Go to `http://localhost:8000/login` (note: localhost, not 127.0.0.1)
2. Login with your credentials
3. Go to Settings
4. Try connecting a store again

## 🔍 **Alternative: Use ngrok for Public URL**

If localhost doesn't work, use ngrok:

### **Install ngrok:**
```bash
# Download from https://ngrok.com/download
# Or use chocolatey on Windows:
choco install ngrok
```

### **Start ngrok:**
```bash
ngrok http 8000
```

### **Update URLs:**
- **App URL:** `https://your-ngrok-url.ngrok.io`
- **Redirect URI:** `https://your-ngrok-url.ngrok.io/app/shopify/callback`

## ✅ **Expected Result**

After fixing, the OAuth flow should work:
1. Click "Connect Store"
2. Enter shop domain (e.g., "your-store")
3. Redirect to Shopify authorization
4. Authorize the app
5. Redirect back to your app
6. Shop connected successfully!

## 🆘 **Still Having Issues?**

If you're still getting errors, check:
1. **Shopify Partner Dashboard** - Make sure URLs match exactly
2. **.env file** - Verify all variables are set
3. **Server restart** - Restart after changing .env
4. **URL format** - Use exact same URL in both places

The key is making sure the redirect URI in your Shopify app settings **exactly matches** the URL you're using to access your app!
