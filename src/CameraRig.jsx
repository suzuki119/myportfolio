import { useRef } from "react";
import { useFrame, useThree } from "@react-three/fiber";
import * as THREE from "three";
import { CAMERA_DAMPING, SECTIONS } from "./config.js";

// 毎フレームの計算で使い回す
const _camA = new THREE.Vector3();
const _camB = new THREE.Vector3();
const _pan = new THREE.Vector3();
const _dir = new THREE.Vector3();
const _right = new THREE.Vector3();
const _up = new THREE.Vector3();
const WORLD_UP = new THREE.Vector3(0, 1, 0);

/**
 * セクションの screen: [x, y] を、カメラを平行移動させる量に変換する。
 *
 * x / y は画面の中心を 0、右端（上端）を 1 とした割合。実際の移動量は
 * 「被写体のある平面で画面の半分が何ワールド単位か」から毎フレーム求めるので、
 * ウィンドウ幅が変わってもキャラクターは画面上の同じ位置に居続ける。
 *
 * カメラと注視点を同じだけ動かすため、LP から引き継いだアングルは変わらない。
 * 変わるのは「画面のどこに写るか」だけ。
 */
function computePan(section, camera, out) {
  const [sx, sy] = section.screen ?? [0, 0];
  out.set(0, 0, 0);
  if (sx === 0 && sy === 0) return out;

  _camA.fromArray(section.camera);
  _camB.fromArray(section.target);
  _dir.subVectors(_camB, _camA);
  const distance = _dir.length();
  if (distance < 1e-6) return out;
  _dir.divideScalar(distance);

  const halfH = Math.tan(THREE.MathUtils.degToRad(camera.fov) / 2) * distance;
  const halfW = halfH * camera.aspect;

  _right.crossVectors(_dir, WORLD_UP);
  if (_right.lengthSq() < 1e-8) return out; // 真上／真下を向いている場合
  _right.normalize();
  _up.crossVectors(_right, _dir).normalize();

  // カメラを右へ動かすと被写体は画面の左へ動くので、符号は逆になる
  return out
    .copy(_right)
    .multiplyScalar(-sx * halfW)
    .addScaledVector(_up, -sy * halfH);
}

/**
 * セクションに合わせてカメラを動かす。<Canvas> の中に置く。
 *
 * 目標は「いまのセクションの camera / target」そのもので、隣のセクションとの
 * 補間はしない。つまりスクロールしてもカメラは動かず、セクションが
 * 切り替わった瞬間から新しい位置へ寄り始める（＝クリップの切り替えと同時）。
 * 寄せ方はスクロール量ではなく時間で進むので、スクロールを止めても動き切る。
 */
export function CameraRig({ index }) {
  const camera = useThree((state) => state.camera);
  const lookAt = useRef(new THREE.Vector3(...SECTIONS[0].target));
  const snap = useRef(true); // 最初の1フレームだけ補間せず即座に合わせる
  const section = SECTIONS[index];

  useFrame((_, delta) => {
    computePan(section, camera, _pan);
    _camA.fromArray(section.camera).add(_pan);
    _camB.fromArray(section.target).add(_pan);

    // フレームレートに依存しない指数減衰で目標へ寄せる。
    // delta を頭打ちにしているのは、裏タブから戻った直後に飛ばないようにするため。
    const k = snap.current
      ? 1
      : 1 - Math.exp(-CAMERA_DAMPING * Math.min(delta, 1 / 30));
    snap.current = false;

    camera.position.lerp(_camA, k);
    lookAt.current.lerp(_camB, k);
    camera.lookAt(lookAt.current);
  });

  return null;
}

export default CameraRig;
