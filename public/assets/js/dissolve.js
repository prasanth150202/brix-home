/*
 * Particle dissolve for the BRIX AI chat transition.
 *
 * Dissolve.out(elements, {onStart, onDone}) redraws the given elements onto
 * an offscreen canvas (text word by word, plus box backgrounds and borders),
 * samples it into particles, hides the real page (onStart) and lets the
 * particles drift away. Dissolve.in(...) plays the same thing backwards.
 */
(function () {
  'use strict';

  var canvas = document.getElementById('dissolveCanvas');
  if (!canvas) return;

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var MAX_PARTICLES = 70000;
  var ctx, W, H, image, pixels, raf = 0, fadeTimer = 0;

  function setup() {
    W = Math.max(1, window.innerWidth);
    H = Math.max(1, window.innerHeight);
    canvas.width = W;
    canvas.height = H;
    ctx = canvas.getContext('2d');
    image = ctx.createImageData(W, H);
    pixels = new Uint32Array(image.data.buffer);
  }

  function rgba(str) {
    var m = /rgba?\(([^)]+)\)/.exec(str || '');
    if (!m) return null;
    var p = m[1].split(/[\s,/]+/).filter(Boolean).map(parseFloat);
    return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 };
  }

  function roundRect(o, x, y, w, h, r) {
    r = Math.max(0, Math.min(r, w / 2, h / 2));
    o.beginPath();
    o.moveTo(x + r, y);
    o.arcTo(x + w, y, x + w, y + h, r);
    o.arcTo(x + w, y + h, x, y + h, r);
    o.arcTo(x, y + h, x, y, r);
    o.arcTo(x, y, x + w, y, r);
    o.closePath();
  }

  // Hidden by an ancestor's opacity, visibility or display (the page wrapper excepted).
  function makeVisibilityCheck() {
    var cache = new Map();
    return function shown(el) {
      if (!el || el.id === 'site' || el === document.body) return true;
      if (cache.has(el)) return cache.get(el);
      var cs = getComputedStyle(el);
      var ok = cs.display !== 'none' && cs.visibility !== 'hidden' && parseFloat(cs.opacity) > 0.05 && shown(el.parentElement);
      cache.set(el, ok);
      return ok;
    };
  }

  function drawBox(o, el, cs) {
    var rect = el.getBoundingClientRect();
    if (rect.width < 2 || rect.height < 2 || rect.bottom < 0 || rect.top > H) return;
    var radius = parseFloat(cs.borderTopLeftRadius) || 0;
    var bg = rgba(cs.backgroundColor);
    // A gradient clipped to text colours the letters, not the box (drawText handles it).
    var clipsText = cs.backgroundClip === 'text' || cs.webkitBackgroundClip === 'text';
    var hasGradient = !clipsText && cs.backgroundImage && cs.backgroundImage.indexOf('gradient') !== -1;

    if (hasGradient || (bg && bg.a >= 0.12)) {
      var a = hasGradient ? 0.55 : Math.min(1, Math.max(bg.a, 0.32));
      o.fillStyle = hasGradient ? 'rgba(200, 200, 200, ' + a + ')' : 'rgba(' + bg.r + ',' + bg.g + ',' + bg.b + ',' + a + ')';
      roundRect(o, rect.left, rect.top, rect.width, rect.height, radius);
      o.fill();
    }
    var bw = parseFloat(cs.borderTopWidth) || 0;
    var bc = rgba(cs.borderTopColor);
    if (bw > 0 && bc && bc.a > 0.03) {
      o.strokeStyle = 'rgba(' + bc.r + ',' + bc.g + ',' + bc.b + ',' + Math.min(1, Math.max(bc.a * 2.4, 0.4)) + ')';
      o.lineWidth = Math.max(1.5, bw);
      roundRect(o, rect.left + 0.5, rect.top + 0.5, rect.width - 1, rect.height - 1, radius);
      o.stroke();
    }
  }

  function drawImageEl(o, img) {
    if (!img.complete || !img.naturalWidth) return;
    try {
      if (new URL(img.currentSrc || img.src, location.href).origin !== location.origin) return; // would taint the canvas
      var r = img.getBoundingClientRect();
      if (r.width && r.height) o.drawImage(img, r.left, r.top, r.width, r.height);
    } catch (e) { /* ignore */ }
  }

  function drawText(o, root, shown) {
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    var range = document.createRange();
    var node;
    while ((node = walker.nextNode())) {
      var text = node.nodeValue;
      if (!text || !text.trim()) continue;
      var parent = node.parentElement;
      if (!parent || !shown(parent)) continue;
      var cs = getComputedStyle(parent);
      var color = rgba(cs.color);
      var gradientEl = parent.closest('[data-dissolve-gradient]');
      var gradient = (!color || color.a < 0.05) ? (gradientEl ? gradientEl.getAttribute('data-dissolve-gradient') : '#FFFFFF,#8C8C8C').split(',') : null;
      o.font = cs.fontStyle + ' ' + cs.fontWeight + ' ' + cs.fontSize + ' ' + cs.fontFamily;
      o.textBaseline = 'middle';
      var upper = cs.textTransform === 'uppercase';
      var re = /\S+/g, m;
      while ((m = re.exec(text))) {
        range.setStart(node, m.index);
        range.setEnd(node, m.index + m[0].length);
        var rects = range.getClientRects();
        if (!rects.length) continue;
        var r = rects[0];
        if (r.width < 1 || r.bottom < 0 || r.top > H) continue;
        var word = upper ? m[0].toUpperCase() : m[0];
        var measured = o.measureText(word).width || r.width;
        o.save();
        o.translate(r.left, r.top + r.height / 2);
        o.scale(r.width / measured, 1); // match the page's letter-spacing exactly
        if (gradient) {
          var g = o.createLinearGradient(0, 0, measured, 0);
          gradient.forEach(function (c, i) { g.addColorStop(gradient.length > 1 ? i / (gradient.length - 1) : 0, c.trim()); });
          o.fillStyle = g;
        } else {
          o.fillStyle = cs.color;
        }
        o.fillText(word, 0, 0);
        o.restore();
      }
    }
  }

  /** Draw the elements and turn the result into particle arrays. */
  function capture(elements, focus) {
    var off = document.createElement('canvas');
    off.width = W;
    off.height = H;
    var o = off.getContext('2d', { willReadFrequently: true });
    var shown = makeVisibilityCheck();

    elements.forEach(function (el) {
      if (!shown(el)) return;
      var all = [el].concat(Array.prototype.slice.call(el.querySelectorAll('*')));
      all.forEach(function (child) {
        if (child.tagName === 'svg' || child.closest('svg') || !shown(child)) return;
        if (child.tagName === 'IMG') { drawImageEl(o, child); return; }
        drawBox(o, child, getComputedStyle(child));
      });
      drawText(o, el, shown);
    });

    var data = o.getImageData(0, 0, W, H).data;
    var step = W * H > 1600000 ? 3 : 2;
    var xs = [], ys = [], cs = [];
    for (var y = 0; y < H; y += step) {
      for (var x = 0; x < W; x += step) {
        var i = (y * W + x) * 4;
        var a = data[i + 3];
        if (a < 60) continue;
        xs.push(x); ys.push(y);
        cs.push((a << 24) | (data[i + 2] << 16) | (data[i + 1] << 8) | data[i]);
      }
    }

    var total = xs.length;
    var keep = total > MAX_PARTICLES ? MAX_PARTICLES / total : 1;
    var n = Math.min(total, MAX_PARTICLES);
    var p = {
      n: 0,
      ox: new Float32Array(n), oy: new Float32Array(n),
      dx: new Float32Array(n), dy: new Float32Array(n),
      delay: new Float32Array(n), life: new Float32Array(n), seed: new Float32Array(n),
      col: new Uint32Array(n), maxDelay: 0, maxEnd: 0
    };
    var fx = focus ? focus.x : W / 2;
    for (var k = 0; k < total && p.n < n; k++) {
      if (keep < 1 && Math.random() > keep) continue;
      var j = p.n++;
      var px = xs[k], py = ys[k];
      p.ox[j] = px;
      p.oy[j] = py;
      p.col[j] = cs[k];
      // Swept away by a wind blowing up and to the side, away from the chat box.
      var side = px < fx ? -1 : 1;
      p.dx[j] = side * (40 + Math.random() * 170) + (Math.random() - 0.5) * 80;
      p.dy[j] = -(70 + Math.random() * 230);
      p.delay[j] = (1 - py / H) * 140 + Math.abs(px - fx) / W * 320 + Math.random() * 260;
      p.life[j] = 520 + Math.random() * 520;
      p.seed[j] = Math.random() * 6.283;
      if (p.delay[j] > p.maxDelay) p.maxDelay = p.delay[j];
      if (p.delay[j] + p.life[j] > p.maxEnd) p.maxEnd = p.delay[j] + p.life[j];
    }
    // Played backwards, the last particle to leave is the first to return.
    p.maxEndIn = 0;
    for (var q = 0; q < p.n; q++) {
      var end = p.maxDelay - p.delay[q] + p.life[q];
      if (end > p.maxEndIn) p.maxEndIn = end;
    }
    return p;
  }

  function render(p, t, direction) {
    pixels.fill(0);
    var w = W, h = H;
    for (var i = 0; i < p.n; i++) {
      var prog = direction > 0
        ? (t - p.delay[i]) / p.life[i]
        : 1 - (t - (p.maxDelay - p.delay[i])) / p.life[i];
      if (prog < 0) prog = 0;
      if (prog >= 1) continue;
      var e = prog * prog;
      var wobble = Math.sin(prog * 9 + p.seed[i]) * 7 * prog;
      var x = (p.ox[i] + p.dx[i] * e + wobble) | 0;
      var y = (p.oy[i] + p.dy[i] * e) | 0;
      if (x < 0 || y < 0 || x >= w - 1 || y >= h - 1) continue;
      var c = p.col[i];
      var alpha = ((c >>> 24) * (1 - prog) * (1 - prog * 0.3)) | 0;
      var v = ((alpha << 24) | (c & 0x00ffffff)) >>> 0;
      var idx = y * w + x;
      pixels[idx] = v;
      pixels[idx + 1] = v;
      pixels[idx + w] = v;
      pixels[idx + w + 1] = v;
    }
    ctx.putImageData(image, 0, 0);
  }

  function play(p, direction, onDone) {
    cancelAnimationFrame(raf);
    clearTimeout(fadeTimer);
    canvas.style.transition = 'none';
    canvas.style.opacity = '1';
    canvas.classList.add('is-active');
    var duration = (direction > 0 ? p.maxEnd : p.maxEndIn) + 40;
    var start = performance.now();
    function frame(now) {
      var t = now - start;
      render(p, Math.min(t, duration), direction);
      if (t < duration) {
        raf = requestAnimationFrame(frame);
      } else {
        onDone();
      }
    }
    render(p, 0, direction);
    raf = requestAnimationFrame(frame);
  }

  function clear(fade) {
    if (!fade) {
      canvas.classList.remove('is-active');
      return;
    }
    canvas.style.transition = 'opacity 0.25s ease';
    canvas.style.opacity = '0';
    fadeTimer = setTimeout(function () { canvas.classList.remove('is-active'); }, 260);
  }

  window.Dissolve = {
    out: function (elements, opts) {
      opts = opts || {};
      if (reduceMotion || !elements.length) {
        if (opts.onStart) opts.onStart();
        if (opts.onDone) opts.onDone();
        return;
      }
      setup();
      var p = capture(elements, opts.focus);
      play(p, 1, function () {
        clear(false);
        if (opts.onDone) opts.onDone();
      });
      // The first frame is on screen: swap the real page out underneath it.
      if (opts.onStart) opts.onStart();
    },
    in: function (elements, opts) {
      opts = opts || {};
      if (reduceMotion || !elements.length) {
        if (opts.onDone) opts.onDone();
        return;
      }
      setup();
      var p = capture(elements, opts.focus);
      play(p, -1, function () {
        if (opts.onDone) opts.onDone();
        clear(true);
      });
    }
  };
})();
