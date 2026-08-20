import { useMemo, useRef } from "react";
import { useFrame } from "@react-three/fiber";
import { enterProgress } from "./useSectionIndex.js";

/**
 * スクロールで拡大・回転するパーティクル。
 *
 * LP では gsap の scrollTrigger（scrub）で動かしていたぶんを、スクロール進捗
 * から直接出している。範囲は start:"top bottom" / end:"bottom bottom" と同じ。
 * 既定では出さない（config.js の SHOW_PARTICLES）。
 */
export function Particles({
  num = 1000,
  spread = 4,
  size = 0.05,
  color = "#b8a88a", // LPは紫（#7a3bff）。サイトの配色に合わせて金にしている
  scaleTrigger = ".works",
  rotateTrigger = ".about",
}) {
  const ref = useRef();

  const positions = useMemo(() => {
    const pos = new Float32Array(num * 3);
    for (let i = 0; i < num * 3; i++) {
      pos[i] = (Math.random() - 0.5) * spread;
    }
    return pos;
  }, [num, spread]);

  const targets = useMemo(
    () => ({
      scale: document.querySelector(scaleTrigger),
      rotate: document.querySelector(rotateTrigger),
    }),
    [scaleTrigger, rotateTrigger],
  );

  useFrame(() => {
    if (!ref.current) return;
    ref.current.scale.setScalar(1 + enterProgress(targets.scale) * 9); // 1 → 10
    ref.current.rotation.y = enterProgress(targets.rotate) * Math.PI * 2;
  });

  return (
    <points ref={ref}>
      <bufferGeometry>
        <bufferAttribute
          attach="attributes-position"
          count={positions.length / 3}
          array={positions}
          itemSize={3}
        />
      </bufferGeometry>

      <pointsMaterial
        size={size}
        color={color}
        sizeAttenuation
        transparent
        opacity={0.6}
        depthWrite={false}
      />
    </points>
  );
}

export default Particles;
