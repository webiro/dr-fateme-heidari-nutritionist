// Parsa Diet Plan - v0.1
document.addEventListener('DOMContentLoaded', () => {
  const btnPrint = document.getElementById('btnPrint');
  const btnTop = document.getElementById('btnTop');

  if (btnPrint) {
    btnPrint.addEventListener('click', () => {
      window.print();
    });
  }

  if (btnTop) {
    btnTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  console.log('برنامه غذایی پارسا — نسخه ۰٫۱ آماده است 🎾');
});
