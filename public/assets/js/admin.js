/* BRIX admin interactions. */
(function () {
  'use strict';

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var csrfMeta = $('meta[name="csrf-token"]');
  var csrf = csrfMeta ? csrfMeta.content : '';
  var base = document.body.getAttribute('data-base') || '';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function slugify(text) {
    return String(text).normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase()
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  }
  function mediaUrl(path) {
    if (!path) return '';
    return /^https?:\/\//i.test(path) ? path : base + '/' + path.replace(/^\/+/, '');
  }

  // Sidebar on small screens
  var sidebar = $('#sidebar');
  $$('[data-sidebar-toggle]').forEach(function (b) { b.addEventListener('click', function () { sidebar.classList.toggle('is-open'); }); });
  $$('[data-sidebar-close]').forEach(function (b) { b.addEventListener('click', function () { sidebar.classList.remove('is-open'); }); });

  // Alerts
  $$('[data-dismiss]').forEach(function (b) { b.addEventListener('click', function () { b.parentElement.remove(); }); });

  // Confirm destructive actions
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
  }, true);

  // Slug fields follow their title until edited by hand
  $$('[data-slug-source]').forEach(function (source) {
    var target = document.querySelector('[name="' + source.getAttribute('data-slug-source') + '"][data-slug-target]');
    if (!target) return;
    var manual = target.hasAttribute('data-slug-locked') || target.value !== '';
    target.addEventListener('input', function () { manual = target.value !== ''; });
    source.addEventListener('input', function () {
      if (manual) return;
      target.value = slugify(source.value);
      target.dispatchEvent(new Event('change'));
    });
  });

  // Character counters
  $$('[data-count]').forEach(function (input) {
    var max = parseInt(input.getAttribute('data-count'), 10);
    var counter = $('[data-count-for="' + input.id + '"]');
    if (!counter) return;
    var update = function () {
      counter.textContent = input.value.length + ' / ' + max;
      counter.classList.toggle('is-over', input.value.length > max);
    };
    input.addEventListener('input', update);
    update();
  });

  // Search-result preview
  $$('[data-seo-form]').forEach(function (form) {
    var title = $('#f-title', form), slug = $('#f-slug', form), excerpt = $('#f-excerpt', form);
    var metaTitle = $('[data-serp-source="title"]', form), metaDesc = $('[data-serp-source="desc"]', form);
    var outTitle = $('[data-serp-title]', form), outDesc = $('[data-serp-desc]', form), outSlug = $('[data-serp-slug]', form);
    if (!outTitle) return;
    var update = function () {
      outTitle.textContent = (metaTitle && metaTitle.value) || (title && title.value) || 'Title';
      outDesc.textContent = (metaDesc && metaDesc.value) || (excerpt && excerpt.value) || 'Description';
      if (outSlug && slug) outSlug.textContent = slug.value;
    };
    [title, slug, excerpt, metaTitle, metaDesc].forEach(function (f) {
      if (f) { f.addEventListener('input', update); f.addEventListener('change', update); }
    });
  });

  // Radio tabs (knowledge base: upload or paste)
  $$('[data-tabs]').forEach(function (group) {
    var form = group.closest('form');
    var sync = function () {
      var checked = $('input:checked', group);
      $$('[data-tab-panel]', form).forEach(function (panel) { panel.hidden = !checked || panel.getAttribute('data-tab-panel') !== checked.value; });
    };
    group.addEventListener('change', sync);
    sync();
  });

  // Drop zones
  $$('[data-dropzone]').forEach(function (zone) {
    var input = $('input[type="file"]', zone);
    var label = $('[data-dropzone-file]', zone);
    var show = function () {
      if (label) label.textContent = input.files.length ? Array.prototype.map.call(input.files, function (f) { return f.name; }).join(', ') : '';
      if (input.hasAttribute('data-autosubmit') && input.files.length) input.form.submit();
    };
    input.addEventListener('change', show);
    ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.remove('is-over'); }); });
  });

  // Copy buttons
  $$('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (navigator.clipboard) navigator.clipboard.writeText(btn.getAttribute('data-copy'));
      var original = btn.innerHTML;
      btn.textContent = 'Copied';
      setTimeout(function () { btn.innerHTML = original; }, 1200);
    });
  });

  // ---------- Media ----------
  function upload(file) {
    var data = new FormData();
    data.append('file', file, file.name || 'image.png');
    data.append('_csrf', csrf);
    return fetch(base + '/admin/media/upload', { method: 'POST', body: data, headers: { 'X-CSRF-Token': csrf } })
      .then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (body) {
          if (!res.ok) throw new Error(body.error || 'Upload failed.');
          return body;
        });
      });
  }

  var modal = $('#mediaModal');
  var modalGrid = $('#mediaModalGrid');
  var onPick = null;
  function openLibrary(callback) {
    if (!modal) return;
    onPick = callback;
    modal.hidden = false;
    modalGrid.innerHTML = '<p class="muted">Loading…</p>';
    fetch(base + '/admin/media/list').then(function (r) { return r.json(); }).then(function (body) {
      if (!body.items || !body.items.length) {
        modalGrid.innerHTML = '<p class="muted">No images yet. Use Upload instead.</p>';
        return;
      }
      modalGrid.innerHTML = '';
      body.items.forEach(function (item) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.title = item.original_name;
        btn.innerHTML = '<img src="' + esc(item.url) + '" alt="" loading="lazy">';
        btn.addEventListener('click', function () {
          modal.hidden = true;
          if (onPick) onPick(item);
        });
        modalGrid.appendChild(btn);
      });
    });
  }
  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal || e.target.closest('[data-modal-close]')) modal.hidden = true;
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') modal.hidden = true; });
  }

  $$('[data-media-picker]').forEach(function (picker) {
    var input = $('input[type="text"]', picker);
    var file = $('input[type="file"]', picker);
    var thumb = $('.media-thumb', picker);
    var empty = thumb.innerHTML.indexOf('<img') === -1 ? thumb.innerHTML : '<span></span>';
    var set = function (path, link) {
      input.value = path;
      thumb.innerHTML = path ? '<img src="' + esc(link || mediaUrl(path)) + '" alt="">' : empty;
    };
    $('[data-media-upload]', picker).addEventListener('click', function () { file.click(); });
    file.addEventListener('change', function () {
      if (!file.files[0]) return;
      picker.classList.add('is-loading');
      upload(file.files[0])
        .then(function (r) { set(r.path, r.location); })
        .catch(function (err) { window.alert(err.message); })
        .then(function () { picker.classList.remove('is-loading'); file.value = ''; });
    });
    $('[data-media-library]', picker).addEventListener('click', function () { openLibrary(function (item) { set(item.path, item.url); }); });
    $('[data-media-clear]', picker).addEventListener('click', function () { set('', ''); });
    input.addEventListener('change', function () { set(input.value.trim()); });
  });

  // ---------- Repeater lists (homepage) ----------
  $$('[data-repeater]').forEach(function (rep) {
    var schema = JSON.parse(rep.getAttribute('data-schema'));
    var hidden = $('input[type="hidden"]', rep);
    var list = $('.repeater-rows', rep);
    var rows = [];
    try { rows = JSON.parse(hidden.value) || []; } catch (e) { rows = []; }
    var sync = function () { hidden.value = JSON.stringify(rows); };

    function control(col, row) {
      var ctl;
      if (col.type === 'select') {
        ctl = document.createElement('select');
        Object.keys(col.options).forEach(function (key) { ctl.appendChild(new Option(col.options[key], key)); });
        ctl.value = row[col.key] || Object.keys(col.options)[0];
        row[col.key] = ctl.value;
      } else if (col.type === 'textarea') {
        ctl = document.createElement('textarea');
        ctl.rows = 2;
        ctl.value = row[col.key] || '';
      } else {
        ctl = document.createElement('input');
        ctl.type = 'text';
        ctl.value = row[col.key] || '';
      }
      var store = function () { row[col.key] = ctl.value; sync(); };
      ctl.addEventListener('input', store);
      ctl.addEventListener('change', store);
      return ctl;
    }

    function move(from, to) {
      if (to < 0 || to >= rows.length) return;
      var item = rows.splice(from, 1)[0];
      rows.splice(to, 0, item);
      render();
    }

    function render() {
      list.innerHTML = '';
      rows.forEach(function (row, i) {
        var wrap = document.createElement('div');
        wrap.className = 'repeater-row';
        var fields = document.createElement('div');
        fields.className = 'repeater-fields';
        schema.forEach(function (col) {
          var label = document.createElement('label');
          label.className = 'repeater-field' + (col.type === 'textarea' ? ' is-wide' : '');
          label.innerHTML = '<span>' + esc(col.label) + '</span>';
          label.appendChild(control(col, row));
          fields.appendChild(label);
        });
        var tools = document.createElement('div');
        tools.className = 'repeater-tools';
        tools.innerHTML = '<button type="button" title="Move up" aria-label="Move up">↑</button><button type="button" title="Move down" aria-label="Move down">↓</button><button type="button" title="Remove" aria-label="Remove">✕</button>';
        var btns = $$('button', tools);
        btns[0].addEventListener('click', function () { move(i, i - 1); });
        btns[1].addEventListener('click', function () { move(i, i + 1); });
        btns[2].addEventListener('click', function () { rows.splice(i, 1); render(); });
        wrap.appendChild(fields);
        wrap.appendChild(tools);
        list.appendChild(wrap);
      });
      sync();
    }

    $('[data-repeater-add]', rep).addEventListener('click', function () {
      var row = {};
      schema.forEach(function (col) { row[col.key] = col.type === 'select' ? Object.keys(col.options)[0] : ''; });
      rows.push(row);
      render();
      var inputs = $$('.repeater-row:last-child input, .repeater-row:last-child textarea', rep);
      if (inputs[0]) inputs[0].focus();
    });
    render();
  });

  // ---------- AI settings ----------
  $$('[data-provider-switch]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      $$('[data-provider-panel]').forEach(function (panel) { panel.hidden = panel.getAttribute('data-provider-panel') !== radio.value; });
    });
  });
  var testBtn = $('[data-ai-test]');
  if (testBtn) {
    testBtn.addEventListener('click', function () {
      var out = $('[data-ai-test-result]');
      out.hidden = false;
      out.className = 'ai-test-result';
      out.textContent = 'Sending a test message…';
      testBtn.disabled = true;
      fetch(testBtn.getAttribute('data-url'), { method: 'POST', headers: { 'X-CSRF-Token': csrf } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (j.ok) {
            out.classList.add('is-ok');
            out.textContent = 'Connected to ' + j.provider + ' (' + j.model + ') in ' + (j.ms / 1000).toFixed(1) + 's. It replied: "' + j.reply + '"';
          } else {
            out.classList.add('is-error');
            out.textContent = j.error || 'The test failed.';
          }
        })
        .catch(function () { out.classList.add('is-error'); out.textContent = 'The test request failed.'; })
        .then(function () { testBtn.disabled = false; });
    });
  }

  // ---------- Rich text editor ----------
  if (window.tinymce && $('textarea.richtext')) {
    window.tinymce.init({
      selector: 'textarea.richtext',
      license_key: 'gpl',
      promotion: false,
      branding: false,
      plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen media table wordcount codesample autoresize',
      menubar: 'edit insert format table view',
      toolbar: 'undo redo | blocks | bold italic underline | link image media table | bullist numlist blockquote | alignleft aligncenter alignright | codesample hr | removeformat code fullscreen',
      block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Quote=blockquote; Code=pre',
      min_height: 520,
      autoresize_bottom_margin: 40,
      relative_urls: false,
      remove_script_host: true,
      image_caption: true,
      image_advtab: true,
      automatic_uploads: true,
      images_upload_handler: function (blobInfo) {
        return upload(new File([blobInfo.blob()], blobInfo.filename(), { type: blobInfo.blob().type }))
          .then(function (r) { return r.location; })
          .catch(function (err) { return Promise.reject({ message: err.message, remove: true }); });
      },
      file_picker_types: 'image',
      file_picker_callback: function (callback) {
        openLibrary(function (item) { callback(item.url, { alt: item.alt || '' }); });
      },
      content_style: 'body { font-family: Inter, system-ui, sans-serif; font-size: 16px; line-height: 1.7; color: #120A24; max-width: 760px; margin: 18px auto; padding: 0 12px; } h2, h3, h4 { font-family: Sora, Inter, sans-serif; letter-spacing: -0.01em; } img { max-width: 100%; height: auto; border-radius: 12px; } blockquote { border-left: 3px solid #7C3AED; margin-left: 0; padding-left: 16px; color: #5F5570; }',
      setup: function (editor) {
        editor.on('change input undo redo', function () { editor.save(); });
      }
    });
  }
})();
