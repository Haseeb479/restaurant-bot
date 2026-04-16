@echo off
REM WhatsApp QR Quick Test Start
cd restaurant-bot

echo.
echo ========================================
echo   WhatsApp QR Bot - Quick Start
echo ========================================
echo.

echo [1/2] Verifying setup...
php artisan qr:verify

echo.
echo [2/2] Starting Laravel development server...
echo.
echo NOTE: Once running, follow these steps:
echo   1. Open new terminal
echo   2. Run: ngrok http 8000
echo   3. Copy ngrok URL to Meta app webhook settings
echo   4. Scan QR code in WhatsApp sandbox
echo   5. Send a message to test!
echo.
echo Starting server...
echo.

php artisan serve
