@echo off
title Grillcafe POS Desktop App
echo ========================================================
echo   Starting Grillcafe POS Desktop Application...
echo ========================================================
cd /d "%~dp0"

REM Run npm run desktop silently in Electron
npm run desktop
