/* ============================================================
   TOP — Three.js 背景（dash の zintai アニメーション）

   dash/ の GLB と速度線（speedLines.js）をそのまま流用し、
   背景として使うために次の3点だけ変えている。
     ・操作UI／OrbitControls なし
       （#canvas-container が pointer-events:none なのでそもそも触れない）
     ・run を常時ループ再生（速度線も出しっぱなし）
     ・サイトの配色に合わせたマテリアルで上書き
============================================================ */
import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { SpeedLineSystem } from './dash/speedLines.js';

// GLBそのままの派手な色を使いたい場合は false にする
const USE_SITE_MATERIAL = true;

// 再生するアニメーション名（GLBに無ければ最初のクリップを使う）
const PLAY_ANIMATION = 'run';

const canvas = document.getElementById('backcanvas');

if (document.querySelector('main.top') && canvas) {

  const scene = new THREE.Scene();
  // 背景色は設定しない（alpha:true でCSSの黒背景を透かす）

  const camera = new THREE.PerspectiveCamera(45, innerWidth / innerHeight, 0.1, 100);

  const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
  renderer.setSize(innerWidth, innerHeight);
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2)); // 高DPI端末で描画量が跳ね上がるのを防ぐ

  // ── ライト（クリスタル版の構成を踏襲）──
  scene.add(new THREE.HemisphereLight(0xffffff, 0x444444, 1.2));
  const dLight = new THREE.DirectionalLight(0xffffff, 1.2);
  dLight.position.set(3, 5, 2);
  scene.add(dLight);
  const dLight2 = new THREE.DirectionalLight(0xb8a88a, 0.6); // $color-accent
  dLight2.position.set(-5, -3, -4);
  scene.add(dLight2);

  // ── モデル ──
  // モデルはグループに入れて回す。モデル自身を回すとGLB側の
  // アニメーションが持つ回転と競合するため、必ず親側で回転させる。
  const modelGroup = new THREE.Group();
  scene.add(modelGroup);

  let mixer = null;
  let speedLines = null;

  const loader = new GLTFLoader();
  loader.load(
    // ページのURLではなくこのJSの位置を基準に解決する（どのページから読んでもズレない）
    new URL('./dash/zintai.glb', import.meta.url).href,
    (gltf) => {
      const model = gltf.scene;

      // 背景なので、クリスタルと同じ半透明マテリアルに置き換えて主張を抑える
      const bgMat = new THREE.MeshStandardMaterial({
        color: '#a25c00',
        metalness: 0.1,
        roughness: 0.7,
        transparent: true,
        opacity: 0.38,
        flatShading: true,
      });

      model.traverse((obj) => {
        if (!obj.isMesh && !obj.isSkinnedMesh) return;

        if (USE_SITE_MATERIAL) obj.material = bgMat;

        // スキンメッシュの境界球はバインドポーズのまま更新されないため、
        // 走りで手足が外に出ると画面内でもカリングされてモデルごと消える。
        obj.frustumCulled = false;
      });

      // 原点に中央寄せしてから、画面に収まる距離にカメラを引く
      const box = new THREE.Box3().setFromObject(model);
      const size = box.getSize(new THREE.Vector3());
      const center = box.getCenter(new THREE.Vector3());
      model.position.sub(center);
      modelGroup.add(model);

      const maxDim = Math.max(size.x, size.y, size.z);
      const fov = camera.fov * (Math.PI / 180);
      const distance = (maxDim / 2) / Math.tan(fov / 2) * 2.2; // 2.2 = 余白ぶんの倍率
      camera.position.set(0, size.y * 0.15, distance);
      camera.lookAt(0, 0, 0);

      mixer = new THREE.AnimationMixer(model);
      const clip =
        THREE.AnimationClip.findByName(gltf.animations, PLAY_ANIMATION) ?? gltf.animations[0];

      if (clip) {
        const action = mixer.clipAction(clip);
        action.setLoop(THREE.LoopRepeat, Infinity);
        action.play();
      }

      // 速度線はモデル中央から少し奥を発生源にする（dash と同じ考え方）
      speedLines = new SpeedLineSystem(scene, camera, new THREE.Vector3(0, 0, -1), {
        color: 0xb8a88a, // $color-accent
        opacity: 0.5,
        spawnRate: 28, // 背景なので dash（45）より控えめに
        maxParticles: 90,
      });
      speedLines.setActive(true);
    },
    undefined,
    (error) => {
      // 読み込みに失敗しても背景が消えるだけでページは動く
      console.error('背景モデル（zintai.glb）の読み込みに失敗しました', error);
    }
  );

  // ── アニメーション ──
  const clock = new THREE.Clock();

  function animate() {
    requestAnimationFrame(animate);
    const delta = clock.getDelta();

    if (mixer) mixer.update(delta);
    if (speedLines) speedLines.update(delta);

    modelGroup.rotation.y += delta * 0.18; // クリスタルと同じくらいのゆっくりした回転

    renderer.render(scene, camera);
  }
  animate();

  addEventListener('resize', () => {
    camera.aspect = innerWidth / innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(innerWidth, innerHeight);
  }, { passive: true });

  // ── タイムライン線（スクロール連動）──
  const tlWrap = document.querySelector('.timeline__wrap');
  if (tlWrap) {
    const updateTlLine = () => {
      const rect = tlWrap.getBoundingClientRect();
      const scrolled = window.innerHeight / 2 - rect.top;
      const height = Math.max(0, Math.min(tlWrap.offsetHeight, scrolled));
      tlWrap.style.setProperty('--tl-line-height', height + 'px');
    };
    addEventListener('scroll', updateTlLine, { passive: true });
    updateTlLine();
  }
}
