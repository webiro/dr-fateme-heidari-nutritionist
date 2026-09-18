/* فاطمه حیدری نائیج — site application */

let SITE_CONFIG = {
  doctor: {
    nameFa: "فاطمه حیدری نائیج",
    nameEn: "Fateme Heidari Naeij",
    shortNameFa: "فاطمه حیدری",
    title: "متخصص و کارشناس تغذیه",
    medicalCode: "ت-۱۱۴۴۲",
    city: "نوشهر",
    province: "مازندران",
    country: "ایران",
    intro: "",
    footerText: ""
  },
  links: {
    website: "",
    instagram: "",
    telegram: "",
    tisaAssistant: "",
    phone: "",
    whatsapp: "",
    medicalCouncil: "",
    bale: "",
    rubika: "",
    eitaa: ""
  },
  appointmentEndpoint: "",
  location: { enabled: false, title: "آدرس مطب", address: "", lat: "", lng: "" },
  colors: {
    light: { bg: "#faf9f6", surface: "rgba(255,255,255,.58)", blue: "#b0c4de", pink: "#f5b7c1", navy: "#1b2433", text: "#1b2433", muted: "#5c6b80", line: "rgba(27,36,51,.08)" },
    dark: { bg: "#0e141d", surface: "rgba(22,30,44,.62)", blue: "#b0c4de", pink: "#f5b7c1", navy: "#eef3f8", text: "#f6f3ee", muted: "#b7c2d0", line: "rgba(250,249,246,.08)" }
  },
  media: { videosPath: "videos/", postsPath: "posts/", maxVideos: 50, maxPosts: 50, maxPostSlides: 30 },
  sections: {
    intro: true, services: true, faq: true, videos: true, posts: true,
    contact: true, appointment: true, article: true, partners: true, location: true
  },
  faq: []
};

const CONTACT_CHANNELS = [
  { key: "instagram", label: "اینستاگرام", icon: "photo_camera", desc: "پیج آموزشی و شخصی." },
  { key: "telegram", label: "کانال تلگرام", icon: "send", desc: "کانال یا پیام تلگرام." },
  { key: "bale", label: "کانال بله", icon: "forum", desc: "ارتباط از طریق بله." },
  { key: "rubika", label: "کانال روبیکا", icon: "chat_bubble", desc: "ارتباط از طریق روبیکا." },
  { key: "eitaa", label: "کانال ایتا", icon: "campaign", desc: "ارتباط از طریق ایتا." },
  { key: "tisaAssistant", label: "دستیار هوشمند تغذیه تیسا", icon: "smart_toy", desc: "دسترسی به دستیار تیسا." },
  { key: "phone", label: "تماس", icon: "call", desc: "گفت‌وگوی تلفنی برای هماهنگی." },
  { key: "whatsapp", label: "واتساپ", icon: "chat", desc: "پیام‌رسانی سریع." }
];

function hasLink(value) {
  return !!(value && String(value).trim());
}

function channelHref(key, value) {
  if (!hasLink(value)) return "";
  if (key === "phone") return value.startsWith("tel:") ? value : `tel:${value}`;
  return value;
}

const FALLBACK_SLOGAN = "Small steps. Stronger habits.";
const isTouch = window.matchMedia("(pointer: coarse)").matches;
const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const isQrPage = document.body && document.body.dataset.page === "qr";

function $(sel, root = document) { return root.querySelector(sel); }
function $$(sel, root = document) { return Array.from(root.querySelectorAll(sel)); }

function hrefOrNull(url) {
  return url && String(url).trim() ? url : null;
}

function mergeDeep(base, extra) {
  if (!extra || typeof extra !== "object") return base;
  Object.keys(extra).forEach((key) => {
    if (extra[key] && typeof extra[key] === "object" && !Array.isArray(extra[key])) {
      base[key] = mergeDeep(base[key] || {}, extra[key]);
    } else {
      base[key] = extra[key];
    }
  });
  return base;
}

function applyColors() {
  const mode = document.documentElement.getAttribute("data-theme") === "dark" ? "dark" : "light";
  const pack = (SITE_CONFIG.colors && SITE_CONFIG.colors[mode]) || {};
  const root = document.documentElement;
  const map = {
    bg: ["--color-bg", "--color-warm"],
    surface: ["--color-surface"],
    blue: ["--color-blue"],
    pink: ["--color-pink"],
    navy: ["--color-navy"],
    text: ["--color-text"],
    muted: ["--color-muted", "--color-text-soft"],
    line: ["--color-line"]
  };
  Object.keys(map).forEach((key) => {
    if (!pack[key]) return;
    map[key].forEach((cssVar) => root.style.setProperty(cssVar, pack[key]));
  });
}

function applyIdentity() {
  const d = SITE_CONFIG.doctor || {};
  const siteUrl = (SITE_CONFIG.links && SITE_CONFIG.links.website) || "https://drfatemeheidari.ir";
  const homeUrl = siteUrl.replace(/\/+$/, "") + "/index.html";
  const canon = document.querySelector('link[rel="canonical"]');
  if (canon) canon.setAttribute("href", homeUrl);
  const ogUrl = document.querySelector('meta[property="og:url"]');
  if (ogUrl) ogUrl.setAttribute("content", homeUrl);
  const base = window.SITE_BASE || "";
  $$("img").forEach((img) => {
    const src = img.getAttribute("src") || "";
    if (/^(images|videos|posts)\//.test(src) && base && !src.startsWith(base) && !src.startsWith("http")) {
      img.src = base + src;
    }
  });
  $$('link[rel="icon"]').forEach((l) => {
    const href = l.getAttribute("href") || "";
    if (href.startsWith("images/") && base) l.href = base + href;
  });

  const loc = `${d.city || ""}، ${d.province || ""}`.replace(/^، |، $/g, "");
  document.title = `${d.nameFa || ""} | ${d.nameEn || ""} | ${d.title || ""}`.replace(/^\s\|\s|\s\|\s$/g, "");
  $$("[data-name-fa], .brand-text strong, .loader-name").forEach((el) => {
    if (el.classList.contains("loader-name") || el.matches(".brand-text strong")) el.textContent = d.shortNameFa || d.nameFa || el.textContent;
  });
  const h1 = $("h1");
  if (h1 && !isQrPage) h1.textContent = `دکتر ${d.nameFa || ""}`.replace("دکتر دکتر", "دکتر");
  if (h1 && isQrPage) h1.textContent = d.nameFa || h1.textContent;
  const role = $(".hero-role, .qr-role");
  if (role) role.textContent = d.title || role.textContent;
  const en = $(".qr-en");
  if (en) en.textContent = d.nameEn || "";
  const lead = $(".hero-lead");
  if (lead && d.intro) lead.textContent = d.intro;
  $$(".chip").forEach((chip) => {
    if (chip.textContent.includes("نظام پزشکی") && d.medicalCode) {
      chip.lastChild && (chip.innerHTML = `<span class="material-symbols-rounded" style="font-size:16px">badge</span> کد نظام پزشکی: ${d.medicalCode}`);
    }
    if (chip.textContent.includes("نوشهر") || chip.textContent.includes("استان")) {
      chip.innerHTML = `<span class="material-symbols-rounded" style="font-size:16px">location_on</span> ${loc}`;
    }
  });
  const badge = $(".badge-med");
  if (badge && d.medicalCode) badge.textContent = d.medicalCode;
  const float = $(".float-card strong");
  if (float) float.textContent = loc;
  const footName = $(".footer-brand strong");
  if (footName) footName.textContent = d.nameFa || footName.textContent;
  const footCopy = $(".footer-copy");
  if (footCopy && d.footerText) footCopy.textContent = d.footerText;
  const yearLine = $(".copyright");
  if (yearLine) yearLine.innerHTML = `${d.shortNameFa || d.nameFa || ""} · ${loc} · <span id="year">${new Date().getFullYear()}</span>`;
  const contactH2 = $("#contact .section-head h2");
  if (contactH2) contactH2.textContent = `راه‌های ارتباطی با دکتر ${d.shortNameFa || d.nameFa || ""}`;
  const appointH2 = $("#appoint h2");
  if (appointH2) appointH2.textContent = `رزرو وقت مشاوره تغذیه با دکتر ${d.shortNameFa || d.nameFa || ""}`;
  const videosH2 = $("#videos .section-head h2");
  if (videosH2) videosH2.textContent = `آخرین ویدیوهای اینستاگرام دکتر ${d.shortNameFa || d.nameFa || ""}`;
  const postsH2 = $("#posts .section-head h2");
  if (postsH2) postsH2.textContent = `پست‌های اینستاگرام دکتر ${d.shortNameFa || d.nameFa || ""}`;
  const faqH2 = $("#faq .section-head h2");
  if (faqH2) faqH2.textContent = `سوالات متداول درباره رژیم و تغذیه با دکتر ${d.shortNameFa || d.nameFa || ""}`;
  const servicesH2 = $("#services .section-head h2");
  if (servicesH2) servicesH2.textContent = `خدمات تغذیه و رژیم غذایی دکتر ${d.shortNameFa || d.nameFa || ""}`;
}

function applySections() {
  const s = SITE_CONFIG.sections || {};
  const map = {
    intro: "#intro",
    services: "#services",
    faq: "#faq",
    videos: "#videos",
    posts: "#posts",
    contact: "#contact",
    appointment: "#appoint",
    article: "#article",
    partners: "#partners",
    location: "#location"
  };
  Object.keys(map).forEach((key) => {
    const el = $(map[key]);
    if (!el) return;
    if (s[key] === false) el.classList.add("hidden");
  });
  $$(".nav-desktop a, .mobile-panel a").forEach((a) => {
    const href = a.getAttribute("href") || "";
    const id = href.replace("#", "");
    const pair = {
      intro: "intro", services: "services", videos: "videos", posts: "posts",
      faq: "faq", contact: "contact", appoint: "appointment", location: "location"
    };
    if (pair[id] && s[pair[id]] === false) a.classList.add("hidden");
  });
}

async function loadConfig() {
  try {
    const res = await fetch(`${window.SITE_BASE || ""}config.json`, { cache: "no-store" });
    if (res.ok) SITE_CONFIG = mergeDeep(SITE_CONFIG, await res.json());
  } catch (e) { /* keep defaults */ }
  applyColors();
  applyIdentity();
  applySections();
}

/* ---------- Theme ---------- */
const Theme = {
  key: "fh-theme",
  init() {
    const saved = localStorage.getItem(this.key);
    const preferDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const theme = saved || (preferDark ? "dark" : "light");
    this.apply(theme, false);
    $$("[data-theme-toggle]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const next = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
        this.apply(next, true);
      });
    });
  },
  apply(theme, persist) {
    document.documentElement.setAttribute("data-theme", theme);
    if (persist) localStorage.setItem(this.key, theme);
    applyColors();
    $$("[data-theme-toggle]").forEach((btn) => {
      const icon = btn.querySelector(".material-symbols-rounded");
      if (icon) icon.textContent = theme === "dark" ? "light_mode" : "dark_mode";
      btn.setAttribute("aria-label", theme === "dark" ? "حالت روشن" : "حالت تیره");
    });
  }
};

/* ---------- Loader ---------- */
function hideLoader() {
  const loader = $("#loader");
  if (!loader) return;
  requestAnimationFrame(() => loader.classList.add("is-gone"));
  setTimeout(() => loader.remove(), 800);
}

/* ---------- Navigation ---------- */
function initNav() {
  const header = $(".site-header");
  const panel = $(".mobile-panel");
  const menuBtn = $(".menu-btn");
  const closeBtn = $(".menu-close");

  const onScroll = () => {
    if (!header) return;
    header.classList.toggle("is-scrolled", window.scrollY > 12);
  };
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  function openMenu(open) {
    if (!panel) return;
    panel.classList.toggle("is-open", open);
    panel.setAttribute("aria-hidden", String(!open));
    document.body.style.overflow = open ? "hidden" : "";
    if (menuBtn) menuBtn.setAttribute("aria-expanded", String(open));
  }

  menuBtn && menuBtn.addEventListener("click", () => openMenu(true));
  closeBtn && closeBtn.addEventListener("click", () => openMenu(false));
  panel && $$("a", panel).forEach((a) => a.addEventListener("click", () => openMenu(false)));
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") openMenu(false);
  });

  $$('a[href^="#"]').forEach((a) => {
    a.addEventListener("click", (e) => {
      const id = a.getAttribute("href");
      if (!id || id === "#") return;
      const target = $(id);
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "start" });
    });
  });
}

/* ---------- FAQ ---------- */
function renderServices() {
  const grid = $("#services-grid");
  if (!grid) return;
  grid.innerHTML = "";
  (SITE_CONFIG.services || []).forEach((item) => {
    if (!item || !item.title) return;
    const el = document.createElement("article");
    el.className = "svc";
    el.innerHTML = `
      <div class="ico"><span class="material-symbols-rounded">${item.icon || "nutrition"}</span></div>
      <h3>${item.title}</h3>
      <p>${item.text || ""}</p>`;
    grid.appendChild(el);
  });
}

function renderFaq() {
  const list = $("#faq-list");
  if (!list) return;
  list.innerHTML = "";
  SITE_CONFIG.faq.forEach((item, i) => {
    const el = document.createElement("div");
    el.className = "faq-item";
    el.innerHTML = `
      <button class="faq-q" id="faq-q-${i}" aria-expanded="false" aria-controls="faq-a-${i}">
        <span>${item.question}</span>
        <span class="faq-ico" aria-hidden="true"><span class="material-symbols-rounded">add</span></span>
      </button>
      <div class="faq-a" id="faq-a-${i}" role="region" aria-labelledby="faq-q-${i}">
        <div><p>${item.answer}</p></div>
      </div>`;
    list.appendChild(el);
  });

  list.addEventListener("click", (e) => {
    const btn = e.target.closest(".faq-q");
    if (!btn) return;
    const item = btn.closest(".faq-item");
    const open = item.classList.contains("is-open");
    $$(".faq-item", list).forEach((it) => {
      it.classList.remove("is-open");
      const b = it.querySelector(".faq-q");
      if (b) b.setAttribute("aria-expanded", "false");
    });
    if (!open) {
      item.classList.add("is-open");
      btn.setAttribute("aria-expanded", "true");
    }
  });

  list.addEventListener("keydown", (e) => {
    if (!e.target.classList.contains("faq-q")) return;
    const buttons = $$(".faq-q", list);
    const idx = buttons.indexOf(e.target);
    if (e.key === "ArrowDown") { e.preventDefault(); buttons[(idx + 1) % buttons.length].focus(); }
    if (e.key === "ArrowUp") { e.preventDefault(); buttons[(idx - 1 + buttons.length) % buttons.length].focus(); }
  });
}

/* ---------- Asset discovery ---------- */
function mediaUrl(path) {
  const clean = String(path || "").replace(/^\/+/, "");
  return `${window.SITE_BASE || ""}${clean}`;
}

function existsImage(url) {
  return new Promise((resolve) => {
    const img = new Image();
    const done = (ok) => {
      img.onload = img.onerror = null;
      resolve(ok);
    };
    const timer = setTimeout(() => done(false), 4000);
    img.referrerPolicy = "no-referrer";
    img.onload = () => { clearTimeout(timer); done(true); };
    img.onerror = () => { clearTimeout(timer); done(false); };
    img.src = url;
  });
}

async function existsFile(url) {
  try {
    const res = await fetch(url, { method: "GET", cache: "no-store" });
    return res.ok;
  } catch (e) {
    return false;
  }
}

function existsVideo(url) {
  return new Promise((resolve) => {
    const v = document.createElement("video");
    v.preload = "metadata";
    v.muted = true;
    v.setAttribute("playsinline", "");
    const done = (ok) => {
      v.onloadedmetadata = v.onerror = null;
      v.removeAttribute("src");
      try { v.load(); } catch (e) { /* ignore */ }
      resolve(ok);
    };
    const timer = setTimeout(() => done(false), 5000);
    v.onloadedmetadata = () => { clearTimeout(timer); done(true); };
    v.onerror = () => { clearTimeout(timer); done(false); };
    v.src = url;
  });
}

const exists = existsImage;

async function firstExisting(candidates) {
  for (const url of candidates) {
    if (await exists(url)) return url;
  }
  return null;
}

async function discoverNumbered(buildCandidates, max) {
  const found = [];
  for (let i = 1; i <= max; i += 1) {
    const url = await firstExisting(buildCandidates(i));
    if (!url) break;
    found.push(url);
  }
  return found;
}

/* ---------- Videos ---------- */
let plyrPlayers = [];

async function initVideos() {
  const mount = $("#video-carousel");
  const empty = $("#videos-empty");
  if (!mount) return;

  const videos = [];
  for (let n = 1; n <= SITE_CONFIG.media.maxVideos; n += 1) {
    const candidates = [
      mediaUrl(`${SITE_CONFIG.media.videosPath}${n}.mp4`),
      mediaUrl(`${SITE_CONFIG.media.videosPath}${n}.webm`),
      mediaUrl(`${SITE_CONFIG.media.videosPath}${n}.MP4`)
    ];
    let found = "";
    for (const cand of candidates) {
      if (await existsVideo(cand) || await existsFile(cand)) { found = cand; break; }
    }
    if (!found) break;
    videos.push(found);
  }

  if (!videos.length) {
    if (empty) empty.classList.remove("hidden");
    return;
  }
  if (empty) empty.classList.add("hidden");

  videos.forEach((src, i) => {
    const item = document.createElement("div");
    item.className = "item";
    item.innerHTML = `
      <div class="video-card">
        <video playsinline controls preload="none" data-plyr>
          <source src="${src}" type="video/mp4">
        </video>
      </div>`;
    mount.appendChild(item);
  });

  try {
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.owlCarousel) {
      window.jQuery(mount).owlCarousel({
        rtl: true,
        loop: videos.length > 5,
        margin: 16,
        nav: true,
        dots: false,
        lazyLoad: true,
        responsive: {
          0: { items: 2 },
          768: { items: 3 },
          1100: { items: 5 }
        }
      });
    }
  } catch (err) {
    console.warn("Owl Carousel unavailable", err);
  }

  initPlyr();
}

function initPlyr() {
  const nodes = $$("[data-plyr]");
  if (!nodes.length) return;
  try {
    if (window.Plyr) {
      plyrPlayers = nodes.map((el) => new window.Plyr(el, {
        autoplay: false,
        clickToPlay: true,
        ratio: "9:16",
        controls: ["play", "progress", "current-time", "mute", "volume", "fullscreen"]
      }));
      plyrPlayers.forEach((p) => {
        p.on("play", () => {
          plyrPlayers.forEach((other) => {
            if (other !== p) other.pause();
          });
        });
      });
      return;
    }
  } catch (err) {
    console.warn("Plyr unavailable", err);
  }
  nodes.forEach((v) => {
    v.addEventListener("play", () => {
      nodes.forEach((o) => { if (o !== v) o.pause(); });
    });
  });
}

/* ---------- Posts + lightbox ---------- */
const Lightbox = {
  slides: [],
  index: 0,
  startX: 0,
  init() {
    this.root = $("#lightbox");
    if (!this.root) return;
    this.img = $("#lightbox-img");
    this.counter = $("#lightbox-counter");
    $("#lightbox-close")?.addEventListener("click", () => this.close());
    $("#lightbox-prev")?.addEventListener("click", () => this.step(-1));
    $("#lightbox-next")?.addEventListener("click", () => this.step(1));
    this.root.addEventListener("click", (e) => {
      if (e.target === this.root) this.close();
    });
    document.addEventListener("keydown", (e) => {
      if (!this.root.classList.contains("is-open")) return;
      if (e.key === "Escape") this.close();
      if (e.key === "ArrowLeft") this.step(1);
      if (e.key === "ArrowRight") this.step(-1);
    });
    this.root.addEventListener("touchstart", (e) => { this.startX = e.changedTouches[0].clientX; }, { passive: true });
    this.root.addEventListener("touchend", (e) => {
      const dx = e.changedTouches[0].clientX - this.startX;
      if (Math.abs(dx) > 40) this.step(dx > 0 ? -1 : 1);
    }, { passive: true });
  },
  open(slides, start = 0) {
    this.slides = slides;
    this.index = start;
    this.render();
    this.root.classList.add("is-open");
    this.root.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
    $("#lightbox-close")?.focus();
  },
  close() {
    this.root.classList.remove("is-open");
    this.root.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  },
  step(dir) {
    if (!this.slides.length) return;
    this.index = (this.index + dir + this.slides.length) % this.slides.length;
    this.render();
  },
  render() {
    if (this.img) {
      this.img.referrerPolicy = "no-referrer";
      this.img.src = this.slides[this.index];
    }
    if (this.counter) this.counter.textContent = `${this.index + 1} / ${this.slides.length}`;
  }
};

function postSlideCandidates(folder, slide) {
  const base = `${SITE_CONFIG.media.postsPath}${folder}/${slide}`;
  return [".jpg", ".jpeg", ".png", ".webp", ".JPG", ".JPEG", ".PNG"].map((ext) => mediaUrl(base + ext));
}

async function discoverPostSlides(folder) {
  const slides = [];
  for (let i = 1; i <= SITE_CONFIG.media.maxPostSlides; i += 1) {
    const url = await firstExisting(postSlideCandidates(folder, i));
    if (!url) break;
    slides.push(url);
  }
  return slides;
}

async function initPosts() {
  const grid = $("#posts-grid");
  const empty = $("#posts-empty");
  if (!grid) return;

  const thumbs = await discoverNumbered(
    (n) => postSlideCandidates(n, 1),
    SITE_CONFIG.media.maxPosts
  );

  if (!thumbs.length) {
    if (empty) empty.classList.remove("hidden");
    return;
  }
  if (empty) empty.classList.add("hidden");

  const cache = {};
  thumbs.forEach((thumb, idx) => {
    const folder = idx + 1;
    const tile = document.createElement("button");
    tile.className = "post-tile";
    tile.type = "button";
    tile.setAttribute("aria-label", `پست ${folder}`);
    tile.innerHTML = `<img src="${thumb}" alt="" loading="lazy" width="400" height="400" referrerpolicy="no-referrer" decoding="async"><span class="post-count">گالری</span>`;
    tile.addEventListener("click", async () => {
      if (!cache[folder]) cache[folder] = await discoverPostSlides(folder);
      const slides = cache[folder].length ? cache[folder] : [thumb];
      const badge = tile.querySelector(".post-count");
      if (badge) badge.textContent = `${slides.length} اسلاید`;
      Lightbox.open(slides, 0);
    });
    grid.appendChild(tile);
  });
}

/* ---------- Contact links ---------- */
function bindExternalLinks() {
  const links = SITE_CONFIG.links || {};
  const medical = $("[data-link=medical]");
  if (medical) {
    if (hasLink(links.medicalCouncil)) {
      medical.setAttribute("href", links.medicalCouncil);
      medical.setAttribute("target", "_blank");
      medical.setAttribute("rel", "noopener noreferrer");
    } else {
      medical.classList.add("hidden");
    }
  }

  const grid = $("#contact-grid");
  if (grid) {
    grid.innerHTML = "";
    CONTACT_CHANNELS.forEach((ch) => {
      const raw = links[ch.key];
      if (!hasLink(raw)) return;
      const href = channelHref(ch.key, raw);
      const a = document.createElement("a");
      a.className = "contact-card";
      a.href = href;
      if (ch.key !== "phone") {
        a.target = "_blank";
        a.rel = "noopener noreferrer";
      }
      a.innerHTML = `<span class="material-symbols-rounded">${ch.icon}</span><h3>${ch.label}</h3><p>${ch.desc}</p>`;
      grid.appendChild(a);
    });
    if (!grid.children.length && $("#contact")) $("#contact").classList.add("hidden");
  }
}

function renderLocation() {
  const loc = SITE_CONFIG.location || {};
  const enabled = SITE_CONFIG.sections?.location !== false && loc.enabled !== false && loc.lat && loc.lng;
  const section = $("#location");
  if (section && !enabled) section.classList.add("hidden");
  if (!enabled) {
    const qrLoc = $("#qr-location");
    if (qrLoc) qrLoc.hidden = true;
    return;
  }

  const title = loc.title || "آدرس مطب";
  const address = loc.address || "";
  const lat = loc.lat;
  const lng = loc.lng;
  const maps = [
    { label: "مسیریابی با گوگل مپ", href: `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}` },
    { label: "مسیریابی با Waze", href: `https://waze.com/ul?ll=${lat},${lng}&navigate=yes` },
    { label: "مسیریابی با نشان", href: `https://neshan.org/maps/@${lat},${lng},16z` }
  ];

  const titleEl = $("#location-title");
  const addrEl = $("#location-address");
  const actions = $("#location-actions");
  if (titleEl) titleEl.textContent = title;
  if (addrEl) addrEl.textContent = address;
  if (actions) {
    actions.innerHTML = maps.map((m) => `<a class="btn btn-ghost" href="${m.href}" target="_blank" rel="noopener noreferrer">${m.label}</a>`).join("");
  }
  const frame = $("#map-frame");
  if (frame) {
    frame.innerHTML = `<iframe title="${title}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q=${lat},${lng}&z=16&output=embed"></iframe>`;
  }

  const qrLoc = $("#qr-location");
  if (qrLoc) {
    qrLoc.hidden = false;
    qrLoc.innerHTML = `<h2>${title}</h2><p>${address}</p><div class="location-actions">${maps.map((m) => `<a class="btn btn-ghost" href="${m.href}" target="_blank" rel="noopener noreferrer">${m.label}</a>`).join("")}</div>`;
  }
}

/* ---------- Appointment form ---------- */
function initForm() {
  const form = $("#appointment-form");
  if (!form) return;
  const msg = $("#form-msg");
  const submit = form.querySelector("[type=submit]");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    msg.className = "form-msg";
    msg.textContent = "";

    const data = Object.fromEntries(new FormData(form).entries());
    if (!data.name || !data.age || !data.height || !data.goal || !data.phone) {
      msg.classList.add("err");
      msg.textContent = "لطفاً فیلدهای ضروری را تکمیل کنید.";
      return;
    }

    const endpoint = SITE_CONFIG.appointmentEndpoint || (SITE_CONFIG.workerUrl ? `${SITE_CONFIG.workerUrl.replace(/\/+$/, "")}/api/appointments` : "");
    if (!endpoint) {
      msg.classList.add("err");
      msg.textContent = "ارتباط با سرور برقرار نشد. لطفاً بعداً دوباره تلاش کنید.";
      return;
    }

    submit.disabled = true;
    submit.dataset.label = submit.textContent;
    submit.textContent = "در حال ارسال...";
    try {
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      });
      if (!res.ok) throw new Error("bad");
      msg.classList.add("ok");
      msg.textContent = "درخواست شما با موفقیت ثبت شد. به‌زودی با شما تماس خواهیم گرفت.";
      form.reset();
    } catch (err) {
      msg.classList.add("err");
      msg.textContent = "ارتباط با سرور برقرار نشد. لطفاً بعداً دوباره تلاش کنید.";
    } finally {
      submit.disabled = false;
      submit.textContent = submit.dataset.label || "ارسال درخواست";
    }
  });
}

/* ---------- SEO article ---------- */
async function initArticle() {
  const art = $("#seo-article");
  const btn = $("#seo-toggle");
  if (!art || !btn) return;
  try {
    const res = await fetch(`${window.SITE_BASE || ""}content.txt`, { cache: "no-store" });
    if (res.ok) {
      const html = (await res.text()).trim();
      if (html) art.innerHTML = html;
    }
  } catch (e) { /* keep fallback markup */ }
  btn.addEventListener("click", () => {
    const open = art.classList.toggle("is-open");
    btn.textContent = open ? "بستن" : "نمایش بیشتر";
  });
}

/* ---------- ASAP slogan ---------- */
async function initSlogan() {
  const el = $("#asap-slogan");
  if (!el) return;
  let slogan = FALLBACK_SLOGAN;
  try {
    const res = await fetch(`${window.SITE_BASE || ""}asap.json`, { cache: "no-store" });
    if (res.ok) {
      const json = await res.json();
      if (json.slogans && json.slogans.length) {
        slogan = json.slogans[Math.floor(Math.random() * json.slogans.length)];
      }
    }
  } catch (e) { /* fallback */ }

  if (reduceMotion) {
    el.textContent = slogan;
    return;
  }
  el.textContent = "";
  let i = 0;
  const tick = () => {
    i += 1;
    el.textContent = slogan.slice(0, i);
    if (i < slogan.length) setTimeout(tick, 38);
  };
  tick();
}

/* ---------- Partners ---------- */
async function initPartners() {
  const section = $("#partners");
  const track = $("#marquee-track");
  if (!section || !track) return;
  const names = ["1.png", "2.png", "3.png", "4.png", "5.png", "1.svg", "2.svg", "3.svg", "logo1.png", "partner1.png"];
  const found = [];
  for (const name of names) {
    const url = mediaUrl(`images/partners/${name}`);
    if (await exists(url)) found.push(url);
  }
  if (!found.length) {
    section.classList.add("hidden");
    return;
  }
  section.classList.remove("hidden");
  const html = found.map((src) => `<img src="${src}" alt="" height="42">`).join("");
  track.innerHTML = html + html;
}

/* ---------- Cursor / micro interactions ---------- */
function initCursor() {
  const glow = $(".cursor-glow");
  if (!glow || isTouch || reduceMotion) return;
  document.body.classList.add("has-pointer");
  let x = 0, y = 0, tx = 0, ty = 0;
  window.addEventListener("pointermove", (e) => {
    tx = e.clientX; ty = e.clientY;
    glow.classList.add("is-on");
  }, { passive: true });
  const loop = () => {
    x += (tx - x) * 0.12;
    y += (ty - y) * 0.12;
    glow.style.left = `${x}px`;
    glow.style.top = `${y}px`;
    requestAnimationFrame(loop);
  };
  loop();

  $$(".btn-primary").forEach((btn) => {
    btn.addEventListener("pointermove", (e) => {
      const r = btn.getBoundingClientRect();
      const dx = e.clientX - (r.left + r.width / 2);
      const dy = e.clientY - (r.top + r.height / 2);
      btn.style.transform = `translate(${dx * 0.08}px, ${dy * 0.12}px)`;
    });
    btn.addEventListener("pointerleave", () => { btn.style.transform = ""; });
  });

  const portrait = $(".portrait-frame img");
  const stage = $(".portrait-stage");
  if (portrait && stage) {
    stage.addEventListener("pointermove", (e) => {
      const r = stage.getBoundingClientRect();
      const px = (e.clientX - r.left) / r.width - 0.5;
      const py = (e.clientY - r.top) / r.height - 0.5;
      portrait.style.transform = `scale(1.04) translate(${px * -10}px, ${py * -8}px)`;
    });
    stage.addEventListener("pointerleave", () => { portrait.style.transform = ""; });
  }
}

/* ---------- Optional Three.js orb ---------- */
function initOrb() {
  const canvas = $("#nutrition-orb");
  if (!canvas || isTouch || reduceMotion || !window.THREE) return;
  if (window.matchMedia("(max-width: 720px)").matches) return;

  try {
    const THREE = window.THREE;
    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(35, 2, 0.1, 20);
    camera.position.z = 3.2;
    const geo = new THREE.IcosahedronGeometry(0.9, 1);
    const mat = new THREE.MeshPhysicalMaterial({
      color: 0xb0c4de,
      roughness: 0.25,
      transmission: 0.65,
      thickness: 0.6,
      transparent: true,
      opacity: 0.85
    });
    const mesh = new THREE.Mesh(geo, mat);
    scene.add(mesh);
    const light = new THREE.DirectionalLight(0xf5b7c1, 1.2);
    light.position.set(2, 2, 3);
    scene.add(light);
    scene.add(new THREE.AmbientLight(0xffffff, 0.6));

    const resize = () => {
      const w = canvas.clientWidth || canvas.parentElement.clientWidth;
      const h = canvas.clientHeight || 180;
      renderer.setSize(w, h, false);
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
    };
    resize();
    window.addEventListener("resize", resize);

    let raf;
    const tick = (t) => {
      mesh.rotation.y = t * 0.00025;
      mesh.rotation.x = Math.sin(t * 0.0002) * 0.2;
      renderer.render(scene, camera);
      raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);
    document.addEventListener("visibilitychange", () => {
      if (document.hidden) cancelAnimationFrame(raf);
      else raf = requestAnimationFrame(tick);
    });
  } catch (err) {
    console.warn("Three.js scene skipped", err);
    canvas.style.display = "none";
  }
}

/* ---------- QR page ---------- */
function initQr() {
  bindExternalLinks();
  renderLocation();
  const box = $("#qr-actions");
  if (!box) return;
  const links = SITE_CONFIG.links || {};
  const items = [
    { href: links.website || "index.html", label: "ورود به سایت", icon: "home", key: "website" },
    { href: links.telegram, label: "کانال تلگرام", icon: "send", key: "telegram" },
    { href: links.instagram, label: "پیج اینستاگرام", icon: "photo_camera", key: "instagram" },
    { href: links.bale, label: "کانال بله", icon: "forum", key: "bale" },
    { href: links.rubika, label: "کانال روبیکا", icon: "chat_bubble", key: "rubika" },
    { href: links.eitaa, label: "کانال ایتا", icon: "campaign", key: "eitaa" },
    { href: "index.html#appoint", label: "رزرو وقت مشاوره رایگان", icon: "event_available", key: "appointment" },
    { href: channelHref("phone", links.phone), label: "تماس", icon: "call", key: "phone" },
    { href: links.whatsapp, label: "واتساپ", icon: "chat", key: "whatsapp" }
  ];
  box.innerHTML = "";
  items.forEach((item) => {
    if (item.key === "appointment" && SITE_CONFIG.sections?.appointment === false) return;
    if (item.key !== "website" && item.key !== "appointment" && !hasLink(item.href)) return;
    const a = document.createElement("a");
    a.href = item.href || "#";
    if (item.key !== "phone" && item.key !== "website" && item.key !== "appointment") {
      a.target = "_blank";
      a.rel = "noopener noreferrer";
    }
    a.innerHTML = `<span class="material-symbols-rounded">${item.icon}</span> ${item.label}`;
    box.appendChild(a);
  });
}

/* ---------- Boot ---------- */
function loadScript(src) {
  return new Promise((resolve, reject) => {
    const s = document.createElement("script");
    s.src = src;
    s.async = true;
    s.onload = resolve;
    s.onerror = reject;
    document.head.appendChild(s);
  });
}

async function boot() {
  try {
    Theme.init();
    await loadConfig();
    const qr = document.body && document.body.dataset.page === "qr";
    if (qr) {
      initQr();
      return;
    }
    initNav();
    renderServices();
    renderFaq();
    bindExternalLinks();
    renderLocation();
    initForm();
    initArticle();
    Lightbox.init();
    initCursor();
    initSlogan();
    if (SITE_CONFIG.sections?.videos !== false) initVideos();
    if (SITE_CONFIG.sections?.posts !== false) initPosts();
    if (SITE_CONFIG.sections?.partners !== false) initPartners();
  } catch (err) {
    console.warn(err);
  } finally {
    hideLoader();
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}

window.SITE_CONFIG = SITE_CONFIG;
