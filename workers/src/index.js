/* Cloudflare Worker — فرم رزرو مشاوره + پنل ادمین
 * D1 binding: DB
 * Env: ADMIN_TOKEN (secret)
 */

const CORS = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET,POST,PATCH,DELETE,OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type,X-Admin-Password",
};

const json = (data, status = 200, extra = {}) =>
  new Response(JSON.stringify(data), {
    status,
    headers: { "Content-Type": "application/json; charset=utf-8", ...CORS, ...extra },
  });

function checkAdmin(request, env) {
  const pass = request.headers.get("X-Admin-Password") || "";
  return pass && pass === env.ADMIN_TOKEN;
}

function clean(str, max = 500) {
  return String(str ?? "").trim().slice(0, max);
}

async function handleCreateAppointment(request, env) {
  let body;
  try {
    body = await request.json();
  } catch {
    return json({ ok: false, error: "invalid_json" }, 400);
  }

  const name = clean(body.name, 120);
  const phone = clean(body.phone, 30);
  const goal = clean(body.goal, 60);
  const message = clean(body.message, 2000);
  const age = parseInt(body.age, 10);
  const height = clean(body.height, 20);

  if (!name || !phone) {
    return json({ ok: false, error: "name_and_phone_required" }, 400);
  }
  if (!/^[+\d][\d\s-]{6,}$/.test(phone)) {
    return json({ ok: false, error: "invalid_phone" }, 400);
  }

  // نرخ‌گذاری ساده ضد اسپم: حداکثر ۱۰ ثبت در دقیقه از یک IP
  const ip = request.headers.get("CF-Connecting-IP") || "unknown";
  const recent = await env.DB
    .prepare("SELECT COUNT(*) AS c FROM appointments WHERE user_agent = ? AND created_at > datetime('now','-1 minute')")
    .bind(ip)
    .first();
  if (recent && recent.c >= 10) {
    return json({ ok: false, error: "too_many_requests" }, 429);
  }

  await env.DB
    .prepare(
      "INSERT INTO appointments (name, age, height, goal, phone, message, user_agent) VALUES (?,?,?,?,?,?,?)"
    )
    .bind(name, Number.isFinite(age) ? age : null, height || null, goal || null, phone, message || null, ip)
    .run();

  return json({ ok: true });
}

async function handleAdminList(env) {
  const { results } = await env.DB
    .prepare("SELECT id, name, age, height, goal, phone, message, status, created_at FROM appointments ORDER BY id DESC LIMIT 500")
    .all();
  return json({ ok: true, appointments: results });
}

async function handleAdminUpdate(request, env, id) {
  const body = await request.json().catch(() => ({}));
  const status = ["new", "contacted", "done"].includes(body.status) ? body.status : null;
  if (!status) return json({ ok: false, error: "invalid_status" }, 400);
  const r = await env.DB.prepare("UPDATE appointments SET status = ? WHERE id = ?").bind(status, id).run();
  return json({ ok: true, changed: r.meta.changes });
}

async function handleAdminDelete(env, id) {
  const r = await env.DB.prepare("DELETE FROM appointments WHERE id = ?").bind(id).run();
  return json({ ok: true, deleted: r.meta.changes });
}

export default {
  async fetch(request, env) {
    try {
    const url = new URL(request.url);
    const path = url.pathname.replace(/\/+$/, "") || "/";

    if (request.method === "OPTIONS") return new Response(null, { status: 204, headers: CORS });

    // ---- فرم عمومی ----
    if (path === "/api/appointments" && request.method === "POST") {
      return handleCreateAppointment(request, env);
    }

    // ---- پنل ادمین ----
    if (path === "/admin" || path === "/admin/") {
      return new Response(ADMIN_HTML, { headers: { "Content-Type": "text/html; charset=utf-8" } });
    }

    if (path.startsWith("/api/admin")) {
      if (!checkAdmin(request, env)) return json({ ok: false, error: "unauthorized" }, 401);

      const m = path.match(/^\/api\/admin\/appointments(?:\/(\d+))?$/);
      if (!m) return json({ ok: false, error: "not_found" }, 404);

      if (request.method === "GET" && !m[1]) return handleAdminList(env);
      if (request.method === "PATCH" && m[1]) return handleAdminUpdate(request, env, Number(m[1]));
      if (request.method === "DELETE" && m[1]) return handleAdminDelete(env, Number(m[1]));
      return json({ ok: false, error: "method_not_allowed" }, 405);
    }

    return json({ ok: false, error: "not_found" }, 404);
    } catch (err) {
      return new Response("ERR: " + (err && (err.stack || err.message || String(err))), { status: 500 });
    }
  },
};

/* ---------- HTML پنل ادمین ---------- */
const ADMIN_HTML = `<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>پنل مدیریت رزروها</title>
<style>
  :root { --bg:#0e141d; --card:#161e2c; --line:rgba(255,255,255,.08); --text:#f6f3ee; --muted:#b7c2d0; --pink:#f5b7c1; --blue:#b0c4de; }
  * { box-sizing:border-box; margin:0; }
  body { font-family:Vazirmatn,system-ui,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; padding:24px; }
  .wrap { max-width:1000px; margin:0 auto; }
  h1 { font-size:20px; margin-bottom:4px; }
  .sub { color:var(--muted); font-size:13px; margin-bottom:20px; }
  .card { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:20px; }
  input[type=password] { width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--line); background:var(--bg); color:var(--text); font:inherit; }
  button { font:inherit; cursor:pointer; border:none; border-radius:10px; padding:10px 18px; background:var(--pink); color:#1b2433; font-weight:600; }
  button.ghost { background:transparent; border:1px solid var(--line); color:var(--muted); padding:6px 12px; font-size:12px; }
  button.danger { background:#e26d6d; color:#fff; }
  .row { display:flex; gap:10px; align-items:center; }
  table { width:100%; border-collapse:collapse; font-size:13px; }
  th, td { text-align:right; padding:10px 8px; border-bottom:1px solid var(--line); vertical-align:top; }
  th { color:var(--muted); font-weight:500; font-size:12px; }
  .badge { display:inline-block; padding:2px 10px; border-radius:99px; font-size:11px; }
  .badge.new { background:rgba(245,183,193,.15); color:var(--pink); }
  .badge.contacted { background:rgba(176,196,222,.15); color:var(--blue); }
  .badge.done { background:rgba(120,200,140,.15); color:#8fd6a0; }
  .empty { color:var(--muted); text-align:center; padding:30px; }
  #login { max-width:360px; margin:12vh auto 0; }
  .err { color:#e26d6d; font-size:12px; margin-top:8px; }
  .head { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
  a.tel { color:var(--blue); text-decoration:none; direction:ltr; display:inline-block; }
  select { font:inherit; background:var(--bg); color:var(--text); border:1px solid var(--line); border-radius:8px; padding:4px 8px; }
</style>
</head>
<body>
<div id="login" class="card" hidden>
  <h1>ورود به پنل مدیریت</h1>
  <p class="sub">رمز عبور ادمین را وارد کنید.</p>
  <input type="password" id="pass" placeholder="رمز عبور">
  <div class="err" id="login-err" hidden>رمز اشتباه است.</div>
  <button style="width:100%;margin-top:14px" onclick="login()">ورود</button>
</div>

<div id="panel" class="wrap" hidden>
  <div class="head">
    <div>
      <h1>درخواست‌های رزرو مشاوره</h1>
      <p class="sub" id="count"></p>
    </div>
    <div class="row">
      <button class="ghost" onclick="load()">بازخوانی</button>
      <button class="ghost" onclick="logout()">خروج</button>
    </div>
  </div>
  <div class="card">
    <div id="table-wrap"><div class="empty">در حال بارگذاری...</div></div>
  </div>
</div>

<script>
const KEY = "admin_pass";
function authHeaders() { return { "X-Admin-Password": sessionStorage.getItem(KEY) || "" }; }

async function login() {
  const p = document.getElementById("pass").value;
  const r = await fetch("/api/admin/appointments", { headers: { "X-Admin-Password": p } });
  if (r.status === 401) {
    document.getElementById("login-err").hidden = false;
    return;
  }
  sessionStorage.setItem(KEY, p);
  showPanel();
}
function logout() { sessionStorage.removeItem(KEY); location.reload(); }

async function showPanel() {
  document.getElementById("login").hidden = true;
  document.getElementById("panel").hidden = false;
  await load();
}

async function load() {
  const r = await fetch("/api/admin/appointments", { headers: authHeaders() });
  if (r.status === 401) { logout(); return; }
  const data = await r.json();
  const list = data.appointments || [];
  document.getElementById("count").textContent = list.length + " درخواست ثبت‌شده";
  const wrap = document.getElementById("table-wrap");
  if (!list.length) { wrap.innerHTML = '<div class="empty">هنوز درخواستی ثبت نشده است.</div>'; return; }
  wrap.innerHTML = '<table><thead><tr><th>نام</th><th>سن / قد</th><th>هدف</th><th>تلفن</th><th>پیام</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead><tbody>' +
    list.map(a => {
      const d = a.created_at ? new Date(a.created_at + "Z").toLocaleString("fa-IR") : "";
      return "<tr>" +
        "<td><b>" + esc(a.name) + "</b></td>" +
        "<td>" + (a.age ?? "-") + " / " + esc(a.height ?? "-") + "</td>" +
        "<td>" + esc(a.goal ?? "-") + "</td>" +
        '<td><a class="tel" href="tel:' + esc(a.phone) + '">' + esc(a.phone) + "</a></td>" +
        "<td style='max-width:220px'>" + esc(a.message ?? "") + "</td>" +
        '<td><select onchange="setStatus(' + a.id + ',this.value)">' +
          ["new","contacted","done"].map(s => '<option value="' + s + '"' + (a.status===s?" selected":"") + ">" + ({new:"جدید",contacted:"تماس گرفته شد",done:"تمام‌شده"})[s] + "</option>").join("") +
        "</select></td>" +
        "<td>" + d + "</td>" +
        '<td><button class="danger" onclick="del(' + a.id + ')">حذف</button></td>' +
      "</tr>";
    }).join("") + "</tbody></table>";
}

async function setStatus(id, status) {
  await fetch("/api/admin/appointments/" + id, { method: "PATCH", headers: { ...authHeaders(), "Content-Type": "application/json" }, body: JSON.stringify({ status }) });
}
async function del(id) {
  if (!confirm("این درخواست حذف شود؟")) return;
  await fetch("/api/admin/appointments/" + id, { method: "DELETE", headers: authHeaders() });
  load();
}
function esc(s) { return String(s ?? "").replace(/[&<>"]/g, c => ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;" })[c]); }

document.getElementById("pass").addEventListener("keydown", e => { if (e.key === "Enter") login(); });
if (sessionStorage.getItem(KEY)) showPanel(); else document.getElementById("login").hidden = false;
</script>
</body>
</html>`;
