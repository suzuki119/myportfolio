/* ============================================================
   TOP背景の設定値

   もとの LP（WebGl演習 12/lp）の App.jsx にあった定数を、
   このサイトのセクション構成に合わせて置き換えたもの。
============================================================ */
import * as THREE from "three";

// GLB本来の色ではなく、サイトの配色（半透明の金）に寄せたい場合は true
export const USE_SITE_MATERIAL = false;

// パーティクルを出すか
export const SHOW_PARTICLES = false;

// ページのURLからの相対パス。index.php と models/ は同じ階層にあるので、
// /myportfolio/ 以下のどのページから読んでも同じ場所を指す。
export const MODEL_URL = "./models/zintai.glb";

/**
 * スクロール順に並べる。上から順に判定して「越えているうち一番あと」を採用するので、
 * 並び順を崩すとセクション判定がおかしくなる。
 * camera / target は LP の値をそのまま流用している（＝アングルは LP のまま）。
 * LP は header / main / footer の3段だったが、このサイトは5段。
 *
 * screen: [x, y] … キャラクターを画面のどこに置くか。中心が 0、右端／上端が 1。
 *   LP はページ全体が3Dの上に乗る前提だったので中央でよかったが、こちらは
 *   本文の背後に入るため、見出しや本文と重ならない位置へ逃がしている。
 *   カメラと注視点を同じだけ平行移動させるだけなので、アングルは変わらない。
 *   実際の移動量は画面サイズから毎フレーム計算する（CameraRig.jsx）ので、
 *   ウィンドウ幅を変えても画面上の位置は変わらない。
 */
export const SECTIONS = [
  // ヒーロー：中央のロゴ（SUZUKI PORTFOLIO）を避けて右下に立たせる
  { trigger: ".main-visual", anchor: "top",    name: "tati",    camera: [0, 0.3, 5.5],    target: [0, 0.0, 0],  screen: [0.75, -0.6] },
  // 作品一覧：カード2列の間（gap）に入るので中央のまま
  { trigger: ".works",       anchor: "top",    name: "run",     camera: [-0.8, 0.5, 4.2], target: [0, 0.3, 0],  screen: [0, 0] },
  // アバウト：不透明なカードが全幅を覆うため、どこに置いても見えない
  { trigger: ".about",       anchor: "center", name: "fastrun", camera: [3.6, 1.2, 0.8],  target: [0, 0.5, 0],  screen: [0, 0] },
  // タイムライン：中央の縦線に沿わせたいので左右は動かさず、見出しの下へ落とす
  { trigger: ".timeline",    anchor: "top",    name: "run",     camera: [-2.4, 0.8, 3.4], target: [0, 0.3, 0],  screen: [0, -0.5] },
  // フッター：Contact の見出し・メールアドレスの右へ寄せる
  { trigger: ".footer",      anchor: "top",    name: "tukare",  camera: [0.9, -0.5, 2.4], target: [0, -0.4, 0], screen: [0.58, -0.05] },
];

// セクションの基準点がこの高さ（画面の上から何割か）を越えたら切り替える
export const TRIGGER_LINE = 0.75;

// 集中線を出すアニメーション
export const RUNNING_ANIMATIONS = new Set(["run", "fastrun"]);

// クリップを切り替えるときのクロスフェード秒数（0 にすると即座に切り替わる）
export const FADE_DURATION = 0.4;

export const CAMERA_DAMPING = 5;

// 集中線の発生源。キャラクターの胸のあたり・少し奥
export const SPEED_LINE_ORIGIN = [0, 0.3, -1];

// モデルのローカル座標で「正面」を指す軸。zintai.glb は +Z が正面。
// 実際の向きはこの軸をモデルのワールド回転で回して求めるので、
// <Zintai rotation> を変えてもここは触らなくてよい。
export const CHARACTER_FORWARD_AXIS = new THREE.Vector3(0, 0, 1);
