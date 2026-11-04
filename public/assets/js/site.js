/* BRIX AI public site: page interactions and the chat. */
(function () {
  'use strict';

  document.documentElement.classList.remove('no-js');

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var cfgEl = $('#brixConfig');
  var cfg = cfgEl ? JSON.parse(cfgEl.textContent) : {};
  var icons = cfg.icons || {};
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var motion = !reduceMotion;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function el(tag, className, html) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (html != null) node.innerHTML = html;
    return node;
  }

  // ---------- Headings that rise in word by word ----------
  $$('[data-split]').forEach(function (heading) {
    var text = heading.textContent.trim();
    if (!text) return;
    heading.setAttribute('aria-label', text);
    heading.innerHTML = text.split(/\s+/).map(function (word, i) {
      return '<span class="w" aria-hidden="true"><span class="wi" style="--wi:' + i + '">' + esc(word) + '</span></span>';
    }).join(' ');
  });

  // ---------- Header: scrolled state and reading progress ----------
  var header = $('#siteHeader');
  var progress = $('#scrollProgress');
  var ticking = false;
  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () {
      ticking = false;
      var y = window.scrollY;
      if (header) header.classList.toggle('is-scrolled', y > 8);
      if (progress) {
        var max = document.documentElement.scrollHeight - window.innerHeight;
        progress.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, y / max) : 0) + ')';
      }
    });
  }
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  var navToggle = $('.nav-toggle');
  var nav = $('#siteNav');
  if (navToggle && nav) {
    navToggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(open));
    });
    nav.addEventListener('click', function (e) {
      if (e.target.closest('a')) {
        nav.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // ---------- Reveal on scroll, count-up numbers ----------
  function countUp(node) {
    var raw = node.getAttribute('data-count');
    var target = parseFloat(raw);
    if (isNaN(target) || !motion) return;
    var prefix = node.getAttribute('data-prefix') || '';
    var suffix = node.getAttribute('data-suffix') || '';
    var decimals = (raw.split('.')[1] || '').length;
    var start = performance.now();
    var duration = 1700;
    (function frame(now) {
      var p = Math.min(1, (now - start) / duration);
      var eased = 1 - Math.pow(1 - p, 4);
      node.textContent = prefix + (target * eased).toFixed(decimals) + suffix;
      if (p < 1) requestAnimationFrame(frame);
    })(start);
  }

  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        $$('[data-count]', entry.target).forEach(countUp);
        observer.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.15 });
    $$('.reveal, [data-reveal], [data-split]').forEach(function (node) { observer.observe(node); });
  } else {
    $$('.reveal, [data-reveal], [data-split]').forEach(function (node) { node.classList.add('is-visible'); });
  }

  // ---------- Pointer effects (mouse and trackpad only) ----------
  if (finePointer && motion) {
    var hero = $('.hero');
    if (hero) {
      hero.addEventListener('pointermove', function (e) {
        var r = hero.getBoundingClientRect();
        hero.style.setProperty('--mx', (e.clientX - r.left) + 'px');
        hero.style.setProperty('--my', (e.clientY - r.top) + 'px');
        hero.classList.add('is-lit');
      });
      hero.addEventListener('pointerleave', function () { hero.classList.remove('is-lit'); });
    }

    $$('[data-tilt]').forEach(function (card) {
      card.addEventListener('pointermove', function (e) {
        var r = card.getBoundingClientRect();
        var x = (e.clientX - r.left) / r.width;
        var y = (e.clientY - r.top) / r.height;
        card.style.setProperty('--mx', (x * 100) + '%');
        card.style.setProperty('--my', (y * 100) + '%');
        card.style.transform = 'perspective(900px) rotateX(' + ((0.5 - y) * 6).toFixed(2) + 'deg) rotateY(' + ((x - 0.5) * 8).toFixed(2) + 'deg) translateY(-4px)';
      });
      card.addEventListener('pointerleave', function () { card.style.transform = ''; });
    });

    $$('.bento-tile').forEach(function (tile) {
      tile.addEventListener('pointermove', function (e) {
        var r = tile.getBoundingClientRect();
        tile.style.setProperty('--mx', (e.clientX - r.left) + 'px');
        tile.style.setProperty('--my', (e.clientY - r.top) + 'px');
      });
    });

    $$('[data-magnetic]').forEach(function (btn) {
      btn.addEventListener('pointermove', function (e) {
        var r = btn.getBoundingClientRect();
        var dx = e.clientX - (r.left + r.width / 2);
        var dy = e.clientY - (r.top + r.height / 2);
        btn.style.transform = 'translate(' + (dx * 0.18).toFixed(1) + 'px,' + (dy * 0.3).toFixed(1) + 'px)';
      });
      btn.addEventListener('pointerleave', function () { btn.style.transform = ''; });
    });
  }

  // ---------- Pricing: monthly / yearly ----------
  var billing = $('.billing-toggle');
  var indicator = billing && $('.billing-indicator', billing);
  function placeIndicator() {
    var active = billing && $('button.is-active', billing);
    if (!active || !indicator) return;
    indicator.style.left = active.offsetLeft + 'px';
    indicator.style.width = active.offsetWidth + 'px';
  }
  placeIndicator();
  window.addEventListener('resize', placeIndicator);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(placeIndicator);

  $$('[data-billing]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var yearly = btn.getAttribute('data-billing') === 'yearly';
      $$('[data-billing]').forEach(function (b) {
        b.classList.toggle('is-active', b === btn);
        b.setAttribute('aria-pressed', String(b === btn));
      });
      placeIndicator();
      $$('.plan-price[data-monthly]').forEach(function (price) {
        var useYearly = yearly && price.getAttribute('data-yearly');
        var amount = $('.amount', price);
        amount.textContent = useYearly ? price.getAttribute('data-yearly') : price.getAttribute('data-monthly');
        $('.per', price).textContent = useYearly ? '/year' : '/month';
        if (motion) {
          amount.classList.remove('is-flipping');
          void amount.offsetWidth;
          amount.classList.add('is-flipping');
        }
      });
    });
  });

  // ---------- Live cart demo ----------
  var demo = $('[data-demo]');
  if (demo) {
    (function () {
      var cur = demo.getAttribute('data-currency') || '$';
      var shippingAt = parseFloat(demo.getAttribute('data-shipping')) || 75;
      var giftAt = parseFloat(demo.getAttribute('data-gift')) || 120;
      var list = $('[data-demo-items]', demo);
      var fill = $('[data-demo-fill]', demo);
      var msg = $('[data-demo-msg]', demo);
      var count = $('[data-demo-count]', demo);
      var subtotalEl = $('[data-demo-subtotal]', demo);
      var totalEl = $('[data-demo-total]');
      var liftEl = $('[data-demo-lift]');
      var nudgesEl = $('[data-demo-nudges]');
      var markShipping = $('[data-mark="shipping"]', demo);
      var markGift = $('[data-mark="gift"]', demo);
      var check = icons.check ? icons.check.replace('class="icon"', 'class="icon icon-xs"') : '✓';
      var base = 48;
      var cart = null;

      function money(n) {
        var rounded = Math.round(n * 100) / 100;
        return cur + (rounded % 1 ? rounded.toFixed(2) : String(rounded));
      }
      function lineTotal(line) { return line.price * line.qty * (1 - (line.discount || 0)); }
      function subtotal() { return cart.lines.reduce(function (sum, line) { return sum + lineTotal(line); }, 0); }

      // Count a number from what is on screen now to its new value; a newer count cancels an older one.
      function tween(node, to, format, initial) {
        if (!node) return;
        var from = typeof node._value === 'number' ? node._value : initial;
        var id = (node._tween || 0) + 1;
        node._tween = id;
        if (!motion || from === to) { node._value = to; node.textContent = format(to); return; }
        var start = performance.now();
        (function frame(now) {
          if (node._tween !== id) return;
          var p = Math.min(1, (now - start) / 650);
          var eased = 1 - Math.pow(1 - p, 3);
          node._value = from + (to - from) * eased;
          node.textContent = format(node._value);
          if (p < 1) requestAnimationFrame(frame);
        })(start);
      }

      function lineHtml(line, isNew) {
        var total = lineTotal(line);
        var price = line.price === 0
          ? '<span class="is-free">FREE</span>'
          : money(total) + (line.discount ? '<s>' + money(line.price * line.qty) + '</s>' : '');
        var note = line.qty > 1 ? line.qty + ' × ' + money(line.price) + (line.discount ? ' · 5% off' : '') : line.note;
        var pack = line.id === 'serum' && !cart.packed ? '<button type="button" class="dc-pack" data-pack>Buy 2, save 5%</button>' : '';
        return '<li class="dc-item' + (isNew ? ' is-new' : '') + '"><span class="dc-thumb" style="--c:' + esc(line.shade) + '"></span>' +
          '<div><b>' + esc(line.name) + '</b><small>' + esc(note) + '</small>' + pack + '</div><em>' + price + '</em></li>';
      }

      function render(newId) {
        var sub = subtotal();
        if (sub >= giftAt && !cart.lines.some(function (l) { return l.id === 'gift'; })) {
          cart.lines.push({ id: 'gift', name: 'Mini Lip Balm', note: 'Free gift', price: 0, qty: 1, shade: '#F5F5F4' });
          newId = 'gift';
        }
        if (sub >= shippingAt) cart.nudges.shipping = true;
        if (sub >= giftAt) cart.nudges.gift = true;

        list.innerHTML = cart.lines.map(function (line) { return lineHtml(line, line.id === newId); }).join('');

        fill.style.width = Math.min(100, sub / giftAt * 100) + '%';
        fill.classList.toggle('is-done', sub >= giftAt);
        markShipping.classList.toggle('is-on', sub >= shippingAt);
        markGift.classList.toggle('is-on', sub >= giftAt);
        if (sub < shippingAt) {
          msg.textContent = 'Add ' + money(shippingAt - sub) + ' more for free shipping';
        } else if (sub < giftAt) {
          msg.innerHTML = check + 'Free shipping unlocked · ' + esc(money(giftAt - sub)) + ' more for a free gift';
        } else {
          msg.innerHTML = check + 'Free shipping and a free gift unlocked';
        }
        msg.classList.toggle('is-win', sub >= shippingAt);

        var items = cart.lines.reduce(function (n, l) { return n + l.qty; }, 0);
        count.textContent = items + (items === 1 ? ' item' : ' items');

        var lift = (sub - base) / base * 100;
        tween(subtotalEl, sub, money, base);
        tween(totalEl, sub, money, base);
        tween(liftEl, lift, function (v) { return '+' + Math.round(v) + '%'; }, 0);

        var used = ['fbt', 'pack', 'shipping', 'gift'].filter(function (k) { return cart.nudges[k]; });
        if (nudgesEl) nudgesEl.textContent = used.length + '/4';
        $$('[data-nudge]').forEach(function (li) { li.classList.toggle('is-done', !!cart.nudges[li.getAttribute('data-nudge')]); });
      }

      function reset() {
        cart = { lines: [{ id: 'serum', name: 'Hydrating Serum', note: '30 ml', price: base, qty: 1, shade: '#E7E5E4' }], packed: false, nudges: {} };
        $$('[data-add]', demo).forEach(function (btn) {
          btn.disabled = false;
          $('.dc-add', btn).textContent = '+ Add';
        });
        render(null);
      }

      demo.addEventListener('click', function (e) {
        var add = e.target.closest('[data-add]');
        if (add && !add.disabled) {
          cart.lines.push({
            id: add.getAttribute('data-add'),
            name: add.getAttribute('data-name'),
            note: add.getAttribute('data-note'),
            price: parseFloat(add.getAttribute('data-price')),
            qty: 1,
            shade: add.getAttribute('data-shade')
          });
          cart.nudges.fbt = true;
          add.disabled = true;
          $('.dc-add', add).textContent = 'Added ✓';
          render(add.getAttribute('data-add'));
          return;
        }
        if (e.target.closest('[data-pack]')) {
          var serum = cart.lines[0];
          serum.qty = 2;
          serum.discount = 0.05;
          cart.packed = true;
          cart.nudges.pack = true;
          render(null);
        }
      });
      $$('[data-demo-reset]').forEach(function (btn) { btn.addEventListener('click', reset); });
      reset();
    })();
  }

  // ---------- FAQ: open and close smoothly ----------
  if (motion) {
    $$('.faq').forEach(function (item) {
      var summary = $('summary', item);
      var body = $('.faq-answer', item);
      if (!summary || !body || !body.animate) return;
      summary.addEventListener('click', function (e) {
        e.preventDefault();
        if (item.getAttribute('data-busy')) return;
        item.setAttribute('data-busy', '1');
        if (item.open) {
          var anim = body.animate([
            { height: body.offsetHeight + 'px', opacity: 1 },
            { height: '0px', paddingBottom: '0px', opacity: 0 }
          ], { duration: 280, easing: 'cubic-bezier(.4,0,.2,1)' });
          anim.onfinish = function () { item.open = false; item.removeAttribute('data-busy'); };
        } else {
          item.open = true;
          var open = body.animate([
            { height: '0px', paddingBottom: '0px', opacity: 0 },
            { height: body.offsetHeight + 'px', opacity: 1 }
          ], { duration: 380, easing: 'cubic-bezier(.2,.8,.2,1)' });
          open.onfinish = function () { item.removeAttribute('data-busy'); };
        }
      });
    });
  }

  $$('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (navigator.clipboard) navigator.clipboard.writeText(btn.getAttribute('data-copy'));
      btn.classList.add('is-done');
      setTimeout(function () { btn.classList.remove('is-done'); }, 1200);
    });
  });

  // ---------- Install-click tracking ----------
  function track(link) {
    if (!cfg.trackUrl) return;
    var body = JSON.stringify({
      source: link.getAttribute('data-track'),
      product: link.getAttribute('data-product') || null,
      conversation: link.getAttribute('data-conversation') || null,
      url: link.href || ''
    });
    if (navigator.sendBeacon) {
      navigator.sendBeacon(cfg.trackUrl, new Blob([body], { type: 'application/json' }));
    } else {
      fetch(cfg.trackUrl, { method: 'POST', body: body, headers: { 'Content-Type': 'application/json' }, keepalive: true });
    }
  }
  document.addEventListener('click', function (e) {
    var link = e.target.closest('[data-track]');
    if (link) track(link);
  });

  // ---------- Chat ----------
  var chat = $('#chat');
  if (!chat) return; // Other pages link to /?chat=1 instead.

  var site = $('#site');
  var composer = $('#composer');
  var input = $('#composerInput');
  var sendBtn = $('#composerSend');
  var heroSlot = $('#composerSlot');
  var dockSlot = $('#dockSlot');
  var thread = $('#chatThread');
  var scroller = $('#chatScroll');
  var root = document.documentElement;
  var basePlaceholder = input.getAttribute('placeholder') || '';

  var state = { open: false, busy: false, animating: false, conversation: null, controller: null, stick: true };

  // The hero chat box types out example questions while nobody is using it.
  (function typewriter() {
    var examples = (cfg.examples || []).filter(Boolean);
    if (!examples.length || !motion) return;
    var index = 0, pos = 0, deleting = false;
    function idle() { return !state.open && document.activeElement !== input && !input.value; }
    function tick() {
      if (!idle()) {
        input.setAttribute('placeholder', basePlaceholder);
        pos = 0;
        deleting = false;
        setTimeout(tick, 700);
        return;
      }
      var text = examples[index % examples.length];
      if (!deleting) {
        pos++;
        input.setAttribute('placeholder', text.slice(0, pos));
        if (pos >= text.length) { deleting = true; setTimeout(tick, 2400); return; }
        setTimeout(tick, 34 + Math.random() * 40);
      } else {
        pos--;
        input.setAttribute('placeholder', text.slice(0, pos));
        if (pos <= 0) { deleting = false; index++; setTimeout(tick, 380); return; }
        setTimeout(tick, 16);
      }
    }
    setTimeout(tick, 2600);
  })();

  if (window.marked && window.marked.use) window.marked.use({ gfm: true, breaks: false });
  if (window.DOMPurify) {
    window.DOMPurify.addHook('afterSanitizeAttributes', function (node) {
      if (node.tagName === 'A') {
        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener noreferrer');
      }
    });
  }

  function renderMarkdown(text) {
    if (window.marked && window.DOMPurify) {
      return window.DOMPurify.sanitize(window.marked.parse(text), {
        FORBID_TAGS: ['img', 'style', 'form', 'input', 'button', 'iframe'],
        FORBID_ATTR: ['style']
      });
    }
    return '<p>' + esc(text).replace(/\n{2,}/g, '</p><p>').replace(/\n/g, '<br>') + '</p>';
  }

  function autosize() {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 220) + 'px';
    sendBtn.disabled = !state.busy && !input.value.trim();
  }

  function setBusy(on) {
    state.busy = on;
    composer.classList.toggle('is-busy', on);
    sendBtn.setAttribute('aria-label', on ? 'Stop answering' : 'Send');
    autosize();
  }

  function nearBottom() {
    return scroller.scrollHeight - scroller.scrollTop - scroller.clientHeight < 90;
  }
  scroller.addEventListener('scroll', function () { state.stick = nearBottom(); }, { passive: true });
  function scrollDown(force) {
    if (force || state.stick) scroller.scrollTop = scroller.scrollHeight;
  }

  // Slide the chat box between the hero and the bottom dock.
  function flip(node, first, last, opts) {
    if (!motion || !node.animate) return null;
    var dx = first.left - last.left;
    var dy = first.top - last.top;
    var sx = first.width / Math.max(1, last.width);
    var from = 'translate(' + dx + 'px,' + dy + 'px) scaleX(' + sx + ')';
    var frames = opts && opts.reverse ? [{ transform: 'none' }, { transform: from }] : [{ transform: from }, { transform: 'none' }];
    return node.animate(frames, {
      duration: (opts && opts.duration) || 760,
      easing: 'cubic-bezier(.2,.8,.2,1)',
      fill: opts && opts.reverse ? 'forwards' : 'none'
    });
  }
  composer.style.transformOrigin = 'top left';

  function dissolveTargets() {
    var vh = window.innerHeight;
    return $$('#site [data-dissolve]').filter(function (node) {
      if (node.parentElement && node.parentElement.closest('[data-dissolve]')) return false;
      var r = node.getBoundingClientRect();
      return r.width > 0 && r.height > 0 && r.bottom > 0 && r.top < vh;
    });
  }

  // The particle effect is made for the dark hero; elsewhere the page simply fades.
  function heroInView() {
    var r = heroSlot.getBoundingClientRect();
    return r.bottom > 0 && r.top < window.innerHeight;
  }

  function centerOf(rect) {
    return { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
  }

  // Some embeds (sandboxed frames, previews) refuse history changes; the chat works without them.
  function safeHistory(fn) {
    try { fn(); } catch (e) { /* ignore */ }
  }

  function openChat(opts) {
    opts = opts || {};
    if (state.open || state.animating) return;
    state.open = true;
    state.animating = true;
    input.setAttribute('placeholder', basePlaceholder);

    var first = composer.getBoundingClientRect();
    heroSlot.style.minHeight = first.height + 'px';

    var swap = function () {
      root.classList.add('chat-open');
      chat.setAttribute('aria-hidden', 'false');
      if ('inert' in site) site.inert = true;
      dockSlot.appendChild(composer);
      flip(composer, first, composer.getBoundingClientRect());
      input.focus({ preventScroll: true });
      autosize();
    };

    if (window.Dissolve && heroInView()) {
      window.Dissolve.out(dissolveTargets(), {
        focus: centerOf(first),
        onStart: swap,
        onDone: function () { state.animating = false; }
      });
      // Never leave the Back button locked if the animation is interrupted.
      setTimeout(function () { state.animating = false; }, 2500);
    } else {
      swap();
      setTimeout(function () { state.animating = false; }, 300);
    }
    if (!opts.fromHistory) safeHistory(function () { history.pushState({ brixChat: true }, '', '#chat'); });
  }

  function closeChat(opts) {
    opts = opts || {};
    if (!state.open || state.animating) return;
    state.animating = true;
    root.classList.add('chat-closing');
    input.blur();

    var from = composer.getBoundingClientRect();
    var to = heroSlot.getBoundingClientRect();
    var particles = window.Dissolve && heroInView();
    // Glide the chat box back to where it sits in the hero, then re-home it there.
    var glide = flip(composer, to, from, { reverse: true, duration: 800 });
    var pending = 2;
    var done = false;
    var finish = function (force) {
      if (done || (--pending > 0 && force !== true)) return;
      done = true;
      root.classList.remove('chat-open', 'chat-closing');
      chat.setAttribute('aria-hidden', 'true');
      if ('inert' in site) site.inert = false;
      heroSlot.appendChild(composer);
      heroSlot.style.minHeight = '';
      if (glide) glide.cancel();
      state.open = false;
      state.animating = false;
    };
    if (glide) glide.onfinish = finish; else finish();

    if (particles) {
      window.Dissolve.in(dissolveTargets(), { focus: centerOf(to), onDone: finish });
    } else {
      finish();
    }
    setTimeout(function () { finish(true); }, 2500);
    if (!opts.fromHistory && location.hash === '#chat') safeHistory(function () { history.back(); });
  }

  window.addEventListener('popstate', function () {
    if (state.open && location.hash !== '#chat') closeChat({ fromHistory: true });
    else if (!state.open && location.hash === '#chat') openChat({ fromHistory: true });
  });

  input.addEventListener('focus', function () { if (!state.open) openChat(); });
  input.addEventListener('input', autosize);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
      e.preventDefault();
      submit();
    }
  });
  composer.addEventListener('submit', function (e) {
    e.preventDefault();
    if (state.busy) {
      if (state.controller) state.controller.abort();
    } else {
      submit();
    }
  });

  function submit() {
    var question = input.value.trim();
    if (!question || state.busy) return;
    if (!state.open) openChat();
    input.value = '';
    autosize();
    ask(question);
  }

  document.addEventListener('click', function (e) {
    var asker = e.target.closest('[data-ask]');
    if (asker) {
      e.preventDefault();
      var question = asker.getAttribute('data-ask');
      if (!state.open) openChat();
      ask(question);
      return;
    }
    if (e.target.closest('[data-open-chat]')) {
      e.preventDefault();
      if (nav) nav.classList.remove('is-open');
      openChat();
    }
  });

  $('#chatBack').addEventListener('click', function () { closeChat(); });
  $('#chatNew').addEventListener('click', function () {
    if (state.controller) state.controller.abort();
    thread.innerHTML = '';
    state.conversation = null;
    chat.classList.remove('has-messages');
    input.value = '';
    autosize();
    input.focus();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && state.open && !input.value) closeChat();
  });

  // ---------- Messages ----------
  function addUserMessage(text) {
    var node = el('div', 'msg msg-user');
    var bubble = el('div', 'bubble');
    bubble.textContent = text;
    node.appendChild(bubble);
    thread.appendChild(node);
    return node;
  }

  function sourceCards(sources) {
    var block = el('div', 'extras-block', '<div class="extras-label">' + (icons.file || '') + 'Based on these case studies</div>');
    var grid = el('div', 'sources');
    sources.forEach(function (s, i) {
      var card = el(s.url ? 'a' : 'div', 'source-card');
      if (s.url) card.href = s.url;
      card.style.animationDelay = (i * 80) + 'ms';
      card.innerHTML =
        '<span class="source-kicker">' + esc(s.industry || 'Case study') + '</span>' +
        (s.result_value ? '<span class="source-value">' + esc(s.result_value) + '</span>' : '') +
        (s.result_label ? '<span class="source-label">' + esc(s.result_label) + '</span>' : '') +
        '<span class="source-title">' + esc(s.title) + '</span>';
      grid.appendChild(card);
    });
    block.appendChild(grid);
    return block;
  }

  function productCard(p) {
    var card = el('div', 'rec');
    card.style.animationDelay = '160ms';
    var media = p.image ? '<img src="' + esc(p.image) + '" alt="">' : p.icon; // icon is SVG markup from the server
    card.innerHTML =
      '<div class="rec-head">' +
        '<span class="rec-icon">' + media + '</span>' +
        '<div class="rec-title"><span class="rec-kicker">' + (icons.sparkles || '') + 'Recommended for you</span>' +
          '<h4>' + esc(p.name) + '</h4>' + (p.tagline ? '<p>' + esc(p.tagline) + '</p>' : '') + '</div>' +
        (p.metric_value ? '<div class="rec-metric"><strong>' + esc(p.metric_value) + '</strong><span>' + esc(p.metric_label) + '</span></div>' : '') +
      '</div>' +
      (p.features && p.features.length ? '<ul class="rec-features">' + p.features.map(function (f) {
        return '<li>' + (icons.check || '') + '<span>' + esc(f) + '</span></li>';
      }).join('') + '</ul>' : '') +
      '<div class="rec-actions">' +
        '<a class="btn btn-primary btn-sm" target="_blank" rel="noopener" data-track="chat">Install ' + esc(p.name) + (icons.arrow || '') + '</a>' +
        (p.learn_url ? '<a class="btn btn-ghost btn-sm" href="' + esc(p.learn_url) + '">Learn more</a>' : '') +
      '</div>';
    var install = $('[data-track]', card);
    install.href = p.install_url;
    install.setAttribute('data-product', p.id);
    install.setAttribute('data-conversation', state.conversation || '');
    return card;
  }

  function addAssistantMessage(onRetry) {
    var node = el('div', 'msg msg-ai');
    node.innerHTML =
      '<div class="msg-avatar"><img src="' + esc(cfg.mark) + '" alt=""></div>' +
      '<div class="msg-body">' +
        '<div class="msg-status"><span class="dots"><i></i><i></i><i></i></span><span class="shimmer">Reading BRIX case studies…</span></div>' +
        '<div class="answer" hidden></div>' +
        '<div class="msg-extras" hidden></div>' +
      '</div>';
    thread.appendChild(node);

    var status = $('.msg-status', node);
    var answer = $('.answer', node);
    var extras = $('.msg-extras', node);
    var text = '';
    var queued = false;

    function paint() {
      queued = false;
      answer.innerHTML = renderMarkdown(text);
      scrollDown();
    }
    function settle() {
      if (status.isConnected) status.remove();
      answer.hidden = false;
      answer.classList.remove('is-streaming');
    }

    return {
      append: function (piece) {
        if (!text) {
          settle();
          answer.classList.add('is-streaming');
        }
        text += piece;
        if (!queued) {
          queued = true;
          requestAnimationFrame(paint);
        }
      },
      reset: function () {
        text = '';
        answer.innerHTML = '';
      },
      done: function (data) {
        paint();
        settle();
        var blocks = [];
        if (data.sources && data.sources.length) blocks.push(sourceCards(data.sources));
        if (data.product) blocks.push(productCard(data.product));
        if (blocks.length) {
          extras.hidden = false;
          blocks.forEach(function (b) { extras.appendChild(b); });
        }        var actions = el('div', 'msg-actions');
        var copy = el('button', 'icon-btn', (icons.copy || '') + '<span>Copy</span>');
        copy.type = 'button';
        copy.addEventListener('click', function () {
          if (navigator.clipboard) navigator.clipboard.writeText(text);
          $('span', copy).textContent = 'Copied';
          setTimeout(function () { $('span', copy).textContent = 'Copy'; }, 1400);
        });
        actions.appendChild(copy);
        $('.msg-body', node).appendChild(actions);
        scrollDown();
      },
      fail: function (message, partial) {
        if (!partial) text = '';
        paint();
        settle();
        if (!text) answer.innerHTML = '';
        var box = el('div', 'answer-error', '<span>' + esc(message) + '</span>');
        var retry = el('button', '', 'Try again');
        retry.type = 'button';
        retry.addEventListener('click', onRetry);
        box.appendChild(retry);
        $('.msg-body', node).appendChild(box);
        scrollDown(true);
      },
      stopped: function () {
        settle();
        if (!text) answer.innerHTML = '<p>Stopped.</p>';
      },
      finish: function () {
        if (status.isConnected) status.remove();
        answer.hidden = false;
        answer.classList.remove('is-streaming');
      },
      node: node
    };
  }

  function parseEvent(raw) {
    var event = 'message';
    var data = '';
    raw.split('\n').forEach(function (line) {
      if (line.indexOf('event:') === 0) event = line.slice(6).trim();
      else if (line.indexOf('data:') === 0) data += line.slice(5).trim();
    });
    if (!data) return null;
    try { return { event: event, data: JSON.parse(data) }; } catch (e) { return null; }
  }

  function ask(question) {
    question = String(question || '').trim();
    if (!question || state.busy) return;
    chat.classList.add('has-messages');
    var userNode = addUserMessage(question);
    var msg = addAssistantMessage(function () {
      userNode.remove();
      msg.node.remove();
      ask(question);
    });
    state.stick = true;
    scrollDown(true);
    setBusy(true);

    var controller = new AbortController();
    state.controller = controller;
    var failed = false;

    fetch(cfg.chatUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'text/event-stream', 'X-CSRF-Token': cfg.csrf },
      body: JSON.stringify({ message: question, conversation: state.conversation }),
      signal: controller.signal
    }).then(function (res) {
      if (!res.ok || !res.body) {
        return res.json().catch(function () { return {}; }).then(function (body) {
          throw new Error(body.error || 'Something went wrong. Please try again.');
        });
      }
      var reader = res.body.getReader();
      var decoder = new TextDecoder();
      var buffer = '';
      function pump() {
        return reader.read().then(function (chunk) {
          if (chunk.done) return;
          buffer += decoder.decode(chunk.value, { stream: true });
          var cut;
          while ((cut = buffer.indexOf('\n\n')) !== -1) {
            var ev = parseEvent(buffer.slice(0, cut));
            buffer = buffer.slice(cut + 2);
            if (!ev) continue;
            if (ev.event === 'start') state.conversation = ev.data.conversation;
            else if (ev.event === 'delta') msg.append(ev.data.t);
            else if (ev.event === 'reset') msg.reset();
            else if (ev.event === 'done') msg.done(ev.data);
            else if (ev.event === 'error') { failed = true; msg.fail(ev.data.message, ev.data.partial); }
          }
          return pump();
        });
      }
      return pump();
    }).catch(function (err) {
      if (err.name === 'AbortError') msg.stopped();
      else if (!failed) msg.fail(err.message || 'Connection lost. Please try again.', false);
    }).then(function () {
      msg.finish();
      state.controller = null;
      setBusy(false);
    });
  }

  // ---------- Deep links: /?chat=1 and /?ask=... ----------
  var params = new URLSearchParams(location.search);
  if (location.hash === '#chat' && !params.has('chat') && !params.has('ask')) {
    safeHistory(function () { history.replaceState(null, '', location.pathname + location.search); });
  }
  if (params.has('chat') || params.has('ask')) {
    var question = params.get('ask');
    safeHistory(function () { history.replaceState(null, '', location.pathname); });
    var start = function () {
      openChat();
      if (question) ask(question);
    };
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { setTimeout(start, 150); });
    else setTimeout(start, 300);
  }
})();
