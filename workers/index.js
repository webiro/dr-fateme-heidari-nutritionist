/**
 * Cloudflare Worker — نوبت‌دهی و داشبورد ادمین
 * Bindings: DB (D1)
 * Secrets: ADMIN_PASS, AUTH_SECRET
 * Vars: ALLOWED_ORIGINS, ADMIN_USER
 */

const encoder = new TextEncoder();

function parseOrigins(env) {
  const raw = env.ALLOWED_ORIGINS || "";
  const list = raw.split(",").map((s) => s.trim()).filter(Boolean);
  list.push("http://127.0.0.1", "http://localhost", "http://127.0.0.1:5500", "http://localhost:5500");
  return list;
}

function isAllowedOrigin(origin, env) {
  if (!origin) return false;
  try {
    const u = new URL(origin);
    if (u.hostname === "127.0.0.1" || u.hostname === "localhost") return true;
    return parseOrigins(env).some((allowed) => origin === allowed || origin.startsWith(allowed));
  } catch {
    return false;
  }
}

function corsHeaders(origin, env) {
  const allow = isAllowedOrigin(origin, env) ? origin : "null";
  return {
    "Access-Control-Allow-Origin": allow,
    "Access-Control-Allow-Methods": "GET,POST,DELETE,OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type, Authorization",
    "Access-Control-Max-Age": "86400",
    "Vary": "Origin"
  };
}

function json(data, status, origin, env) {
  return new Response(JSON.stringify(data), {
    status,
    headers: { "Content-Type": "application/json; charset=utf-8", ...corsHeaders(origin, env) }
  });
}

async function sha256(text) {
  const buf = await crypto.subtle.digest("SHA-256", encoder.encode(text));
  return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, "0")).join("");
}

async function signToken(payload, secret) {
  const body = btoa(unescape(encodeURIComponent(JSON.stringify(payload))));
  const sig = await sha256(`${body}.${secret}`);
  return `${body}.${sig}`;
}

async function verifyToken(token, secret) {
  if (!token) return null;
  const [body, sig] = token.split(".");
  if (!body || !sig) return null;
  const expected = await sha256(`${body}.${secret}`);
  if (expected !== sig) return null;
  try {
    const data = JSON.parse(decodeURIComponent(escape(atob(body))));
    if (data.exp && Date.now() > data.exp) return null;
    return data;
  } catch {
    return null;
  }
}

function bearer(req) {
  const h = req.headers.get("Authorization") || "";
  return h.startsWith("Bearer ") ? h.slice(7) : "";
}

export default {
  async fetch(request, env) {
    const origin = request.headers.get("Origin") || "";
    const url = new URL(request.url);

    if (request.method === "OPTIONS") {
      return new Response(null, { status: 204, headers: corsHeaders(origin, env) });
    }

    if (request.method !== "GET" && !isAllowedOrigin(origin, env) && origin) {
      return json({ ok: false, error: "origin_denied" }, 403, origin, env);
    }

    try {
      if (url.pathname === "/api/login" && request.method === "POST") {
        const body = await request.json().catch(() => ({}));
        const user = String(body.username || "");
        const pass = String(body.password || "");
        if (user === (env.ADMIN_USER || "admin") && pass === (env.ADMIN_PASS || "")) {
          const token = await signToken({ u: user, exp: Date.now() + 1000 * 60 * 60 * 12 }, env.AUTH_SECRET || "change-me");
          return json({ ok: true, token }, 200, origin, env);
        }
        return json({ ok: false, error: "invalid_credentials" }, 401, origin, env);
      }

      if (url.pathname === "/api/appointments" && request.method === "POST") {
        const body = await request.json().catch(() => ({}));
        if (!body.name || !body.phone) return json({ ok: false, error: "invalid" }, 400, origin, env);
        await env.DB.prepare(
          "INSERT INTO appointments (name, age, height, goal, phone, message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)"
        ).bind(
          String(body.name).slice(0, 120),
          String(body.age || "").slice(0, 20),
          String(body.height || "").slice(0, 20),
          String(body.goal || "").slice(0, 80),
          String(body.phone).slice(0, 40),
          String(body.message || "").slice(0, 2000),
          new Date().toISOString()
        ).run();
        return json({ ok: true }, 200, origin, env);
      }

      if (url.pathname === "/api/appointments" && request.method === "GET") {
        const session = await verifyToken(bearer(request), env.AUTH_SECRET || "change-me");
        if (!session) return json({ ok: false, error: "unauthorized" }, 401, origin, env);
        const { results } = await env.DB.prepare("SELECT * FROM appointments ORDER BY id DESC").all();
        return json({ ok: true, items: results || [] }, 200, origin, env);
      }

      const del = url.pathname.match(/^\/api\/appointments\/(\d+)$/);
      if (del && request.method === "DELETE") {
        const session = await verifyToken(bearer(request), env.AUTH_SECRET || "change-me");
        if (!session) return json({ ok: false, error: "unauthorized" }, 401, origin, env);
        await env.DB.prepare("DELETE FROM appointments WHERE id = ?").bind(Number(del[1])).run();
        return json({ ok: true }, 200, origin, env);
      }

      return json({ ok: false, error: "not_found" }, 404, origin, env);
    } catch (err) {
      return json({ ok: false, error: "server_error" }, 500, origin, env);
    }
  }
};
