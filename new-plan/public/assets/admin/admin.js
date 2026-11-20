/* پنل مدیریت — interactions (vanilla, RTL-aware) */
(function () {
  "use strict";

  /* ---------- Theme ---------- */
  var KEY = "fh-admin-theme";
  function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    document.querySelectorAll("[data-admin-theme] .material-symbols-rounded").forEach(function (icon) {
      icon.textContent = theme === "dark" ? "light_mode" : "dark_mode";
    });
  }
  var savedTheme = localStorage.getItem(KEY);
  if (savedTheme) applyTheme(savedTheme);
  else if (window.matchMedia("(prefers-color-scheme: dark)").matches) applyTheme("dark");

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-admin-theme]");
    if (!btn) return;
    var next = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
    localStorage.setItem(KEY, next);
    applyTheme(next);
  });

  /* ---------- Toast auto-dismiss ---------- */
  document.querySelectorAll("[data-toast]").forEach(function (toast) {
    var close = toast.querySelector(".toast-close");
    var kill = function () {
      toast.classList.add("is-hidden");
      setTimeout(function () { toast.remove(); }, 300);
    };
    if (close) close.addEventListener("click", kill);
    setTimeout(kill, 6000);
  });

  /* ---------- Confirm modal ---------- */
  function ensureModal() {
    var scrim = document.getElementById("confirm-modal");
    if (scrim) return scrim;
    scrim = document.createElement("div");
    scrim.className = "modal-scrim";
    scrim.id = "confirm-modal";
    scrim.innerHTML =
      '<div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">' +
      '<h3 id="confirm-title">تأیید عملیات</h3>' +
      '<p id="confirm-text"></p>' +
      '<div class="modal-actions">' +
      '<button type="button" class="btn btn-danger btn-sm" id="confirm-yes">بله، انجام بده</button>' +
      '<button type="button" class="btn btn-ghost btn-sm" id="confirm-no">انصراف</button>' +
      "</div></div>";
    document.body.appendChild(scrim);
    return scrim;
  }

  var pendingForm = null;
  document.addEventListener("submit", function (e) {
    var form = e.target;
    if (!form.matches("[data-confirm]")) return;
    e.preventDefault();
    var scrim = ensureModal();
    scrim.querySelector("#confirm-text").textContent =
      form.getAttribute("data-confirm") || "آیا مطمئن هستید؟";
    pendingForm = form;
    scrim.classList.add("is-open");
    scrim.querySelector("#confirm-yes").focus();
  });

  document.addEventListener("click", function (e) {
    var scrim = document.getElementById("confirm-modal");
    if (!scrim || !scrim.classList.contains("is-open")) return;
    if (e.target === scrim || e.target.id === "confirm-no") {
      scrim.classList.remove("is-open");
      pendingForm = null;
    } else if (e.target.id === "confirm-yes") {
      scrim.classList.remove("is-open");
      if (pendingForm) pendingForm.submit();
      pendingForm = null;
    }
  });
  document.addEventListener("keydown", function (e) {
    var scrim = document.getElementById("confirm-modal");
    if (scrim && scrim.classList.contains("is-open") && e.key === "Escape") {
      scrim.classList.remove("is-open");
      pendingForm = null;
    }
  });

  /* ---------- Icon picker (searchable) ---------- */
  document.querySelectorAll("[data-icon-picker]").forEach(function (picker) {
    var input = picker.querySelector("input[type=hidden]");
    var search = picker.querySelector(".icon-search");
    var count = picker.querySelector(".icon-count");
    var opts = Array.from(picker.querySelectorAll(".icon-opt"));

    function select(opt) {
      input.value = opt.dataset.icon || "";
      opts.forEach(function (o) { o.classList.toggle("is-selected", o === opt); });
      var preview = document.querySelector("[data-icon-preview]");
      if (preview) preview.textContent = input.value || "nutrition";
    }
    opts.forEach(function (opt) {
      opt.addEventListener("click", function () { select(opt); });
    });

    function filter() {
      if (!search) return;
      var q = search.value.trim().toLowerCase();
      var visible = 0;
      opts.forEach(function (o) {
        var hit = !q || (o.dataset.icon || "").indexOf(q) !== -1;
        o.hidden = !hit;
        if (hit) visible++;
      });
      if (count) count.textContent = visible + " آیکون";
    }
    if (search) search.addEventListener("input", filter);
    filter();
  });

  /* ---------- Section preview: click a row → scroll the live iframe ---------- */
  document.querySelectorAll("[data-section-row]").forEach(function (row) {
    row.addEventListener("click", function (e) {
      if (e.target.closest("a, button, label, input, form")) return;
      document.querySelectorAll("[data-section-row]").forEach(function (r) {
        r.classList.toggle("is-current", r === row);
      });
      var frame = document.querySelector("[data-preview-frame]");
      if (frame && row.dataset.sectionId) {
        frame.src = "../index.php#" + row.dataset.sectionId;
      }
    });
  });

  /* ---------- Reorder rows (up/down buttons submit a mini form) ---------- */
  document.querySelectorAll("[data-move]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var form = btn.closest("form");
      if (!form) return;
      // data-move-id → input[name=move_id], data-move-key → input[name=key]
      var mapping = { moveId: "move_id", moveKey: "key" };
      Object.keys(mapping).forEach(function (dk) {
        if (btn.dataset[dk] !== undefined) {
          var input = form.querySelector('input[name="' + mapping[dk] + '"]');
          if (input) input.value = btn.dataset[dk];
        }
      });
      var dir = form.querySelector("input[name=direction]");
      if (dir) dir.value = btn.dataset.move || "";
      form.submit();
    });
  });

  /* ---------- Reel source type switcher ---------- */
  document.querySelectorAll("[data-reel-type]").forEach(function (radio) {
    radio.addEventListener("change", syncReelPanels);
  });
  function syncReelPanels() {
    var checked = document.querySelector("[data-reel-type]:checked");
    if (!checked) return;
    var value = checked.value;
    document.querySelectorAll("[data-reel-panel]").forEach(function (panel) {
      panel.hidden = panel.dataset.reelPanel !== value;
    });
  }
  syncReelPanels();

  /* ---------- Auto-hide honeypot-ish helpers ---------- */
  document.querySelectorAll("[data-disable-when]").forEach(function (el) {
    var source = document.querySelector(el.dataset.disableWhen);
    if (!source) return;
    var sync = function () { el.disabled = !source.checked; };
    source.addEventListener("change", sync);
    sync();
  });

  /* ---------- Unsaved indicator ---------- */
  document.querySelectorAll("form[data-dirty]").forEach(function (form) {
    form.addEventListener("input", function () {
      form.dataset.dirtySeen = "1";
    });
    form.addEventListener("submit", function () {
      form.dataset.dirtySeen = "";
    });
    window.addEventListener("beforeunload", function (e) {
      if (form.dataset.dirtySeen === "1") {
        e.preventDefault();
        e.returnValue = "";
      }
    });
  });

  /* ---------- Sidebar: close after navigating on mobile ---------- */
  document.querySelectorAll(".side-link").forEach(function (link) {
    link.addEventListener("click", function () {
      var ctl = document.getElementById("sidebar-toggle");
      if (ctl && ctl.checked) ctl.checked = false;
    });
  });
})();
