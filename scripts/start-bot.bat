@echo off
REM Restaurant Bot - Quick Start
REM Powered by Groq AI (llama-3.3-70b-versatile)

cls
echo.
echo ========================================
echo   Restaurant Bot - AI Version (Groq)
echo   Powered by Llama 3.3 via Groq
echo ========================================
echo.

REM Check for Groq API key in .env.llm
if not exist ".env.llm" (
    echo.
    echo ERROR: .env.llm file not found!
    echo.
    echo Steps to fix:
    echo.
    echo 1. Copy .env.llm.example to .env.llm
    echo 2. Go to: https://console.groq.com/keys
    echo 3. Create API Key and paste it in .env.llm:
    echo.
    echo    GROQ_API_KEY=gsk_your_key_here
    echo.
    pause
    exit /b 1
)

echo Starting bot...
echo.
echo Features:
echo - Understands Urdu, English, any language
echo - AI-powered natural responses (Groq / Llama)
echo - Order tracking with codes
echo - Notifies restaurant owner via WhatsApp
echo.
echo Launching...
echo.

cd /d "%~dp0.."
node bot-waiter-v2.js

pause
