/* پنل مدیریت — Rich text editor (vanilla, no dependencies)
 * <div class="rte" data-editor data-target="#hiddenInput">
 *   <div class="rte-toolbar">…</div>  (rendered automatically if missing)
 *   <div class="rte-area" contenteditable="true"></div>
 * </div>
 */
(function () {
  "use strict";

  var ICONS = {
    p: "text_fields", h2: "format_h1", h3: "format_h2", h4: "format_h3",
    bold: "format_bold", italic: "format_italic", underline: "format_underlined",
    strike: "strikethrough_s", ul: "format_list_bulleted", ol: "format_list_numbered",
    quote: "format_quote", link: "link", unlink: "link_off",
    clean: "mop", undo: "undo", redo: "redo", source: "code"
  };

  var GROUPS = [
    ["p", "h2", "h3", "h4"],
    ["bold", "italic", "underline", "strike"],
    ["ul", "ol", "quote"],
    ["link", "unlink"],
    ["clean", "undo", "redo", "source"]
  ];

  function exec(area, command, value) {
    area.focus();
    document.execCommand(command, false, value || null);
    sync(area);
  }

  function sync(area) {
    var wrap = area.closest("[data-editor]");
    var target = wrap ? document.querySelector(wrap.dataset.target) : null;
    var html = area.innerHTML.replace(/^\s*<br\s*\/?>\s*$/, "");
    if (target) target.value = html;
    var empty = !area.textContent.trim() && !area.querySelector("img");
    area.dataset.empty = empty ? "1" : "0";
    var counter = wrap.querySelector(".rte-count");
    if (counter) counter.textContent = area.textContent.trim().length + " کاراکتر";
  }

  function queryState(area) {
    var wrap = area.closest("[data-editor]");
    if (!wrap) return;
    wrap.querySelectorAll(".rte-btn[data-cmd]").forEach(function (btn) {
      var cmd = btn.dataset.cmd;
      try {
        var on = document.queryCommandState(cmd);
        btn.classList.toggle("is-on", !!on);
      } catch (e) { /* some commands are not stateful */ }
    });
  }

  function buildToolbar(area) {
    var wrap = area.closest("[data-editor]");
    var bar = document.createElement("div");
    bar.className = "rte-toolbar";
    GROUPS.forEach(function (group, gi) {
      if (gi > 0) {
        var sep = document.createElement("span");
        sep.className = "rte-sep";
        bar.appendChild(sep);
      }
      group.forEach(function (cmd) {
        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "rte-btn";
        btn.dataset.cmd = cmd;
        btn.title = cmd;
        btn.setAttribute("aria-label", cmd);
        if (cmd === "source" || cmd === "p" || cmd === "h2" || cmd === "h3" || cmd === "h4") {
          btn.classList.add("rte-btn-text");
          if (cmd === "source") { btn.textContent = "HTML"; btn.classList.remove("rte-btn-text"); btn.style.fontSize = "12px"; }
          else btn.textContent = cmd.toUpperCase();
        } else {
          btn.textContent = ICONS[cmd] || cmd;
        }
        bar.appendChild(btn);
      });
    });
    wrap.insertBefore(bar, area);
    return bar;
  }

  function cleanPasted(area, e) {
    e.preventDefault();
    var text = (e.clipboardData || window.clipboardData).getData("text/html") ||
               (e.clipboardData || window.clipboardData).getData("text/plain");
    var allowed = /<(h2|h3|h4|p|ul|ol|li|strong|b|em|i|u|br|blockquote|a)\b[^>]*>/gi;
    var html = String(text);
    // strip scripts/styles/comments first
    html = html.replace(/<script[\s\S]*?<\/script>/gi, "")
               .replace(/<style[\s\S]*?<\/style>/gi, "")
               .replace(/<!--[\s\S]*?-->/g, "");
    var keep = [];
    (html.match(new RegExp("<(?:h2|h3|h4|p|ul|ol|li|strong|b|em|i|u|br|blockquote|a)\\b[^>]*>[\\s\\S]*?</(?:h2|h3|h4|p|ul|ol|li|strong|b|em|i|u|blockquote|a)>|<(?:br|img)[^>]*>", "gi")) || [])
      .forEach(function (chunk) { keep.push(chunk); });
    if (!keep.length) {
      document.execCommand("insertText", false, (e.clipboardData || window.clipboardData).getData("text/plain"));
      return;
    }
    var cleaned = keep.join("").replace(allowed, function (m) {
      return m.replace(/\s(?:class|style|id|on\w+)="[^"]*"/gi, "");
    }).replace(/<a(?![^>]*href=)[^>]*>/gi, "<p>");
    document.execCommand("insertHTML", false, cleaned);
    sync(area);
  }

  document.querySelectorAll("[data-editor]").forEach(function (wrap) {
    var area = wrap.querySelector(".rte-area");
    if (!area) return;
    var target = document.querySelector(wrap.dataset.target);

    buildToolbar(area);

    if (target && target.value.trim()) {
      area.innerHTML = target.value;
    }
    if (target) {
      var foot = document.createElement("div");
      foot.className = "rte-foot";
      foot.innerHTML = '<span class="rte-count"></span><span style="flex:1"></span>' +
        '<span>HTML نهایی در فیلد زیر ذخیره می‌شود</span>';
      wrap.appendChild(foot);
    }

    area.addEventListener("input", function () { sync(area); queryState(area); });
    area.addEventListener("keyup", function () { queryState(area); });
    area.addEventListener("mouseup", function () { queryState(area); });
    area.addEventListener("paste", function (e) { cleanPasted(area, e); });
    area.addEventListener("keydown", function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") e.preventDefault();
    });
    sync(area);

    var sourceMode = false;
    wrap.addEventListener("click", function (e) {
      var btn = e.target.closest(".rte-btn");
      if (!btn) return;
      var cmd = btn.dataset.cmd;
      if (cmd === "source") {
        sourceMode = !sourceMode;
        if (sourceMode) {
          var src = document.createElement("textarea");
          src.className = "rte-src";
          src.value = area.innerHTML;
          area.style.display = "none";
          area.after(src);
        } else {
          var src2 = wrap.querySelector(".rte-src");
          if (src2) {
            area.innerHTML = src2.value;
            src2.remove();
          }
          area.style.display = "";
          sync(area);
        }
        btn.classList.toggle("is-on", sourceMode);
        return;
      }
      if (sourceMode) return;

      if (cmd === "link") {
        var url = prompt("آدرس لینک (https://…):", "https://");
        if (url && /^https?:\/\//i.test(url)) exec(area, "createLink", url);
        return;
      }
      if (cmd === "unlink") { exec(area, "unlink"); return; }
      if (cmd === "clean") { exec(area, "removeFormat"); return; }
      if (cmd === "undo") { exec(area, "undo"); return; }
      if (cmd === "redo") { exec(area, "redo"); return; }
      if (cmd === "p") { exec(area, "formatBlock", "P"); return; }
      if (cmd === "h2") { exec(area, "formatBlock", "H2"); return; }
      if (cmd === "h3") { exec(area, "formatBlock", "H3"); return; }
      if (cmd === "h4") { exec(area, "formatBlock", "H4"); return; }
      if (cmd === "quote") { exec(area, "formatBlock", "BLOCKQUOTE"); return; }
      // boolean styles
      exec(area, cmd === "bold" ? "bold" : cmd === "italic" ? "italic" : cmd === "underline" ? "underline" : cmd === "strike" ? "strikeThrough" : cmd === "ul" ? "insertUnorderedList" : "insertOrderedList");
    });
  });
})();
