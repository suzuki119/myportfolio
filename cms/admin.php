<?php
require_once __DIR__ . '/config.php';
session_name(CMS_SESSION);
session_start();
if (!isset($_SESSION['cms_admin_logged_in'])) {
  header('Location: login.php');
  exit;
}
$works = json_decode(file_get_contents(__DIR__ . '/works.json'), true) ?? [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Works CMS — 管理画面</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300&family=Space+Mono:wght@400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-body">

<header>
  <h1>Works CMS</h1>
  <div class="header__links">
    <a href="../index.php" target="_blank">ポートフォリオ ↗</a>
    <a href="preview.php" target="_blank">一覧プレビュー ↗</a>
    <a href="logout.php" class="link--danger">ログアウト</a>
  </div>
</header>

<div class="layout">

  <!-- 左：一覧 -->
  <div class="list-pane">
    <div class="list-header">
      <h2>作品一覧 <span id="count"></span></h2>
      <button class="btn btn--primary" onclick="openNew()">＋ 追加</button>
    </div>
    <div class="works-list" id="worksList"></div>
  </div>

  <!-- 右：フォーム -->
  <div class="form-pane">
    <h2 id="formTitle">作品を選択してください</h2>
    <div id="formBody">
      <div class="empty-state">← 左の一覧から作品を選ぶか<br>「追加」ボタンを押してください</div>
    </div>
  </div>

</div>

<div class="toast" id="toast"></div>

<script>
  let works = [];
  let editingId = null;
  let currentTags = [];
  let currentSections = [];
  let activeTab = 'basic';
  let dragSrc = null;

  /* ── API通信 ── */
  async function api(action, body = null) {
    const opts = { method: body ? 'POST' : 'GET' };
    if (body) {
      opts.headers = { 'Content-Type': 'application/json' };
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(`api.php?action=${action}`, opts);
    return res.json();
  }

  /* ── 初期読み込み ── */
  async function init() {
    works = await api('list');
    renderList();
  }

  /* ── トースト ── */
  function toast(msg, type = 'ok') {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = `toast ${type} show`;
    setTimeout(() => el.classList.remove('show'), 2500);
  }

  /* ── 一覧レンダリング ── */
  function renderList() {
    const list = document.getElementById('worksList');
    document.getElementById('count').textContent = `(${works.length})`;
    list.innerHTML = '';

    works.forEach((w, i) => {
      const item = document.createElement('div');
      item.className = 'work-item' + (w.id === editingId ? ' active' : '');
      item.draggable = true;
      item.dataset.index = i;

      item.innerHTML = `
        <div class="work-item__info" onclick="openEdit(${w.id})"
          <div class="work-item__title">${w.title}</div>
          <div class="work-item__meta">${w.period}</div>
          <div class="work-item__tags">
            ${w.tags.map(t => `<span class="tag">${t}</span>`).join('')}
          </div>
        </div>
        <div class="work-item__actions">
          <a class="icon-btn" title="詳細ページを見る" href="work.php?id=${w.id}" target="_blank">↗</a>
          <button class="icon-btn" title="上へ" onclick="moveUp(${i})">↑</button>
          <button class="icon-btn" title="下へ" onclick="moveDown(${i})">↓</button>
          <button class="icon-btn delete" title="削除" onclick="deleteWork(${w.id})">✕</button>
        </div>
      `;

      // ドラッグ＆ドロップ
      item.addEventListener('dragstart', () => { dragSrc = i; item.classList.add('dragging'); });
      item.addEventListener('dragend', () => item.classList.remove('dragging'));
      item.addEventListener('dragover', e => { e.preventDefault(); item.classList.add('drag-over'); });
      item.addEventListener('dragleave', () => item.classList.remove('drag-over'));
      item.addEventListener('drop', async e => {
        e.preventDefault();
        item.classList.remove('drag-over');
        if (dragSrc === null || dragSrc === i) return;
        const moved = works.splice(dragSrc, 1)[0];
        works.splice(i, 0, moved);
        dragSrc = null;
        renderList();
        await api('reorder', { ids: works.map(w => w.id) });
      });

      list.appendChild(item);
    });
  }

  /* ── 新規追加フォーム ── */
  function openNew() {
    editingId = null;
    currentTags = [];
    currentSections = [];
    activeTab = 'basic';
    renderList();
    renderForm(null);
  }

  /* ── 編集フォーム ── */
  function openEdit(id) {
    editingId = id;
    const w = works.find(w => w.id === id);
    currentTags = [...(w.tags || [])];
    currentSections = JSON.parse(JSON.stringify(w.detail?.sections || []));
    renderList();
    renderForm(w);
  }

  function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('is-hidden', p.dataset.panel !== tab));
  }

  function renderForm(w) {
    const isNew = !w;
    const d = w?.detail || {};
    document.getElementById('formTitle').textContent = isNew ? '新規追加' : w.title;

    document.getElementById('formBody').innerHTML = `
      ${!isNew ? `
      <div class="tab-bar">
        <button class="tab-btn ${activeTab==='basic'?'active':''}" data-tab="basic" onclick="switchTab('basic')">基本情報</button>
        <button class="tab-btn ${activeTab==='detail'?'active':''}" data-tab="detail" onclick="switchTab('detail')">詳細ページ</button>
      </div>` : ''}

      <!-- 基本情報タブ -->
      <div class="tab-panel ${!isNew && activeTab!=='basic' ? 'is-hidden' : ''}" data-panel="basic">
        <div class="form-group">
          <label>タイトル</label>
          <input id="f_title" type="text" value="${esc(w?.title)}" placeholder="MYBLOG">
        </div>
        <div class="form-group">
          <label>期間</label>
          <input id="f_period" type="text" value="${esc(w?.period)}" placeholder="2025.06 – 08">
        </div>
        <div class="form-group">
          <label>タグ（Enterで追加）</label>
          <div class="tags-input-wrap" id="tagsWrap"></div>
        </div>
        <div class="form-group">
          <label>作品画像</label>
          <div class="img-field" id="imgField">
            ${w?.image ? `<img class="img-preview" id="imgPreviewEl" src="${buildPreviewSrc(w.image)}" alt="">` : ''}
            <div class="img-actions">
              <input type="file" id="imgFile" accept="image/*" class="u-hidden" onchange="uploadImage(this)">
              <button type="button" class="upload-btn" onclick="document.getElementById('imgFile').click()">↑ ファイルをアップロード</button>
              ${w?.image ? `<button type="button" class="btn btn--danger btn--sm" onclick="clearImage()">画像を削除</button>` : ''}
            </div>
            <div class="input-row">
              <input id="f_image" type="text" class="input--flex" value="${esc(w?.image)}" placeholder="または画像URLを直接入力（https://...）" oninput="onImageUrlChange(this.value)">
            </div>
            <p class="img-url-hint">jpg / png / webp / gif  ·  最大10MB</p>
          </div>
        </div>
        <div class="form-actions">
          <button class="btn btn--primary" onclick="submitForm(${isNew})">保存</button>
          <button class="btn btn--ghost" onclick="cancelForm()">キャンセル</button>
          ${!isNew ? `<button class="btn btn--danger" onclick="deleteWork(${w.id})">削除</button>` : ''}
          ${!isNew ? `<a class="preview-link" href="work.php?id=${w.id}" target="_blank">詳細ページを見る ↗</a>` : ''}
        </div>
      </div>

      <!-- 詳細ページタブ -->
      ${!isNew ? `
      <div class="tab-panel ${activeTab!=='detail' ? 'is-hidden' : ''}" data-panel="detail">
        <div class="form-group">
          <label>ヒーロータイトル</label>
          <input id="d_hero_title" type="text" value="${esc(d.hero_title ?? w?.title)}" placeholder="MYBLOG">
        </div>
        <div class="form-group">
          <label>制作期間（表示用）</label>
          <input id="d_meta_period" type="text" value="${esc(d.meta_period)}" placeholder="2025年6月 – 8月（約3ヶ月）">
        </div>
        <div class="form-group">
          <label>種別（改行可）</label>
          <textarea id="d_meta_type" rows="2" placeholder="個人制作&#10;ブログサイト">${esc(d.meta_type)}</textarea>
        </div>
        <div class="form-group">
          <label>外部リンクURL</label>
          <input id="d_external_url" type="text" value="${esc(d.external_url)}" placeholder="https://...">
        </div>

        <label class="label--gap">セクション</label>
        <div class="section-list" id="sectionList"></div>
        <button class="add-section-btn" onclick="addSection()">＋ セクションを追加</button>

        <div class="form-actions form-actions--mt">
          <button class="btn btn--primary" onclick="submitDetail()">詳細を保存</button>
          <a class="preview-link" href="work.php?id=${w.id}" target="_blank">詳細ページを見る ↗</a>
        </div>
      </div>` : ''}
    `;

    renderTags();
    document.getElementById('tagsWrap')?.addEventListener('click', () =>
      document.getElementById('tagInput')?.focus()
    );

    if (!isNew) renderSections();
  }

  /* ── セクション一覧レンダリング ── */
  function renderSections() {
    const list = document.getElementById('sectionList');
    if (!list) return;
    list.innerHTML = '';
    currentSections.forEach((sec, i) => {
      const card = document.createElement('div');
      card.className = 'section-card';
      card.innerHTML = `
        <div class="section-card-head" onclick="toggleSection(${i})">
          <span class="section-card-label">${sec.label || '(無題)'}</span>
          <div class="section-card-actions">
            <button class="icon-btn" title="上へ" onclick="event.stopPropagation();moveSec(${i},-1)">↑</button>
            <button class="icon-btn" title="下へ" onclick="event.stopPropagation();moveSec(${i},1)">↓</button>
            <button class="icon-btn delete" title="削除" onclick="event.stopPropagation();deleteSec(${i})">✕</button>
          </div>
        </div>
        <div class="section-card-body" id="secBody_${i}">
          <div class="form-group">
            <label>ID（アンカー用）</label>
            <input type="text" value="${esc(sec.id)}" oninput="updateSec(${i},'id',this.value)" placeholder="overview">
          </div>
          <div class="form-group">
            <label>ラベル</label>
            <input type="text" value="${esc(sec.label)}" oninput="updateSec(${i},'label',this.value);updateSecHead(${i})" placeholder="Overview">
          </div>
          <div class="form-group">
            <label>タイトル</label>
            <input type="text" value="${esc(sec.title)}" oninput="updateSec(${i},'title',this.value)" placeholder="セクションタイトル">
          </div>
          <div class="form-group">
            <label>セクション画像（タイトル下に表示）</label>
            <div class="img-field" id="secImgField_${i}">
              ${sec.image ? `<img class="img-preview" id="secImgPreview_${i}" src="${buildPreviewSrc(sec.image)}" alt="">` : ''}
              <div class="img-actions">
                <input type="file" id="secImgFile_${i}" accept="image/*" class="u-hidden" onchange="uploadSecImage(this,${i})">
                <button type="button" class="upload-btn" onclick="document.getElementById('secImgFile_${i}').click()">↑ ファイルをアップロード</button>
                ${sec.image ? `<button type="button" class="btn btn--danger btn--sm" id="secImgClear_${i}" onclick="clearSecImage(${i})">画像を削除</button>` : ''}
              </div>
              <div class="input-row">
                <input id="secImgUrl_${i}" type="text" class="input--flex" value="${esc(sec.image)}" placeholder="またはURLを直接入力" oninput="updateSec(${i},'image',this.value);showSecImagePreview(${i},this.value)">
              </div>
              <p class="img-url-hint">jpg / png / webp / gif &nbsp;·&nbsp; 最大10MB</p>
            </div>
          </div>
          <div class="form-group">
            <label>本文（段落間は空行で区切る）</label>
            <textarea rows="5" oninput="updateSec(${i},'body',this.value)">${esc(sec.body)}</textarea>
          </div>
          <div class="form-group">
            <label>ハイライトボックス（任意）</label>
            <textarea rows="2" oninput="updateSec(${i},'highlight',this.value)">${esc(sec.highlight)}</textarea>
          </div>
          <div class="form-group">
            <label>CTAボタンテキスト</label>
            <input type="text" value="${esc(sec.cta_text)}" oninput="updateSec(${i},'cta_text',this.value)" placeholder="サイトを見る">
          </div>
          <div class="form-group">
            <label>CTAボタンURL</label>
            <input type="text" value="${esc(sec.cta_url)}" oninput="updateSec(${i},'cta_url',this.value)" placeholder="https://...">
          </div>
        </div>
      `;
      list.appendChild(card);
    });
  }

  function esc(v) {
    if (v === null || v === undefined) return '';
    return String(v).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function toggleSection(i) {
    const body = document.getElementById(`secBody_${i}`);
    if (body) body.classList.toggle('open');
  }

  function updateSec(i, key, val) {
    currentSections[i][key] = val;
  }

  function updateSecHead(i) {
    const label = document.querySelector(`#secBody_${i}`)?.previousElementSibling?.querySelector('.section-card-label');
    if (label) label.textContent = currentSections[i].label || '(無題)';
  }

  function addSection() {
    currentSections.push({ id: 'section' + (currentSections.length + 1), label: '', title: '', body: '', highlight: '', cta_text: '', cta_url: '' });
    renderSections();
    // 新しいセクションを開く
    const newIdx = currentSections.length - 1;
    const body = document.getElementById(`secBody_${newIdx}`);
    if (body) body.classList.add('open');
  }

  function deleteSec(i) {
    if (!confirm('このセクションを削除しますか？')) return;
    currentSections.splice(i, 1);
    renderSections();
  }

  function moveSec(i, dir) {
    const j = i + dir;
    if (j < 0 || j >= currentSections.length) return;
    [currentSections[i], currentSections[j]] = [currentSections[j], currentSections[i]];
    renderSections();
  }

  function renderTags() {
    const wrap = document.getElementById('tagsWrap');
    if (!wrap) return;
    wrap.innerHTML = '';
    currentTags.forEach((t, i) => {
      const chip = document.createElement('span');
      chip.className = 'tag-chip';
      chip.innerHTML = `${t}<button type="button" onclick="removeTag(${i})">✕</button>`;
      wrap.appendChild(chip);
    });
    const input = document.createElement('input');
    input.id = 'tagInput';
    input.type = 'text';
    input.placeholder = 'WordPress';
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const val = input.value.trim();
        if (val && !currentTags.includes(val)) { currentTags.push(val); renderTags(); }
        else input.value = '';
      }
      if (e.key === 'Backspace' && input.value === '' && currentTags.length) {
        currentTags.pop(); renderTags();
      }
    });
    wrap.appendChild(input);
  }

  function removeTag(i) { currentTags.splice(i, 1); renderTags(); }

  /* ── 画像パスを admin.php 用のプレビューパスに変換 ── */
  function buildPreviewSrc(path) {
    if (!path) return '';
    return path.startsWith('http') ? path : '../' + path;
  }

  /* ── 画像アップロード（アップロード完了と同時に自動保存） ── */
  async function uploadImage(input) {
    if (!editingId) { toast('先に作品を保存してから画像をアップロードしてください', 'err'); return; }

    const file = input.files[0];
    if (!file) return;

    // ファイル形式を事前チェック
    const ext = file.name.split('.').pop().toLowerCase();
    const allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!allowed.includes(ext)) {
      toast('jpg / png / webp / gif のみアップロードできます', 'err');
      input.value = '';
      return;
    }
    // ファイルサイズチェック（2MB = PHPデフォルト上限）
    if (file.size > 2 * 1024 * 1024) {
      toast('ファイルサイズは2MB以下にしてください', 'err');
      input.value = '';
      return;
    }

    const formData = new FormData();
    formData.append('file', file);

    // ① アップロードボタンを無効化
    const btn = input.nextElementSibling;
    if (btn) btn.disabled = true;
    toast('アップロード中...', 'ok');

    try {
      const uploadRes = await fetch('api.php?action=upload', { method: 'POST', body: formData });
      if (!uploadRes.ok) throw new Error('HTTP ' + uploadRes.status);
      const uploadData = await uploadRes.json();
      if (!uploadData.ok) { toast(uploadData.error || 'アップロード失敗', 'err'); return; }

      // ② UIに反映
      const imageInput = document.getElementById('f_image');
      if (imageInput) imageInput.value = uploadData.path;
      showImagePreview(uploadData.path);

      // ③ 現在の作品データに画像パスを加えてすぐ保存
      const existing = works.find(w => w.id === editingId);
      if (existing) {
        const item = { ...existing, image: uploadData.path };
        const saveRes = await api('save', item);
        if (saveRes.ok) {
          works = await api('list');
          // works配列を更新するが、フォームは再描画しない（入力中の値が消えるため）
          toast('画像を保存しました ✓');
        } else {
          toast('画像のアップロードは完了しましたが保存に失敗しました。「保存」を押してください', 'err');
        }
      }
    } catch (e) {
      toast('通信エラーが発生しました: ' + e.message, 'err');
    } finally {
      if (btn) btn.disabled = false;
      input.value = '';
    }
  }

  function onImageUrlChange(val) {
    if (val) showImagePreview(val);
    else clearImagePreview();
  }

  function showImagePreview(path) {
    const field = document.getElementById('imgField');
    if (!field) return;
    let img = document.getElementById('imgPreviewEl');
    if (!img) {
      img = document.createElement('img');
      img.id = 'imgPreviewEl';
      img.className = 'img-preview';
      field.insertBefore(img, field.firstChild);
    }
    img.src = buildPreviewSrc(path);

    // 削除ボタンがなければ追加
    if (!document.getElementById('imgClearBtn')) {
      const actions = field.querySelector('.img-actions');
      const btn = document.createElement('button');
      btn.id = 'imgClearBtn';
      btn.type = 'button';
      btn.className = 'btn btn--danger';
      btn.style.cssText = 'font-size:9px;padding:6px 12px';
      btn.textContent = '画像を削除';
      btn.onclick = clearImage;
      actions.appendChild(btn);
    }
  }

  function clearImage() {
    const img = document.getElementById('imgPreviewEl');
    if (img) img.remove();
    const btn = document.getElementById('imgClearBtn');
    if (btn) btn.remove();
    const input = document.getElementById('f_image');
    if (input) input.value = '';
  }

  function clearImagePreview() {
    const img = document.getElementById('imgPreviewEl');
    if (img) img.remove();
    const btn = document.getElementById('imgClearBtn');
    if (btn) btn.remove();
  }

  /* ── セクション画像アップロード ── */
  async function uploadSecImage(input, secIdx) {
    const file = input.files[0];
    if (!file) return;

    const ext = file.name.split('.').pop().toLowerCase();
    const allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!allowed.includes(ext)) {
      toast('jpg / png / webp / gif のみアップロードできます', 'err');
      input.value = ''; return;
    }
    if (file.size > 10 * 1024 * 1024) {
      toast('ファイルサイズは10MB以下にしてください', 'err');
      input.value = ''; return;
    }

    const formData = new FormData();
    formData.append('file', file);
    const btn = input.nextElementSibling;
    if (btn) btn.disabled = true;
    toast('アップロード中...', 'ok');

    try {
      const res = await fetch('api.php?action=upload', { method: 'POST', body: formData });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      if (!data.ok) { toast(data.error || 'アップロード失敗', 'err'); return; }

      currentSections[secIdx].image = data.path;
      const urlInput = document.getElementById(`secImgUrl_${secIdx}`);
      if (urlInput) urlInput.value = data.path;
      showSecImagePreview(secIdx, data.path);
      toast('画像を設定しました。「詳細を保存」で確定してください ✓');
    } catch (e) {
      toast('通信エラー: ' + e.message, 'err');
    } finally {
      if (btn) btn.disabled = false;
      input.value = '';
    }
  }

  function showSecImagePreview(secIdx, path) {
    const field = document.getElementById(`secImgField_${secIdx}`);
    if (!field) return;
    let img = document.getElementById(`secImgPreview_${secIdx}`);
    if (!img) {
      img = document.createElement('img');
      img.id = `secImgPreview_${secIdx}`;
      img.className = 'img-preview';
      field.insertBefore(img, field.firstChild);
    }
    img.src = path ? buildPreviewSrc(path) : '';

    if (path && !document.getElementById(`secImgClear_${secIdx}`)) {
      const actions = field.querySelector('.img-actions');
      const clearBtn = document.createElement('button');
      clearBtn.id = `secImgClear_${secIdx}`;
      clearBtn.type = 'button';
      clearBtn.className = 'btn btn--danger btn--sm';
      clearBtn.textContent = '画像を削除';
      clearBtn.onclick = () => clearSecImage(secIdx);
      actions.appendChild(clearBtn);
    }
  }

  function clearSecImage(secIdx) {
    currentSections[secIdx].image = '';
    const img = document.getElementById(`secImgPreview_${secIdx}`);
    if (img) img.remove();
    const btn = document.getElementById(`secImgClear_${secIdx}`);
    if (btn) btn.remove();
    const urlInput = document.getElementById(`secImgUrl_${secIdx}`);
    if (urlInput) urlInput.value = '';
  }

  /* ── 基本情報を保存 ── */
  async function submitForm(isNew) {
    const title  = document.getElementById('f_title').value.trim();
    const period = document.getElementById('f_period').value.trim();
    const image  = document.getElementById('f_image')?.value.trim() ?? '';

    if (!title) { toast('タイトルを入力してください', 'err'); return; }

    // 既存の detail を保持
    const existing = isNew ? null : works.find(w => w.id === editingId);
    const item = {
      id: isNew ? null : editingId,
      title, period, image,
      tags: [...currentTags],
      detail: existing?.detail ?? {}
    };

    const res = await api('save', item);
    if (!res.ok) { toast('保存に失敗しました', 'err'); return; }

    works = await api('list');
    editingId = res.id;
    renderList();
    openEdit(res.id);
    toast('保存しました ✓');
  }

  /* ── 詳細ページを保存 ── */
  async function submitDetail() {
    const existing = works.find(w => w.id === editingId);
    if (!existing) return;

    const detail = {
      hero_title:  document.getElementById('d_hero_title').value.trim(),
      meta_period: document.getElementById('d_meta_period').value.trim(),
      meta_type:   document.getElementById('d_meta_type').value,
      external_url: document.getElementById('d_external_url').value.trim(),
      sections:    JSON.parse(JSON.stringify(currentSections)),
    };

    const item = { ...existing, detail };
    const res = await api('save', item);
    if (!res.ok) { toast('保存に失敗しました', 'err'); return; }

    works = await api('list');
    renderList();
    // 詳細タブのまま再表示
    activeTab = 'detail';
    openEdit(editingId);
    toast('詳細ページを保存しました ✓');
  }

  /* ── キャンセル ── */
  function cancelForm() {
    editingId = null;
    currentTags = [];
    currentSections = [];
    activeTab = 'basic';
    renderList();
    document.getElementById('formTitle').textContent = '作品を選択してください';
    document.getElementById('formBody').innerHTML =
      '<div class="empty-state">← 左の一覧から作品を選ぶか<br>「追加」ボタンを押してください</div>';
  }

  /* ── 削除 ── */
  async function deleteWork(id) {
    if (!confirm('削除しますか？')) return;
    const res = await api('delete', { id });
    if (!res.ok) { toast('削除に失敗しました', 'err'); return; }
    works = await api('list');
    cancelForm();
    toast('削除しました');
  }

  /* ── 並び替え ── */
  async function moveUp(i) {
    if (i === 0) return;
    [works[i-1], works[i]] = [works[i], works[i-1]];
    renderList();
    await api('reorder', { ids: works.map(w => w.id) });
  }

  async function moveDown(i) {
    if (i === works.length - 1) return;
    [works[i], works[i+1]] = [works[i+1], works[i]];
    renderList();
    await api('reorder', { ids: works.map(w => w.id) });
  }

  init();
</script>
</body>
</html>
