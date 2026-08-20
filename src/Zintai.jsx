import { useEffect, useRef } from "react";
import { useGLTF, useAnimations } from "@react-three/drei";
import * as THREE from "three";
import { FADE_DURATION, MODEL_URL, USE_SITE_MATERIAL } from "./config.js";

useGLTF.preload(MODEL_URL);

/**
 * キャラクター本体。再生するクリップ名を props で受け取るだけにして、
 * 「どのセクションで何を再生するか」の判断は App 側に集約している。
 */
export function Zintai({ animation, modelRef, position, scale, rotation }) {
  // 集中線が体の向きを読めるよう、モデルの Object3D を親と共有する
  const ref = modelRef;
  const { scene, animations } = useGLTF(MODEL_URL);
  const { actions } = useAnimations(animations, ref);
  const currentRef = useRef(null); // いま再生中の AnimationAction

  useEffect(() => {
    scene.traverse((obj) => {
      if (!obj.isMesh && !obj.isSkinnedMesh) return;

      // スキンメッシュの境界球はバインドポーズのまま更新されないため、
      // 走りで手足が外に出ると画面内でもカリングされてモデルごと消える。
      obj.frustumCulled = false;

      if (USE_SITE_MATERIAL) {
        // GLB本来の色ではなく、サイトの配色（半透明の金）に寄せる
        obj.material = new THREE.MeshStandardMaterial({
          color: "#a25c00",
          metalness: 0.1,
          roughness: 0.7,
          transparent: true,
          opacity: 0.38,
          flatShading: true,
        });
      }
    });
  }, [scene]);

  useEffect(() => {
    const next = actions[animation];
    if (!next) return;

    // 同じクリップでも「実際に再生中か」まで確かめる。
    // StrictMode の二重マウントでは drei が一度 mixer をリセットするため、
    // 参照が同じというだけで飛ばすと誰も再生されずバインドポーズになる。
    const previous = currentRef.current;
    if (previous === next && next.isRunning()) return;

    // 直前のクリップだけをフェードアウトさせ、次のクリップをフェードイン。
    // 2つ以上のクリップの重みが混ざらないので、姿勢が崩れない。
    next
      .reset()
      .setLoop(THREE.LoopRepeat, Infinity)
      .setEffectiveWeight(1)
      .fadeIn(FADE_DURATION)
      .play();
    if (previous && previous !== next) previous.fadeOut(FADE_DURATION);
    currentRef.current = next;
  }, [actions, animation]);

  return (
    <primitive
      ref={ref}
      object={scene}
      position={position}
      scale={scale}
      rotation={rotation}
    />
  );
}

export default Zintai;
