/* فاطمه حیدری نائیج — site application */

const SITE_CONFIG = {
  doctor: {
    nameFa: "فاطمه حیدری نائیج",
    nameEn: "Fateme Heidari Naeij",
    shortNameFa: "فاطمه حیدری",
    title: "متخصص و کارشناس تغذیه",
    medicalCode: "ت-۱۱۴۴۲",
    city: "نوشهر",
    province: "مازندران",
    country: "ایران"
  },
  links: {
    website: "https://drfatemeheidari.ir",
    instagram: "https://instagram.com/diet_tisa",
    telegram: "https://t.me/diet_tisa",
    tisaAssistant: "https://t.me/ai_diet_tisa_bot",
    phone: "+989121231212",
    whatsapp: "https://wa.me/989121231212",
    medicalCouncil: "https://membersearch.irimc.org/member/profile?id=e5608720-3b33-42c7-a2e5-f45aec17a7ba"
  },
  appointmentEndpoint: "",
  media: {
    videosPath: "videos/",
    postsPath: "posts/",
    maxVideos: 50,
    maxPosts: 50,
    maxPostSlides: 30
  },
  faq: [
    { question: "سوال اول", answer: "جواب اول" },
    { question: "سوال اول", answer: "جواب اول" },
    { question: "سوال اول", answer: "جواب اول" },
    { question: "سوال اول", answer: "جواب اول" },
    { question: "سوال اول", answer: "جواب اول" },
    { question: "سوال اول", answer: "جواب اول" },
    { question: "سوال اول", answer: "جواب اول" },
    { question: "سوال اول", answer: "جواب اول" },
    { question: "سوال اول", answer: "جواب اول" },
    { question: "سوال اول", answer: "جواب اول" }
  ]
};

const FALLBACK_SLOGAN = "Small steps. Stronger habits.";
const isTouch = window.matchMedia("(pointer: coarse)").matches;
const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const isQrPage = document.body && document.body.dataset.page === "qr";

function $(sel, root = document) { return root.querySelector(sel); }
function $$(sel, root = document) { return Array.from(root.querySelectorAll(sel)); }

function hrefOrNull(url) {
  return url && String(url).trim() ? url : null;
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
function exists(url) {
  const ctrl = new AbortController();
  const t = setTimeout(() => ctrl.abort(), 2500);
  return fetch(url, { method: "HEAD", signal: ctrl.signal })
    .then((r) => r.ok)
    .catch(() => false)
    .finally(() => clearTimeout(t));
}

async function discoverNumbered(buildUrl, max) {
  const found = [];
  for (let i = 1; i <= max; i += 1) {
    const url = buildUrl(i);
    /* Some static hosts reject HEAD; fall back to GET range-less check */
    let ok = await exists(url);
    if (!ok) {
      try {
        const r = await fetch(url, { method: "GET", cache: "no-store" });
        ok = r.ok;
      } catch (e) {
        ok = false;
      }
    }
    if (!ok) break;
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

  const videos = await discoverNumbered(
    (n) => `${SITE_CONFIG.media.videosPath}${n}.mp4`,
    SITE_CONFIG.media.maxVideos
  );

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
    if (this.img) this.img.src = this.slides[this.index];
    if (this.counter) this.counter.textContent = `${this.index + 1} / ${this.slides.length}`;
  }
};

async function discoverPostSlides(folder) {
  const slides = [];
  for (let i = 1; i <= SITE_CONFIG.media.maxPostSlides; i += 1) {
    const url = `${SITE_CONFIG.media.postsPath}${folder}/${i}.jpg`;
    let ok = await exists(url);
    if (!ok) {
      try { ok = (await fetch(url, { cache: "no-store" })).ok; } catch (e) { ok = false; }
    }
    if (!ok) break;
    slides.push(url);
  }
  return slides;
}

async function initPosts() {
  const grid = $("#posts-grid");
  const empty = $("#posts-empty");
  if (!grid) return;

  const thumbs = await discoverNumbered(
    (n) => `${SITE_CONFIG.media.postsPath}${n}/1.jpg`,
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
    tile.innerHTML = `<img src="${thumb}" alt="" loading="lazy" width="400" height="400"><span class="post-count">گالری</span>`;
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
  const map = {
    instagram: SITE_CONFIG.links.instagram,
    telegram: SITE_CONFIG.links.telegram,
    tisa: SITE_CONFIG.links.tisaAssistant,
    phone: SITE_CONFIG.links.phone ? `tel:${SITE_CONFIG.links.phone}` : "",
    whatsapp: SITE_CONFIG.links.whatsapp,
    medical: SITE_CONFIG.links.medicalCouncil,
    website: SITE_CONFIG.links.website || "/"
  };

  $$("[data-link]").forEach((el) => {
    const key = el.getAttribute("data-link");
    const url = hrefOrNull(map[key]);
    if (url) {
      el.setAttribute("href", url);
      if (key !== "phone" && key !== "website") {
        el.setAttribute("target", "_blank");
        el.setAttribute("rel", "noopener noreferrer");
      }
    } else {
      el.setAttribute("href", "#");
      el.classList.add("is-disabled");
      el.addEventListener("click", (e) => e.preventDefault());
    }
  });
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

    if (!SITE_CONFIG.appointmentEndpoint) {
      msg.classList.add("err");
      msg.textContent = "ارتباط با سرور برقرار نشد. لطفاً بعداً دوباره تلاش کنید.";
      return;
    }

    submit.disabled = true;
    submit.dataset.label = submit.textContent;
    submit.textContent = "در حال ارسال...";
    try {
      const res = await fetch(SITE_CONFIG.appointmentEndpoint, {
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
function initArticle() {
  const art = $("#seo-article");
  const btn = $("#seo-toggle");
  if (!art || !btn) return;
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
    const res = await fetch("asap.json", { cache: "no-store" });
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
    const url = `images/partners/${name}`;
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

document.addEventListener("DOMContentLoaded", () => {
  Theme.init();
  if (isQrPage) {
    initQr();
    hideLoader();
    return;
  }
  initNav();
  renderFaq();
  bindExternalLinks();
  initForm();
  initArticle();
  Lightbox.init();
  initCursor();
  hideLoader();
  initSlogan();
  initVideos();
  initPosts();
  initPartners();
});

window.SITE_CONFIG = SITE_CONFIG;
