/* ============================================================
   TOP — Three.js 背景（スクロール連動の zintai アニメーション）

   WebGl演習 12/lp（LP）の src/App.jsx の演出を、素の three.js に移植したもの。
   移植したのは次の3つ。
     ・スクロール位置でセクションを判定し、クリップとカメラを同時に切り替える
     ・走行中（run / fastrun）だけ集中線を出す（speedlines.js）
     ・スクロールで拡大・回転するパーティクル

   LP との違い（背景として使うための変更点）：
     ・React / R3F / gsap → 素の three.js とスクロールイベント
     ・drei の <Environment preset="city" /> → RoomEnvironment（CDNへ取りに行かない）
     ・集中線の色を黒→金。LPは背景が明るいので黒＋NormalBlending だが、
       このサイトは背景が黒なので、そのままだと線が完全に見えなくなる
============================================================ */
import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { SpeedLines } from './speedlines.js';

// GLB本来の色ではなく、サイトの配色（半透明の金）に寄せたい場合は true
const USE_SITE_MATERIAL = false;

// パーティクルを出すか
const SHOW_PARTICLES = true;

/**
 * スクロール順に並べる。上から順に判定して「越えているうち一番あと」を採用するので、
 * 並び順を崩すとセクション判定がおかしくなる。
 * camera / target は LP の値をそのまま流用している。
 */
const SECTIONS = [
  { trigger: '.main-visual', anchor: 'top',    name: 'tati',    camera: [0, 0.3, 5.5],    target: [0, 0.0, 0] },
  { trigger: '.works',       anchor: 'top',    name: 'run',     camera: [-0.8, 0.5, 4.2], target: [0, 0.3, 0] },
  { trigger: '.about',       anchor: 'center', name: 'fastrun', camera: [3.6, 1.2, 0.8],  target: [0, 0.5, 0] },
  { trigger: '.timeline',    anchor: 'top',    name: 'run',     camera: [-2.4, 0.8, 3.4], target: [0, 0.3, 0] },
  { trigger: '.footer',      anchor: 'top',    name: 'tukare',  camera: [0.9, -0.5, 2.4], target: [0, -0.4, 0] },
];

// セクションの基準点がこの高さ（画面の上から何割か）を越えたら切り替える
const TRIGGER_LINE = 0.75;

// 集中線を出すアニメーション
const RUNNING_ANIMATIONS = new Set(['run', 'fastrun']);

// クリップを切り替えるときのクロスフェード秒数（0 にすると即座に切り替わる）
const FADE_DURATION = 0.4;

const CAMERA_DAMPING = 5;

// 集中線の発生源。キャラクターの胸のあたり・少し奥
const SPEED_LINE_ORIGIN = [0, 0.3, -1];

// モデルのローカル座標で「正面」を指す軸。zintai.glb は +Z が正面
const CHARACTER_FORWARD_AXIS = [0, 0, 1];

const canvas = document.getElementById('backcanvas');

if (document.querySelector('main.top') && canvas) {

  /* ── レンダラー / シーン ───────────────────────────── */
  const scene = new THREE.Scene();
  // 背景色は設定しない（alpha:true でCSSの黒背景を透かす）

  const camera = new THREE.PerspectiveCamera(75, innerWidth / innerHeight, 0.1, 1000);
  camera.position.fromArray(SECTIONS[0].camera);

  const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
  renderer.setSize(innerWidth, innerHeight);
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2)); // 高DPI端末で描画量が跳ね上がるのを防ぐ
  renderer.toneMapping = THREE.ACESFilmicToneMapping; // R3F の既定に合わせる

  // drei の <Environment preset="city" /> の代わり。
  // HDRIをCDNから取りに来ないぶん軽く、オフラインでも壊れない。
  const pmrem = new THREE.PMREMGenerator(renderer);
  scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;

  /* ── セクション判定 ──────────────────────────────── */
  const elements = SECTIONS.map(({ trigger }) => document.querySelector(trigger));
  let sectionIndex = 0;

  /**
   * いま見ているセクションの番号を返す。
   * 各セクションの基準点が TRIGGER_LINE を越えたかだけを見るので、値は
   * スクロール量に対して連続ではなく、境界を越えた瞬間に1段ずつ変わる。
   * カメラもクリップも集中線もこの1つの値から決めるので、三者がズレない。
   */
  function measureSection() {
    const line = innerHeight * TRIGGER_LINE;
    let next = 0;

    for (let i = 0; i < SECTIONS.length; i++) {
      const el = elements[i];
      if (!el) continue;
      const rect = el.getBoundingClientRect();
      const point = SECTIONS[i].anchor === 'center' ? rect.top + rect.height / 2 : rect.top;
      if (point <= line) next = i;
    }

    if (next !== sectionIndex) {
      sectionIndex = next;
      playAnimation(SECTIONS[next].name);
    }
  }

  /**
   * 要素が画面下から入りきるまでの進捗（0→1）を返す。
   * gsap の scrollTrigger（start:"top bottom" / end:"bottom bottom" / scrub）と同じ範囲。
   */
  function enterProgress(el) {
    if (!el) return 0;
    const rect = el.getBoundingClientRect();
    return THREE.MathUtils.clamp((innerHeight - rect.top) / rect.height, 0, 1);
  }

  /* ── モデル ─────────────────────────────────────── */
  let mixer = null;
  let actions = {};
  let currentAction = null;
  let pendingAnimation = SECTIONS[0].name; // 読み込み完了前に決まった分を覚えておく

  /**
   * 直前のクリップだけをフェードアウトさせ、次のクリップをフェードイン。
   * 2つ以上のクリップの重みが混ざらないので、姿勢が崩れない。
   */
  function playAnimation(name) {
    pendingAnimation = name;

    const next = actions[name];
    if (!next) return;
    if (currentAction === next && next.isRunning()) return;

    next.reset().setLoop(THREE.LoopRepeat, Infinity).setEffectiveWeight(1).fadeIn(FADE_DURATION).play();
    if (currentAction && currentAction !== next) currentAction.fadeOut(FADE_DURATION);
    currentAction = next;
  }

  let speedLines = null;

  const loader = new GLTFLoader();
  loader.load(
    // ページのURLではなくこのJSの位置を基準に解決する（どのページから読んでもズレない）
    new URL('./models/zintai.glb', import.meta.url).href,
    (gltf) => {
      const model = gltf.scene;

      // LP と同じ置き方
      model.position.set(0, -1, 0);
      model.scale.setScalar(1.2);

      if (USE_SITE_MATERIAL) {
        const bgMat = new THREE.MeshStandardMaterial({
          color: '#a25c00',
          metalness: 0.1,
          roughness: 0.7,
          transparent: true,
          opacity: 0.38,
          flatShading: true,
        });
        model.traverse((obj) => {
          if (obj.isMesh || obj.isSkinnedMesh) obj.material = bgMat;
        });
      }

      model.traverse((obj) => {
        if (!obj.isMesh && !obj.isSkinnedMesh) return;
        // スキンメッシュの境界球はバインドポーズのまま更新されないため、
        // 走りで手足が外に出ると画面内でもカリングされてモデルごと消える。
        obj.frustumCulled = false;
      });

      scene.add(model);

      mixer = new THREE.AnimationMixer(model);
      gltf.animations.forEach((clip) => {
        actions[clip.name] = mixer.clipAction(clip);
      });

      // 集中線が体の向きを読めるよう、モデルそのものを渡す
      speedLines = new SpeedLines(scene, camera, {
        origin: SPEED_LINE_ORIGIN,
        motionSource: model,
        motionAxis: CHARACTER_FORWARD_AXIS,
        followCamera: true,
        frontAngle: 22,
        sideAngle: 50,
        trailSpread: 2.2,
        // LPは背景が明るいので黒＋NormalBlending。こちらは背景が黒なので
        // 金＋AdditiveBlending にしないと線が見えない。
        color: 0xb8a88a,
        blending: THREE.AdditiveBlending,
        opacity: 0.55,
        width: 0.035,
        spawnRate: 90,
        maxParticles: 200,
        maxRadius: 5,
        minLength: 0.8,
        maxLength: 2.0,
      });

      // 読み込み中にスクロールされていた場合に備えて、今のセクションから始める
      playAnimation(pendingAnimation);
    },
    undefined,
    (error) => {
      // 読み込みに失敗しても背景が消えるだけでページは動く
      console.error('背景モデル（zintai.glb）の読み込みに失敗しました', error);
    }
  );

  /* ── パーティクル ────────────────────────────────── */
  let particles = null;

  if (SHOW_PARTICLES) {
    const COUNT = 1000;
    const SPREAD = 4;
    const positions = new Float32Array(COUNT * 3);
    for (let i = 0; i < COUNT * 3; i++) {
      positions[i] = (Math.random() - 0.5) * SPREAD;
    }

    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));

    particles = new THREE.Points(
      geo,
      new THREE.PointsMaterial({
        size: 0.05,
        color: '#b8a88a', // LPは紫（#7a3bff）。サイトの配色に合わせて金にしている
        sizeAttenuation: true,
        transparent: true,
        opacity: 0.6,
        depthWrite: false,
      })
    );
    scene.add(particles);
  }

  /* ── カメラワーク ────────────────────────────────── */
  // 毎フレームの計算で使い回す
  const _camA = new THREE.Vector3();
  const _camB = new THREE.Vector3();
  const lookAt = new THREE.Vector3(...SECTIONS[0].target);
  let snap = true; // 最初の1フレームだけ補間せず即座に合わせる

  /* ── ループ ─────────────────────────────────────── */
  const clock = new THREE.Clock();

  function animate() {
    requestAnimationFrame(animate);
    const delta = clock.getDelta();

    if (mixer) mixer.update(delta);
    if (speedLines) {
      speedLines.setActive(RUNNING_ANIMATIONS.has(SECTIONS[sectionIndex].name));
      speedLines.update(delta);
    }

    // 目標はいまのセクションの camera / target そのもの。隣との補間はしない。
    // フレームレートに依存しない指数減衰で寄せる。delta を頭打ちにしているのは、
    // 裏タブから戻った直後に飛ばないようにするため。
    const section = SECTIONS[sectionIndex];
    _camA.fromArray(section.camera);
    _camB.fromArray(section.target);

    const k = snap ? 1 : 1 - Math.exp(-CAMERA_DAMPING * Math.min(delta, 1 / 30));
    snap = false;

    camera.position.lerp(_camA, k);
    lookAt.lerp(_camB, k);
    camera.lookAt(lookAt);

    if (particles) {
      // LP では gsap の scrollTrigger で動かしていたぶんを、スクロール進捗から直接出す
      const scaleP = enterProgress(elements[1]); // .works
      const rotateP = enterProgress(elements[2]); // .about
      particles.scale.setScalar(1 + scaleP * 9); // 1 → 10
      particles.rotation.y = rotateP * Math.PI * 2;
    }

    renderer.render(scene, camera);
  }

  animate();
  measureSection();

  addEventListener('scroll', measureSection, { passive: true });

  addEventListener('resize', () => {
    camera.aspect = innerWidth / innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(innerWidth, innerHeight);
    measureSection();
  }, { passive: true });

  /* ── タイムライン線（スクロール連動）──────────────── */
  const tlWrap = document.querySelector('.timeline__wrap');
  if (tlWrap) {
    const updateTlLine = () => {
      const rect = tlWrap.getBoundingClientRect();
      const scrolled = innerHeight / 2 - rect.top;
      const height = Math.max(0, Math.min(tlWrap.offsetHeight, scrolled));
      tlWrap.style.setProperty('--tl-line-height', height + 'px');
    };
    addEventListener('scroll', updateTlLine, { passive: true });
    updateTlLine();
  }
}
