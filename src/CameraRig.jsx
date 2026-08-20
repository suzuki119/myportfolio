import { useRef } from "react";
import { useFrame, useThree } from "@react-three/fiber";
import * as THREE from "three";
import { CAMERA_DAMPING, SECTIONS } from "./config.js";

// 毎フレームの計算で使い回す
const _camA = new THREE.Vector3();
const _camB = new THREE.Vector3();

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
    _camA.fromArray(section.camera);
    _camB.fromArray(section.target);

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
