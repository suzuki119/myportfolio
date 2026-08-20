/**
 * タイムラインの縦線をスクロールに合わせて伸ばす（--tl-line-height）。
 *
 * 3Dとは関係ないが、もともと bg.js に同居していたぶんをそのまま持ってきている。
 * script.js に移してもよい。
 */
export function initTimelineLine() {
  const wrap = document.querySelector(".timeline__wrap");
  if (!wrap) return;

  const update = () => {
    const rect = wrap.getBoundingClientRect();
    const scrolled = window.innerHeight / 2 - rect.top;
    const height = Math.max(0, Math.min(wrap.offsetHeight, scrolled));
    wrap.style.setProperty("--tl-line-height", height + "px");
  };

  window.addEventListener("scroll", update, { passive: true });
  update();
}
