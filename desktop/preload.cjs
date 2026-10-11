// Preload script for secure isolated communication
const { contextBridge } = require('electron');

contextBridge.exposeInMainWorld('desktopApp', {
    platform: process.platform,
    isDesktop: true,
    version: '1.0.0'
});
