/* ============================================================
   共通 — メールコピーボタン
============================================================ */
const copyBtn   = document.querySelector('.contact__copy');
const emailLink = document.querySelector('.contact__email');

if (copyBtn) {
  // メールアドレスをコピーし、「コピーできました」表示＋ボタンを暗くする
  const copyEmail = () => {
    navigator.clipboard.writeText(copyBtn.dataset.email).then(() => {
      copyBtn.classList.add('copied');
      setTimeout(() => copyBtn.classList.remove('copied'), 2000);
    });
  };

  copyBtn.addEventListener('click', copyEmail);

  // メールアドレスの文字を押してもコピーできるようにする（mailto は開かない）
  if (emailLink) {
    emailLink.addEventListener('click', (e) => {
      e.preventDefault();
      copyEmail();
    });
  }
}

/* ============================================================
   共通 — ハンバーガーメニュー
============================================================ */
const navToggle = document.getElementById('nav-toggle');
const mainNav   = document.getElementById('main-nav');

if (navToggle && mainNav) {
  navToggle.addEventListener('click', () => {
    const isOpen = mainNav.classList.toggle('open');
    navToggle.classList.toggle('open', isOpen);
    navToggle.setAttribute('aria-label', isOpen ? 'メニューを閉じる' : 'メニューを開く');
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  mainNav.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      mainNav.classList.remove('open');
      navToggle.classList.remove('open');
      navToggle.setAttribute('aria-label', 'メニューを開く');
      document.body.style.overflow = '';
    });
  });
}

/* ============================================================
   TOP — Three.js 背景
   three.js r160（ESM）で dash の zintai アニメーションを描画する。
   実装は bg.js（index.php からのみ読み込む）。
   タイムライン線のスクロール連動も bg.js 側に移した。
============================================================ */


/* ============================================================
   WORKS — カテゴリフィルター
============================================================ */
if (document.querySelector('main.works')) {
  const FADE_MS   = 220;
  const filterBtns = document.querySelectorAll('.works__filter-btn');
  const cards      = document.querySelectorAll('.works__card[data-category]');
  const countEl    = document.getElementById('works-count');
  const total      = cards.length;

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      const filter = btn.dataset.filter;
      let visible  = 0;

      cards.forEach(card => {
        const match = filter === 'all' || card.dataset.category === filter;
        if (match) {
          card.style.display = '';
          requestAnimationFrame(() => {
            requestAnimationFrame(() => card.classList.remove('is-hiding'));
          });
          visible++;
        } else {
          card.classList.add('is-hiding');
          setTimeout(() => {
            if (card.classList.contains('is-hiding')) card.style.display = 'none';
          }, FADE_MS);
        }
      });

      if (countEl) countEl.textContent = visible + ' / ' + total;
    });
  });
}
