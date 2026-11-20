// Shared helpers for the read-only scrape of https://www.corsidiformazionecm.it/
// Never submits forms, never logs in: only GET requests and public file downloads.
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

export const BASE = 'https://www.corsidiformazionecm.it';
export const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
export const DATA = path.join(ROOT, 'data', 'scrape');
export const ASSETS = path.join(DATA, 'assets');
export const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

export const rel = (p) => path.relative(ROOT, p).split(path.sep).join('/');
export const ensureDir = (d) => fs.mkdirSync(d, { recursive: true });
export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
export const sha1 = (buf) => crypto.createHash('sha1').update(buf).digest('hex');
export const writeJSON = (file, obj) => { ensureDir(path.dirname(file)); fs.writeFileSync(file, JSON.stringify(obj, null, 2) + '\n', 'utf8'); };
export const readJSON = (file, dflt = null) => (fs.existsSync(file) ? JSON.parse(fs.readFileSync(file, 'utf8')) : dflt);

/** lowercase ascii slug: no accents/apostrophes, hyphen separated */
export function cleanSlug(s) {
  let x = s;
  try { x = decodeURIComponent(s); } catch { /* keep */ }
  return x
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/['’‘`]/g, '-')
    .replace(/&/g, '-')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
}

export async function launch() {
  return chromium.launch();
}

/** Context with JS disabled: DOM == server HTML (no carousel clones, no CF email decoding by page JS). */
export async function noJsContext(browser) {
  return browser.newContext({ javaScriptEnabled: false, locale: 'it-IT', userAgent: UA, viewport: { width: 1440, height: 900 } });
}
export async function jsContext(browser, extra = {}) {
  return browser.newContext({ locale: 'it-IT', userAgent: UA, viewport: { width: 1440, height: 900 }, ...extra });
}

export async function scrollAll(page) {
  await page.evaluate(async () => {
    let last = -1;
    for (let i = 0; i < 80; i++) {
      const h = document.documentElement.scrollHeight;
      for (let y = 0; y < h; y += 500) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 90)); }
      if (h === last) break;
      last = h;
    }
    window.scrollTo(0, 0);
  });
}

/** Dismiss iubenda cookie banner choosing "Rifiuta" (reject) only. */
export async function rejectCookies(page) {
  for (const sel of ['.iubenda-cs-reject-btn', 'button:has-text("Rifiuta")', 'button:has-text("Reject")', 'button:has-text("Solo necessari")']) {
    try {
      const el = page.locator(sel).first();
      if (await el.isVisible({ timeout: 1500 })) { await el.click({ timeout: 3000 }); await page.waitForTimeout(600); return sel; }
    } catch { /* not present */ }
  }
  return null;
}

export async function gotoIdle(page, url) {
  const resp = await page.goto(url, { waitUntil: 'networkidle', timeout: 120000 }).catch(async (e) => {
    // networkidle may never settle on pages with long-polling; fall back to load
    return page.goto(url, { waitUntil: 'load', timeout: 120000 });
  });
  return resp;
}

/** fetch with retries, returns {status, headers, buf, url} */
export async function fetchBuf(url, opts = {}) {
  let lastErr;
  for (let i = 0; i < 4; i++) {
    try {
      const r = await fetch(url, { redirect: 'follow', ...opts, headers: { 'user-agent': UA, 'accept-language': 'it-IT,it;q=0.9', ...(opts.headers || {}) } });
      const buf = Buffer.from(await r.arrayBuffer());
      return { status: r.status, headers: Object.fromEntries(r.headers), buf, url: r.url };
    } catch (e) { lastErr = e; await sleep(1500 * (i + 1)); }
  }
  throw lastErr;
}

export function extFromType(ct, url) {
  ct = (ct || '').split(';')[0].trim().toLowerCase();
  const map = { 'image/png': 'png', 'image/jpeg': 'jpg', 'image/jpg': 'jpg', 'image/webp': 'webp', 'image/gif': 'gif', 'image/svg+xml': 'svg', 'image/x-icon': 'ico', 'image/vnd.microsoft.icon': 'ico', 'application/pdf': 'pdf', 'image/avif': 'avif' };
  if (map[ct]) return map[ct];
  const m = (url || '').split('?')[0].match(/\.([a-z0-9]{2,5})$/i);
  return m ? m[1].toLowerCase().replace('jpeg', 'jpg') : 'bin';
}

/** sniff image type from magic bytes */
export function sniffExt(buf) {
  if (buf.slice(0, 8).equals(Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]))) return 'png';
  if (buf[0] === 0xff && buf[1] === 0xd8) return 'jpg';
  if (buf.slice(0, 4).toString() === 'RIFF' && buf.slice(8, 12).toString() === 'WEBP') return 'webp';
  if (buf.slice(0, 3).toString() === 'GIF') return 'gif';
  if (buf.slice(0, 4).toString() === '%PDF') return 'pdf';
  if (buf.slice(0, 4).equals(Buffer.from([0, 0, 1, 0]))) return 'ico';
  const head = buf.slice(0, 300).toString('utf8').trimStart();
  if (head.startsWith('<svg') || (head.startsWith('<?xml') && head.includes('<svg'))) return 'svg';
  return null;
}

/** image dimensions for png/jpg/gif/webp (no deps) */
export function imageSize(buf) {
  try {
    const t = sniffExt(buf);
    if (t === 'png') return { width: buf.readUInt32BE(16), height: buf.readUInt32BE(20) };
    if (t === 'gif') return { width: buf.readUInt16LE(6), height: buf.readUInt16LE(8) };
    if (t === 'jpg') {
      let i = 2;
      while (i < buf.length) {
        if (buf[i] !== 0xff) { i++; continue; }
        const marker = buf[i + 1];
        const len = buf.readUInt16BE(i + 2);
        if ([0xc0, 0xc1, 0xc2, 0xc3, 0xc5, 0xc6, 0xc7, 0xc9, 0xca, 0xcb, 0xcd, 0xce, 0xcf].includes(marker)) {
          return { height: buf.readUInt16BE(i + 5), width: buf.readUInt16BE(i + 7) };
        }
        i += 2 + len;
      }
    }
    if (t === 'webp') {
      const chunk = buf.slice(12, 16).toString();
      if (chunk === 'VP8 ') return { width: buf.readUInt16LE(26) & 0x3fff, height: buf.readUInt16LE(28) & 0x3fff };
      if (chunk === 'VP8L') { const b0 = buf[21], b1 = buf[22], b2 = buf[23], b3 = buf[24]; return { width: 1 + (((b1 & 0x3f) << 8) | b0), height: 1 + (((b3 & 0xf) << 10) | (b2 << 2) | ((b1 & 0xc0) >> 6)) }; }
      if (chunk === 'VP8X') return { width: 1 + buf.readUIntLE(24, 3), height: 1 + buf.readUIntLE(27, 3) };
    }
    if (t === 'ico') return { width: buf[6] || 256, height: buf[7] || 256 };
  } catch { /* ignore */ }
  return { width: null, height: null };
}

/** download a URL to a file (ext from sniffing). Returns info object. */
export async function downloadTo(url, fileNoExt, { forceExt } = {}) {
  const r = await fetchBuf(url);
  if (r.status !== 200) return { ok: false, status: r.status, url };
  const ext = forceExt || sniffExt(r.buf) || extFromType(r.headers['content-type'], url);
  const file = `${fileNoExt}.${ext}`;
  ensureDir(path.dirname(file));
  fs.writeFileSync(file, r.buf);
  const dim = imageSize(r.buf);
  return { ok: true, status: 200, url, final_url: r.url, file, rel: rel(file), bytes: r.buf.length, sha1: sha1(r.buf), content_type: r.headers['content-type'], ...dim };
}

/** Strip known resize params from Thinkific/filestack image URLs. Returns candidate list (best first). */
export function imageCandidates(url) {
  const out = [];
  try {
    const u = new URL(url, BASE);
    const noQuery = u.origin + u.pathname;
    if (u.search) out.push(noQuery);
    out.push(u.href);
  } catch { out.push(url); }
  return [...new Set(out)];
}

/** Try all candidates, keep the one with most pixels (ties: largest bytes). */
export async function downloadBestImage(url, fileNoExt) {
  const tries = [];
  for (const c of imageCandidates(url)) {
    try {
      const r = await fetchBuf(c);
      if (r.status !== 200 || !sniffExt(r.buf)) { tries.push({ url: c, status: r.status, ok: false }); continue; }
      tries.push({ url: c, status: 200, ok: true, buf: r.buf, ct: r.headers['content-type'], ...imageSize(r.buf) });
    } catch (e) { tries.push({ url: c, ok: false, error: String(e) }); }
  }
  const good = tries.filter((t) => t.ok);
  if (!good.length) return { ok: false, tries: tries.map(({ buf, ...t }) => t) };
  good.sort((a, b) => ((b.width || 0) * (b.height || 0)) - ((a.width || 0) * (a.height || 0)) || b.buf.length - a.buf.length);
  const best = good[0];
  const ext = sniffExt(best.buf) || extFromType(best.ct, best.url);
  const file = `${fileNoExt}.${ext}`;
  ensureDir(path.dirname(file));
  fs.writeFileSync(file, best.buf);
  return { ok: true, url_best: best.url, file, rel: rel(file), bytes: best.buf.length, sha1: sha1(best.buf), width: best.width, height: best.height, tries: tries.map(({ buf, ...t }) => t) };
}

/** Google Drive id from any drive/docs URL */
export function driveId(href) {
  if (!href) return null;
  const m = href.match(/\/d\/([a-zA-Z0-9_-]{10,})/) || href.match(/[?&]id=([a-zA-Z0-9_-]{10,})/);
  return m ? m[1] : null;
}

/** Download a public Drive file as PDF (handles virus-scan interstitial). */
export async function downloadDrivePdf(id, outFile, { docsType } = {}) {
  const attempts = [];
  const urls = docsType === 'document'
    ? [`https://docs.google.com/document/d/${id}/export?format=pdf`]
    : [`https://drive.google.com/uc?export=download&id=${id}`, `https://drive.usercontent.google.com/download?id=${id}&export=download&confirm=t`];
  for (const u of urls) {
    try {
      let r = await fetchBuf(u);
      attempts.push({ url: u, status: r.status, final_url: r.url, ct: r.headers['content-type'], bytes: r.buf.length });
      if (r.buf.slice(0, 4).toString() !== '%PDF' && /text\/html/.test(r.headers['content-type'] || '')) {
        // interstitial: parse form action + hidden inputs
        const html = r.buf.toString('utf8');
        const action = (html.match(/<form[^>]+id="download-form"[^>]+action="([^"]+)"/) || html.match(/<form[^>]+action="([^"]+)"/) || [])[1];
        if (action) {
          const params = new URLSearchParams();
          for (const m of html.matchAll(/<input[^>]+type="hidden"[^>]*>/g)) {
            const n = (m[0].match(/name="([^"]+)"/) || [])[1]; const v = (m[0].match(/value="([^"]*)"/) || [])[1] || '';
            if (n) params.set(n, v.replace(/&amp;/g, '&'));
          }
          const u2 = action.replace(/&amp;/g, '&') + (action.includes('?') ? '&' : '?') + params.toString();
          r = await fetchBuf(u2);
          attempts.push({ url: u2, status: r.status, final_url: r.url, ct: r.headers['content-type'], bytes: r.buf.length, interstitial: true });
        }
      }
      if (r.status === 200 && r.buf.slice(0, 4).toString() === '%PDF') {
        ensureDir(path.dirname(outFile));
        fs.writeFileSync(outFile, r.buf);
        return { ok: true, file: outFile, rel: rel(outFile), bytes: r.buf.length, sha1: sha1(r.buf), via: attempts[attempts.length - 1].url, attempts };
      }
    } catch (e) { attempts.push({ url: u, error: String(e) }); }
  }
  return { ok: false, attempts };
}

export function pdfPages(file) {
  try {
    const out = execFileSync('pdfinfo', [file], { encoding: 'utf8' });
    const m = out.match(/Pages:\s+(\d+)/);
    return m ? Number(m[1]) : null;
  } catch { return null; }
}
export function pdfPreview(pdf, jpgNoExt) {
  ensureDir(path.dirname(jpgNoExt));
  execFileSync('pdftoppm', ['-jpeg', '-r', '110', '-f', '1', '-l', '1', '-singlefile', pdf, jpgNoExt]);
  return `${jpgNoExt}.jpg`;
}

/* ------------------------------------------------------------------ */
/* Browser-side helpers, installed as window.__S via page.evaluate      */
/* ------------------------------------------------------------------ */
export function installBrowserHelpers() {
  const S = {};
  // Cloudflare email-protection decoder (same algorithm as email-decode.min.js)
  S.cfDecode = (hex) => {
    const k = parseInt(hex.substr(0, 2), 16); let out = '';
    for (let i = 2; i < hex.length; i += 2) out += String.fromCharCode(parseInt(hex.substr(i, 2), 16) ^ k);
    try { return decodeURIComponent(escape(out)); } catch { return out; }
  };
  // Mimic Cloudflare's client-side decoding on the given root (default: document)
  S.decodeCfEmails = (root = document) => {
    let n = 0;
    root.querySelectorAll('a[href*="/cdn-cgi/l/email-protection#"]').forEach((a) => {
      const hex = a.getAttribute('href').split('#')[1];
      a.setAttribute('href', 'mailto:' + S.cfDecode(hex)); n++;
    });
    root.querySelectorAll('.__cf_email__').forEach((el) => {
      const hex = el.getAttribute('data-cfemail');
      if (!hex) return;
      el.parentNode.replaceChild(document.createTextNode(S.cfDecode(hex)), el); n++;
    });
    return n;
  };
  const ALLOWED = new Set(['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'a', 'blockquote']);
  const BLOCK = new Set(['p', 'div', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'table', 'section', 'article', 'header', 'figure']);
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/ /g, '&nbsp;');
  const escAttr = (s) => s.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
  /** sanitize a node's children into semantic HTML string. opts.keepImg keeps <img src alt>. */
  S.clean = (node, opts = {}) => {
    const notes = opts.notes || [];
    const walk = (n) => {
      if (n.nodeType === 3) return esc(n.nodeValue);
      if (n.nodeType !== 1) return '';
      let tag = n.tagName.toLowerCase();
      if (['script', 'style', 'noscript', 'svg', 'form', 'input', 'button', 'iframe'].includes(tag)) { if (tag === 'iframe') notes.push('iframe:' + n.getAttribute('src')); return ''; }
      if (tag === 'i' && /toga-icon|fa-|fa\b/.test(n.className || '')) return '';
      if (tag === 'img') {
        if (opts.keepImg) return `<img src="${escAttr(n.getAttribute('src') || '')}" alt="${escAttr(n.getAttribute('alt') || '')}">`;
        notes.push('img-dropped:' + n.getAttribute('src')); return '';
      }
      if (tag === 'h1') { tag = 'h2'; notes.push('h1->h2'); }
      if (tag === 'h5' || tag === 'h6') { tag = 'h4'; notes.push(tag + '->h4'); }
      const inner = [...n.childNodes].map(walk).join('');
      if (tag === 'br') return '<br>';
      if (tag === 'div' || tag === 'section' || tag === 'article' || tag === 'header' || tag === 'figure') {
        const hasBlock = [...n.children].some((c) => BLOCK.has(c.tagName.toLowerCase()));
        if (hasBlock || !inner.trim()) return inner;
        return `<p>${inner}</p>`;
      }
      if (!ALLOWED.has(tag)) return inner; // unwrap span/font/etc
      if (tag === 'a') {
        const href = n.getAttribute('href');
        return href ? `<a href="${escAttr(href)}">${inner}</a>` : inner;
      }
      if (['strong', 'b', 'em', 'i', 'u'].includes(tag) && !inner.replace(/&nbsp;|\s/g, '')) return inner; // empty inline
      if ((tag === 'p' || tag.startsWith('h') || tag === 'li') && !inner.replace(/<br>|&nbsp;|\s/g, '')) return ''; // empty block
      return `<${tag}>${inner}</${tag}>`;
    };
    let html = [...node.childNodes].map(walk).join('');
    html = html.replace(/\s*(<\/?(p|ul|ol|li|h2|h3|h4|blockquote)>)\s*/g, '$1').trim();
    return html;
  };
  /**
   * Convert "texty" HTML (text + <br> + raw newlines, as used by Thinkific description/FAQ/testimonial fields)
   * to semantic HTML: blank line or 2+ <br> -> new <p>; single <br> -> <br>;
   * single newline -> <br> when the previous line ends a sentence/clause (. ! ? : ; … » " ” ) ]), otherwise it is a
   * soft wrap -> space (the live site renders all source newlines as spaces: white-space:normal).
   */
  S.textyToHtml = (html) => {
    let h = html.replace(/\r\n?/g, '\n').replace(/<br\s*\/?>/gi, '<br>');
    // already block-structured? then just tidy
    if (/<(p|ul|ol|h2|h3|h4|blockquote)>/.test(h)) return h.trim();
    h = h.replace(/[ \t]*<br>[ \t]*/g, '<br>');
    const paras = h.split(/(?:\n[ \t]*){2,}|(?:<br>\s*){2,}/).map((p) => p.replace(/^\s+|\s+$/g, '').replace(/^(<br>\s*)+|(\s*<br>)+$/g, '')).filter((p) => p.replace(/<br>|&nbsp;|\s/g, ''));
    const joinLines = (p) => {
      const lines = p.split(/[ \t]*\n[ \t]*/);
      let out = lines[0];
      for (let i = 1; i < lines.length; i++) {
        if (/<br>$/.test(out) || /^<br>/.test(lines[i])) { out += lines[i]; continue; }
        const prev = out.replace(/(<\/?[a-z][^>]*>)+$/i, '').replace(/\s+$/, '');
        out += (/[.!?:;…»"”)\]]$/.test(prev) ? '<br>' : ' ') + lines[i];
      }
      return out;
    };
    return paras.map((p) => '<p>' + joinLines(p).replace(/[ \t]{2,}/g, ' ') + '</p>').join('');
  };
  /** semantic HTML -> plain text (paragraphs \n\n, br \n, li "- ") */
  S.htmlToText = (html) => {
    const d = document.createElement('div'); d.innerHTML = html;
    const out = [];
    const walk = (n, ctx) => {
      if (n.nodeType === 3) { out.push(n.nodeValue.replace(/[ \t\r\n]+/g, ' ')); return; }
      if (n.nodeType !== 1) return;
      const t = n.tagName.toLowerCase();
      if (t === 'br') { out.push('\n'); return; }
      const block = ['p', 'h2', 'h3', 'h4', 'blockquote', 'ul', 'ol'].includes(t);
      if (block) out.push('\n\n');
      if (t === 'li') out.push('\n- ');
      if (t === 'img') { out.push(`[img ${n.getAttribute('src')}]`); return; }
      n.childNodes.forEach((c) => walk(c, ctx));
      if (block) out.push('\n\n');
    };
    d.childNodes.forEach((c) => walk(c));
    return out.join('').split('\n').map((l) => l.replace(/^[ \t]+|[ \t]+$/g, '')).join('\n').replace(/\n{3,}/g, '\n\n').trim();
  };
  /** text of an element with <br> -> space, whitespace collapsed (for headings/titles) */
  S.flatText = (el) => {
    if (!el) return null;
    const c = el.cloneNode(true);
    c.querySelectorAll('br').forEach((b) => b.replaceWith(' '));
    c.querySelectorAll('i.toga-icon, i.fa').forEach((b) => b.remove());
    return c.textContent.replace(/[ \t\r\n]+/g, ' ').trim();
  };
  /** texty element -> semantic html: inline runs go through textyToHtml, block children handled recursively */
  S.richTexty = (el, opts = {}) => {
    const parts = [];
    let buf = document.createElement('div');
    const flush = () => { if (buf.childNodes.length) { const h = S.textyToHtml(S.clean(buf, opts)); if (h) parts.push(h); buf = document.createElement('div'); } };
    for (const c of [...el.childNodes]) {
      const tag = c.nodeType === 1 ? c.tagName.toLowerCase() : null;
      if (tag === 'summary' || tag === 'script' || tag === 'style') continue;
      if (tag === 'p' || tag === 'div') { flush(); const h = S.richTexty(c, opts); if (h) parts.push(h); }
      else if (['ul', 'ol', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'table'].includes(tag)) { flush(); const w = document.createElement('div'); w.appendChild(c.cloneNode(true)); parts.push(S.clean(w, opts)); }
      else buf.appendChild(c.cloneNode(true));
    }
    flush();
    return parts.join('');
  };
  S.innerTrim = (el) => (el ? el.innerHTML.replace(/^\s+|\s+$/g, '') : null);
  S.topSections = () => [...document.querySelectorAll('[data-section-uid]')].filter((el) => !el.parentElement.closest('[data-section-uid]'));
  S.abs = (href) => { try { return new URL(href, location.href).href; } catch { return href; } };
  window.__S = S;
  return true;
}
