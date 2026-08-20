import { useEffect, useState } from "react";
import { SECTIONS, TRIGGER_LINE } from "./config.js";

/**
 * いま見ているセクションの番号（SECTIONS の添字）を返す。
 *
 * 各セクションの基準点が画面の TRIGGER_LINE を越えたかどうかだけを見るので、
 * 戻り値はスクロール量に対して連続ではなく、境界を越えた瞬間に 1 段ずつ変わる。
 * カメラもクリップも集中線もこの 1 つの値から決めるので、三者がズレることがない。
 *
 * LP では gsap の ScrollTrigger で測り直していたが、このサイトは gsap を
 * 読み込んでいないので scroll / resize イベントで代用している。毎回
 * getBoundingClientRect() を取り直すため、画像の読み込みでレイアウトが
 * 伸びてもズレない。
 *
 * 判定対象は React の外（PHP が出力した DOM）にあるので、ref ではなく
 * document.querySelector で拾う。
 */
export function useSectionIndex() {
  const [index, setIndex] = useState(0);

  useEffect(() => {
    const elements = SECTIONS.map(({ trigger }) =>
      document.querySelector(trigger),
    );

    const measure = () => {
      const line = window.innerHeight * TRIGGER_LINE;

      let next = 0;
      for (let i = 0; i < SECTIONS.length; i++) {
        const el = elements[i];
        if (!el) continue;
        const rect = el.getBoundingClientRect();
        const point =
          SECTIONS[i].anchor === "center"
            ? rect.top + rect.height / 2
            : rect.top;
        if (point <= line) next = i;
      }
      // 同じ値なら React 側で再レンダリングは起きないので、毎スクロール呼んでよい
      setIndex(next);
    };

    measure(); // 途中スクロール位置でリロードされた場合に備えて初期化
    window.addEventListener("scroll", measure, { passive: true });
    window.addEventListener("resize", measure, { passive: true });
    return () => {
      window.removeEventListener("scroll", measure);
      window.removeEventListener("resize", measure);
    };
  }, []);

  return index;
}

/**
 * 要素が画面下から入りきるまでの進捗（0→1）を返す。
 * gsap の scrollTrigger（start:"top bottom" / end:"bottom bottom" / scrub）と同じ範囲。
 */
export function enterProgress(el) {
  if (!el) return 0;
  const rect = el.getBoundingClientRect();
  const p = (window.innerHeight - rect.top) / rect.height;
  return p < 0 ? 0 : p > 1 ? 1 : p;
}
