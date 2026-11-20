// Step 3: global site data (header, footer, brand, home, newsletter, contact, legal).
// Output: data/scrape/site.json + assets/{brand,home,legal}
import fs from 'node:fs';
import path from 'node:path';
import {
  BASE, DATA, ASSETS, rel, writeJSON, launch, noJsContext, jsContext, installBrowserHelpers, gotoIdle, scrollAll,
  rejectCookies, downloadBestImage, downloadTo, driveId, downloadDrivePdf, pdfPages, fetchBuf, sha1, ensureDir, imageSize, sniffExt, cleanSlug,
} from './lib.mjs';
import { extractPage } from './sections.mjs';

const browser = await launch();
const ctx = await noJsContext(browser);
const page = await ctx.newPage();

async function loadNoJs(url) {
  const r = await page.goto(url, { waitUntil: 'load', timeout: 120000 });
  await page.evaluate(installBrowserHelpers);
  return r.status();
}

// ---------------------------------------------------------------- header / footer / home (server DOM)
await loadNoJs(BASE + '/');
const chrome = await page.evaluate(() => {
  const S = window.__S;
  S.decodeCfEmails();
  const hdr = document.querySelector('header[data-preview-item="header"]');
  const ftr = document.querySelector('footer[data-preview-item="footer"]');
  const logoImg = hdr.querySelector('.header__logo img');
  return {
    header: {
      logo: logoImg ? { src: logoImg.getAttribute('src'), srcset: logoImg.getAttribute('srcset'), alt: logoImg.getAttribute('alt'), link: logoImg.closest('a') ? logoImg.closest('a').getAttribute('href') : null } : null,
      site_name_sr_only: S.flatText(hdr.querySelector('.header__logo h1')),
      skip_link: S.flatText(hdr.querySelector('#skip-link a')),
      nav: [...hdr.querySelectorAll('.header__nav li')].map((li) => { const a = li.querySelector('a'); return { label_source: S.flatText(a), href: a.getAttribute('href'), target: a.getAttribute('target'), is_sign_in: li.classList.contains('header__nav-sign-in') }; }),
      menu_style: hdr.getAttribute('data-menu-type'),
      mobile_button_label: S.flatText(hdr.querySelector('.header__mobile-button__label')),
    },
    footer: {
      links: [...ftr.querySelectorAll('.footer__nav-site a')].map((a) => ({ label_source: S.flatText(a), href: a.getAttribute('href'), target: a.getAttribute('target') })),
      social: [...ftr.querySelectorAll('.footer__nav-social a')].map((a) => ({ sr_label: S.flatText(a.querySelector('.sr-only')), icon: (a.querySelector('i') || {}).className || null, href: a.getAttribute('href'), target: a.getAttribute('target') })),
      copyright_raw: ftr.querySelector('.footer__copyright') ? ftr.querySelector('.footer__copyright').innerHTML.trim() : null,
      copyright: S.flatText(ftr.querySelector('.footer__copyright')),
      white_label: (() => { const a = ftr.querySelector('.footer__white-label a'); return a ? { href: a.getAttribute('href'), title: S.flatText(a.querySelector('title')) } : null; })(),
    },
    head: {
      title: document.title,
      links: [...document.querySelectorAll('head link')].map((l) => ({ rel: l.getAttribute('rel'), href: l.getAttribute('href'), type: l.getAttribute('type') })).filter((l) => !/stylesheet|preconnect|dns-prefetch|preload/.test(l.rel || '') || /fonts\.googleapis/.test(l.href || '')),
      metas: [...document.querySelectorAll('head meta')].map((m) => ({ name: m.getAttribute('name') || m.getAttribute('property') || m.getAttribute('http-equiv'), content: m.getAttribute('content') })).filter((m) => m.name && !/csrf/.test(m.name)),
      lang: document.documentElement.getAttribute('lang'),
    },
    scripts: [...document.querySelectorAll('script[src]')].map((s) => s.getAttribute('src')),
    inline_script_hints: [...document.querySelectorAll('script:not([src])')].map((s) => s.textContent).filter((t) => /iubenda|gtag|fbq|googletagmanager|analytics|pixel|hotjar|clarity|leadconnector|msgsndr/i.test(t)).map((t) => t.replace(/data:image\/[a-z]+;base64,[A-Za-z0-9+/=]+/g, '(base64 image)').slice(0, 3000)),
  };
});
const home = await page.evaluate(extractPage, {});

// ---------------------------------------------------------------- computed styles (JS on, real rendering)
const jctx = await jsContext(browser);
const jp = await jctx.newPage();
await gotoIdle(jp, BASE + '/');
const cookieBanner = await jp.evaluate(() => {
  const b = document.querySelector('#iubenda-cs-banner');
  return b ? { present: true, text: b.innerText.trim(), buttons: [...b.querySelectorAll('button, a')].map((x) => x.innerText.trim()).filter(Boolean) } : { present: false };
});
const cookieDismissed = await rejectCookies(jp);
await scrollAll(jp);
const styles = await jp.evaluate(() => {
  const pick = (el, props) => { if (!el) return null; const cs = getComputedStyle(el); const o = {}; props.forEach((p) => { o[p] = cs.getPropertyValue(p); }); return o; };
  const P = ['color', 'background-color', 'background-image', 'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing', 'text-transform', 'border-radius', 'border-color', 'border-width', 'padding'];
  const q = (s) => document.querySelector(s);
  const out = {
    body: pick(document.body, P),
    header: pick(q('header.header'), P),
    header_nav_link: pick(q('.header__nav-item a'), P),
    header_sign_in: pick(q('.header__nav-sign-in a'), P),
    header_logo_img: (() => { const i = q('.header__logo img'); return i ? { rendered_width: i.getBoundingClientRect().width, rendered_height: i.getBoundingClientRect().height, natural: [i.naturalWidth, i.naturalHeight] } : null; })(),
    section_heading_h2: pick(q('.section__heading'), P),
    card_name_h3: pick(q('.card__name'), P),
    card_description: pick(q('.card__description'), P),
    card_badge: pick(q('.card__badge'), P),
    card: pick(q('.card'), P),
    testimonial_title: pick(q('.testimonial__title'), P),
    testimonial_author: pick(q('.testimonial__author'), P),
    button_primary: pick(q('.button-primary'), P),
    button_secondary: pick(q('.button-secondary'), P),
    lead_capture_section: pick(q('.lead-capture'), P),
    form_control: pick(q('.form__control'), P),
    footer: pick(q('footer.footer'), P),
    footer_link: pick(q('.footer__nav-item a'), P),
    footer_copyright: pick(q('.footer__copyright'), P),
    sections_bg: [...document.querySelectorAll('[data-section-uid]')].map((s) => ({ type: s.getAttribute('data-preview-item'), ...pick(s, ['background-color', 'background-image', 'color']) })),
  };
  // color frequency over visible elements
  const freq = {};
  const add = (k, v, w) => { if (!v || v === 'rgba(0, 0, 0, 0)' || v === 'transparent') return; freq[k + '|' + v] = (freq[k + '|' + v] || 0) + w; };
  document.querySelectorAll('body *').forEach((el) => {
    const r = el.getBoundingClientRect(); if (!r.width || !r.height) return;
    const cs = getComputedStyle(el);
    const area = Math.min(r.width * r.height, 2e6) / 1000;
    if (el.childNodes.length && [...el.childNodes].some((n) => n.nodeType === 3 && n.nodeValue.trim())) add('text', cs.color, 1);
    add('bg', cs.backgroundColor, area);
    if (parseFloat(cs.borderTopWidth) > 0) add('border', cs.borderTopColor, 1);
  });
  out.color_frequency = Object.entries(freq).sort((a, b) => b[1] - a[1]).slice(0, 30).map(([k, v]) => ({ usage: k.split('|')[0], color: k.split('|')[1], weight: Math.round(v) }));
  // font families actually used by visible text
  const fams = {};
  document.querySelectorAll('body *').forEach((el) => { if ([...el.childNodes].some((n) => n.nodeType === 3 && n.nodeValue.trim())) { const f = getComputedStyle(el).fontFamily; fams[f] = (fams[f] || 0) + 1; } });
  out.font_families_used = Object.entries(fams).sort((a, b) => b[1] - a[1]).map(([f, n]) => ({ font_family: f, elements: n }));
  out.document_fonts_loaded = [...document.fonts].filter((f) => f.status === 'loaded').map((f) => `${f.family} ${f.weight} ${f.style}`);
  return out;
});
// course page components (WhatsApp button, brochure CTA band, FAQ, testimonials)
await gotoIdle(jp, BASE + '/courses/METABOLISMO,%20NUTRIZIONE%20E%20PERFORMANCE%20NEL%20CANE%20SPORTIVO%20E%20NEL%20CAVALLO%20ATLETA');
await rejectCookies(jp);
await scrollAll(jp);
styles.course_page = await jp.evaluate(() => {
  const pick = (el, props) => { if (!el) return null; const cs = getComputedStyle(el); const o = {}; props.forEach((p) => { o[p] = cs.getPropertyValue(p); }); return o; };
  const P = ['color', 'background-color', 'background-image', 'font-family', 'font-size', 'font-weight', 'line-height', 'text-transform', 'border-radius', 'border-color', 'border-width', 'padding'];
  const q = (s) => document.querySelector(s);
  return {
    url: location.href,
    description_heading: pick(q('[data-preview-item="description"] .section__heading'), P),
    description_subheading: pick(q('[data-preview-item="description"] .section__subheading'), P),
    description_body: pick(q('[data-preview-item="description"] .section__body'), P),
    whatsapp_button_primary: pick(q('[data-preview-item="description"] .button-primary'), P),
    cta_section: pick(q('[data-preview-item="call-to-action"]'), [...P, 'background-size', 'background-position']),
    cta_overlay_before: (() => { const e = q('[data-preview-item="call-to-action"]'); if (!e) return null; const cs = getComputedStyle(e, '::before'); return { content: cs.content, background: cs.backgroundColor, opacity: cs.opacity }; })(),
    cta_heading: pick(q('[data-preview-item="call-to-action"] .section__heading'), P),
    cta_subheading: pick(q('[data-preview-item="call-to-action"] .section__subheading'), P),
    brochure_button_secondary: pick(q('[data-preview-item="call-to-action"] .button-secondary'), P),
    faq_question: pick(q('.faq__list-item summary strong'), P),
    faq_answer: pick(q('.faq__list-item p'), P),
    faq_item: pick(q('.faq__list-item'), P),
    testimonial_item: pick(q('.testimonials__list-item'), P),
    testimonial_title: pick(q('.testimonial__title'), P),
    testimonial_author: pick(q('.testimonial__author'), P),
    sections_bg: [...document.querySelectorAll('[data-section-uid]')].map((s) => ({ type: s.getAttribute('data-preview-item'), ...pick(s, ['background-color', 'background-image', 'color']) })),
  };
});
// dominant colours of the header logo (canvas sampling)
{
  const logoPath = path.join(ASSETS, 'brand', 'logo-header.png');
  const logoUrl = chrome.header.logo ? chrome.header.logo.src : null;
  if (logoUrl) {
    styles.logo_palette = await jp.evaluate(async (src) => {
      const img = new Image(); img.src = src;
      await img.decode();
      const c = document.createElement('canvas'); c.width = img.naturalWidth; c.height = img.naturalHeight;
      const g = c.getContext('2d'); g.drawImage(img, 0, 0);
      let d; try { d = g.getImageData(0, 0, c.width, c.height).data; } catch (e) { return { error: String(e) }; }
      const counts = {};
      for (let i = 0; i < d.length; i += 4) { if (d[i + 3] < 200) continue; const r = d[i] >> 3 << 3, gg = d[i + 1] >> 3 << 3, b = d[i + 2] >> 3 << 3; if (r > 235 && gg > 235 && b > 235) continue; const k = `${r},${gg},${b}`; counts[k] = (counts[k] || 0) + 1; }
      const hex = (k) => '#' + k.split(',').map((v) => Number(v).toString(16).padStart(2, '0')).join('').toUpperCase();
      return Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 6).map(([k, n]) => ({ rgb_bucket: k, hex_approx: hex(k), pixels: n }));
    }, 'data:image/png;base64,' + (await fetchBuf(logoUrl)).buf.toString('base64')).catch((e) => ({ error: String(e) }));
  }
}
// theme CSS custom colors from the inline theme stylesheet
const rawHome = (await fetchBuf(BASE + '/')).buf.toString('utf8');
const hexCounts = {};
for (const m of rawHome.matchAll(/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b(?![0-9a-zA-Z-])/g)) { const h = '#' + m[1].toUpperCase(); hexCounts[h] = (hexCounts[h] || 0) + 1; }
const iubConf = (() => { const m = rawHome.match(/_iub\.csConfiguration\s*=\s*(\{[\s\S]*?\});/); if (!m) return null; try { return JSON.parse(m[1].replace(/"logo":"data:image\/png;base64,[^"]+"/, '"logo":"(base64 png)"')); } catch { return m[1].replace(/data:image\/png;base64,[A-Za-z0-9+/=]+/, '(base64 png)').slice(0, 2000); } })();
const iubLogoB64 = (rawHome.match(/"logo":"data:image\/png;base64,([A-Za-z0-9+/=]+)"/) || [])[1];

// ---------------------------------------------------------------- brand assets
const brandDir = path.join(ASSETS, 'brand');
ensureDir(brandDir);
const brand = { logos: [], favicon: null, apple_touch_icon: null, svg_available: false, notes: [] };
if (chrome.header.logo) {
  const d = await downloadBestImage(chrome.header.logo.src, path.join(brandDir, 'logo-header'));
  brand.logos.push({ role: 'header logo', url_original: chrome.header.logo.src, url_best: d.url_best, file: d.rel, width: d.width, height: d.height, bytes: d.bytes, sha1: d.sha1 });
}
const iconLink = chrome.head.links.find((l) => l.rel === 'icon' && /png/.test(l.type || ''));
const appleLink = chrome.head.links.find((l) => l.rel === 'apple-touch-icon');
if (appleLink) {
  const d = await downloadBestImage(appleLink.href, path.join(brandDir, 'logo-icon-apple-touch'));
  brand.apple_touch_icon = { url_original: appleLink.href, url_best: d.url_best, file: d.rel, width: d.width, height: d.height, sha1: d.sha1 };
  brand.logos.push({ role: 'apple-touch-icon / png icon', ...brand.apple_touch_icon });
}
if (iconLink && (!appleLink || iconLink.href !== appleLink.href)) {
  const d = await downloadBestImage(iconLink.href, path.join(brandDir, 'icon-png'));
  brand.logos.push({ role: 'png icon', url_original: iconLink.href, url_best: d.url_best, file: d.rel, width: d.width, height: d.height, sha1: d.sha1 });
}
{
  const d = await downloadTo(BASE + '/favicon.ico', path.join(brandDir, 'favicon'));
  brand.favicon = { url: BASE + '/favicon.ico', ok: d.ok, file: d.rel || null, bytes: d.bytes || null, content_type: d.content_type || null, width: d.width || null, height: d.height || null, sha1: d.sha1 || null };
}
if (iubLogoB64) {
  const buf = Buffer.from(iubLogoB64, 'base64');
  const f = path.join(brandDir, 'logo-iubenda-banner.png');
  fs.writeFileSync(f, buf);
  brand.logos.push({ role: 'logo embedded (base64) in iubenda cookie banner config', file: rel(f), ...imageSize(buf), sha1: sha1(buf) });
}
// other logo variants referenced across the site (Logo_CDF-0x)
const logoRefs = [...new Set([...rawHome.matchAll(/https:\/\/import\.cdn\.thinkific\.com\/[^"' )]*Logo_CDF[^"' )?]*/g)].map((m) => m[0]))];
for (const u of logoRefs) {
  if (brand.logos.some((l) => l.url_original === u || l.url_best === u)) continue;
  const name = 'logo-variant-' + cleanSlug(decodeURIComponent(u).split('/').pop()).slice(0, 50);
  const d = await downloadBestImage(u, path.join(brandDir, name));
  if (d.ok && !brand.logos.some((l) => l.sha1 === d.sha1)) brand.logos.push({ role: 'logo variant (used as home testimonial avatar / icon)', url_original: u, url_best: d.url_best, file: d.rel, width: d.width, height: d.height, sha1: d.sha1 });
  else if (d.ok) fs.unlinkSync(d.file); // identical to a logo already saved
}
// probe for an SVG logo (Thinkific themes normally only store raster uploads)
for (const u of [BASE + '/logo.svg', BASE + '/favicon.svg']) {
  try { const r = await fetchBuf(u); if (r.status === 200 && sniffExt(r.buf) === 'svg') { brand.svg_available = true; fs.writeFileSync(path.join(brandDir, path.basename(u)), r.buf); } } catch { /* */ }
}
if (!brand.svg_available) brand.notes.push('Nessuna versione SVG del logo trovata sul sito (solo PNG caricati nel tema Thinkific).');

// ---------------------------------------------------------------- home assets (images)
const homeDir = path.join(ASSETS, 'home');
const homeImages = [];
for (const s of home.sections) {
  for (const [i, img] of s.images.entries()) {
    if (!img.src) continue;
    const base = `${String(s.idx).padStart(2, '0')}-${s.type}-${String(i + 1).padStart(2, '0')}-${cleanSlug(decodeURIComponent(img.src.split('?')[0]).split('/').pop()).slice(0, 50)}`;
    const d = await downloadBestImage(img.src, path.join(homeDir, base));
    homeImages.push({ section: s.type, section_idx: s.idx, url_original: img.src, url_best: d.url_best || null, file: d.rel || null, width: d.width || null, height: d.height || null, sha1: d.sha1 || null, alt: img.alt });
  }
}

// ---------------------------------------------------------------- legal
const legalDir = path.join(ASSETS, 'legal');
const legal = {};
const cookieLink = chrome.footer.links.find((l) => /cookie|privacy/i.test(l.label_source));
if (cookieLink) {
  const id = driveId(cookieLink.href);
  const r = id ? await downloadDrivePdf(id, path.join(legalDir, 'cookie-privacy-policy.pdf')) : { ok: false };
  legal.cookie_privacy = { label: cookieLink.label_source, url: cookieLink.href, drive_id: id, downloaded: r.ok, file: r.rel || null, bytes: r.bytes || null, pages: r.ok ? pdfPages(r.file) : null, sha1: r.sha1 || null };
}
const termsLink = chrome.footer.links.find((l) => /termini/i.test(l.label_source));
if (termsLink) {
  const r = await fetchBuf(termsLink.href, { redirect: 'manual' });
  legal.terms = { label: termsLink.label_source, url: termsLink.href, http_status: r.status, redirects_to: r.headers.location || null };
}
// /pages/terms and /pages/privacy (Thinkific system pages)
for (const slug of ['terms', 'privacy']) {
  await loadNoJs(`${BASE}/pages/${slug}`);
  const info = await page.evaluate(() => {
    const S = window.__S; S.decodeCfEmails();
    const main = document.querySelector('main') || document.body;
    return { title: document.title, html: S.clean(main, { keepImg: true }), text: main.innerText.trim(), links: [...main.querySelectorAll('a[href]')].map((a) => ({ label: S.flatText(a), href: a.getAttribute('href') })) };
  });
  legal[`page_${slug}`] = { url: `${BASE}/pages/${slug}`, ...info };
  for (const l of info.links) {
    if (/\.pdf(\?|$)/i.test(l.href)) {
      const name = cleanSlug(decodeURIComponent(l.href.split('?')[0]).split('/').pop().replace(/\.pdf$/i, ''));
      const d = await downloadTo(l.href, path.join(legalDir, name), { forceExt: 'pdf' });
      (legal[`page_${slug}`].pdfs ||= []).push({ label: l.label, url: l.href, ok: d.ok && fs.readFileSync(d.file).slice(0, 4).toString() === '%PDF', file: d.rel || null, bytes: d.bytes || null, pages: d.ok ? pdfPages(d.file) : null });
    }
  }
}

// ---------------------------------------------------------------- contact page
await loadNoJs(`${BASE}/pages/contatti`);
const contatti = await page.evaluate(extractPage, {});
const contactItems = (contatti.sections.find((s) => s.type === 'text-icon') || { items: [] }).items;
const emails = [];
for (const s of contatti.sections) {
  for (const it of s.items || []) {
    for (const m of (it.text || '').matchAll(/[\w.+-]+@[\w-]+\.[\w.-]+/g)) emails.push({ label: it.heading, email: m[0], section: s.type });
  }
  for (const m of (s.subheading || '').matchAll(/[\w.+-]+@[\w-]+\.[\w.-]+/g)) emails.push({ label: s.heading, email: m[0], section: s.type });
}
const waItem = contactItems.find((i) => /whatsapp/i.test(i.heading || '') || /whatsapp/.test(i.icon || ''));
const phoneDigits = waItem ? (waItem.text.match(/[\d ]{9,}/) || [''])[0].replace(/\s/g, '') : null;
// the info request form lives on Google Forms: read its public field labels (GET only, never submitted)
const formLink = contatti.sections.flatMap((s) => s.buttons).find((b) => /forms\.gle|docs\.google\.com\/forms/.test(b.href));
let googleForm = null;
if (formLink) {
  const gp = await jctx.newPage();
  try {
    await gotoIdle(gp, formLink.href);
    googleForm = await gp.evaluate(() => {
      const items = [...document.querySelectorAll('[role="listitem"]')].map((li) => {
        const h = li.querySelector('[role="heading"]');
        const inputs = [...li.querySelectorAll('input, textarea, [role="radio"], [role="checkbox"], [role="listbox"]')].map((i) => i.getAttribute('type') || i.getAttribute('role') || i.tagName.toLowerCase());
        const opts = [...li.querySelectorAll('[role="radio"], [role="checkbox"], [role="option"]')].map((o) => o.getAttribute('aria-label') || o.getAttribute('data-value') || o.innerText.trim()).filter(Boolean);
        return { heading: h ? h.innerText.trim() : null, required: !!li.querySelector('[aria-label*="obbligatori"], .vnumgf') || /\*\s*$/.test(h ? h.innerText.trim() : ''), inputs: [...new Set(inputs)], options: opts, text: li.innerText.trim().slice(0, 1500) };
      });
      return { final_url: location.href, title: document.title, description: (document.querySelector('[role="heading"][aria-level="1"]') || {}).innerText || null, body_text: document.body.innerText.slice(0, 6000), items };
    });
  } catch (e) { googleForm = { error: String(e) }; } finally { await gp.close(); }
}

await browser.close();

// ---------------------------------------------------------------- assemble
const displayed = (s) => (s || '').toUpperCase(); // nav/footer labels are uppercased by CSS (text-transform)
const navTransform = styles.header_nav_link ? styles.header_nav_link['text-transform'] : null;
const footTransform = styles.footer_link ? styles.footer_link['text-transform'] : null;
const piva = (chrome.footer.copyright.match(/PARTITA IVA:\s*(\d{11})/i) || [])[1] || null;
const newsletter = (home.sections.find((s) => s.type === 'lead_capture') || {});
const site = {
  scraped_at: new Date().toISOString(),
  platform: { main: 'Thinkific (theme "custom_site_themes", tenant id 692348)', thinkific_subdomain: 'corsidiformazionecm.thinkific.com', testimonials_subdomain: 'sales.corsidiformazionecm.it (GoHighLevel / LeadConnector funnel, header x-ghl-*)', cdn: ['import.cdn.thinkific.com', 'files.cdn.thinkific.com'], cloudflare_email_obfuscation: true },
  head: chrome.head,
  header: {
    ...chrome.header,
    nav: chrome.header.nav.map((n) => ({ ...n, label_displayed: navTransform === 'uppercase' ? displayed(n.label_source) : n.label_source })),
    login: (() => { const s = chrome.header.nav.find((n) => n.is_sign_in); return s ? { label_source: s.label_source, label_displayed: navTransform === 'uppercase' ? displayed(s.label_source) : s.label_source, href: s.href, absolute_url: new URL(s.href, BASE).href } : null; })(),
    nav_text_transform: navTransform,
  },
  brand: {
    ...brand,
    colors: {
      computed: styles,
      theme_hex_in_html: Object.entries(hexCounts).sort((a, b) => b[1] - a[1]).slice(0, 25).map(([hex, n]) => ({ hex, occurrences: n })),
      iubenda_banner_colors: iubConf && typeof iubConf === 'object' && iubConf.banner ? Object.fromEntries(Object.entries(iubConf.banner).filter(([k]) => /color/i.test(k))) : null,
    },
    fonts: {
      google_fonts_links: chrome.head.links.filter((l) => /fonts\.googleapis/.test(l.href || '')).map((l) => l.href),
      used: styles.font_families_used,
      loaded: styles.document_fonts_loaded,
      headings: styles.section_heading_h2 ? styles.section_heading_h2['font-family'] : null,
      body: styles.body ? styles.body['font-family'] : null,
      icon_fonts: ['Font Awesome 4.7.0 (cdnjs)', 'Thinkific toga-icons'],
    },
  },
  footer: {
    links: chrome.footer.links.map((l) => ({ ...l, label_displayed: footTransform === 'uppercase' ? displayed(l.label_source) : l.label_source })),
    footer_text_transform: footTransform,
    social: chrome.footer.social,
    social_urls: Object.fromEntries(chrome.footer.social.map((s) => [new URL(s.href).hostname.replace(/^www./, '').split('.')[0], s.href])),
    copyright_raw: chrome.footer.copyright_raw,
    copyright: chrome.footer.copyright,
    piva,
    company_name_in_footer: (chrome.footer.copyright.match(/Copyright\s+(.+?)\s+\d{4}/) || [])[1] || null,
    address: null,
    white_label: chrome.footer.white_label,
    notes: ['Nessun indirizzo/sede legale/ragione sociale completa nel footer: solo "Corsi di Formazionecm" + P.IVA.', 'Etichetta social Facebook nello screen-reader text: "Facebooke" (refuso nel tema).'],
  },
  legal: {
    terms_url: termsLink ? termsLink.href : null,
    cookie_privacy_url: cookieLink ? cookieLink.href : null,
    ...legal,
    cookie_banner: { provider: 'iubenda Cookie Solution', config: iubConf, banner_on_load: cookieBanner, dismissed_with: cookieDismissed },
  },
  newsletter: {
    heading: newsletter.heading, subheading: newsletter.subheading, form: newsletter.form,
    provider: 'Thinkific nativo (lead capture): POST /tenant_leads con data-remote (Rails UJS) — i contatti finiscono nella sezione Leads/Marketing di Thinkific; nessun Mailchimp/Brevo/ActiveCampaign rilevato nel markup.',
    consent_text: newsletter.form ? (newsletter.form.fields.find((f) => f.name === 'accept') || {}).label : null,
    present_on: 'home, tutte le pagine corso, contatti (sezione lead_capture)',
  },
  contact: {
    url: `${BASE}/pages/contatti`,
    emails,
    whatsapp: waItem ? { label: waItem.heading, text: waItem.text, number_display: (waItem.text.match(/[\d ]{9,}/) || [''])[0].trim(), number_e164: phoneDigits ? '+39' + phoneDigits : null, wa_link_on_contact_page: (waItem.links[0] || {}).href || null, wa_link_used_in_course_pages: phoneDigits ? `https://wa.me/39${phoneDigits}` : null } : null,
    phone: null,
    address: null,
    info_request: formLink ? { label: formLink.label, href: formLink.href, provider: 'Google Forms', form: googleForm } : null,
    on_page_form: 'Nessun form sulla pagina oltre alla newsletter: la richiesta informazioni rimanda a Google Forms.',
    sections: contatti.sections,
  },
  home: {
    url: BASE + '/',
    html_title: home.html_title,
    sections: home.sections.map((s) => ({ ...s })),
    images: homeImages,
    featured_courses: (home.sections.find((s) => /course-cards/.test(s.type)) || { cards: [] }).cards,
    reviews: (home.sections.find((s) => s.type === 'testimonials') || { items: [] }).items.map((it, i) => ({ position: i + 1, name: it.title, course_or_meta: it.author, text: it.text, text_html: it.text_html, text_raw: it.text_raw, rating: null, image: it.image ? it.image.src : null })),
    patrocini_logos: [],
    notes: ['Nessuna sezione statistiche/numeri e nessun logo patrocinio in home (solo hero gallery, griglia corsi, testimonianze, newsletter).', 'Le immagini delle testimonianze in home sono tutte il logo Corsi di Formazione ECM (avatar generico).'],
  },
};
writeJSON(path.join(DATA, 'site.json'), site);
console.log('nav:', site.header.nav.map((n) => `${n.label_displayed} -> ${n.href}`).join(' | '));
console.log('login:', JSON.stringify(site.header.login));
console.log('footer:', site.footer.links.map((n) => `${n.label_displayed} -> ${n.href}`).join(' | '));
console.log('social:', JSON.stringify(site.footer.social_urls), 'piva', piva);
console.log('emails:', JSON.stringify(emails));
console.log('whatsapp:', JSON.stringify(site.contact.whatsapp));
console.log('legal:', JSON.stringify({ cp: legal.cookie_privacy, terms: legal.terms, tp: legal.page_terms && legal.page_terms.pdfs }));
console.log('fonts:', JSON.stringify(site.brand.fonts.used.slice(0, 6)));
console.log('home sections:', home.sections.map((s) => s.type).join(','), 'reviews', site.home.reviews.length, 'featured', site.home.featured_courses.length);
console.log('google form items:', googleForm && googleForm.items ? googleForm.items.map((i) => i.heading).join(' | ') : JSON.stringify(googleForm));
