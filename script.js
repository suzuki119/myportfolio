/* ============================================================
   共通 — メールコピーボタン
============================================================ */
const copyBtn = document.querySelector('.contact__copy');
if (copyBtn) {
  copyBtn.addEventListener('click', () => {
    navigator.clipboard.writeText(copyBtn.dataset.email).then(() => {
      copyBtn.classList.add('copied');
      setTimeout(() => copyBtn.classList.remove('copied'), 2000);
    });
  });
}

/* ============================================================
   共通 — ハンバーガーメニュー
============================================================ */
const navToggle = document.getElementById('nav-toggle');
const mainNav   = document.getElementById('main-nav');

if (navToggle && mainNav) {
  navToggle.addEventListener('click', () => {
    const isOpen = mainNav.classList.toggle('open');
    navToggle.classList.toggle('open', isOpen);
    navToggle.setAttribute('aria-label', isOpen ? 'メニューを閉じる' : 'メニューを開く');
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  mainNav.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      mainNav.classList.remove('open');
      navToggle.classList.remove('open');
      navToggle.setAttribute('aria-label', 'メニューを開く');
      document.body.style.overflow = '';
    });
  });
}

/* ============================================================
   TOP — Three.js 背景クリスタル
============================================================ */
if (document.querySelector('main.top')) {

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(75, innerWidth / innerHeight, 0.1, 2000);
  const renderer = new THREE.WebGLRenderer({
    canvas: document.getElementById('backcanvas'),
    antialias: true, alpha: true
  });
  renderer.setSize(innerWidth, innerHeight);
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));

  // ── クリスタル ──
  const createDiamond = () => {
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(new Float32Array([
      0, 1.1, 0, 1, 0, 0, 0, 0, 1, -1, 0, 0, 0, 0, -1, 0, -1.1, 0
    ]), 3));
    geo.setIndex([0, 1, 2, 0, 2, 3, 0, 3, 4, 0, 4, 1, 5, 2, 1, 5, 3, 2, 5, 4, 3, 5, 1, 4]);
    geo.computeVertexNormals();
    return geo;
  };

  const crystalGeo = createDiamond();
  const crystalMat = new THREE.MeshStandardMaterial({
    color: "#a25c00", metalness: 0.1, roughness: 0.7,
    transparent: true, opacity: 0.28, flatShading: true
  });

  const crystal = new THREE.Mesh(crystalGeo, crystalMat);
  crystal.scale.setScalar(2);
  scene.add(crystal);
  crystal.add(new THREE.LineSegments(
    new THREE.EdgesGeometry(crystalGeo),
    new THREE.LineBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.5 })
  ));

  // ── ガラスパネル ──
  const glassMat = new THREE.MeshPhysicalMaterial({
    color: "#6f6f6f", metalness: 0, roughness: 0,
    transparent: true, opacity: 0.3,
    transmission: 0.5,
    reflectivity: 0.9, side: THREE.DoubleSide
  });
  const edgeMat = new THREE.LineBasicMaterial({ color: 0xcccccc, transparent: true, opacity: 0.55 });
  const panelGroup = new THREE.Group();
  const PANEL_COUNT = 16, BASE_RADIUS = 4.55;
  const PANEL_W = 1.8, PANEL_H = 1.8, V_LAYERS = 5, V_SPACING = 1.8;

  for (let layer = 0; layer < V_LAYERS; layer++) {
    const offsetY = (layer - (V_LAYERS - 1) / 2) * V_SPACING;
    for (let i = layer % 2; i < PANEL_COUNT; i += 2) {
      const angle = (i / PANEL_COUNT) * Math.PI * 2;
      const pGeo = new THREE.PlaneGeometry(PANEL_W, PANEL_H);
      const mesh = new THREE.Mesh(pGeo, glassMat);
      mesh.position.set(Math.cos(angle) * BASE_RADIUS, offsetY, Math.sin(angle) * BASE_RADIUS);
      mesh.rotation.y = -angle + Math.PI / 2;
      mesh.add(new THREE.LineSegments(new THREE.EdgesGeometry(pGeo), edgeMat));
      panelGroup.add(mesh);
    }
  }
  scene.add(panelGroup);

  // ── ライト ──
  scene.add(new THREE.AmbientLight(0xffffff, 1.1));
  const dLight = new THREE.DirectionalLight(0xffffff, 0.9);
  dLight.position.set(0, 10, 10);
  scene.add(dLight);
  const dLight2 = new THREE.DirectionalLight(0xb8a88a, 0.5);
  dLight2.position.set(-5, -5, -5);
  scene.add(dLight2);
  camera.position.z = BASE_RADIUS * 3;

  // ── アニメーション ──
  let ticker = 0;
  function animate() {
    requestAnimationFrame(animate);
    ticker += 0.003;
    crystal.rotation.y = ticker;
    panelGroup.rotation.y = ticker * 0.4;
    renderer.render(scene, camera);
  }
  animate();
  window.addEventListener('resize', () => {
    camera.aspect = innerWidth / innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(innerWidth, innerHeight);
  }, { passive: true });

  // ── タイムライン線（スクロール連動） ──
  const tlWrap = document.querySelector('.timeline__wrap');
  if (tlWrap) {
    const updateTlLine = () => {
      const rect = tlWrap.getBoundingClientRect();
      const scrolled = window.innerHeight / 2 - rect.top;
      const height = Math.max(0, Math.min(tlWrap.offsetHeight, scrolled));
      tlWrap.style.setProperty('--tl-line-height', height + 'px');
    };
    window.addEventListener('scroll', updateTlLine, { passive: true });
    updateTlLine();
  }
}

/* ============================================================
   WORKS — カテゴリフィルター
============================================================ */
if (document.querySelector('main.works')) {
  const FADE_MS   = 220;
  const filterBtns = document.querySelectorAll('.works__filter-btn');
  const cards      = document.querySelectorAll('.works__card[data-category]');
  const countEl    = document.getElementById('works-count');
  const total      = cards.length;

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      const filter = btn.dataset.filter;
      let visible  = 0;

      cards.forEach(card => {
        const match = filter === 'all' || card.dataset.category === filter;
        if (match) {
          card.style.display = '';
          requestAnimationFrame(() => {
            requestAnimationFrame(() => card.classList.remove('is-hiding'));
          });
          visible++;
        } else {
          card.classList.add('is-hiding');
          setTimeout(() => {
            if (card.classList.contains('is-hiding')) card.style.display = 'none';
          }, FADE_MS);
        }
      });

      if (countEl) countEl.textContent = visible + ' / ' + total;
    });
  });
}
