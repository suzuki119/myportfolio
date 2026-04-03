/* ============================================================
   PROGRESS BAR
============================================================ */
const bar = document.getElementById('progressBar');
window.addEventListener('scroll', () => {
  const max = document.body.scrollHeight - innerHeight;
  bar.style.width = (max > 0 ? scrollY / max * 100 : 0) + '%';
}, { passive: true });

/* ============================================================
   THREE.JS — 装飾的な背景クリスタル（スクロール連動なし）
============================================================ */
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
  color: 0x1a1a1a, metalness: 0.1, roughness: 0.7,
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
  color: 0x289399, metalness: 0, roughness: 0,
  transparent: true, opacity: 0.14,
  transmission: 0.5, thickness: 0.1,
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

// ── アニメーション（ゆっくり自動回転のみ） ──
let ticker = 0;
function animate() {
  requestAnimationFrame(animate);
  ticker += 0.003;
  crystal.rotation.y = ticker;
  crystal.rotation.x = Math.sin(ticker * 0.3) * 0.15;
  panelGroup.rotation.y = ticker * 0.4;
  renderer.render(scene, camera);
}
animate();

window.addEventListener('resize', () => {
  camera.aspect = innerWidth / innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(innerWidth, innerHeight);
}, { passive: true });

/* ============================================================
   CHART.JS — レーダーチャート
============================================================ */
const radarOpts = (labels, data) => ({
  type: 'radar',
  data: {
    labels,
    datasets: [{
      data,
      backgroundColor: 'rgba(184,168,138,0.15)',
      borderColor: 'rgba(110, 110, 110, 0.9)',
      borderWidth: 2,
      pointBackgroundColor: 'rgba(184,168,138,1)',
      pointRadius: 4,
      pointHoverRadius: 6,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 1,
    plugins: { legend: { display: false } },
    scales: {
      r: {
        min: 0, max: 5,
        ticks: { stepSize: 1, display: false },
        grid: { color: 'rgb(145, 145, 145)' },
        angleLines: { color: 'rgb(145, 145, 145)' },
        pointLabels: {
          color: 'rgb(145, 145, 145)',
          font: { family: "'Space Mono', monospace", size: 11 }
        }
      }
    },
    animation: { duration: 800, easing: 'easeInOutQuart' }
  }
});

// レーダーチャート：スクロールで視野に入ったときに描画
const radarQueue = [
  { id: 'radarCoding', labels: ['HTML', 'CSS/SCSS', 'JavaScript', 'C言語'],                               data: [5, 4, 5, 3] },
  { id: 'radarDesign', labels: ['Illustrator', 'Photoshop', 'Figma', 'Canva'],                           data: [4, 2, 4, 3] },
  { id: 'radarOther',  labels: ['Blender', 'Premiere Pro', 'WordPress', 'Git', 'Office', 'After Effects'], data: [3, 3, 4, 3, 4, 2] },
];
const radarObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const cfg = radarQueue.find(q => q.id === entry.target.id);
    if (cfg) new Chart(entry.target, radarOpts(cfg.labels, cfg.data));
    radarObserver.unobserve(entry.target);
  });
}, { threshold: 0.3 });
radarQueue.forEach(q => {
  const el = document.getElementById(q.id);
  if (el) radarObserver.observe(el);
});

/* ============================================================
   SCROLL ANIMATIONS — タイムライン線（スクロール連動）
============================================================ */
const tlWrap = document.querySelector('.tl__wrap');
if (tlWrap) {
  const updateTlLine = () => {
    const rect = tlWrap.getBoundingClientRect();
    const wrapHeight = tlWrap.offsetHeight;
    // 画面中央を基準に、どこまで進んだかを計算
    const scrolled = window.innerHeight / 2 - rect.top;
    const height = Math.max(0, Math.min(wrapHeight, scrolled));
    tlWrap.style.setProperty('--tl-line-height', height + 'px');
  };
  window.addEventListener('scroll', updateTlLine, { passive: true });
  updateTlLine(); // 初期値セット
}

/* ============================================================
   HAMBURGER MENU
============================================================ */
const navToggle = document.getElementById('nav-toggle');
const mainNav = document.getElementById('main-nav');
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
