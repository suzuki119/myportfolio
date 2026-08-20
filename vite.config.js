/* ============================================================
   TOP背景（src/）を dist/bg.js にまとめる設定

   このサイトは PHP が HTML を出力するので、Vite の開発サーバー
   （localhost:5173）は使わない。MAMP で index.php を開いて確認する。
   src を編集したら以下のどちらかで dist/bg.js を作り直すこと。

     npm run build   … 1回だけビルド
     npm run watch   … 保存するたびに自動でビルド（開発中はこちら）
============================================================ */
import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { fileURLToPath } from "node:url";
import { dirname, resolve } from "node:path";

const root = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  root,
  // dist/bg.js から見た相対パスで解決させる
  base: "./",
  plugins: [react()],

  build: {
    outDir: resolve(root, "dist"),
    emptyOutDir: true,
    // index.html ではなく JS を入口にする（HTMLは PHP が出力するため）
    rollupOptions: {
      input: resolve(root, "src/main.jsx"),
      output: {
        // footer.php から <script type="module" src="dist/bg.js"> で読むので
        // ファイル名にハッシュを付けない（PHP側を毎回書き換えずに済む）
        entryFileNames: "bg.js",
        chunkFileNames: "bg-[name].js",
        assetFileNames: "bg-[name][extname]",
      },
    },
  },
});
