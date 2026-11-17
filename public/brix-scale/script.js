/* =========================================================
   BRIX landing page — behaviour
   ---------------------------------------------------------
   1. Edit CONFIG below (Google Sheets endpoint + Calendly link).
   2. See README.md for the 5-minute Google Sheets setup.
   ========================================================= */

const CONFIG = {
  // Google Apps Script Web App URL (Deploy → Web app → copy URL ending in /exec)
  SHEETS_ENDPOINT: "https://script.google.com/macros/s/AKfycbyGMGnIKBYSTPVRTPyYDfglqrsiL-06uMSIz73MYWZ1sH9JpbMMKoTHoSX8xRpHLJMq/exec",

  // Your Calendly event link, e.g. "https://calendly.com/brix/30-percent-call"
  CALENDLY_URL: "https://calendly.com/d/d4j6-5s8-qqz/brix-agency-product-demo",

  // Optional: pre-fill Calendly custom questions (a1 = 1st question on your event, a2 = 2nd, ...)
  // Map them to form fields. Remove any you don't use in Calendly.
  CALENDLY_CUSTOM_ANSWERS: {
    a1: "agencyName",
    a2: "clientsLabel",
    a3: "phone",
  },

  // Calendly brand colours (hex without #)
  CALENDLY_PRIMARY_COLOR: "ff5a1f",
};

/* ---------- Small helpers ---------- */
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
const isConfigured = (v) => typeof v === "string" && v.startsWith("https://");

const storage = {
  get(key) {
    try { return JSON.parse(sessionStorage.getItem(key)); } catch { return null; }
  },
  set(key, value) {
    try { sessionStorage.setItem(key, JSON.stringify(value)); } catch { /* storage blocked — fine */ }
  },
};

const uid = () =>
  (window.crypto && crypto.randomUUID)
    ? crypto.randomUUID()
    : "lead-" + Date.now().toString(36) + "-" + Math.random().toString(36).slice(2, 8);

const getUtm = () => {
  const p = new URLSearchParams(location.search);
  return {
    utm_source: p.get("utm_source") || "",
    utm_medium: p.get("utm_medium") || "",
    utm_campaign: p.get("utm_campaign") || "",
    utm_content: p.get("utm_content") || "",
    utm_term: p.get("utm_term") || "",
  };
};

/* =========================================================
   Page interactions
   ========================================================= */

// Year
$("#year").textContent = new Date().getFullYear();

// Nav border on scroll
const nav = $("#nav");
const onScroll = () => nav.classList.toggle("is-scrolled", window.scrollY > 8);
window.addEventListener("scroll", onScroll, { passive: true });
onScroll();

// Reveal on scroll
if ("IntersectionObserver" in window) {
  const io = new IntersectionObserver(
    (entries) => entries.forEach((e) => {
      if (e.isIntersecting) { e.target.classList.add("is-visible"); io.unobserve(e.target); }
    }),
    { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
  );
  $$(".reveal").forEach((el, i) => {
    el.style.transitionDelay = `${Math.min((i % 4) * 70, 210)}ms`;
    io.observe(el);
  });
} else {
  $$(".reveal").forEach((el) => el.classList.add("is-visible"));
}

/* =========================================================
   Multi-step application form
   ========================================================= */

const overlay = $("#apply");
const form = $("#leadForm");
const steps = $$(".q", form);
const TOTAL = steps.length;
const nextBtn = $("#nextBtn");
const backBtn = $("#backBtn");
const progressFill = $("#progressFill");
const progressBar = $("#progress");
const progressLabel = $("#progressLabel");
const booking = $("#booking");
const done = $("#done");
const clientsSelect = $("#clients");
const otherWrap = $("#otherWrap");
const otherInput = $("#clientsOther");

const LEAD_KEY = "brix_lead";
let current = 1;
let lastOpener = null;
let calendlyMounted = false;

/* ---------- Validation ---------- */
const validators = {
  fullName: (v) => {
    v = v.trim();
    if (!v) return "Please enter your full name.";
    if (v.length < 2 || !/[a-zA-ZÀ-ɏऀ-෿]/.test(v)) return "That doesn't look like a name.";
    return "";
  },
  email: (v) => {
    v = v.trim();
    if (!v) return "Please enter your email.";
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return "Please enter a valid email, like you@agency.com.";
    return "";
  },
  phone: (v) => {
    v = v.trim();
    if (!v) return "Please enter your phone number.";
    if (!/^[+\d\s().-]+$/.test(v)) return "Use digits only (spaces, +, - and brackets are fine).";
    const digits = v.replace(/\D/g, "").length;
    if (digits < 7 || digits > 15) return "Please enter a valid phone number with country code.";
    return "";
  },
  agencyName: (v) => (v.trim().length < 2 ? "Please enter your agency name." : ""),
  clients: (v) => {
    if (!v) return "Please choose an option.";
    if (v === "Other" && !otherInput.value.trim()) return "Please tell us a bit more.";
    return "";
  },
};

const fieldForStep = (n) => $("[name]:not([name='clientsOther'])", steps[n - 1]);

function validateStep(n, show = true) {
  const field = fieldForStep(n);
  const msg = validators[field.name](field.value);
  if (show) {
    const err = $(`[data-error-for="${field.name}"]`, form);
    err.textContent = msg;
    const target = field.name === "clients" && field.value === "Other" ? otherInput : field;
    field.classList.toggle("is-invalid", !!msg && target === field);
    otherInput.classList.toggle("is-invalid", !!msg && target === otherInput);
    if (msg) target.focus();
  }
  return !msg;
}

/* ---------- Progress (updates live as fields become valid) ---------- */
function updateProgress() {
  const completed = steps.filter((_, i) => validateStep(i + 1, false)).length;
  const pct = Math.round((completed / TOTAL) * 100);
  progressFill.style.width = pct + "%";
  progressBar.setAttribute("aria-valuenow", String(pct));
  progressLabel.textContent = `Question ${current} of ${TOTAL} · ${pct}% complete`;
}

function setFirstName() {
  const first = $("#fullName").value.trim().split(/\s+/)[0];
  const name = first ? first.charAt(0).toUpperCase() + first.slice(1) : "there";
  $$("[data-first-name]").forEach((el) => (el.textContent = name));
}

/* ---------- Step navigation ---------- */
function showStep(n, direction = 1) {
  current = Math.max(1, Math.min(TOTAL, n));
  steps.forEach((s, i) => {
    const active = i + 1 === current;
    s.classList.toggle("is-active", active);
    s.classList.toggle("is-back", active && direction < 0);
  });
  backBtn.hidden = current === 1;
  $(".btn__label", nextBtn).textContent = current === TOTAL ? "Submit & book my call" : "Next";
  updateProgress();
  // Focus the field after the animation begins (avoids iOS jump)
  requestAnimationFrame(() => fieldForStep(current).focus({ preventScroll: true }));
}

form.addEventListener("submit", (e) => {
  e.preventDefault();
  if (!validateStep(current)) return;
  if (current < TOTAL) showStep(current + 1, 1);
  else submitLead();
});

backBtn.addEventListener("click", () => showStep(current - 1, -1));

// Live updates
form.addEventListener("input", (e) => {
  const name = e.target.name;
  if (name === "fullName") setFirstName();
  if (e.target.classList.contains("is-invalid")) {
    e.target.classList.remove("is-invalid");
    const errFor = name === "clientsOther" ? "clients" : name;
    const err = $(`[data-error-for="${errFor}"]`, form);
    if (err) err.textContent = "";
  }
  updateProgress();
});

clientsSelect.addEventListener("change", () => {
  const isOther = clientsSelect.value === "Other";
  otherWrap.hidden = !isOther;
  otherInput.required = isOther;
  if (isOther) otherInput.focus();
  updateProgress();
});

// Enter on the <select> should advance too
clientsSelect.addEventListener("keydown", (e) => {
  if (e.key === "Enter") { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : nextBtn.click(); }
});

/* ---------- Open / close overlay ---------- */
function openForm(opener) {
  lastOpener = opener || document.activeElement;
  overlay.hidden = false;
  document.body.classList.add("no-scroll");

  const saved = storage.get(LEAD_KEY);
  if (saved && saved.submitted) {
    restoreLead(saved);
    if (saved.booked) showDone(); else showBooking(saved);
  } else {
    showStep(current);
  }
}

function closeForm() {
  overlay.hidden = true;
  document.body.classList.remove("no-scroll");
  if (lastOpener && lastOpener.focus) lastOpener.focus();
}

$$("[data-open-form]").forEach((btn) => btn.addEventListener("click", () => openForm(btn)));
$("#closeForm").addEventListener("click", closeForm);
$("#doneClose").addEventListener("click", closeForm);
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && !overlay.hidden) closeForm();
});

/* ---------- Submit to Google Sheets ---------- */
function collectLead() {
  const data = Object.fromEntries(new FormData(form).entries());
  const clientsOption = clientsSelect.options[clientsSelect.selectedIndex];
  return {
    leadId: uid(),
    fullName: data.fullName.trim(),
    email: data.email.trim().toLowerCase(),
    phone: data.phone.trim(),
    agencyName: data.agencyName.trim(),
    clients: data.clients,
    clientsOther: data.clients === "Other" ? (data.clientsOther || "").trim() : "",
    clientsLabel: data.clients === "Other" ? `Other: ${(data.clientsOther || "").trim()}` : clientsOption.textContent.trim(),
    website: data.website || "", // honeypot
    pageUrl: location.href,
    referrer: document.referrer || "",
    userAgent: navigator.userAgent,
    ...getUtm(),
  };
}

async function postToSheets(payload) {
  if (!isConfigured(CONFIG.SHEETS_ENDPOINT)) {
    console.warn("[BRIX] SHEETS_ENDPOINT is not set — lead not saved. Payload:", payload);
    return;
  }
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 10000);
  try {
    // Apps Script doesn't send CORS headers, so we use a "simple" no-cors POST.
    // The data still lands in the sheet; we just can't read the response.
    await fetch(CONFIG.SHEETS_ENDPOINT, {
      method: "POST",
      mode: "no-cors",
      body: new URLSearchParams(payload),
      signal: controller.signal,
      keepalive: true,
    });
  } finally {
    clearTimeout(timer);
  }
}

async function submitLead() {
  // Validate everything (in case someone skipped around)
  for (let i = 1; i <= TOTAL; i++) {
    if (!validateStep(i, false)) { showStep(i); validateStep(i); return; }
  }

  const lead = collectLead();
  nextBtn.disabled = true;
  backBtn.disabled = true;
  const label = $(".btn__label", nextBtn);
  label.textContent = "Saving…";
  nextBtn.insertAdjacentHTML("afterbegin", '<span class="spinner" aria-hidden="true"></span>');

  try {
    await postToSheets({ action: "lead", ...lead });
  } catch (err) {
    // Never block the booking because of a network hiccup — log and continue.
    console.error("[BRIX] Could not reach Google Sheets:", err);
  }

  storage.set(LEAD_KEY, { ...lead, submitted: true, booked: false });

  nextBtn.disabled = false;
  backBtn.disabled = false;
  const spinner = $(".spinner", nextBtn);
  if (spinner) spinner.remove();

  if (window.dataLayer) window.dataLayer.push({ event: "brix_lead_submitted", clients: lead.clients });
  showBooking(lead);
}

function restoreLead(lead) {
  ["fullName", "email", "phone", "agencyName"].forEach((k) => { if (lead[k]) form.elements[k].value = lead[k]; });
  if (lead.clients) clientsSelect.value = lead.clients;
  if (lead.clientsOther) { otherInput.value = lead.clientsOther; otherWrap.hidden = false; }
  setFirstName();
}

/* ---------- Calendly ---------- */
function loadCalendlyScript() {
  return new Promise((resolve, reject) => {
    if (window.Calendly) return resolve();
    const s = document.createElement("script");
    s.src = "https://assets.calendly.com/assets/external/widget.js";
    s.async = true;
    s.onload = resolve;
    s.onerror = () => reject(new Error("Calendly failed to load"));
    document.head.appendChild(s);
  });
}

function setBookingProgress(text) {
  progressFill.style.width = "100%";
  progressBar.setAttribute("aria-valuenow", "100");
  progressLabel.textContent = text;
}

async function showBooking(lead) {
  form.hidden = true;
  done.hidden = true;
  booking.hidden = false;
  setBookingProgress("Details saved ✓ · Last step: book your call");
  $("#bookingTitle").focus?.();

  if (calendlyMounted) return;
  const wrap = $("#calendlyWrap");

  if (!isConfigured(CONFIG.CALENDLY_URL)) {
    wrap.innerHTML = '<div class="calendly-missing"><strong>Calendly isn\'t connected yet.</strong><br/>Set <code>CALENDLY_URL</code> in <code>script.js</code> to your event link.</div>';
    return;
  }

  try {
    await loadCalendlyScript();
    const customAnswers = {};
    Object.entries(CONFIG.CALENDLY_CUSTOM_ANSWERS || {}).forEach(([key, field]) => {
      if (lead[field]) customAnswers[key] = lead[field];
    });
    const sep = CONFIG.CALENDLY_URL.includes("?") ? "&" : "?";
    const url = `${CONFIG.CALENDLY_URL}${sep}hide_gdpr_banner=1&primary_color=${CONFIG.CALENDLY_PRIMARY_COLOR}`;
    const utm = getUtm();

    window.Calendly.initInlineWidget({
      url,
      parentElement: wrap,
      prefill: { name: lead.fullName, email: lead.email, customAnswers },
      utm: {
        utmSource: utm.utm_source || undefined,
        utmMedium: utm.utm_medium || undefined,
        utmCampaign: utm.utm_campaign || undefined,
        utmContent: lead.leadId, // lets you match Calendly bookings to sheet rows
        utmTerm: utm.utm_term || undefined,
      },
    });
    calendlyMounted = true;
    setTimeout(() => $("#calendlyLoading")?.remove(), 4000);
  } catch (err) {
    console.error(err);
    wrap.innerHTML = `<div class="calendly-missing"><strong>The calendar couldn't load.</strong><br/>Please <a href="${CONFIG.CALENDLY_URL}" target="_blank" rel="noopener" style="text-decoration:underline">open the booking page here</a>.</div>`;
  }
}

function showDone() {
  form.hidden = true;
  booking.hidden = true;
  done.hidden = false;
  setBookingProgress("All done ✓ · You're booked");
}

// Listen to Calendly events (booking confirmed → update the sheet row)
window.addEventListener("message", (e) => {
  if (!/^https:\/\/([a-z0-9-]+\.)?calendly\.com$/i.test(e.origin)) return;
  const evt = e.data && e.data.event;
  if (!evt || typeof evt !== "string" || !evt.startsWith("calendly.")) return;

  $("#calendlyLoading")?.remove();

  if (evt === "calendly.event_scheduled") {
    const lead = storage.get(LEAD_KEY) || {};
    const p = e.data.payload || {};
    storage.set(LEAD_KEY, { ...lead, booked: true });

    postToSheets({
      action: "booked",
      leadId: lead.leadId || "",
      email: lead.email || "",
      eventUri: (p.event && p.event.uri) || "",
      inviteeUri: (p.invitee && p.invitee.uri) || "",
    }).catch((err) => console.error("[BRIX] Could not mark booking:", err));

    if (window.dataLayer) window.dataLayer.push({ event: "brix_call_booked" });
    setTimeout(showDone, 1800); // let Calendly show its own confirmation briefly
  }
});
