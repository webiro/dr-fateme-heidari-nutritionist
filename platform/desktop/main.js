/* Tisa Platform — Electron main process
 * همان نسخه وب (platform/index.htm) در پوشه app/ باندل می‌شود (توسط sync.js یا CI).
 * اسپلش ثابت ۵ ثانیه → پنجره اصلی تمام‌صفحه → آپدیت خودکار از GitHub Releases.
 */
const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');
const fs = require('fs');
const { autoUpdater } = require('electron-updater');

const SMOKE = process.env.TISA_SMOKE || '';
const SPLASH_MS = SMOKE ? 600 : 5000; // در حالت عادی همیشه ۵ ثانیه
const SPLASH_SHOT = process.env.TISA_SMOKE_SPLASH || '';

let splash = null;
let win = null;

const gotLock = app.requestSingleInstanceLock();
if(!gotLock){
  app.quit();
}

function sendStatus(s){
  if(win && !win.isDestroyed()){
    win.webContents.send('updater:status', s);
  }
}

function createSplash(){
  splash = new BrowserWindow({
    fullscreen: !SMOKE,
    frame: false,
    resizable: false,
    movable: false,
    show: !SMOKE,
    backgroundColor: '#101a16',
    webPreferences: { contextIsolation: true, nodeIntegration: false, spellcheck: false }
  });
  if(SMOKE){
    splash.setBounds({ x: 40, y: 40, width: 900, height: 640 });
    splash.showInactive();
  }
  splash.loadFile(path.join(__dirname, 'splash.html'));
  splash.on('closed', ()=>{ splash = null; });
}

function createMain(){
  win = new BrowserWindow({
    width: 1440,
    height: 900,
    minWidth: 1024,
    minHeight: 680,
    show: false,
    fullscreen: !SMOKE,
    autoHideMenuBar: true,
    backgroundColor: '#fafaf7',
    title: 'Tisa Platform',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      spellcheck: false
    }
  });
  win.setMenuBarVisibility(false);
  win.webContents.on('before-input-event', (e, input)=>{
    if(input.type === 'keyDown' && input.key === 'F11'){
      win.setFullScreen(!win.isFullScreen());
      e.preventDefault();
    }
  });
  win.loadFile(path.join(__dirname, 'app', 'index.htm'));
  win.on('closed', ()=>{ win = null; });
}

app.whenReady().then(()=>{
  createSplash();
  createMain();
  const t0 = Date.now();
  let opened = false;
  const openMain = ()=>{
    if(opened) return;
    opened = true;
    const wait = Math.max(0, SPLASH_MS - (Date.now() - t0));
    setTimeout(()=>{
      if(splash && !splash.isDestroyed()) splash.close();
      if(win && !win.isDestroyed()){
        win.show();
        if(!SMOKE) win.focus();
      }
    }, wait);
  };
  // تا وقتی صفحه لود نشده صبر کن؛ بعد از SPLASH_MS حتماً باز کن (fallback)
  win.webContents.once('did-finish-load', openMain);
  setTimeout(openMain, SPLASH_MS);

  // اسپلش‌شات برای تست دود (smoke)
  if(SMOKE && SPLASH_SHOT){
    setTimeout(async ()=>{
      try{
        const img = await splash.webContents.capturePage();
        fs.writeFileSync(SPLASH_SHOT, img.toPNG());
      }catch(e){ console.error('splash shot failed:', e.message); }
    }, 300);
  }
  // اسکرین‌شات پنجره اصلی و خروج — فقط برای تست دود
  if(SMOKE){
    setTimeout(async ()=>{
      try{
        const img = await win.webContents.capturePage();
        fs.writeFileSync(SMOKE, img.toPNG());
        console.log('smoke shot saved:', SMOKE);
      }catch(e){ console.error('main shot failed:', e.message); }
      app.quit();
    }, SPLASH_MS + 2000);
  }
});

/* ---------- Auto-update از GitHub Releases ---------- */
autoUpdater.autoDownload = true;
autoUpdater.autoInstallOnAppQuit = true;
['checking-for-update','update-available','update-not-available','download-progress','update-downloaded','error'].forEach(ev=>{
  autoUpdater.on(ev, (info)=>{
    if(ev === 'checking-for-update') sendStatus({ state:'checking' });
    else if(ev === 'update-available') sendStatus({ state:'available', version: info && info.version });
    else if(ev === 'update-not-available') sendStatus({ state:'not-available', version: app.getVersion() });
    else if(ev === 'download-progress') sendStatus({ state:'downloading', percent: info ? info.percent : 0 });
    else if(ev === 'update-downloaded') sendStatus({ state:'downloaded', version: info && info.version });
    else if(ev === 'error') sendStatus({ state:'error', message: String((info && info.message) || info || '') });
  });
});
ipcMain.handle('app:version', ()=> app.getVersion());
ipcMain.handle('updater:check', async ()=>{
  try{
    const r = await autoUpdater.checkForUpdates();
    return { status:'ok', version: r && r.update ? r.update.version : undefined };
  }catch(e){
    const msg = String((e && e.message) || e);
    sendStatus({ state:'error', message: msg });
    return { status:'error', message: msg };
  }
});
ipcMain.on('updater:install', ()=>{
  autoUpdater.quitAndInstall(false, true);
});

app.on('second-instance', ()=>{
  if(win){
    if(win.isMinimized()) win.restore();
    win.show();
    win.focus();
  }
});
app.on('window-all-closed', ()=> app.quit());
