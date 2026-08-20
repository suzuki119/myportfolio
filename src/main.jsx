import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App.jsx";
import { initTimelineLine } from "./timelineLine.js";

/*
  LP では #root にページ全体を描いていたが、ここでは背景キャンバスだけを
  index.php の #canvas-container にマウントする。
  CSSリセット（destyle.css）は header.php が the-new-css-reset を読んでいるので import しない。
*/

// 背景を出すのはTOPページだけ。他のページで読み込まれても何もしない。
const container = document.getElementById("canvas-container");

if (document.querySelector("main.top") && container) {
  createRoot(container).render(
    <StrictMode>
      <App />
    </StrictMode>,
  );
}

initTimelineLine();
