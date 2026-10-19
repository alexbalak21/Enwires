/* Header & footer editor.
 *
 * D  = the draft (flat: shared settings + this language's texts).
 * Every edit -> debounced POST of D into the preview iframe (a real page
 * rendered with the draft). Save -> POST to layout-save.php; nothing is
 * stored before that.
 */
(function () {
  'use strict';

  var boot = JSON.parse(document.getElementById('enw-boot').textContent);
  var lang = boot.lang;
  var isEn = lang === 'en';
  var D = boot.state.draft;
  var EN = boot.state.en;
  var rev = boot.state.rev;
  var library = (boot.library || []).slice();

  var dirty = false;
  var saving = false;
  var lastSection = 'header';
  var scrollAfterLoad = false;

  var $ = function (id) { return document.getElementById(id); };
  var area = $('lp-form-area');
  var sideEl = $('bd-side');
  var statusEl = $('bd-status');
  var saveBtn = $('bd-save');
  var noteEl = $('bd-preview-note');
  var frame = $('bd-frame');
  var frameWrap = $('bd-frame-wrap');
  var post = $('lp-post');
  var jsonField = $('lp-json');
  var pageSel = $('lp-page');

  // --- tiny helpers --------------------------------------------------------

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

  function byId(list, id) {
    for (var i = 0; i < (list || []).length; i++) if (list[i].id === id) return list[i];
    return null;
  }

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

  function changed() {
    dirty = true;
    setStatus('dirty', 'Unsaved changes');
    saveBtn.disabled = false;
    schedulePreview(true);
  }

  // --- controls ------------------------------------------------------------

  function textControl(label, value, placeholder, multiline, onInput, hint) {
    var input = multiline ? el('textarea', { rows: 3 }) : el('input', { type: 'text' });
    input.value = value || '';
    if (placeholder) input.placeholder = placeholder;
    input.addEventListener('input', function () { onInput(input.value); });
    var kids = [el('label', { class: 'field-label', text: label }), input];
    if (hint) kids.push(el('div', { class: 'bd-hint', text: hint }));
    return el('div', { class: 'field' }, kids);
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

  function checkControl(label, checked, onChange) {
    var cb = el('input', { type: 'checkbox' });
    cb.checked = !!checked;
    cb.addEventListener('change', function () { onChange(cb.checked); });
    return el('div', { class: 'field' }, [el('label', { class: 'bd-check' }, [cb, label])]);
  }

  function upload(file, done, btn) {
    var fd = new FormData();
    fd.append('file', file);
    fd.append('csrf_token', boot.csrf);
    fd.append('logos', '1');
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

  /** Thumbnail + upload + "choose existing" for a single image value (a file name). */
  function imagePicker(get, set) {
    var wrap = el('div');
    function draw() {
      wrap.innerHTML = '';
      var cur = get();
      if (cur) wrap.appendChild(el('img', { class: 'bd-thumb bd-thumb-logo', src: boot.imgBase + cur, alt: '' }));
      else wrap.appendChild(el('div', { class: 'bd-thumb bd-thumb-empty', text: 'No logo' }));
      var file = el('input', { type: 'file', class: 'bd-file', accept: 'image/jpeg,image/png,image/webp' });
      var up = el('button', { type: 'button', class: 'btn-secondary bd-upload', text: cur ? 'Replace…' : 'Upload…' });
      up.addEventListener('click', function () { file.click(); });
      file.addEventListener('change', function () {
        if (file.files && file.files[0]) upload(file.files[0], function (name) { set(name); draw(); }, up);
      });
      var sel = el('select', { 'aria-label': 'Choose an existing image' });
      sel.appendChild(el('option', { value: '', text: 'Choose existing…' }));
      library.forEach(function (n) {
        var o = el('option', { value: n, text: n });
        if (n === cur) o.selected = true;
        sel.appendChild(o);
      });
      sel.addEventListener('change', function () { if (sel.value) { set(sel.value); draw(); } });
      var tools = [up, sel, file];
      if (cur) {
        tools.splice(2, 0, el('button', {
          type: 'button', class: 'btn-secondary bd-upload', text: 'No logo',
          onclick: function () { set(''); draw(); }
        }));
      }
      wrap.appendChild(el('div', { class: 'bd-imgtools' }, tools));
    }
    draw();
    return wrap;
  }

  // --- navigation links list --------------------------------------------------

  function navList(key, title) {
    var list = D[key];
    var enList = EN[key] || [];
    var rows = el('div');
    var box = el('div', { class: 'field' }, [el('span', { class: 'field-label', text: title }), rows]);

    function actions(i) {
      function mv(dir) {
        var j = i + dir;
        if (j < 0 || j >= list.length) return;
        var t = list[i]; list[i] = list[j]; list[j] = t;
        draw(); changed();
      }
      return el('span', { class: 'bd-sub-actions' }, [
        el('button', { type: 'button', class: 'btn-icon', title: 'Move up', text: '↑', onclick: function () { mv(-1); } }),
        el('button', { type: 'button', class: 'btn-icon', title: 'Move down', text: '↓', onclick: function () { mv(1); } }),
        el('button', {
          type: 'button', class: 'btn-icon btn-danger', title: 'Remove', text: '×',
          onclick: function () {
            if (!confirm('Remove this link? It is removed in every language.')) return;
            list.splice(i, 1); draw(); changed();
          }
        })
      ]);
    }

    function draw() {
      rows.innerHTML = '';
      list.forEach(function (it, i) {
        var enIt = byId(enList, it.id);
        var kids = [el('div', { class: 'bd-sub-head' }, [el('span', { text: 'Link ' + (i + 1) }), actions(i)])];
        kids.push(textControl('Text', it.label, isEn ? '' : (enIt ? enIt.label : ''), false, function (v) { it.label = v; changed(); }));
        kids.push(selectControl('Goes to', { page: 'A page of this site', url: 'A custom address' }, it.kind, function (v) {
          it.kind = v; draw(); changed();
        }));
        if (it.kind === 'url') {
          kids.push(textControl('Address (all languages) — https://…, /path, mailto:…, tel:…', it.url, '', false, function (v) { it.url = v; changed(); }));
        } else {
          kids.push(selectControl('Page (all languages)', boot.pages, it.page, function (v) { it.page = v; changed(); }));
        }
        kids.push(checkControl('Open in a new tab (all languages)', it.new_tab, function (v) { it.new_tab = v; changed(); }));
        rows.appendChild(el('div', { class: 'bd-sub' }, kids));
      });
      if (!list.length) rows.appendChild(el('p', { class: 'bd-hint', text: 'No links yet.' }));
    }

    draw();
    box.appendChild(el('button', {
      type: 'button', class: 'btn-secondary', text: '+ Add link',
      onclick: function () {
        if (list.length >= boot.maxItems) { showAlert('You can have at most ' + boot.maxItems + ' links.'); return; }
        list.push({ id: rid(key === 'nav' ? 'n_' : 'f_'), kind: 'page', page: 'home', url: '', new_tab: false, label: '' });
        draw(); changed();
      }
    }));
    return box;
  }

  // --- sections ------------------------------------------------------------------

  function section(id, title, nodes) {
    var d = el('details', { class: 'bd-meta', 'data-section': id, open: true }, [
      el('summary', { text: title }),
      el('div', { class: 'bd-meta-body' }, nodes)
    ]);
    d.addEventListener('focusin', function () { lastSection = id; });
    return d;
  }

  function headerNodes() {
    var logo = el('div', { class: 'field' }, [
      el('span', { class: 'field-label', text: 'Logo (all languages)' }),
      imagePicker(function () { return D.logo; }, function (v) { D.logo = v; changed(); })
    ]);
    return [
      logo,
      textControl('Logo description for screen readers', D.logo_alt, isEn ? '' : EN.logo_alt, false, function (v) { D.logo_alt = v; changed(); }),
      textControl('Brand name (all languages)', D.brand_name, '', false, function (v) { D.brand_name = v; changed(); }),
      textControl('Tagline (under the brand name)', D.tagline, isEn ? '' : EN.tagline, false, function (v) { D.tagline = v; changed(); },
        'Leave empty to show no tagline.'),
      textControl('Mobile menu button label (accessibility)', D.toggle_aria, isEn ? '' : EN.toggle_aria, false, function (v) { D.toggle_aria = v; changed(); }),
      checkControl('Show the language switcher (all languages)', D.show_lang_switch, function (v) { D.show_lang_switch = v; changed(); }),
      navList('nav', 'Navigation links')
    ];
  }

  function footerNodes() {
    var nodes = [
      textControl('Location (all languages)', D.footer_location, '', false, function (v) { D.footer_location = v; changed(); },
        'Leave empty to hide it.'),
      textControl('Email shown in the footer (all languages)', D.footer_email, '', false, function (v) { D.footer_email = v; changed(); },
        'Leave empty to hide it.'),
      checkControl('Show the same links as the header', D.footer_same_nav, function (v) {
        D.footer_same_nav = v; render(); changed();
      })
    ];
    if (!D.footer_same_nav) nodes.push(navList('footer_nav', 'Footer links'));
    nodes.push(textControl('Copyright line', D.copyright, isEn ? '' : EN.copyright, false, function (v) { D.copyright = v; changed(); },
      '{year} becomes the current year. Leave empty to hide the line.'));
    return nodes;
  }

  function render() {
    area.innerHTML = '';
    area.appendChild(section('header', 'Header', headerNodes()));
    area.appendChild(section('footer', 'Footer', footerNodes()));
  }

  // --- live preview --------------------------------------------------------------

  var pvTimer = null;

  function schedulePreview(fromEdit) {
    clearTimeout(pvTimer);
    pvTimer = setTimeout(function () { submitPreview(fromEdit); }, 450);
  }

  function submitPreview(fromEdit) {
    setNote('Updating preview…');
    scrollAfterLoad = !!fromEdit;
    post.action = boot.previewBase + encodeURIComponent(pageSel.value);
    jsonField.value = JSON.stringify(D);
    post.submit();
  }

  frame.addEventListener('load', function () {
    setNote('');
    if (!scrollAfterLoad) return;
    scrollAfterLoad = false;
    try {
      var w = frame.contentWindow;
      // Keep the part being edited in view: the footer sits at the bottom of the page.
      if (lastSection === 'footer') w.scrollTo(0, w.document.documentElement.scrollHeight);
    } catch (e) { /* ignore */ }
  });

  pageSel.addEventListener('change', function () { submitPreview(false); });

  Array.prototype.forEach.call(document.querySelectorAll('[data-device]'), function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.getAttribute('data-device');
      frameWrap.className = 'bd-frame-wrap' + (d === 'desktop' ? '' : ' is-' + d);
      Array.prototype.forEach.call(document.querySelectorAll('[data-device]'), function (b) {
        b.classList.toggle('is-active', b === btn);
      });
    });
  });

  // --- saving ----------------------------------------------------------------------

  function save() {
    if (saving || !dirty) return;
    saving = true;
    saveBtn.disabled = true;
    setStatus('', 'Saving…');
    var snapshot = JSON.stringify(D);

    fetch(boot.saveUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': boot.csrf },
      body: JSON.stringify({ lang: lang, rev: rev, draft: D })
    }).then(function (r) {
      return r.json().catch(function () { return { ok: false, error: 'Unexpected server response.' }; });
    }).then(function (res) {
      saving = false;
      if (!res.ok) {
        setStatus('error', res.conflict ? 'Out of date — reload' : 'Not saved');
        showAlert(res.error || 'Could not save.');
        saveBtn.disabled = false;
        return;
      }
      rev = res.rev;
      if (JSON.stringify(D) === snapshot) {
        D = res.draft;          // exactly what the server stored
        dirty = false;
        render();
        setStatus('', 'All changes saved');
        saveBtn.disabled = true;
      } else {
        changed();              // the user kept typing while saving: keep their newer draft
      }
    }).catch(function () {
      saving = false;
      setStatus('error', 'Network error');
      saveBtn.disabled = false;
    });
  }

  saveBtn.addEventListener('click', save);
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); }
  });
  window.addEventListener('beforeunload', function (e) {
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
  });
  Array.prototype.forEach.call(document.querySelectorAll('[data-lang-link]'), function (a) {
    a.addEventListener('click', function (e) {
      if (dirty && !confirm('You have unsaved changes. Leave without saving?')) e.preventDefault();
    });
  });

  // --- start ---------------------------------------------------------------------------

  $('bd-notice').textContent = isEn
    ? 'What you save here is used by every page. Logo, links, addresses and options are shared by all languages; texts are translated per language.'
    : 'You are editing the ' + lang.toUpperCase() + ' texts. Logo, links, addresses and options are shared with every language. ' +
      'Leave a text empty to use the English text (shown greyed out in each field).';

  saveBtn.disabled = true;
  render();
})();
