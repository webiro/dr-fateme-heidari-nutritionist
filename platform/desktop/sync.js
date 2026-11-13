/* Sincroniza (sync) — نسخه وب را داخل باندل دسکتاپ کپی می‌کند.
 * منبع حقیقت واحد: platform/index.htm + platform/images/
 * CI هم همین کار را می‌کند؛ این اسکریپت برای اجرای محلی است.
 */
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const dest = path.join(__dirname, 'app');

fs.mkdirSync(path.join(dest, 'images'), { recursive: true });
fs.mkdirSync(path.join(dest, 'vendor'), { recursive: true });
fs.copyFileSync(path.join(root, 'index.htm'), path.join(dest, 'index.htm'));

// images
const imgDir = path.join(root, 'images');
for(const f of fs.readdirSync(imgDir)){
  if(f.toLowerCase().endsWith('.png')){
    fs.copyFileSync(path.join(imgDir, f), path.join(dest, 'images', f));
  }
}

// vendor (کتابخانه‌ها + فونت‌ها) — کپی کامل و بازگشتی برای کار آفلاین کامل
fs.cpSync(path.join(root, 'vendor'), path.join(dest, 'vendor'), { recursive: true });

console.log('✓ web source synced into desktop/app (index.htm + images + vendor)');

