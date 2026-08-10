/* ============================================================
   集中線 / 流線

   LP（WebGl演習 12/lp）の src/SpeedLines.jsx を素の three.js に移植したもの。
   React 依存だったのはフックと JSX で板を並べる部分だけなので、
   計算はほぼそのまま持ってきている。

   使い方：
     const lines = new SpeedLines(scene, camera, { origin: [0, 0.3, -1] });
     lines.setMotionSource(model);  // 体の向きに線を追従させる
     lines.setActive(true);
     // 毎フレーム
     lines.update(delta);
============================================================ */
import * as THREE from 'three';

const UP = new THREE.Vector3(0, 1, 0);
const FORWARD = new THREE.Vector3(0, 0, 1);

// 毎フレームの計算で使い回す作業用オブジェクト（GCを発生させないため）。
// メソッドの中で new すると1フレームごとにゴミが出るのでモジュール直下に置く。
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

// [x, y, z] でも THREE.Vector3 でも受け取れるようにする
function toVector3(value) {
  return Array.isArray(value) ? new THREE.Vector3(...value) : value.clone();
}

function createLineGeometry(width) {
  const half = width / 2;
  const geometry = new THREE.BufferGeometry();
  // ローカル+Z軸に沿って0→1に伸びる細長い板（粒子ごとにscale.zで長さを調整する）
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

export class SpeedLines {
  constructor(scene, camera, options = {}) {
    this.scene = scene;
    this.camera = camera;

    this.active = options.active ?? false;
    this.origin = toVector3(options.origin ?? [0, 0, 0]);

    // 線の流れる向きの取得元（モデルの Object3D）。無ければ motion をそのまま使う
    this.motionSource = options.motionSource ?? null;
    this.motion = toVector3(options.motion ?? FORWARD).normalize();
    // モデルのローカル座標で「前」を指す軸。zintai.glb は +Z が正面
    this.motionAxis = toVector3(options.motionAxis ?? FORWARD).normalize();

    // 発生源をずらす向き。省略するとカメラから見た奥方向を自動で使う
    this.fixedBack = options.backDirection ? toVector3(options.backDirection).normalize() : null;

    this.maxParticles = options.maxParticles ?? 120;
    this.spawnRate = options.spawnRate ?? 45; // 1秒あたりの生成数
    this.minSpeed = options.minSpeed ?? 6;
    this.maxSpeed = options.maxSpeed ?? 10;
    this.minLength = options.minLength ?? 0.6;
    this.maxLength = options.maxLength ?? 1.4;
    this.minLife = options.minLife ?? 0.35;
    this.maxLife = options.maxLife ?? 0.6;
    this.minRadius = options.minRadius ?? 0.3;
    this.maxRadius = options.maxRadius ?? 2.4;
    this.backOffset = options.backOffset ?? 0.8; // キャラクターの後ろにずらす距離
    this.width = options.width ?? 0.02;
    this.color = options.color ?? 0xffffff;
    this.opacity = options.opacity ?? 0.9;
    // 暗い背景なら AdditiveBlending が映える。明るい背景では
    // NormalBlending ＋ 濃い色にしないと線が潰れて見えない。
    this.blending = options.blending ?? THREE.AdditiveBlending;
    // カメラがセクションごとに動く場合は true。毎フレーム「後ろ」方向を
    // 取り直すので、どの角度から見ても線が画面奥から手前に湧いて見える。
    this.followCamera = options.followCamera ?? false;
    // カメラが motion の正面からこの角度以内なら完全に放射状（度）
    this.frontAngle = options.frontAngle ?? 22;
    // この角度以上なら完全に流線（度）。間はなめらかに混ざる
    this.sideAngle = options.sideAngle ?? 50;
    // 流線のときに、進行方向を軸としたどれくらいの太さの筒に線をばらまくか
    this.trailSpread = options.trailSpread ?? 2.2;
    // 1フレームで進める時間の上限（秒）。裏タブから戻って delta が跳ねたとき、
    // 大量の粒子が同時に湧いて同時に消えるのを防ぐ
    this.maxDelta = options.maxDelta ?? 1 / 30;

    this.spawnCarry = 0;
    this.burstOrigin = new THREE.Vector3();
    this.backDirection = new THREE.Vector3();
    this._originDirty = true;

    this.group = new THREE.Group();
    this.scene.add(this.group);

    this.geometry = createLineGeometry(this.width);
    this.meshes = [];
    this.pool = [];

    for (let i = 0; i < this.maxParticles; i++) {
      // 粒子ごとに opacity を動かすのでマテリアルも粒子ごとに持つ
      const material = new THREE.MeshBasicMaterial({
        color: this.color,
        transparent: true,
        opacity: 0,
        depthWrite: false,
        blending: this.blending,
        side: THREE.DoubleSide,
      });
      const mesh = new THREE.Mesh(this.geometry, material);
      mesh.visible = false;
      mesh.frustumCulled = false;
      this.group.add(mesh);
      this.meshes.push(mesh);
      this.pool.push({
        origin: new THREE.Vector3(),
        dir: new THREE.Vector3(),
        speed: 0,
        length: 0,
        life: 0,
        age: 0,
        active: false,
      });
    }
  }

  setActive(active) {
    this.active = active;
  }

  /** 体の向きの取得元。渡すと毎フレームそのモデルのワールド回転から進行方向を読む */
  setMotionSource(object3d) {
    this.motionSource = object3d;
  }

  setOrigin(origin) {
    this.origin.copy(toVector3(origin));
    this._originDirty = true;
  }

  /** 発生源をキャラクターの「カメラから見て奥側」に置き直す */
  _updateBurstOrigin() {
    const back = this.backDirection;

    if (this.fixedBack) {
      back.copy(this.fixedBack);
    } else {
      back.copy(this.origin).sub(this.camera.position);
      if (back.lengthSq() < 1e-8) back.copy(FORWARD).negate();
      back.normalize();
    }

    this.burstOrigin.copy(this.origin).addScaledVector(back, this.backOffset);
  }

  /**
   * 板が視線と平行になって消えないよう、線の軸まわりに回して面をカメラへ向ける。
   * ローカル +Z が線の伸びる方向、ローカル +Y が板の法線。
   */
  _orient(mesh, dir) {
    _toCamera.copy(this.camera.position).sub(mesh.position);
    if (_toCamera.lengthSq() < 1e-8) return;
    _toCamera.normalize();

    _basisX.crossVectors(_toCamera, dir);
    if (_basisX.lengthSq() < 1e-8) return; // ほぼ真正面：前フレームの姿勢を保つ
    _basisX.normalize();
    _basisY.crossVectors(dir, _basisX).normalize();

    _basis.makeBasis(_basisX, _basisY, dir);
    mesh.quaternion.setFromRotationMatrix(_basis);
  }

  _spawnOne() {
    const index = this.pool.findIndex((p) => !p.active);
    if (index === -1) return;

    const p = this.pool[index];
    const mesh = this.meshes[index];

    // 線の向きは常に「画面に平行な平面」の中で決める。
    // 右/上ベクトルを現在のカメラ基準で取るので、カメラが動いても
    // 画面上での見え方が破綻しない。
    _toCamera.copy(this.camera.position).sub(this.burstOrigin);
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
    _trailDir.copy(this.motion).negate().normalize();

    // 正面度。1に近いほどカメラが進行方向の正面／真後ろにある
    const frontness = Math.abs(_toCamera.dot(this.motion));
    // radial = 1 で完全に放射、0 で完全に流線
    const radial = smoothstep(
      Math.cos(THREE.MathUtils.degToRad(this.sideAngle)),
      Math.cos(THREE.MathUtils.degToRad(this.frontAngle)),
      frontness
    );

    p.dir.copy(_trailDir).lerp(_radialDir, radial).normalize();
    p.origin.copy(this.burstOrigin);

    // 流線のときは1点から出ると不自然なので、進行方向を軸とした
    // 円盤の上にばらまき、流れる向きにも前後させる。
    if (radial < 1) {
      const spread = this.trailSpread * (1 - radial);

      // 進行方向に直交する2軸を作る（体を輪切りにする面）
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

    p.speed = THREE.MathUtils.lerp(this.minSpeed, this.maxSpeed, Math.random());
    p.length = THREE.MathUtils.lerp(this.minLength, this.maxLength, Math.random());
    p.life = THREE.MathUtils.lerp(this.minLife, this.maxLife, Math.random());
    p.age = 0;
    p.active = true;

    mesh.visible = true;
    mesh.position.copy(p.origin).addScaledVector(p.dir, this.minRadius);
    this._orient(mesh, p.dir);
  }

  update(delta) {
    // 体の向きを毎フレーム読み直す。向きを変えれば線もついてくる
    if (this.motionSource) {
      this.motionSource.getWorldQuaternion(_quat);
      this.motion.copy(this.motionAxis).applyQuaternion(_quat).normalize();
    }

    // カメラが動く構成では、発生源も「今のカメラから見た奥側」に取り直す
    if (this.followCamera || this._originDirty) {
      this._updateBurstOrigin();
      this._originDirty = false;
    }

    const dt = Math.min(delta, this.maxDelta);

    if (this.active) {
      this.spawnCarry += this.spawnRate * dt;
      while (this.spawnCarry >= 1) {
        this._spawnOne();
        this.spawnCarry -= 1;
      }
    } else {
      this.spawnCarry = 0;
    }

    for (let i = 0; i < this.pool.length; i++) {
      const p = this.pool[i];
      if (!p.active) continue;
      const mesh = this.meshes[i];

      p.age += dt;
      const t = p.age / p.life;

      if (t >= 1) {
        p.active = false;
        mesh.visible = false;
        mesh.material.opacity = 0;
        continue;
      }

      const headDist = this.minRadius + p.speed * p.age;
      const tailDist = Math.max(this.minRadius, headDist - p.length);
      const segLength = Math.max(headDist - tailDist, 0.001);

      mesh.position.copy(p.origin).addScaledVector(p.dir, tailDist);
      mesh.scale.set(1, 1, segLength);
      // カメラが動いている最中でも板が真横を向かないよう毎フレーム向け直す
      this._orient(mesh, p.dir);

      let alpha;
      if (t < 0.15) alpha = t / 0.15;
      else if (t > 0.7) alpha = 1 - (t - 0.7) / 0.3;
      else alpha = 1;
      mesh.material.opacity = alpha * this.opacity;

      if (headDist > this.maxRadius) {
        p.active = false;
        mesh.visible = false;
        mesh.material.opacity = 0;
      }
    }
  }

  dispose() {
    this.meshes.forEach((mesh) => mesh.material.dispose());
    this.geometry.dispose();
    this.scene.remove(this.group);
  }
}

export default SpeedLines;
