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
 * camera / target は LP の値をそのまま流用している。
 * LP は header / main / footer の3段だったが、このサイトは5段。
 */
export const SECTIONS = [
  { trigger: ".main-visual", anchor: "top",    name: "tati",    camera: [0, 0.3, 5.5],    target: [0, 0.0, 0] },
  { trigger: ".works",       anchor: "top",    name: "run",     camera: [-0.8, 0.5, 4.2], target: [0, 0.3, 0] },
  { trigger: ".about",       anchor: "center", name: "fastrun", camera: [3.6, 1.2, 0.8],  target: [0, 0.5, 0] },
  { trigger: ".timeline",    anchor: "top",    name: "run",     camera: [-2.4, 0.8, 3.4], target: [0, 0.3, 0] },
  { trigger: ".footer",      anchor: "top",    name: "tukare",  camera: [0.9, -0.5, 2.4], target: [0, -0.4, 0] },
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
