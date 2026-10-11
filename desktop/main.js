import { app, BrowserWindow, Menu, shell, ipcMain, Tray, nativeImage, dialog } from 'electron';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';
import { spawn } from 'child_process';
import http from 'http';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

let mainWindow = null;
let tray = null;
let phpServerProcess = null;

// Determine target dashboard URL:
// 1. If RAILWAY_URL or DESKTOP_APP_URL is specified in environment
// 2. Otherwise local development server http://127.0.0.1:8000
const DEFAULT_LOCAL_URL = 'http://127.0.0.1:8000/dashboard/1/orders';
const TARGET_URL = process.env.DESKTOP_APP_URL || process.env.RAILWAY_URL || DEFAULT_LOCAL_URL;

function checkUrlLive(url, timeoutMs = 1200) {
    return new Promise((resolve) => {
        try {
            const u = new URL(url);
            const req = http.request({
                hostname: u.hostname,
                port: u.port || 80,
                path: u.pathname || '/',
                method: 'HEAD',
                timeout: timeoutMs
            }, (res) => {
                resolve(true);
            });
            req.on('error', () => resolve(false));
            req.on('timeout', () => { req.destroy(); resolve(false); });
            req.end();
        } catch (e) {
            resolve(false);
        }
    });
}

async function ensureLocalServerRunning() {
    const isLive = await checkUrlLive('http://127.0.0.1:8000');
    if (isLive) {
        console.log('[Desktop] Existing server detected on port 8000.');
        return;
    }

    // Auto-launch php artisan serve locally
    console.log('[Desktop] Starting background local PHP server...');
    phpServerProcess = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=8000'], {
        cwd: rootDir,
        stdio: 'ignore',
        detached: false,
        shell: true
    });

    // Wait until server is ready
    for (let i = 0; i < 20; i++) {
        await new Promise(r => setTimeout(r, 400));
        const ready = await checkUrlLive('http://127.0.0.1:8000');
        if (ready) {
            console.log('[Desktop] Local PHP server is ready.');
            return;
        }
    }
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1440,
        height: 900,
        minWidth: 1024,
        minHeight: 650,
        title: 'Grillcafe — Restaurant Command Center',
        backgroundColor: '#070A08',
        autoHideMenuBar: true,
        show: false,
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true,
            preload: path.join(__dirname, 'preload.cjs'),
            spellcheck: false,
        }
    });

    // Remove default menu bar for clean modern POS interface
    Menu.setApplicationMenu(null);

    // Load splash/loading or target url
    mainWindow.loadURL(TARGET_URL);

    mainWindow.once('ready-to-show', () => {
        mainWindow.show();
        mainWindow.focus();
    });

    // Handle external links (open in default browser instead of electron)
    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        if (url.startsWith('https://wa.me/') || url.startsWith('mailto:') || url.startsWith('tel:')) {
            shell.openExternal(url);
            return { action: 'deny' };
        }
        return { action: 'allow' };
    });

    // Handle failure to connect with friendly retry
    mainWindow.webContents.on('did-fail-load', (event, errorCode, errorDescription) => {
        if (errorCode === -102 || errorCode === -105 || errorCode === -106) {
            setTimeout(() => {
                if (mainWindow && !mainWindow.isDestroyed()) {
                    mainWindow.loadURL(TARGET_URL);
                }
            }, 1500);
        }
    });

    mainWindow.on('closed', () => {
        mainWindow = null;
    });
}

// Single instance lock (prevent multiple windows)
const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
    app.quit();
} else {
    app.on('second-instance', () => {
        if (mainWindow) {
            if (mainWindow.isMinimized()) mainWindow.restore();
            mainWindow.focus();
        }
    });

    app.whenReady().then(async () => {
        // If targeting local server, make sure it is booted
        if (TARGET_URL.includes('127.0.0.1') || TARGET_URL.includes('localhost')) {
            await ensureLocalServerRunning();
        }

        createWindow();

        app.on('activate', () => {
            if (BrowserWindow.getAllWindows().length === 0) createWindow();
        });
    });
}

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        if (phpServerProcess) {
            try { phpServerProcess.kill(); } catch (e) {}
        }
        app.quit();
    }
});

app.on('before-quit', () => {
    if (phpServerProcess) {
        try { phpServerProcess.kill(); } catch (e) {}
    }
});
