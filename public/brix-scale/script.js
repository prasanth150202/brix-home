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
  // Must match the ORDER of the questions on the Calendly event. Right now the event has one:
  //   a1 = "Phone Number (WhatsApp)"
  // If you add more questions in Calendly (e.g. agency name, client count), add them here in the same order,
  // using "agencyName" or "clientsLabel" as the field.
  CALENDLY_CUSTOM_ANSWERS: {
    a1: "phone",
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

// Mobile card sliders: dots that follow the swipe and jump to a card when tapped
document.querySelectorAll("[data-slider]").forEach((track) => {
  const items = Array.from(track.children);
  if (items.length < 2) return;
  const dots = document.createElement("div");
  dots.className = "slider-dots";
  const buttons = items.map((_, i) => {
    const b = document.createElement("button");
    b.type = "button";
    b.setAttribute("aria-label", `Show card ${i + 1} of ${items.length}`);
    b.addEventListener("click", () => {
      track.scrollTo({ left: items[i].offsetLeft - items[0].offsetLeft, behavior: "smooth" });
    });
    dots.appendChild(b);
    return b;
  });
  track.after(dots);

  let ticking = false;
  const update = () => {
    ticking = false;
    const step = items[1].offsetLeft - items[0].offsetLeft || 1;
    const atEnd = track.scrollLeft >= track.scrollWidth - track.clientWidth - 2;
    const active = atEnd ? items.length - 1 : Math.round(track.scrollLeft / step);
    buttons.forEach((b, i) => b.classList.toggle("is-active", i === active));
  };
  track.addEventListener("scroll", () => {
    if (!ticking) { ticking = true; requestAnimationFrame(update); }
  }, { passive: true });
  update();
});

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

/* ---------- Phone country codes ---------- */
// ISO|Country|Dial code. The first six are shown at the top of the list.
const POPULAR_COUNTRIES = ["IN", "US", "GB", "AE", "AU", "CA"];
const COUNTRIES = `AF|Afghanistan|93,AL|Albania|355,DZ|Algeria|213,AD|Andorra|376,AO|Angola|244,AR|Argentina|54,AM|Armenia|374,
AU|Australia|61,AT|Austria|43,AZ|Azerbaijan|994,BS|Bahamas|1242,BH|Bahrain|973,BD|Bangladesh|880,BB|Barbados|1246,
BY|Belarus|375,BE|Belgium|32,BZ|Belize|501,BJ|Benin|229,BT|Bhutan|975,BO|Bolivia|591,BA|Bosnia and Herzegovina|387,
BW|Botswana|267,BR|Brazil|55,BN|Brunei|673,BG|Bulgaria|359,BF|Burkina Faso|226,BI|Burundi|257,KH|Cambodia|855,
CM|Cameroon|237,CA|Canada|1,CV|Cape Verde|238,CF|Central African Republic|236,TD|Chad|235,CL|Chile|56,CN|China|86,
CO|Colombia|57,KM|Comoros|269,CG|Congo|242,CD|Congo (DRC)|243,CR|Costa Rica|506,CI|Côte d'Ivoire|225,HR|Croatia|385,
CU|Cuba|53,CY|Cyprus|357,CZ|Czechia|420,DK|Denmark|45,DJ|Djibouti|253,DO|Dominican Republic|1809,EC|Ecuador|593,
EG|Egypt|20,SV|El Salvador|503,GQ|Equatorial Guinea|240,ER|Eritrea|291,EE|Estonia|372,SZ|Eswatini|268,ET|Ethiopia|251,
FJ|Fiji|679,FI|Finland|358,FR|France|33,GA|Gabon|241,GM|Gambia|220,GE|Georgia|995,DE|Germany|49,GH|Ghana|233,
GR|Greece|30,GT|Guatemala|502,GN|Guinea|224,GY|Guyana|592,HT|Haiti|509,HN|Honduras|504,HK|Hong Kong|852,HU|Hungary|36,
IS|Iceland|354,IN|India|91,ID|Indonesia|62,IR|Iran|98,IQ|Iraq|964,IE|Ireland|353,IL|Israel|972,IT|Italy|39,
JM|Jamaica|1876,JP|Japan|81,JO|Jordan|962,KZ|Kazakhstan|7,KE|Kenya|254,KW|Kuwait|965,KG|Kyrgyzstan|996,LA|Laos|856,
LV|Latvia|371,LB|Lebanon|961,LS|Lesotho|266,LR|Liberia|231,LY|Libya|218,LI|Liechtenstein|423,LT|Lithuania|370,
LU|Luxembourg|352,MO|Macau|853,MG|Madagascar|261,MW|Malawi|265,MY|Malaysia|60,MV|Maldives|960,ML|Mali|223,MT|Malta|356,
MR|Mauritania|222,MU|Mauritius|230,MX|Mexico|52,MD|Moldova|373,MC|Monaco|377,MN|Mongolia|976,ME|Montenegro|382,
MA|Morocco|212,MZ|Mozambique|258,MM|Myanmar|95,NA|Namibia|264,NP|Nepal|977,NL|Netherlands|31,NZ|New Zealand|64,
NI|Nicaragua|505,NE|Niger|227,NG|Nigeria|234,MK|North Macedonia|389,NO|Norway|47,OM|Oman|968,PK|Pakistan|92,
PS|Palestine|970,PA|Panama|507,PG|Papua New Guinea|675,PY|Paraguay|595,PE|Peru|51,PH|Philippines|63,PL|Poland|48,
PT|Portugal|351,PR|Puerto Rico|1787,QA|Qatar|974,RO|Romania|40,RU|Russia|7,RW|Rwanda|250,SA|Saudi Arabia|966,
SN|Senegal|221,RS|Serbia|381,SC|Seychelles|248,SL|Sierra Leone|232,SG|Singapore|65,SK|Slovakia|421,SI|Slovenia|386,
SO|Somalia|252,ZA|South Africa|27,KR|South Korea|82,SS|South Sudan|211,ES|Spain|34,LK|Sri Lanka|94,SD|Sudan|249,
SR|Suriname|597,SE|Sweden|46,CH|Switzerland|41,SY|Syria|963,TW|Taiwan|886,TJ|Tajikistan|992,TZ|Tanzania|255,
TH|Thailand|66,TG|Togo|228,TT|Trinidad and Tobago|1868,TN|Tunisia|216,TR|Türkiye|90,TM|Turkmenistan|993,UG|Uganda|256,
UA|Ukraine|380,AE|United Arab Emirates|971,GB|United Kingdom|44,US|United States|1,UY|Uruguay|598,UZ|Uzbekistan|998,
VE|Venezuela|58,VN|Vietnam|84,YE|Yemen|967,ZM|Zambia|260,ZW|Zimbabwe|263`
  .split(",").map((row) => {
    const [iso, name, dial] = row.trim().split("|");
    return { iso, name, dial };
  });
const COUNTRY_BY_ISO = Object.fromEntries(COUNTRIES.map((c) => [c.iso, c]));

// Best guess at the visitor's country from their time zone, falling back to India
function guessCountry() {
  const tz = (Intl.DateTimeFormat().resolvedOptions().timeZone || "");
  const byZone = {
    "Asia/Kolkata": "IN", "Asia/Calcutta": "IN", "Europe/London": "GB", "Asia/Dubai": "AE", "Asia/Singapore": "SG",
    "Asia/Karachi": "PK", "Asia/Dhaka": "BD", "Asia/Colombo": "LK", "Asia/Kathmandu": "NP", "Asia/Riyadh": "SA",
    "Asia/Qatar": "QA", "Europe/Dublin": "IE", "Europe/Berlin": "DE", "Europe/Paris": "FR", "Europe/Amsterdam": "NL",
    "Europe/Madrid": "ES", "Europe/Rome": "IT", "Pacific/Auckland": "NZ", "Africa/Johannesburg": "ZA", "Africa/Lagos": "NG",
    "Africa/Nairobi": "KE", "Asia/Kuala_Lumpur": "MY", "Asia/Manila": "PH", "Asia/Jakarta": "ID", "Asia/Tokyo": "JP",
  };
  if (byZone[tz]) return byZone[tz];
  if (tz.startsWith("Australia/")) return "AU";
  if (/^America\/(Toronto|Vancouver|Edmonton|Winnipeg|Halifax|Regina|St_Johns)/.test(tz)) return "CA";
  if (/^(America\/(New_York|Chicago|Denver|Los_Angeles|Phoenix|Anchorage|Detroit|Boise|Indiana|Kentucky)|Pacific\/Honolulu)/.test(tz)) return "US";
  return "IN";
}

const phoneCountry = $("#phoneCountry");
const phoneCcLabel = $("#phoneCcLabel");
const phoneInput = $("#phone");

function fillCountryOptions() {
  const option = (c) => `<option value="${c.iso}">${c.name} (+${c.dial})</option>`;
  const popular = POPULAR_COUNTRIES.map((iso) => option(COUNTRY_BY_ISO[iso])).join("");
  const all = [...COUNTRIES].sort((a, b) => a.name.localeCompare(b.name)).map(option).join("");
  phoneCountry.innerHTML = `<optgroup label="Popular">${popular}</optgroup><optgroup label="All countries">${all}</optgroup>`;
}

function setCountry(iso) {
  const c = COUNTRY_BY_ISO[iso] || COUNTRY_BY_ISO.IN;
  phoneCountry.value = c.iso;
  phoneCcLabel.textContent = `${c.iso} +${c.dial}`;
}

// If someone types or pastes a full international number ("+44 7911 123456"), pick the country for them
function splitInternational(raw) {
  const v = raw.trim();
  if (!/^(\+|00)/.test(v)) return null;
  const digits = v.replace(/\D/g, "").replace(/^00/, "");
  // Longest dial code wins (+1876 Jamaica before +1 US); for shared codes keep the current choice if it matches
  const matches = COUNTRIES.filter((c) => digits.startsWith(c.dial)).sort((a, b) => b.dial.length - a.dial.length);
  if (!matches.length) return null;
  const longest = matches.filter((c) => c.dial.length === matches[0].dial.length);
  const pick = longest.find((c) => c.iso === phoneCountry.value)
    || longest.find((c) => POPULAR_COUNTRIES.includes(c.iso)) || longest[0];
  return { iso: pick.iso, national: digits.slice(pick.dial.length) };
}

// "+91 9876543210": country code plus national digits, trunk "0" dropped (except Italy, which keeps it)
function fullPhone() {
  const split = splitInternational(phoneInput.value);
  const c = COUNTRY_BY_ISO[split ? split.iso : phoneCountry.value] || COUNTRY_BY_ISO.IN;
  let national = split ? split.national : phoneInput.value.replace(/\D/g, "");
  if (c.dial !== "39") national = national.replace(/^0+/, "");
  return { country: c.iso, dial: c.dial, national, e164: `+${c.dial}${national}`, display: `+${c.dial} ${national}` };
}

fillCountryOptions();
setCountry(guessCountry());

phoneCountry.addEventListener("change", () => {
  setCountry(phoneCountry.value);
  phoneInput.focus();
});
phoneInput.addEventListener("change", () => {
  const split = splitInternational(phoneInput.value);
  if (split) {
    setCountry(split.iso);
    phoneInput.value = split.national;
  }
});

/* ---------- Phone validation ----------
   Uses libphonenumber-js (mobile rules, vendored locally) for exact per-country checks.
   It loads when the form opens; until then a simpler length check is used. */
const PHONE_LIB_SRC = "vendor/libphonenumber-mobile-1.13.15.js";
let phoneLibLoading = null;
function loadPhoneLib() {
  if (window.libphonenumber || phoneLibLoading) return phoneLibLoading;
  phoneLibLoading = new Promise((resolve) => {
    const s = document.createElement("script");
    s.src = PHONE_LIB_SRC;
    s.async = true;
    s.onload = () => { updateProgress(); resolve(); };
    s.onerror = () => resolve(); // fall back to the length check
    document.head.appendChild(s);
  });
  return phoneLibLoading;
}

// Fallback: national number lengths for the most common countries, 7 to 12 digits elsewhere
const PHONE_LENGTHS = { IN: [10], US: [10], CA: [10], GB: [10], AE: [9], AU: [9], SG: [8], SA: [9], PK: [10], BD: [10], NZ: [8, 9, 10] };

function checkPhone() {
  const p = fullPhone();
  const country = COUNTRY_BY_ISO[p.country];
  const lib = window.libphonenumber;
  if (lib && lib.parsePhoneNumberFromString) {
    const parsed = lib.parsePhoneNumberFromString(p.e164);
    if (parsed && parsed.isValid()) return { ok: true, display: parsed.formatInternational(), e164: parsed.number };
    return { ok: false, country };
  }
  const lengths = PHONE_LENGTHS[p.country];
  const okLength = lengths ? lengths.includes(p.national.length) : p.national.length >= 7 && p.national.length <= 12;
  const okIndia = p.country !== "IN" || /^[6-9]/.test(p.national);
  return okLength && okIndia ? { ok: true, display: p.display, e164: p.e164 } : { ok: false, country };
}

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
    if (!/^[+\d\s().-]+$/.test(v)) return "Use digits only. Spaces, brackets and dashes are fine.";
    const result = checkPhone();
    if (!result.ok) return `That isn't a valid ${result.country.name} mobile number. Check the number and the country code.`;
    return "";
  },
  agencyName: (v) => (v.trim().length < 2 ? "Please enter your agency name." : ""),
  clients: (v) => {
    if (!v) return "Please choose an option.";
    if (v === "Other" && !otherInput.value.trim()) return "Please tell us a bit more.";
    return "";
  },
};

// The fields a visitor answers on step n (helpers like the country picker and "Other" box are checked with their field)
const fieldsForStep = (n) => $$("[name]:not([name='clientsOther']):not([name='phoneCountry']):not([name='website'])", steps[n - 1]);
const ALL_FIELDS = () => steps.flatMap((_, i) => fieldsForStep(i + 1));

// Returns the element to focus when the field is invalid, or null when it is fine
function validateField(field, show = true) {
  const msg = validators[field.name](field.value);
  const target = field.name === "clients" && field.value === "Other" ? otherInput : field;
  if (show) {
    $(`[data-error-for="${field.name}"]`, form).textContent = msg;
    field.classList.toggle("is-invalid", !!msg && target === field);
    if (field.name === "clients") otherInput.classList.toggle("is-invalid", !!msg && target === otherInput);
  }
  return msg ? target : null;
}

function validateStep(n, show = true) {
  let firstBad = null;
  fieldsForStep(n).forEach((field) => {
    const bad = validateField(field, show);
    if (bad && !firstBad) firstBad = bad;
  });
  if (show && firstBad) firstBad.focus();
  return !firstBad;
}

/* ---------- Progress (updates live as fields become valid) ---------- */
function updateProgress() {
  if (form.hidden) return; // booking/done screens set their own label
  const fields = ALL_FIELDS();
  const completed = fields.filter((f) => !validateField(f, false)).length;
  const pct = Math.round((completed / fields.length) * 100);
  progressFill.style.width = pct + "%";
  progressBar.setAttribute("aria-valuenow", String(pct));
  progressLabel.textContent = `Step ${current} of ${TOTAL} · ${pct}% complete`;
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
  const fields = fieldsForStep(current);
  const target = fields.find((f) => validateField(f, false)) || fields[0];
  requestAnimationFrame(() => target.focus({ preventScroll: true }));
}

form.addEventListener("submit", (e) => {
  e.preventDefault();
  if (!validateStep(current)) return;
  if (current < TOTAL) showStep(current + 1, 1);
  else submitLead();
});

backBtn.addEventListener("click", () => showStep(current - 1, -1));

form.addEventListener("keydown", (e) => {
  if (e.key !== "Enter" || e.target.tagName !== "INPUT" || !e.target.name) return;
  const fields = fieldsForStep(current);
  const i = fields.indexOf(e.target);
  if (i === -1 || i === fields.length - 1) return; // last field on the step: normal submit
  e.preventDefault();
  if (validateField(e.target)) { e.target.focus(); return; }
  fields[i + 1].focus();
});

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
  loadPhoneLib();
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
  const phone = fullPhone();
  const checked = checkPhone();
  return {
    leadId: uid(),
    fullName: data.fullName.trim(),
    email: data.email.trim().toLowerCase(),
    phone: checked.display || phone.display, // "+91 98765 43210"
    phoneCountry: phone.country,
    phoneNational: phone.national,
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
  label.textContent = "One last step…";
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
  ["fullName", "email", "agencyName"].forEach((k) => { if (lead[k]) form.elements[k].value = lead[k]; });
  if (lead.phoneCountry) setCountry(lead.phoneCountry);
  phoneInput.value = lead.phoneNational || lead.phone || "";
  if (lead.clients) clientsSelect.value = lead.clients;
  if (lead.clientsOther) { otherInput.value = lead.clientsOther; otherWrap.hidden = false; }
  setFirstName();
}

/* ---------- Calendly ---------- */
// "+91 98765 43210" -> "+919876543210", "(555) 123-4567" -> "5551234567"
function toCalendlyPhone(raw) {
  const value = String(raw).trim();
  const digits = value.replace(/\D/g, "");
  return value.startsWith("+") || value.startsWith("00")
    ? "+" + digits.replace(/^00/, "")
    : digits;
}

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

/* ---------- Booking ----------
   1. Try the in-page time picker: open times come from /api/brix-scale/slots and a tap books the call
      through /api/brix-scale/book (server side, Admin > BRIX Scale page > Calendly booking).
   2. If that is switched off, not set up, or fails, show the regular Calendly calendar instead. */
const BOOKING_API = new URL("../api/brix-scale/", location.href).href;
const VISITOR_TZ = (Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC");
let slotState = null; // { lead, slots, day, busy }

const fmtDayKey = new Intl.DateTimeFormat("en-CA", { timeZone: VISITOR_TZ, year: "numeric", month: "2-digit", day: "2-digit" });
const fmtDayLabel = new Intl.DateTimeFormat(undefined, { timeZone: VISITOR_TZ, weekday: "short", day: "numeric", month: "short" });
const fmtTime = new Intl.DateTimeFormat(undefined, { timeZone: VISITOR_TZ, hour: "numeric", minute: "2-digit" });
const fmtFull = new Intl.DateTimeFormat(undefined, { timeZone: VISITOR_TZ, weekday: "long", day: "numeric", month: "long", hour: "numeric", minute: "2-digit" });

async function fetchJson(url, options = {}, timeoutMs = 12000) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const res = await fetch(url, { ...options, signal: controller.signal, credentials: "same-origin" });
    const data = await res.json().catch(() => ({}));
    return { status: res.status, data };
  } finally {
    clearTimeout(timer);
  }
}

async function showBooking(lead) {
  form.hidden = true;
  done.hidden = true;
  booking.hidden = false;
  setBookingProgress("Details saved ✓ · Last step: book your call");
  $("#bookingTitle").focus?.();

  if (calendlyMounted || slotState) return;
  $("#bookingLoading").hidden = false;

  let slots = null;
  let duration = 0;
  if (location.protocol.startsWith("http")) {
    try {
      const { status, data } = await fetchJson(BOOKING_API + "slots", {}, 8000);
      if (status === 200 && data.enabled && Array.isArray(data.slots) && data.slots.length) {
        slots = data.slots;
        duration = data.duration || 0;
      }
    } catch (err) {
      console.warn("[BRIX] Open times unavailable, using the Calendly calendar:", err);
    }
  }

  $("#bookingLoading").hidden = true;
  if (slots) showSlots(lead, slots, duration);
  else mountCalendly(lead);
}

/* ---------- In-page time picker ---------- */
function showSlots(lead, slots, duration) {
  const byDay = new Map();
  slots.forEach((iso) => {
    const key = fmtDayKey.format(new Date(iso));
    if (!byDay.has(key)) byDay.set(key, []);
    byDay.get(key).push(iso);
  });
  slotState = { lead, byDay, day: byDay.keys().next().value, busy: false, duration };

  $("#slots").hidden = false;
  $("#calendlyWrap").hidden = true;
  let tzName = VISITOR_TZ.replace(/_/g, " ");
  try {
    tzName = new Intl.DateTimeFormat(undefined, { timeZone: VISITOR_TZ, timeZoneName: "long" })
      .formatToParts(new Date()).find((p) => p.type === "timeZoneName")?.value || tzName; // e.g. "India Standard Time"
  } catch { /* keep the raw name */ }
  $("#slotsTz").textContent = `Times shown in ${tzName}${duration ? ` · ${duration} minute call` : ""}. Tap a time to book it.`;
  renderDays();
  renderTimes();
}

function renderDays() {
  const wrap = $("#slotsDays");
  wrap.innerHTML = "";
  slotState.byDay.forEach((times, key) => {
    const b = document.createElement("button");
    b.type = "button";
    b.className = "slots__day" + (key === slotState.day ? " is-active" : "");
    b.setAttribute("role", "tab");
    b.setAttribute("aria-selected", String(key === slotState.day));
    const [weekday, ...rest] = fmtDayLabel.format(new Date(times[0])).replace(",", "").split(" ");
    b.innerHTML = `<span>${weekday}</span><strong>${rest.join(" ")}</strong><small>${times.length} open</small>`;
    b.addEventListener("click", () => {
      if (slotState.busy) return;
      slotState.day = key;
      $("#slotsMsg").textContent = "";
      renderDays();
      renderTimes();
    });
    wrap.appendChild(b);
  });
  wrap.querySelector(".is-active")?.scrollIntoView({ block: "nearest", inline: "nearest" });
}

function renderTimes() {
  const wrap = $("#slotsTimes");
  wrap.innerHTML = "";
  (slotState.byDay.get(slotState.day) || []).forEach((iso) => {
    const b = document.createElement("button");
    b.type = "button";
    b.className = "slots__time";
    b.textContent = fmtTime.format(new Date(iso));
    b.setAttribute("aria-label", `Book ${fmtFull.format(new Date(iso))}`);
    b.addEventListener("click", () => bookSlot(iso, b));
    wrap.appendChild(b);
  });
}

async function bookSlot(iso, button) {
  if (slotState.busy) return;
  slotState.busy = true;
  const { lead } = slotState;
  const msg = $("#slotsMsg");
  $$(".slots__time, .slots__day").forEach((b) => (b.disabled = true));
  button.classList.add("is-booking");
  button.innerHTML = '<span class="spinner" aria-hidden="true"></span> Booking…';
  msg.textContent = `Booking ${fmtFull.format(new Date(iso))}…`;

  const utm = getUtm();
  let result = null;
  try {
    const { data } = await fetchJson(BOOKING_API + "book", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        start_time: iso,
        name: lead.fullName,
        email: lead.email,
        phone: toCalendlyPhone(lead.phone),
        agency: lead.agencyName,
        clients: lead.clientsLabel,
        timezone: VISITOR_TZ,
        lead_id: lead.leadId,
        ...utm,
      }),
    }, 25000);
    result = data;
  } catch (err) {
    console.error("[BRIX] Booking request failed:", err);
  }

  if (result && result.ok) {
    markBooked(lead, { start: result.start_time || iso, eventUri: result.event_uri, inviteeUri: result.invitee_uri, rescheduleUrl: result.reschedule_url });
    return;
  }

  slotState.busy = false;
  if (result && result.code === "slot_taken") {
    msg.textContent = result.message || "Someone just took that time. Pick another one.";
    // Drop the taken time and let them pick again
    const day = slotState.byDay.get(slotState.day) || [];
    slotState.byDay.set(slotState.day, day.filter((t) => t !== iso));
    if (!slotState.byDay.get(slotState.day).length) slotState.byDay.delete(slotState.day);
    if (!slotState.byDay.size) { mountCalendly(lead); return; }
    if (!slotState.byDay.has(slotState.day)) slotState.day = slotState.byDay.keys().next().value;
    renderDays();
    renderTimes();
    return;
  }

  // Anything else: finish on the Calendly calendar, opened on the day they picked
  msg.textContent = (result && result.message) || "Pick your time on the calendar below to finish booking.";
  mountCalendly(lead, fmtDayKey.format(new Date(iso)));
}

function markBooked(lead, { start, eventUri = "", inviteeUri = "", rescheduleUrl = "" }) {
  storage.set(LEAD_KEY, { ...lead, booked: true, bookedAt: start || "", rescheduleUrl });
  postToSheets({
    action: "booked",
    leadId: lead.leadId || "",
    email: lead.email || "",
    eventUri,
    inviteeUri,
  }).catch((err) => console.error("[BRIX] Could not mark booking:", err));
  // Go to /brix-scale/thank-you/ once GTM has the event (or after 1s if GTM is slow or blocked).
  let left = false;
  const goToThankYou = () => {
    if (left) return;
    left = true;
    location.assign(new URL("thank-you/", location.href).href);
  };
  if (window.dataLayer) {
    window.dataLayer.push({ event: "brix_call_booked", eventCallback: goToThankYou, eventTimeout: 1000 });
  }
  setTimeout(goToThankYou, 1000);
}

/* ---------- Calendly calendar (fallback) ---------- */
async function mountCalendly(lead, date) {
  $("#bookingLoading").hidden = true;
  const wrap = $("#calendlyWrap");
  wrap.hidden = false;
  if (slotState) {
    // Keep any message, hide the picker
    $("#slotsDays").innerHTML = "";
    $("#slotsTimes").innerHTML = "";
    $("#slotsTz").textContent = "";
    if (!$("#slotsMsg").textContent) $("#slots").hidden = true;
  }
  if (calendlyMounted) return;
  calendlyMounted = true;

  if (!isConfigured(CONFIG.CALENDLY_URL)) {
    wrap.innerHTML = '<div class="calendly-missing"><strong>Calendly isn\'t connected yet.</strong><br/>Set <code>CALENDLY_URL</code> in <code>script.js</code> to your event link.</div>';
    return;
  }

  try {
    await loadCalendlyScript();
    const customAnswers = {};
    Object.entries(CONFIG.CALENDLY_CUSTOM_ANSWERS || {}).forEach(([key, field]) => {
      if (!lead[field]) return;
      // Calendly's phone field wants "+<country code><number>" with no spaces, dashes or brackets
      customAnswers[key] = field === "phone" ? toCalendlyPhone(lead.phone) : lead[field];
    });
    const sep = CONFIG.CALENDLY_URL.includes("?") ? "&" : "?";
    const day = date ? `&month=${date.slice(0, 7)}&date=${date}` : "";
    const url = `${CONFIG.CALENDLY_URL}${sep}hide_gdpr_banner=1&primary_color=${CONFIG.CALENDLY_PRIMARY_COLOR}${day}`;
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
    // Let Calendly show its own confirmation briefly, then ours
    setTimeout(() => markBooked(lead, {
      eventUri: (p.event && p.event.uri) || "",
      inviteeUri: (p.invitee && p.invitee.uri) || "",
    }), 1800);
  }
});
