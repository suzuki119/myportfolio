import { useEffect, useMemo, useRef } from 'react';
import { useFrame, useThree } from '@react-three/fiber';
import * as THREE from 'three';

const UP = new THREE.Vector3(0, 1, 0);
const FORWARD = new THREE.Vector3(0, 0, 1);

// 毎フレームの計算で使い回す作業用オブジェクト(GC を発生させないため)。
// コンポーネントの中で new すると 1 フレームごとにゴミが出るのでモジュール直下に置く。
const _toCamera = new THREE.Vector3();
const _right = new THREE.Vector3();
const _up = new THREE.Vector3();
const _radialDir = new THREE.Vector3();
const _trailDir = new THREE.Vector3();
const _perpA = new THREE.Vector3();
const _perpB = new THREE.Vector3();
const _basisX = new THREE.Vector3();
const _basisY = new THREE.Vector3();
const _basis = new THREE.Matrix4();
const _quat = new THREE.Quaternion();

// edge0 → edge1 の間を 0→1 になめらかに補間する
function smoothstep(edge0, edge1, x) {
  const t = THREE.MathUtils.clamp((x - edge0) / (edge1 - edge0), 0, 1);
  return t * t * (3 - 2 * t);
}

// ベクトル系の props は [x, y, z] でも THREE.Vector3 でも受け取る。
// 数値 3 つに開いてから useMemo の依存配列に渡すので、呼び出し側が
// 配列リテラルを直接書いても毎レンダリングで作り直しにならない。
function vectorParts(value) {
  if (Array.isArray(value)) return value;
  return [value.x, value.y, value.z];
}

function createLineGeometry(width) {
  const half = width / 2;
  const geometry = new THREE.BufferGeometry();
  // ローカル+Z軸に沿って0→1に伸びる細長い板(粒子ごとにscale.zで長さを調整する)
  const positions = new Float32Array([
    -half, 0, 0,
     half, 0, 0,
     half, 0, 1,
    -half, 0, 1,
  ]);
  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
  geometry.setIndex([0, 1, 2, 0, 2, 3]);
  return geometry;
}

function createParticle() {
  return {
    origin: new THREE.Vector3(),
    dir: new THREE.Vector3(),
    speed: 0,
    length: 0,
    life: 0,
    age: 0,
    active: false,
  };
}

// フレームをまたいで持ち越す値。React の state ではなく ref に入れる。
function createFrameState() {
  return {
    spawnCarry: 0,
    burstOrigin: new THREE.Vector3(),
    backDirection: new THREE.Vector3(),
    motion: new THREE.Vector3(0, 0, 1),
    originKey: null, // 発生源を計算し直したときの props のスナップショット
  };
}

/**
 * 集中線 / 流線。<Canvas> の中に置いて使う。
 *   <SpeedLines active={isRunning} origin={[0, 0.3, -1]} motionSource={modelRef} />
 *
 * active を true にしている間だけ線が湧き続ける。
 * motionSource にモデルの ref を渡すと、線の流れる向きをそのモデルの
 * 実際のワールド回転から毎フレーム取り直す。
 *
 * 実装方針:
 *   - シーングラフ(板 maxParticles 枚)は JSX で宣言する。生成と破棄は R3F 任せ。
 *   - 毎フレームの位置・回転・不透明度は useFrame の中で mesh を直接 mutate する。
 *     ここを useState にすると maxParticles 個のコンポーネントが 60fps で
 *     再レンダリングされることになるので絶対に通さない。
 *   - 粒子の寿命などの可変状態は useRef に置く。useMemo に置いて mutate すると
 *     React Compiler(react-hooks/immutability)に弾かれる。
 *   - 上記のおかげで数値系の props はすべてマウント後も反映される。
 */
export function SpeedLines({
  active = false,
  origin = [0, 0, 0],
  // 線の流れる向きの取得元(モデルの ref)。省略時は motion をそのまま使う。
  motionSource,
  // キャラクターが走っている方向(ワールド座標)
  motion = FORWARD,
  // モデルのローカル座標で「前」を指す軸。zintai.glb は +Z が正面。
  motionAxis = FORWARD,
  // 発生源をずらす向き。省略するとカメラから見た奥方向を自動で使う。
  backDirection,
  maxParticles = 120,
  spawnRate = 45, // 1秒あたりの生成数
  minSpeed = 6,
  maxSpeed = 10,
  minLength = 0.6,
  maxLength = 1.4,
  minLife = 0.35,
  maxLife = 0.6,
  minRadius = 0.3,
  maxRadius = 2.4,
  backOffset = 0.8, // キャラクターの後ろにずらす距離
  width = 0.02,
  color = 0xffffff,
  opacity = 0.9,
  // 暗い背景なら AdditiveBlending が映える。明るい背景＋CSSフィルタが
  // かかる場合は NormalBlending ＋ 濃い色にしないと線が潰れて見えない。
  blending = THREE.AdditiveBlending,
  // カメラがセクションごとに動く場合は true。毎フレーム「後ろ」方向を
  // 取り直すので、どの角度から見ても線が画面奥から手前に湧いて見える。
  followCamera = false,
  // カメラが motion の正面からこの角度以内なら完全に放射状(度)
  frontAngle = 22,
  // この角度以上なら完全に流線(度)。間はなめらかに混ざる。
  sideAngle = 50,
  // 流線のときに、進行方向を軸としたどれくらいの太さの筒に線をばらまくか
  trailSpread = 2.2,
  // 1フレームで進める時間の上限(秒)。タブを裏にしていた等で delta が
  // 跳ね上がると、粒子が一気に湧いて一気に消えてしまうため。
  maxDelta = 1 / 30,
}) {
  const camera = useThree((s) => s.camera);
  // JSX で並べた板。添字が pool の添字と対応する。
  const meshes = useRef([]);
  const pool = useRef([]);
  const frame = useRef(createFrameState());

  const [originX, originY, originZ] = vectorParts(origin);
  const originVec = useMemo(
    () => new THREE.Vector3(originX, originY, originZ),
    [originX, originY, originZ]
  );

  const [axisX, axisY, axisZ] = vectorParts(motionAxis);
  const motionAxisVec = useMemo(
    () => new THREE.Vector3(axisX, axisY, axisZ).normalize(),
    [axisX, axisY, axisZ]
  );

  const [motionX, motionY, motionZ] = vectorParts(motion);

  const hasFixedBack = backDirection != null;
  const [backX, backY, backZ] = hasFixedBack ? vectorParts(backDirection) : [0, 0, 0];
  const fixedBackVec = useMemo(
    () => new THREE.Vector3(backX, backY, backZ).normalize(),
    [backX, backY, backZ]
  );

  // followCamera が false のときは発生源を毎フレーム計算し直さない。
  // その代わり、関係する props が変わったら 1 回だけ取り直したいので、
  // レンダリング時に作ったこのキーと前回の値を useFrame で見比べる。
  const originKey = `${originX},${originY},${originZ},${backOffset},${backX},${backY},${backZ},${camera.uuid}`;

  const geometry = useMemo(() => createLineGeometry(width), [width]);
  // geometry は props として渡す(＝R3F が作ったものではない)ので自分で捨てる
  useEffect(() => () => geometry.dispose(), [geometry]);

  // 発生源をキャラクターの「カメラから見て奥側」に置き直す
  const updateBurstOrigin = () => {
    const state = frame.current;
    const back = state.backDirection;
    if (hasFixedBack) {
      back.copy(fixedBackVec);
    } else {
      back.copy(originVec).sub(camera.position);
      if (back.lengthSq() < 1e-8) back.copy(FORWARD).negate();
      back.normalize();
    }
    state.burstOrigin.copy(originVec).addScaledVector(back, backOffset);
  };

  // 板が視線と平行になって消えないよう、線の軸まわりに回して面をカメラへ向ける。
  // ローカル +Z が線の伸びる方向、ローカル +Y が板の法線。
  const orient = (mesh, dir) => {
    _toCamera.copy(camera.position).sub(mesh.position);
    if (_toCamera.lengthSq() < 1e-8) return;
    _toCamera.normalize();

    _basisX.crossVectors(_toCamera, dir);
    if (_basisX.lengthSq() < 1e-8) return; // ほぼ真正面：前フレームの姿勢を保つ
    _basisX.normalize();
    _basisY.crossVectors(dir, _basisX).normalize();

    _basis.makeBasis(_basisX, _basisY, dir);
    mesh.quaternion.setFromRotationMatrix(_basis);
  };

  const spawnOne = () => {
    const state = frame.current;
    const index = pool.current.findIndex((p) => !p.active);
    if (index === -1) return;
    const p = pool.current[index];
    const mesh = meshes.current[index];
    if (!mesh) return;

    // 線の向きは常に「画面に平行な平面」の中で決める。
    // 右/上ベクトルを現在のカメラ基準で取るので、カメラが動いても
    // 画面上での見え方が破綻しない。
    _toCamera.copy(camera.position).sub(state.burstOrigin);
    if (_toCamera.lengthSq() < 1e-6) _toCamera.copy(FORWARD);
    _toCamera.normalize();

    _right.crossVectors(UP, _toCamera);
    if (_right.lengthSq() < 1e-6) _right.set(1, 0, 0); // 真上/真下から見たとき
    _right.normalize();
    _up.crossVectors(_toCamera, _right).normalize();

    // 放射候補：画面平面内のランダムな向き（こちらに向かって走る＝正面のとき）
    const angle = Math.random() * Math.PI * 2;
    _radialDir
      .copy(_right)
      .multiplyScalar(Math.cos(angle))
      .addScaledVector(_up, Math.sin(angle))
      .normalize();

    // 流線候補：体の向きの真逆。画面平面には射影せず、ワールド空間の向きを
    // そのまま使う。こうするとキャラクターが向きを変えれば線もついてくるし、
    // 遠近感で奥へ抜けていくので「体に沿って後ろへ流れる」ように見える。
    _trailDir.copy(state.motion).negate().normalize();

    // 正面度。1 に近いほどカメラが進行方向の正面／真後ろにある。
    const frontness = Math.abs(_toCamera.dot(state.motion));
    // radial = 1 で完全に放射、0 で完全に流線
    const radial = smoothstep(
      Math.cos(THREE.MathUtils.degToRad(sideAngle)),
      Math.cos(THREE.MathUtils.degToRad(frontAngle)),
      frontness
    );

    p.dir.copy(_trailDir).lerp(_radialDir, radial).normalize();

    p.origin.copy(state.burstOrigin);
    // 流線のときは 1 点から出ると不自然なので、進行方向を軸とした
    // 円盤の上にばらまき、流れる向きにも前後させる。
    if (radial < 1) {
      const spread = trailSpread * (1 - radial);

      // 進行方向に直交する 2 軸を作る（体を輪切りにする面）
      _perpA.crossVectors(UP, _trailDir);
      if (_perpA.lengthSq() < 1e-6) _perpA.set(1, 0, 0); // 真上/真下を向いているとき
      _perpA.normalize();
      _perpB.crossVectors(_trailDir, _perpA).normalize();

      const around = Math.random() * Math.PI * 2;
      const distance = Math.sqrt(Math.random()) * spread; // 円盤内で偏らないように
      p.origin
        .addScaledVector(_perpA, Math.cos(around) * distance)
        .addScaledVector(_perpB, Math.sin(around) * distance)
        .addScaledVector(p.dir, -Math.random() * spread);
    }

    p.speed = THREE.MathUtils.lerp(minSpeed, maxSpeed, Math.random());
    p.length = THREE.MathUtils.lerp(minLength, maxLength, Math.random());
    p.life = THREE.MathUtils.lerp(minLife, maxLife, Math.random());
    p.age = 0;
    p.active = true;
    mesh.visible = true;
    mesh.position.copy(p.origin).addScaledVector(p.dir, minRadius);
    orient(mesh, p.dir);
  };

  useFrame((_, delta) => {
    const state = frame.current;
    const particles = pool.current;

    // maxParticles が変わったら板の枚数に合わせる。
    // 既存の粒子はそのまま流れ続ける。
    if (particles.length !== maxParticles) {
      particles.length = maxParticles;
      for (let i = 0; i < maxParticles; i++) particles[i] ??= createParticle();
    }

    // 体の向きを毎フレーム読み直す。向きを変えれば線もついてくる。
    const source = motionSource?.current;
    if (source) {
      source.getWorldQuaternion(_quat);
      state.motion.copy(motionAxisVec).applyQuaternion(_quat).normalize();
    } else {
      state.motion.set(motionX, motionY, motionZ).normalize();
    }

    // カメラが動く構成では、発生源も「今のカメラから見た奥側」に取り直す。
    if (followCamera || state.originKey !== originKey) {
      updateBurstOrigin();
      state.originKey = originKey;
    }

    // 裏タブから戻った直後などは delta が巨大になる。そのまま使うと
    // 大量の粒子が同時に湧いて同時に消えるので、1フレーム分を頭打ちにする。
    const dt = Math.min(delta, maxDelta);

    if (active) {
      state.spawnCarry += spawnRate * dt;
      while (state.spawnCarry >= 1) {
        spawnOne();
        state.spawnCarry -= 1;
      }
    } else {
      state.spawnCarry = 0;
    }

    for (let i = 0; i < particles.length; i++) {
      const p = particles[i];
      if (!p.active) continue;
      const mesh = meshes.current[i];
      if (!mesh) continue;

      p.age += dt;
      const t = p.age / p.life;
      if (t >= 1) {
        p.active = false;
        mesh.visible = false;
        mesh.material.opacity = 0;
        continue;
      }

      const headDist = minRadius + p.speed * p.age;
      const tailDist = Math.max(minRadius, headDist - p.length);
      const segLength = Math.max(headDist - tailDist, 0.001);

      mesh.position.copy(p.origin).addScaledVector(p.dir, tailDist);
      mesh.scale.set(1, 1, segLength);
      // カメラが動いている最中でも板が真横を向かないよう毎フレーム向け直す
      orient(mesh, p.dir);

      let alpha;
      if (t < 0.15) alpha = t / 0.15;
      else if (t > 0.7) alpha = 1 - (t - 0.7) / 0.3;
      else alpha = 1;
      mesh.material.opacity = alpha * opacity;

      if (headDist > maxRadius) {
        p.active = false;
        mesh.visible = false;
        mesh.material.opacity = 0;
      }
    }
  });

  return (
    <group>
      {Array.from({ length: maxParticles }, (_, i) => (
        <mesh
          key={i}
          ref={(el) => {
            meshes.current[i] = el;
          }}
          geometry={geometry}
          visible={false}
          frustumCulled={false}
        >
          {/*
            粒子ごとに opacity を動かすのでマテリアルも粒子ごとに持つ。
            opacity は useFrame から直接 mutate するため、ここで渡す 0 は
            湧く前に一瞬見えてしまうのを防ぐための初期値。
          */}
          <meshBasicMaterial
            color={color}
            transparent
            opacity={0}
            depthWrite={false}
            blending={blending}
            side={THREE.DoubleSide}
          />
        </mesh>
      ))}
    </group>
  );
}

export default SpeedLines;
