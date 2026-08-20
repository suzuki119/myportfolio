/* ============================================================
   TOP — 背景（スクロール連動の zintai アニメーション）

   もとは WebGl演習 12/lp（LP）の src/App.jsx。
   LP はページ全体を React が描いていたが、このサイトは PHP が HTML を
   出力するので、React が持つのは <Canvas> ひとつだけにしてある。
   （#canvas-container にマウント。ページの中身には一切触らない）

   LP からの変更点：
     ・ページのマークアップ（header / main / footer / AnimatedCard）を削除
     ・gsap / ScrollTrigger → scroll イベント（このサイトは gsap を読み込んでいない）
     ・drei の <Environment preset="city" /> → RoomEnvironment（CDNへ取りに行かない）
     ・セクションを .main-visual / .works / .about / .timeline / .footer の5段に
     ・集中線の色を $color-text（#2c2a2a）に。LPと同じく背景が明るい配色なので、
       AdditiveBlending にすると線が飛んで見えなくなる
============================================================ */
import { Suspense, useRef } from "react";
import { Canvas } from "@react-three/fiber";
import * as THREE from "three";
import {
  CHARACTER_FORWARD_AXIS,
  RUNNING_ANIMATIONS,
  SECTIONS,
  SHOW_PARTICLES,
  SPEED_LINE_ORIGIN,
} from "./config.js";
import { useSectionIndex } from "./useSectionIndex.js";
import { CameraRig } from "./CameraRig.jsx";
import { Particles } from "./Particles.jsx";
import { RoomEnv } from "./RoomEnv.jsx";
import { SpeedLines } from "./SpeedLines.jsx";
import { Zintai } from "./Zintai.jsx";

function App() {
  // いま見ているセクション番号。カメラもクリップも集中線もここから決める。
  const sectionIndex = useSectionIndex();
  const animationName = SECTIONS[sectionIndex].name;
  // 集中線が体の向きを読むためのモデル参照
  const zintaiRef = useRef();

  return (
    <Canvas
      camera={{ fov: 75, position: SECTIONS[0].camera }}
      // 背景色は塗らない（alpha でCSSの黒背景を透かす）
      gl={{ alpha: true, antialias: true }}
      // 高DPI端末で描画量が跳ね上がるのを防ぐ
      dpr={[1, 2]}
    >
      <RoomEnv />
      <CameraRig index={sectionIndex} />

      {/* GLBの読み込み中は何も描かない。失敗しても背景が出ないだけでページは動く */}
      <Suspense fallback={null}>
        <Zintai
          animation={animationName}
          modelRef={zintaiRef}
          position={[0, -1, 0]}
          scale={1.2}
          rotation={[0, 0, 0]}
        />
      </Suspense>

      <SpeedLines
        active={RUNNING_ANIMATIONS.has(animationName)}
        origin={SPEED_LINE_ORIGIN}
        followCamera
        motionSource={zintaiRef}
        motionAxis={CHARACTER_FORWARD_AXIS}
        frontAngle={22}
        sideAngle={50}
        trailSpread={2.2}
        // 背景が明るい（$color-bg: #f4f1f1）ので、暗い色＋NormalBlending。
        // #canvas-container 自体に opacity: 0.85 がかかっているぶん、
        // ここの opacity は控えめにしても十分見える。
        color={0x2c2a2a}
        blending={THREE.NormalBlending}
        opacity={0.45}
        width={0.035}
        spawnRate={90}
        maxParticles={200}
        maxRadius={5}
        minLength={0.8}
        maxLength={2.0}
      />

      {SHOW_PARTICLES && <Particles num={1000} size={0.05} spread={4} />}
    </Canvas>
  );
}

export default App;
