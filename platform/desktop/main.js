/* Tisa Platform — Electron main process
 * همان نسخه وب (platform/index.htm) در پوشه app/ باندل می‌شود (توسط sync.js یا CI).
 * اسپلش ثابت ۵ ثانیه → پنجره اصلی تمام‌صفحه → آپدیت خودکار از GitHub Releases.
 */
const { app, BrowserWindow, ipcMain, net } = require('electron');
const path = require('path');
const fs = require('fs');
const { autoUpdater } = require('electron-updater');

const SMOKE = process.env.TISA_SMOKE || '';
const SPLASH_MS = SMOKE ? 600 : 5000; // در حالت عادی همیشه ۵ ثانیه
const SPLASH_SHOT = process.env.TISA_SMOKE_SPLASH || '';

/* همان whitelist ورکر — درخواست‌های AI از پروسه اصلی بدون محدودیت CORS عبور می‌کنند */
const AI_ALLOWED = new Set([
  'https://console.paasta.cloud/api/ai/v1/chat/completions',
  'https://console.paasta.cloud/api/ai/v1/models',
  'https://openrouter.ai/api/v1/chat/completions',
  'https://openrouter.ai/api/v1/models',
]);

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
    width: 1280,
    height: 860,
    minWidth: 1024,
    minHeight: 680,
    show: false,
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
        if(!SMOKE) win.maximize(); // ماکسیمایز؛ کاربر می‌تواند restore کند (F11 هم تمام‌صفحه)
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

/* خواندن امن فایل از داخل پوشه اپ (برای inline کردن عکس‌ها در PDF/HTML روی file://) */
ipcMain.handle('app:read-data-url', async (_e, relPath)=>{
  try{
    const rel = String(relPath || '').replace(/^\/+/, '');
    const appDir = path.join(__dirname, 'app');
    const abs = path.resolve(appDir, rel);
    if(!abs.startsWith(appDir + path.sep)) throw new Error('forbidden');
    const ext = path.extname(abs).toLowerCase();
    const mime = ext === '.png' ? 'image/png' : (ext === '.svg' ? 'image/svg+xml' : 'image/jpeg');
    const buf = fs.readFileSync(abs);
    return { ok:true, dataUrl: 'data:' + mime + ';base64,' + buf.toString('base64') };
  }catch(e){
    return { ok:false };
  }
});
ipcMain.handle('updater:check', async ()=>{
  if(!app.isPackaged){
    return { status:'dev', message:'در حالت توسعه آپدیت بررسی نمی‌شود.' };
  }
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

/* شبکه AI — بدون CORS (از پروسه اصلی) */
ipcMain.handle('ai:network', async (_e, opts)=>{
  const url = String((opts && opts.url) || '');
  if(!AI_ALLOWED.has(url)) return { status: 403, text: JSON.stringify({ error:'url_not_allowed' }) };
  const key = String((opts && opts.key) || '');
  const raw = opts && opts.body;
  // رندرر body را به‌صورت رشته JSON می‌فرستد؛ اگر شیء بود هم پذیرفته می‌شود
  const bodyStr = typeof raw === 'string' ? raw : (raw ? JSON.stringify(raw) : null);
  try{
    const res = await net.fetch(url, {
      method: bodyStr ? 'POST' : 'GET',
      headers: {
        'Authorization': 'Bearer ' + key,
        'Content-Type': 'application/json'
      },
      body: bodyStr || undefined
    });
    const text = await res.text();
    return { status: res.status, text };
  }catch(e){
    return { status: 502, text: JSON.stringify({ error: String((e && e.message) || e) }) };
  }
});

app.on('second-instance', ()=>{
  if(win){
    if(win.isMinimized()) win.restore();
    win.show();
    win.focus();
  }
});
app.on('window-all-closed', ()=> app.quit());
