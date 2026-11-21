(() => {
  "use strict";
  const csrf = document.body.dataset.csrf;

  async function sha256hex(str) {
    const buf = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(str));
    return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, "0")).join("");
  }

  // Odcisk przeglądarki – nie identyfikuje osoby, pozwala wykryć wiele kont z jednego urządzenia.
  let fpCache = null;
  async function fingerprint() {
    if (fpCache) return fpCache;
    const parts = [
      navigator.userAgent, navigator.language, (navigator.languages || []).join(","),
      Intl.DateTimeFormat().resolvedOptions().timeZone, screen.width + "x" + screen.height + "x" + screen.colorDepth,
      devicePixelRatio, navigator.hardwareConcurrency, navigator.deviceMemory, navigator.platform,
      navigator.maxTouchPoints,
    ];
    try {
      const c = document.createElement("canvas");
      c.width = 220; c.height = 40;
      const x = c.getContext("2d");
      x.textBaseline = "top"; x.font = "16px Arial";
      x.fillStyle = "#f60"; x.fillRect(100, 1, 62, 20);
      x.fillStyle = "#069"; x.fillText("Radar Innowacji ąęść 🛰", 2, 15);
      x.fillStyle = "rgba(102,204,0,0.7)"; x.fillText("Radar Innowacji ąęść 🛰", 4, 17);
      parts.push(c.toDataURL());
    } catch (_) { parts.push("nocanvas"); }
    try {
      const gl = document.createElement("canvas").getContext("webgl");
      const dbg = gl && gl.getExtension("WEBGL_debug_renderer_info");
      if (dbg) parts.push(gl.getParameter(dbg.UNMASKED_RENDERER_WEBGL));
    } catch (_) {}
    fpCache = await sha256hex(parts.join("|"));
    return fpCache;
  }

  // Uzupełnij ukryte pola fp (strona potwierdzenia logowania)
  document.querySelectorAll(".fp-field").forEach(async (el) => { el.value = await fingerprint(); });

  function solve(challenge, difficulty, onProgress) {
    return new Promise((resolve, reject) => {
      const w = new Worker("/static/pow-worker.js");
      w.onmessage = (e) => {
        if (e.data.nonce !== undefined) { w.terminate(); resolve(e.data.nonce); }
        else if (onProgress) onProgress(e.data.progress);
      };
      w.onerror = (err) => { w.terminate(); reject(err); };
      w.postMessage({ challenge, difficulty });
    });
  }

  function toast(msg, kind) {
    const t = document.createElement("div");
    t.className = "toast toast-" + (kind || "ok");
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.classList.add("show"), 10);
    setTimeout(() => { t.classList.remove("show"); setTimeout(() => t.remove(), 300); }, 4000);
  }

  async function vote(btn) {
    const pid = btn.dataset.project;
    const label = btn.textContent;
    btn.disabled = true;
    btn.classList.add("busy");
    btn.textContent = "Wysyłanie…";
    try {
      const fpP = fingerprint();
      const cr = await fetch("/api/challenge?project_id=" + encodeURIComponent(pid), { credentials: "same-origin" });
      const c = await cr.json();
      if (!cr.ok) throw new Error(c.error || "Błąd");
      const expected = Math.pow(2, c.difficulty);
      const nonce = await solve(c.challenge, c.difficulty, (n) => {
        btn.textContent = "Wysyłanie " + Math.min(99, Math.round((1 - Math.exp(-n / expected)) * 100)) + "%";
      });
      const r = await fetch("/api/vote", {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf },
        body: JSON.stringify({
          project_id: Number(pid), challenge: c.challenge, nonce, fp: await fpP, wd: !!navigator.webdriver,
        }),
      });
      const d = await r.json();
      if (!r.ok) throw new Error(d.error || "Błąd");
      btn.textContent = "✓ Twój głos";
      btn.classList.remove("busy");
      btn.classList.add("btn-voted");
      toast(d.message, "ok");
      if (d.votes_left === 0) {
        document.querySelectorAll(".vote-btn:not(.btn-voted)").forEach((b) => {
          b.disabled = true; b.textContent = "Brak wolnych głosów";
        });
      }
    } catch (err) {
      btn.disabled = false;
      btn.classList.remove("busy");
      btn.textContent = label;
      toast(err.message || "Nie udało się oddać głosu.", "error");
    }
  }

  // Podpowiedź podobnych innowacji w trakcie wypełniania formularza (ograniczanie dublowania prac)
  const simBox = document.getElementById("similar-box");
  if (simBox) {
    let timer = null, last = "";
    const check = async () => {
      const text = [...document.querySelectorAll(".sim-src")].map((el) => el.value).join(" ").trim();
      if (text.length < 25 || text === last) return;
      last = text;
      try {
        const r = await fetch("/api/podobne?text=" + encodeURIComponent(text.slice(0, 3000)), { credentials: "same-origin" });
        const d = await r.json();
        const ul = simBox.querySelector("ul");
        ul.textContent = "";
        (d.items || []).forEach((it) => {
          const li = document.createElement("li");
          const a = document.createElement("a");
          a.href = "/projekt/" + it.id; a.target = "_blank"; a.textContent = it.title;
          li.append(a, document.createTextNode(" – " + it.institution + " (podobieństwo " + it.score + "%)"));
          ul.appendChild(li);
        });
        simBox.hidden = !(d.items && d.items.length);
      } catch (_) {}
    };
    document.querySelectorAll(".sim-src").forEach((el) => {
      el.addEventListener("input", () => { clearTimeout(timer); timer = setTimeout(check, 900); });
      el.addEventListener("blur", check);
    });
  }

  // Potwierdzenie przed usunięciem
  document.addEventListener("submit", (e) => {
    const msg = e.target.dataset && e.target.dataset.confirm;
    if (msg && !window.confirm(msg)) e.preventDefault();
  });

  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".vote-btn");
    if (btn && !btn.disabled) { e.preventDefault(); vote(btn); }
  });
})();
