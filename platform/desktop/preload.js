/* Tisa Platform — preload bridge
 * پل امن بین نسخه وب (index.htm) و Electron؛ روی وب وجود ندارد پس هوک دسکتاپ فعال نمی‌شود.
 */
const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('TisaDesktop', {
  isDesktop: true,
  getVersion: () => ipcRenderer.invoke('app:version'),
  checkForUpdates: () => ipcRenderer.invoke('updater:check'),
  installUpdate: () => ipcRenderer.send('updater:install'),
  onUpdateStatus: (cb) => {
    ipcRenderer.on('updater:status', (_e, s) => { try { cb(s); } catch(err){} });
  }
});
