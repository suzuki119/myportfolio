import { useState, useRef, useMemo, useEffect } from 'react';
import { Canvas, useFrame, useThree } from '@react-three/fiber';
import { Environment, useGLTF, useAnimations } from '@react-three/drei';
import * as THREE from 'three';
import gsap from 'gsap';
import { useGSAP } from '@gsap/react';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SpeedLines } from './SpeedLines';
import './App.css';


gsap.registerPlugin(useGSAP, ScrollTrigger);
useGLTF.preload('./models/zintai.glb');

const SECTIONS = [
  { trigger: 'header', anchor: 'top',    name: 'tati',    camera: [0, 0.3, 5.5],    target: [0, 0.0, 0] },
  { trigger: 'main',   anchor: 'top',    name: 'run',     camera: [-0.8, 0.5, 4.2], target: [0, 0.3, 0] },
  { trigger: 'main',   anchor: 'center', name: 'fastrun', camera: [3.6, 1.2, 0.8],  target: [0, 0.5, 0] },
  { trigger: 'footer', anchor: 'top',    name: 'tukare',  camera: [0.9, -0.5, 2.4], target: [0, -0.4, 0] },
];

const TRIGGER_LINE = 0.75;

// 集中線を出すアニメーション
const RUNNING_ANIMATIONS = new Set(['run', 'fastrun']);

// クリップを切り替えるときのクロスフェード秒数（0 にすると即座に切り替わる）
const FADE_DURATION = 0.4;

const CAMERA_DAMPING = 5;

function Particles(props) {
  const ref = useRef();

  const count = props.num; // パーティクルの数
  const spread = props.spread || 4; // 拡散具合（値を渡さなかった場合は「4」）

  const positions = useMemo(() => {
    const pos = new Float32Array(count * 3);
    for (let i = 0; i < count * 3; i++) {
      pos[i] = (Math.random() - 0.5) * spread;
    }
    return pos;
  }, [count, spread]);

  useGSAP(() => {
    gsap.to(ref.current.scale, {
      x: 10, y: 10, z: 10,
      scrollTrigger: {
        trigger: ".sec1",
        start: "top bottom",
        end: "bottom bottom",
        scrub: true,
      }
    });
    gsap.to(ref.current.rotation, {
      y: Math.PI * 2,
      scrollTrigger: {
        trigger: ".sec2",
        start: "top bottom",
        end: "bottom bottom",
        scrub: true,
      }
    });
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
        size={props.size || 0.05}
        color={props.color}
        sizeAttenuation={true}
        transparent={true}
        depthWrite={false}
      />
    </points>
  );
}

/**
 * いま見ているセクションの番号(SECTIONS の添字)を返す。
 *
 * 各セクションの基準点が画面の TRIGGER_LINE を越えたかどうかだけを見るので、
 * 戻り値はスクロール量に対して連続ではなく、境界を越えた瞬間に 1 段ずつ変わる。
 * カメラもクリップも集中線もこの 1 つの値から決めるので、三者がズレることがない。
 *
 * SECTIONS はスクロール順に並んでいる前提。前のセクションから順に判定して
 * 「越えているうち一番あと」を採用するため、番号は必ず単調に増減する。
 */
function useSectionIndex() {
  const [index, setIndex] = useState(0);

  useGSAP(() => {
    const elements = SECTIONS.map(({ trigger }) => document.querySelector(trigger));

    const measure = () => {
      const line = window.innerHeight * TRIGGER_LINE;

      let next = 0;
      for (let i = 0; i < SECTIONS.length; i++) {
        const el = elements[i];
        if (!el) continue;
        const rect = el.getBoundingClientRect();
        const point =
          SECTIONS[i].anchor === 'center' ? rect.top + rect.height / 2 : rect.top;
        if (point <= line) next = i;
      }
      // 同じ値なら React 側で再レンダリングは起きないので、毎スクロール呼んでよい
      setIndex(next);
    };

    // ページ全体を1つのトリガーにして、スクロールのたびに測り直す。
    // onRefresh も拾うので、画像読み込みでレイアウトが伸びてもズレない。
    ScrollTrigger.create({
      trigger: document.documentElement,
      start: 'top top',
      end: 'bottom bottom',
      onUpdate: measure,
      onRefresh: measure,
    });
    measure(); // 途中スクロール位置でリロードされた場合に備えて初期化
  }, []);

  return index;
}

// CameraRig の毎フレーム計算で使い回す
const _camA = new THREE.Vector3();
const _camB = new THREE.Vector3();

/**
 * セクションに合わせてカメラを動かす。<Canvas> の中に置く。
 *
 * 目標は「いまのセクションの camera / target」そのもので、隣のセクションとの
 * 補間はしない。つまりスクロールしてもカメラは動かず、セクションが
 * 切り替わった瞬間から新しい位置へ寄り始める(＝クリップの切り替えと同時)。
 * 寄せ方はスクロール量ではなく時間で進むので、スクロールを止めても動き切る。
 */
function CameraRig({ index }) {
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

/**
 * キャラクター本体。再生するクリップ名を props で受け取るだけにして、
 * 「どのセクションで何を再生するか」の判断は App 側に集約している。
 */
function Zintai({ animation, modelRef, position, scale, rotation }) {
  // 集中線が体の向きを読めるよう、モデルの Object3D を親と共有する
  const ref = modelRef;
  const { scene, animations } = useGLTF('./models/zintai.glb');
  const { actions } = useAnimations(animations, ref);
  const currentRef = useRef(null); // いま再生中の AnimationAction

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
    next.reset().setLoop(THREE.LoopRepeat, Infinity).setEffectiveWeight(1).fadeIn(FADE_DURATION).play();
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

function AnimatedCard(props) {
  const ref = useRef();

  useGSAP(() => {
    gsap.from(ref.current, {

      transformOrigin: "center left",
      z: -600,
      rotationY: -65,
      opacity: 0,
      ease: "power1.out",

      scrollTrigger: {
        trigger: ref.current,
        start: "top bottom",     // カードの上端が画面下端に来たら開始
        end: "center 60%",    // カードの中心が画面中央に来たら完了
        scrub: true,
      },
    });
  }, { scope: ref });

  return (
    // 外側が「舞台」。ここに perspective を置くことで、消失点が
    // カードの transform-origin ではなく舞台の中央に固定される。
    <div className="animated-card-stage">
      <div className="animated-card" ref={ref}>
        {props.text}
      </div>
    </div>
  );
}

// 集中線の発生源。キャラクターの胸のあたり・少し奥に置く。
const SPEED_LINE_ORIGIN = [0, 0.3, -1];

// モデルのローカル座標で「正面」を指す軸。zintai.glb は +Z が正面。
// 実際の向きはこの軸をモデルのワールド回転で回して求めるので、
// <Zintai rotation> を変えてもここは触らなくてよい。
const CHARACTER_FORWARD_AXIS = new THREE.Vector3(0, 0, 1);

function App() {
  // いま見ているセクション番号。カメラもクリップも集中線もここから決める。
  const sectionIndex = useSectionIndex();
  const animationName = SECTIONS[sectionIndex].name;
  // 集中線が体の向きを読むためのモデル参照
  const zintaiRef = useRef();

  return (
    <>
	    <div className="canvas">
          <Canvas camera={{ position: SECTIONS[0].camera }}>
            <Environment preset="city" />
            <CameraRig index={sectionIndex} />
            <Zintai
              animation={animationName}
              modelRef={zintaiRef}
              position={[0, -1, 0]}
              scale={1.2}
              rotation={[0, 0, 0]}
            />
            <SpeedLines
              active={RUNNING_ANIMATIONS.has(animationName)}
              origin={SPEED_LINE_ORIGIN}
              followCamera
              motionSource={zintaiRef}
              motionAxis={CHARACTER_FORWARD_AXIS}
              frontAngle={22}
              sideAngle={50}
              trailSpread={2.2}
              color={0x000000}
              blending={THREE.NormalBlending}
              opacity={1}
              width={0.035}
              spawnRate={90}
              maxParticles={200}
              maxRadius={5}
              minLength={0.8}
              maxLength={2.0}
            />
          <Particles num={1000} color="#7a3bff" size={0.05} spread={4} />
          </Canvas>
      </div>

      <header>
        <div className="inner">
        <h1>TRIDENT<br></br>WEBDESIGN<br></br>CONFERENCE<br></br>2026</h1>
        <p>2026年8月9日(日)</p>
        </div>
      </header>

      <main>
        <div className="inner">
          {/* sec1 / sec3 はセクションのパネルごと回す。
              sec2 だけはパネルを固定して、中のカード2枚を個別に回す。 */}
          <AnimatedCard text={
            <section className="section sec1">
              <h2>未来のWebは、触れられる。</h2>
              <p>TRIDENT WEBDESIGN CONFERENCE 2026 は、「視覚を超える体験」をテーマに、次世代のWeb表現とその可能性に迫るカンファレンスです。</p>
              <p>WebGL、インタラクションデザイン、空間的なUI設計など、フロントエンドの新領域で活躍するクリエイターが集結し、未来のWebの「触感」を議論・共有します。</p>
              <p>現実とデジタルの境界が曖昧になる時代に、私たちが届けるべき体験とは何か──それを一緒に考え、かたちにする1日を。</p>
            </section>
          } />

          <section className="section sec2">
            <h2>セッション</h2>

            <AnimatedCard text={
              <article>
                <img src="./images/kawaguchi.jpg" alt="" />
                <h3>Immersive Web Design 〜没入型インターフェースの最前線〜</h3>
                <dl>
                  <dt>河口英生</dt>
                  <dd>グラフィックデザイナー</dd>
                </dl>
              </article>
            } />

            <AnimatedCard text={
              <article>
                <img src="./images/takagi.jpg" alt="" />
                <h3>Touch, Motion, Emotion：インタラクションが動かす感情設計</h3>
                <dl>
                  <dt>高木寛貴</dt>
                  <dd>インタラクションエンジニア</dd>
                </dl>
              </article>
            } />
          </section>

          <AnimatedCard text={
            <section className="section sec3">
              <h2>開催概要</h2>
              <dl>
                <div>
                  <dt>日時</dt>
                  <dd>2026年8月9日(日) 13:00- 17:00</dd>
                </div>
                <div>
                  <dt>会場</dt>
                  <dd>トライデントコンピュータ専門学校 メディアホール（名古屋市中村区）</dd>
                </div>
                <div>
                  <dt>参加費</dt>
                  <dd>無料（事前登録制）</dd>
                </div>
                <div>
                  <dt>参加方法</dt>
                  <dd>
                    <ul>
                      <li>学校公式サイトまたは当ページの申込フォームより受付。</li>
                      <li>学生・一般どちらも参加可能。オンライン配信あり（URLは申込者に後日送付）</li>
                    </ul>
                  </dd>
                </div>
                <div>
                  <dt>主催</dt>
                  <dd>トライデントコンピュータ専門学校 Webデザイン学科</dd>
                </div>
              </dl>
              <p>詳細は<a href="https://computer.trident.ac.jp/">公式サイト</a>をご覧ください。</p>
            </section>
          } />
        </div>
      </main>

      <footer>
       <div className="inner">
        <p>ハッシュタグ：#TWC2026</p>
          <p><small>&copy; 2026 TRIDENT WEBDESIGN CONFERENCE</small></p>
        </div>
      </footer>

    </>
  );
}

export default App;