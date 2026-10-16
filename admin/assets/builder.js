/* Block builder — editor UI, live preview and saving.
 *
 * Data flow:
 *   state.blocks  = the draft (same shape the server resolves: see
 *                   includes/blocks-core.php).
 *   Every edit    -> markDirty() -> debounced POST of the draft to
 *                   preview.php -> the returned page is diffed into the
 *                   preview iframe, swapping only the blocks that changed.
 *   Save          -> POST to builder-save.php (nothing is stored before that).
 */
(function () {
  'use strict';

  var boot = JSON.parse(document.getElementById('enw-boot').textContent);
  var TYPES = boot.types;
  var LINKS = boot.links;
  var library = (boot.library || []).slice();
  var isEn = boot.lang === 'en';

  var state = {
    blocks: boot.state.blocks || [],
    meta: boot.state.meta || { title: '', description: '', keywords: '' },
    rev: boot.state.rev
  };
  // English values, shown as greyed placeholders when editing another language.
  var enBlocks = Array.isArray(boot.state.en.blocks) ? {} : (boot.state.en.blocks || {});
  var enMeta = boot.state.en.meta || {};

  var dirty = false;
  var saving = false;
  var openIds = {};
  var selectedId = null;
  var pendingScrollId = null;
  var cardEls = {};   // block id -> { card, body, sum }

  var $ = function (id) { return document.getElementById(id); };
  var blocksEl = $('bd-blocks');
  var metaEl = $('bd-meta');
  var sideEl = $('bd-side');
  var statusEl = $('bd-status');
  var saveBtn = $('bd-save');
  var frame = $('bd-frame');
  var frameWrap = $('bd-frame-wrap');
  var noteEl = $('bd-preview-note');

  // --- tiny DOM helper ---------------------------------------------------

  function el(tag, attrs, kids) {
    var n = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        var v = attrs[k];
        if (v === null || v === undefined || v === false) return;
        if (k === 'class') n.className = v;
        else if (k === 'text') n.textContent = v;
        else if (k.slice(0, 2) === 'on') n.addEventListener(k.slice(2), v);
        else n.setAttribute(k, v === true ? '' : v);
      });
    }
    (kids || []).forEach(function (c) {
      if (c) n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
    });
    return n;
  }

  function rid(prefix) {
    return prefix + Math.random().toString(36).slice(2, 10).replace(/[^a-z0-9]/g, '0');
  }

  function clone(o) { return JSON.parse(JSON.stringify(o)); }

  // --- status / alerts ---------------------------------------------------

  function setStatus(kind, text) {
    statusEl.textContent = text;
    statusEl.className = 'bd-status' + (kind ? ' is-' + kind : '');
  }

  function showAlert(msg) {
    var a = el('div', { class: 'alert alert-error bd-alert', text: msg });
    sideEl.insertBefore(a, sideEl.firstChild);
    setTimeout(function () { if (a.parentNode) a.parentNode.removeChild(a); }, 9000);
  }

  var noteTimer = null;
  function setNote(text) {
    clearTimeout(noteTimer);
    noteEl.textContent = text || '';
    noteEl.classList.toggle('is-visible', !!text);
  }

  function markDirty() {
    dirty = true;
    setStatus('dirty', 'Unsaved changes');
    saveBtn.disabled = false;
    schedulePreview();
  }

  // --- English placeholders ---------------------------------------------

  function enField(blockId, name) {
    var b = enBlocks[blockId];
    return b ? b[name] : undefined;
  }
  function enText(blockId, name) {
    var v = enField(blockId, name);
    return typeof v === 'string' ? v : '';
  }
  function enRow(blockId, name, itemId) {
    var list = enField(blockId, name);
    if (!Array.isArray(list)) return null;
    for (var i = 0; i < list.length; i++) {
      if (list[i].id === itemId) return list[i];
    }
    return null;
  }

  // --- form controls -----------------------------------------------------

  function textControl(label, value, placeholder, multiline, onInput, maxHint) {
    var input = multiline ? el('textarea', { rows: 3 }) : el('input', { type: 'text' });
    input.value = value || '';
    if (placeholder) input.placeholder = placeholder;
    var count = null;
    if (maxHint) {
      count = el('span', { class: 'bd-count' });
    }
    function refreshCount() {
      if (!count) return;
      var n = input.value.length;
      count.textContent = n + ' / ' + maxHint;
      count.classList.toggle('is-over', n > maxHint);
    }
    input.addEventListener('input', function () { onInput(input.value); refreshCount(); });
    refreshCount();
    return el('div', { class: 'field' }, [
      el('label', { class: 'field-label' }, [label, count]),
      input
    ]);
  }

  function selectControl(label, options, value, onChange) {
    var sel = el('select');
    Object.keys(options).forEach(function (k) {
      var o = el('option', { value: k, text: options[k] });
      if (k === value) o.selected = true;
      sel.appendChild(o);
    });
    sel.addEventListener('change', function () { onChange(sel.value); });
    return el('div', { class: 'field' }, [el('label', { class: 'field-label', text: label }), sel]);
  }

  function upload(file, done, btn) {
    var fd = new FormData();
    fd.append('file', file);
    fd.append('csrf_token', boot.csrf);
    var old = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Uploading…';
    function finish() { btn.disabled = false; btn.textContent = old; }
    fetch(boot.uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) {
        return r.json().catch(function () {
          return { ok: false, error: 'Unexpected server response (the file may be larger than the server upload limit).' };
        });
      })
      .then(function (res) {
        finish();
        if (!res.ok) { showAlert(res.error || 'Upload failed.'); return; }
        if (res.library) library = res.library;
        done(res.src);
      })
      .catch(function () { finish(); showAlert('Upload failed — network error.'); });
  }

  /** Thumbnail + upload button + "choose existing" menu for one {src} object. */
  function imagePicker(obj, onChange) {
    var wrap = el('div', { class: 'bd-imgpick' });
    function draw() {
      wrap.innerHTML = '';
      if (obj.src) {
        wrap.appendChild(el('img', { class: 'bd-thumb', src: boot.imgBase + obj.src, alt: '' }));
      } else {
        wrap.appendChild(el('div', { class: 'bd-thumb bd-thumb-empty', text: 'No image yet' }));
      }
      var file = el('input', { type: 'file', class: 'bd-file', accept: 'image/jpeg,image/png,image/webp' });
      var up = el('button', { type: 'button', class: 'btn-secondary bd-upload', text: obj.src ? 'Replace…' : 'Upload…' });
      up.addEventListener('click', function () { file.click(); });
      file.addEventListener('change', function () {
        if (file.files && file.files[0]) {
          upload(file.files[0], function (name) { obj.src = name; draw(); onChange(); }, up);
        }
      });
      var sel = el('select', { 'aria-label': 'Choose an existing image' });
      sel.appendChild(el('option', { value: '', text: 'Choose existing…' }));
      library.forEach(function (n) {
        var o = el('option', { value: n, text: n });
        if (n === obj.src) o.selected = true;
        sel.appendChild(o);
      });
      sel.addEventListener('change', function () {
        if (sel.value) { obj.src = sel.value; draw(); onChange(); }
      });
      wrap.appendChild(el('div', { class: 'bd-imgtools' }, [up, sel, file]));
    }
    draw();
    return wrap;
  }

  function subActions(i, list, redraw, noun) {
    function mv(dir) {
      var j = i + dir;
      if (j < 0 || j >= list.length) return;
      var t = list[i]; list[i] = list[j]; list[j] = t;
      redraw(); markDirty();
    }
    return el('span', { class: 'bd-sub-actions' }, [
      el('button', { type: 'button', class: 'btn-icon', title: 'Move up', text: '↑', onclick: function () { mv(-1); } }),
      el('button', { type: 'button', class: 'btn-icon', title: 'Move down', text: '↓', onclick: function () { mv(1); } }),
      el('button', {
        type: 'button', class: 'btn-icon btn-danger', title: 'Remove', text: '×',
        onclick: function () {
          if (!confirm('Remove this ' + noun + '? It is removed in every language.')) return;
          list.splice(i, 1); redraw(); markDirty();
        }
      })
    ]);
  }

  // --- one field of a block ---------------------------------------------

  function fieldControl(b, fd, onSummary) {
    var name = fd.name;
    switch (fd.type) {
      case 'text':
      case 'textarea':
        return textControl(fd.label, b.fields[name], isEn ? '' : enText(b.id, name), fd.type === 'textarea', function (v) {
          b.fields[name] = v; onSummary(); markDirty();
        });

      case 'select':
        return selectControl(fd.label, fd.options, b.fields[name], function (v) { b.fields[name] = v; markDirty(); });

      case 'link':
        return selectControl(fd.label, LINKS, b.fields[name], function (v) { b.fields[name] = v; markDirty(); });

      case 'checkbox':
        var cb = el('input', { type: 'checkbox' });
        cb.checked = !!b.fields[name];
        cb.addEventListener('change', function () { b.fields[name] = cb.checked; markDirty(); });
        return el('div', { class: 'field' }, [el('label', { class: 'bd-check' }, [cb, fd.label])]);

      case 'image':
        var img = b.fields[name];
        var enImg = enField(b.id, name) || {};
        var box = el('div', { class: 'field' }, [el('span', { class: 'field-label', text: fd.label })]);
        box.appendChild(imagePicker(img, markDirty));
        box.appendChild(textControl('Alt text (describe the photo)', img.alt, isEn ? '' : (enImg.alt || ''), false, function (v) { img.alt = v; markDirty(); }));
        if (fd.caption) {
          box.appendChild(textControl('Caption (shown under the photo)', img.caption, isEn ? '' : (enImg.caption || ''), false, function (v) { img.caption = v; markDirty(); }));
        }
        return box;

      case 'gallery':
        return galleryControl(b, fd);

      case 'repeater':
        return repeaterControl(b, fd);
    }
    return null;
  }

  function galleryControl(b, fd) {
    var list = b.fields[fd.name];
    var rows = el('div');
    var box = el('div', { class: 'field' }, [el('span', { class: 'field-label', text: fd.label }), rows]);
    function draw() {
      rows.innerHTML = '';
      list.forEach(function (it, i) {
        var enIt = enRow(b.id, fd.name, it.id);
        rows.appendChild(el('div', { class: 'bd-sub' }, [
          el('div', { class: 'bd-sub-head' }, [el('span', { text: 'Photo ' + (i + 1) }), subActions(i, list, draw, 'photo')]),
          imagePicker(it, markDirty),
          textControl('Alt text (describe the photo)', it.alt, isEn ? '' : (enIt ? enIt.alt : ''), false, function (v) { it.alt = v; markDirty(); })
        ]));
      });
    }
    draw();
    box.appendChild(el('button', {
      type: 'button', class: 'btn-secondary', text: '+ Add photo',
      onclick: function () { list.push({ id: rid('i_'), src: '', alt: '' }); draw(); markDirty(); }
    }));
    return box;
  }

  function repeaterControl(b, fd) {
    var list = b.fields[fd.name];
    var rows = el('div');
    var noun = (fd.item_label || 'Item');
    var box = el('div', { class: 'field' }, [el('span', { class: 'field-label', text: fd.label }), rows]);
    function draw() {
      rows.innerHTML = '';
      list.forEach(function (it, i) {
        var enIt = enRow(b.id, fd.name, it.id) || {};
        var kids = [el('div', { class: 'bd-sub-head' }, [el('span', { text: noun + ' ' + (i + 1) }), subActions(i, list, draw, noun.toLowerCase())])];
        fd.item_fields.forEach(function (sub) {
          kids.push(textControl(sub.label, it[sub.name], isEn ? '' : (enIt[sub.name] || ''), sub.type === 'textarea', function (v) { it[sub.name] = v; markDirty(); }));
        });
        rows.appendChild(el('div', { class: 'bd-sub' }, kids));
      });
    }
    draw();
    box.appendChild(el('button', {
      type: 'button', class: 'btn-secondary', text: '+ Add ' + noun.toLowerCase(),
      onclick: function () {
        var row = { id: rid('i_') };
        fd.item_fields.forEach(function (s) { row[s.name] = ''; });
        list.push(row); draw(); markDirty();
      }
    }));
    return box;
  }

  // --- block cards --------------------------------------------------------

  function plain(s) {
    return String(s || '').replace(/&[a-z#0-9]+;/gi, ' ').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
  }

  function summaryOf(b) {
    var key = TYPES[b.type].summary;
    if (!key) return '';
    var v = plain(b.fields[key]);
    if (!v && !isEn) v = plain(enText(b.id, key));
    return v || '(empty)';
  }

  function defaultFields(type) {
    var f = {};
    TYPES[type].fields.forEach(function (fd) {
      switch (fd.type) {
        case 'image': f[fd.name] = { src: '', alt: '', caption: '' }; break;
        case 'gallery':
        case 'repeater': f[fd.name] = []; break;
        case 'checkbox': f[fd.name] = !!fd.default; break;
        case 'select':
        case 'link': f[fd.name] = fd.default || ''; break;
        default: f[fd.name] = '';
      }
    });
    return f;
  }

  function reId(block) {
    TYPES[block.type].fields.forEach(function (fd) {
      if (fd.type === 'gallery' || fd.type === 'repeater') {
        (block.fields[fd.name] || []).forEach(function (it) { it.id = rid('i_'); });
      }
    });
  }

  var dragIndex = null;

  function blockCard(b, i) {
    var def = TYPES[b.type];
    var sum = el('span', { class: 'bd-card-sum', text: summaryOf(b) });
    var handle = el('span', { class: 'bd-handle', title: 'Drag to reorder', text: '⋮⋮' });
    var actions = el('span', { class: 'bd-card-actions' }, [
      el('button', { type: 'button', class: 'btn-icon', title: 'Move up', text: '↑', onclick: function () { moveBlock(i, -1); } }),
      el('button', { type: 'button', class: 'btn-icon', title: 'Move down', text: '↓', onclick: function () { moveBlock(i, 1); } }),
      el('button', { type: 'button', class: 'btn-icon', title: 'Duplicate', text: '⧉', onclick: function () { duplicateBlock(i); } }),
      el('button', { type: 'button', class: 'btn-icon btn-danger', title: 'Delete', text: '×', onclick: function () { deleteBlock(i); } })
    ]);
    var head = el('div', { class: 'bd-card-head' }, [
      handle,
      el('span', { class: 'bd-card-title' }, [el('span', { class: 'bd-card-type', text: def.label }), sum]),
      actions
    ]);

    var body = el('div', { class: 'bd-card-body' });
    if (!def.fields.length) {
      body.appendChild(el('p', { class: 'field-label', style: 'margin:12px 0 0', text: 'This block has no settings.' }));
    }
    def.fields.forEach(function (fd) {
      var c = fieldControl(b, fd, function () { sum.textContent = summaryOf(b); });
      if (c) body.appendChild(c);
    });

    var card = el('div', { class: 'bd-card', 'data-id': b.id }, [head, body]);
    cardEls[b.id] = { card: card, body: body, sum: sum };

    head.addEventListener('click', function (e) {
      if (e.target.closest('button') || e.target.closest('.bd-handle')) return;
      if (openIds[b.id]) { openIds[b.id] = false; applyOpenState(); }
      else { selectBlock(b.id, true); }
    });
    head.addEventListener('mouseenter', function () { toFrame({ enw: 'hover', id: b.id }); });
    head.addEventListener('mouseleave', function () { toFrame({ enw: 'hover', id: null }); });

    // Drag and drop (only while the handle is pressed, so text selection in fields still works).
    handle.addEventListener('mousedown', function () { card.draggable = true; });
    handle.addEventListener('mouseup', function () { card.draggable = false; });
    card.addEventListener('dragstart', function (e) {
      dragIndex = i;
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', String(i)); } catch (err) { /* ignore */ }
      card.classList.add('is-dragging');
    });
    card.addEventListener('dragend', function () {
      dragIndex = null; card.draggable = false;
      Array.prototype.forEach.call(blocksEl.querySelectorAll('.bd-card'), function (c) {
        c.classList.remove('is-dragging', 'is-dragover-before', 'is-dragover-after');
      });
    });
    card.addEventListener('dragover', function (e) {
      if (dragIndex === null) return;
      e.preventDefault();
      var r = card.getBoundingClientRect();
      var after = (e.clientY - r.top) > r.height / 2;
      card.classList.toggle('is-dragover-after', after);
      card.classList.toggle('is-dragover-before', !after);
    });
    card.addEventListener('dragleave', function () {
      card.classList.remove('is-dragover-before', 'is-dragover-after');
    });
    card.addEventListener('drop', function (e) {
      if (dragIndex === null) return;
      e.preventDefault();
      var r = card.getBoundingClientRect();
      var after = (e.clientY - r.top) > r.height / 2;
      var to = after ? i + 1 : i;
      var from = dragIndex;
      dragIndex = null;
      if (from < to) to -= 1;
      if (from !== to) {
        var item = state.blocks.splice(from, 1)[0];
        state.blocks.splice(to, 0, item);
        renderBlocks(); markDirty();
      } else {
        card.classList.remove('is-dragover-before', 'is-dragover-after');
      }
    });

    return card;
  }

  function applyOpenState() {
    state.blocks.forEach(function (b) {
      var c = cardEls[b.id];
      if (!c) return;
      c.body.hidden = !openIds[b.id];
      c.card.classList.toggle('is-selected', selectedId === b.id);
    });
  }

  function selectBlock(id, scrollPreview) {
    selectedId = id;
    openIds[id] = true;
    applyOpenState();
    var c = cardEls[id];
    if (c) c.card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    toFrame({ enw: scrollPreview ? 'scrollto' : 'mark', id: id });
  }

  function insertBar(index) {
    return el('div', { class: 'bd-insert' }, [
      el('button', { type: 'button', text: '+ Add block here', onclick: function () { openPicker(index); } })
    ]);
  }

  function renderBlocks() {
    cardEls = {};
    blocksEl.innerHTML = '';
    state.blocks.forEach(function (b, i) {
      if (!TYPES[b.type]) return;
      blocksEl.appendChild(insertBar(i));
      blocksEl.appendChild(blockCard(b, i));
    });
    if (!state.blocks.length) {
      blocksEl.appendChild(el('p', { class: 'field-label', text: 'This page has no blocks yet.' }));
    }
    blocksEl.appendChild(el('button', {
      type: 'button', class: 'btn-secondary bd-add-last', text: '+ Add block',
      onclick: function () { openPicker(state.blocks.length); }
    }));
    applyOpenState();
  }

  // --- block actions ------------------------------------------------------

  function moveBlock(i, dir) {
    var j = i + dir;
    if (j < 0 || j >= state.blocks.length) return;
    var t = state.blocks[i]; state.blocks[i] = state.blocks[j]; state.blocks[j] = t;
    renderBlocks(); markDirty();
  }

  function deleteBlock(i) {
    if (!confirm('Delete this block? It is removed from every language.')) return;
    state.blocks.splice(i, 1);
    renderBlocks(); markDirty();
  }

  function duplicateBlock(i) {
    var c = clone(state.blocks[i]);
    c.id = rid('b_');
    reId(c);
    state.blocks.splice(i + 1, 0, c);
    openIds[c.id] = true;
    pendingScrollId = c.id;
    renderBlocks(); markDirty();
  }

  function addBlock(type, index) {
    var b = { id: rid('b_'), type: type, fields: defaultFields(type) };
    state.blocks.splice(index, 0, b);
    openIds[b.id] = true;
    selectedId = b.id;
    pendingScrollId = b.id;
    renderBlocks(); markDirty();
    var c = cardEls[b.id];
    if (c) c.card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }

  // --- block picker -------------------------------------------------------

  var picker = $('bd-picker');
  var pickerIndex = 0;

  function openPicker(index) {
    pickerIndex = index;
    picker.hidden = false;
    var first = picker.querySelector('.bd-picker-item');
    if (first) first.focus();
  }
  function closePicker() { picker.hidden = true; }

  (function buildPicker() {
    var grid = $('bd-picker-grid');
    Object.keys(TYPES).forEach(function (key) {
      grid.appendChild(el('button', {
        type: 'button', class: 'bd-picker-item',
        onclick: function () { closePicker(); addBlock(key, pickerIndex); }
      }, [el('strong', { text: TYPES[key].label }), el('span', { text: TYPES[key].description })]));
    });
    $('bd-picker-close').addEventListener('click', closePicker);
    picker.addEventListener('click', function (e) { if (e.target === picker) closePicker(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !picker.hidden) closePicker(); });
  })();

  // --- SEO meta panel -----------------------------------------------------

  function renderMeta() {
    metaEl.innerHTML = '';
    var d = el('details', { class: 'bd-meta' });
    d.appendChild(el('summary', { text: 'Page settings (SEO)' }));
    var body = el('div', { class: 'bd-meta-body' });
    [
      ['title', 'Page title', false, 60],
      ['description', 'Meta description', true, 160],
      ['keywords', 'Meta keywords', false, 0]
    ].forEach(function (m) {
      body.appendChild(textControl(m[1], state.meta[m[0]], isEn ? '' : (enMeta[m[0]] || ''), m[2], function (v) {
        state.meta[m[0]] = v; markDirty();
      }, m[3]));
    });
    d.appendChild(body);
    metaEl.appendChild(d);
  }

  // --- live preview -------------------------------------------------------

  var frameReady = false;
  var pvTimer = null;
  var pvSeq = 0;

  function toFrame(msg) {
    if (frameReady && frame.contentWindow) {
      frame.contentWindow.postMessage(msg, location.origin);
    }
  }

  // Must match the hash function in preview.php.
  function hash(str) {
    var h = 5381, i = str.length;
    while (i) { h = (h * 33) ^ str.charCodeAt(--i); }
    return String(h >>> 0);
  }

  function schedulePreview() {
    clearTimeout(pvTimer);
    pvTimer = setTimeout(refreshPreview, 250);
  }

  function refreshPreview() {
    if (!frameReady) return;
    var seq = ++pvSeq;
    setNote('Updating preview…');
    fetch(boot.previewUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': boot.csrf },
      body: JSON.stringify({ blocks: state.blocks })
    }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.text();
    }).then(function (html) {
      if (seq !== pvSeq) return;   // a newer edit is already on its way
      patchPreview(html);
      setNote('');
    }).catch(function () {
      if (seq !== pvSeq) return;
      setNote('Preview unavailable — are you still logged in?');
      noteTimer = setTimeout(function () { setNote(''); }, 5000);
    });
  }

  /** Swap only the blocks that changed, keeping the iframe's scroll position. */
  function patchPreview(html) {
    var idoc = frame.contentDocument;
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var fresh = doc.querySelector('[data-enw-blocks]');
    var cur = idoc && idoc.querySelector('[data-enw-blocks]');
    if (!fresh || !cur) {
      frame.contentWindow.location.reload();
      return;
    }

    var existing = {};
    Array.prototype.forEach.call(cur.children, function (c) {
      var id = c.getAttribute('data-block-id');
      if (id) existing[id] = c;
    });

    var used = {};
    Array.prototype.slice.call(fresh.children).forEach(function (nk, idx) {
      var id = nk.getAttribute('data-block-id');
      if (!id) return;
      var h = hash(nk.outerHTML);
      var node = existing[id];
      if (!node || node.getAttribute('data-enw-h') !== h) {
        var imp = idoc.importNode(nk, true);
        imp.setAttribute('data-enw-h', h);
        if (node && node.parentNode === cur) cur.replaceChild(imp, node);
        node = imp;
      }
      used[id] = true;
      var at = cur.children[idx] || null;
      if (at !== node) cur.insertBefore(node, at);
    });

    Object.keys(existing).forEach(function (id) {
      if (!used[id] && existing[id].parentNode === cur) cur.removeChild(existing[id]);
    });

    if (pendingScrollId) {
      toFrame({ enw: 'scrollto', id: pendingScrollId });
      pendingScrollId = null;
    } else if (selectedId) {
      toFrame({ enw: 'mark', id: selectedId });
    }
  }

  frame.addEventListener('load', function () {
    frameReady = true;
    if (dirty) refreshPreview();
  });

  window.addEventListener('message', function (e) {
    if (e.origin !== location.origin || !e.data || e.data.enw !== 'select') return;
    if (cardEls[e.data.id]) selectBlock(e.data.id, false);
  });

  Array.prototype.forEach.call(document.querySelectorAll('[data-device]'), function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.getAttribute('data-device');
      frameWrap.className = 'bd-frame-wrap' + (d === 'desktop' ? '' : ' is-' + d);
      Array.prototype.forEach.call(document.querySelectorAll('[data-device]'), function (b) {
        b.classList.toggle('is-active', b === btn);
      });
    });
  });

  // --- saving -------------------------------------------------------------

  function save() {
    if (saving || !dirty) return;
    saving = true;
    saveBtn.disabled = true;
    setStatus('', 'Saving…');
    var snapshot = JSON.stringify(state.blocks) + JSON.stringify(state.meta);

    fetch(boot.saveUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': boot.csrf },
      body: JSON.stringify({ page: boot.page, lang: boot.lang, rev: state.rev, meta: state.meta, blocks: state.blocks })
    }).then(function (r) {
      return r.json().catch(function () { return { ok: false, error: 'Unexpected server response.' }; });
    }).then(function (res) {
      saving = false;
      if (!res.ok) {
        setStatus('error', res.conflict ? 'Out of date — reload' : 'Save failed');
        showAlert(res.error || 'Could not save.');
        saveBtn.disabled = false;
        return;
      }
      state.rev = res.rev;
      var unchanged = (JSON.stringify(state.blocks) + JSON.stringify(state.meta)) === snapshot;
      if (unchanged) {
        state.blocks = res.blocks;       // exactly what the server stored
        dirty = false;
        renderBlocks();
        setStatus('', 'All changes saved');
        saveBtn.disabled = true;
        refreshPreview();
      } else {
        // The user kept typing while the save was in flight: keep their newer draft.
        markDirty();
      }
    }).catch(function () {
      saving = false;
      setStatus('error', 'Network error');
      saveBtn.disabled = false;
    });
  }

  saveBtn.addEventListener('click', save);
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
      e.preventDefault();
      save();
    }
  });
  window.addEventListener('beforeunload', function (e) {
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
  });
  Array.prototype.forEach.call(document.querySelectorAll('[data-lang-link]'), function (a) {
    a.addEventListener('click', function (e) {
      if (dirty && !confirm('You have unsaved changes. Leave without saving?')) e.preventDefault();
    });
  });

  // --- start --------------------------------------------------------------

  $('bd-notice').textContent = isEn
    ? 'Order, photos and options are shared by every language. Text is translated per language.'
    : 'You are editing the ' + boot.lang.toUpperCase() + ' text. Order, photos and options are shared with every language. ' +
      'Leave a text empty to use the English text (shown greyed out in each field).';

  saveBtn.disabled = true;
  renderMeta();
  renderBlocks();

  if (boot.isDefault) {
    // Built-in starting content (e.g. the 404 page): it is already live, but
    // nothing is stored yet. Saving keeps it and makes it editable.
    dirty = true;
    setStatus('dirty', 'Default content — press Save to keep it');
    saveBtn.disabled = false;
  }
})();
