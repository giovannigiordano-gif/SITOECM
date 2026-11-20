// Step 6: /pages/* content (chi-siamo, contatti, landing pages, system pages).
// Output: data/scrape/pages.json + assets/{chi-siamo,pages}
import path from 'node:path';
import fs from 'node:fs';
import { BASE, DATA, ASSETS, readJSON, writeJSON, launch, noJsContext, installBrowserHelpers, downloadBestImage, driveId, downloadDrivePdf, pdfPages, cleanSlug } from './lib.mjs';
import { extractPage } from './sections.mjs';

const disc = readJSON(path.join(DATA, '_discovery.json'));
const sales = readJSON(path.join(DATA, '_sales_page.json'), null);
const pagePaths = [...new Set(disc.pages_found.map((p) => new URL(p.url).pathname))];
const browser = await launch();
const ctx = await noJsContext(browser);
const page = await ctx.newPage();
const pages = {};

for (const p of pagePaths) {
  if (p === '/pages/docenti') { pages.docenti = { url: BASE + p, see: 'data/scrape/docenti.json', in_sitemap: disc.sitemap.includes(BASE + p), http_status: 200 }; continue; }
  const slug = p.replace(/^\/pages\//, '');
  const resp = await page.goto(BASE + p, { waitUntil: 'load', timeout: 120000 });
  await page.evaluate(installBrowserHelpers);
  const x = await page.evaluate(extractPage, { skipTypes: ['header', 'footer'] });
  // system pages (terms/privacy) have no sections: take <main>
  let mainHtml = null;
  if (!x.sections.length) mainHtml = await page.evaluate(() => { const m = document.querySelector('main') || document.body; return { html: window.__S.clean(m, { keepImg: true }), text: m.innerText.trim() }; });
  const sections = x.sections.filter((s) => s.type !== 'lead_capture').map((s) => ({
    type: s.type, uid: s.uid, heading: s.heading, subheading: s.subheading, html: s.html, text: s.html_text, raw_html: s.raw_html, images: s.images, links: s.links, buttons: s.buttons, items: s.items, body_html: s.body_html,
  }));
  const assetDir = slug === 'chi-siamo' ? path.join(ASSETS, 'chi-siamo') : path.join(ASSETS, 'pages', slug);
  const images = [];
  for (const s of sections) for (const [i, img] of (s.images || []).entries()) {
    const d = await downloadBestImage(img.src, path.join(assetDir, `${String(images.length + 1).padStart(2, '0')}-${cleanSlug(decodeURIComponent(img.src.split('?')[0]).split('/').pop()).slice(0, 50)}`));
    images.push({ section: s.type, url: img.src, file: d.ok ? d.rel : null, width: d.width || null, height: d.height || null, alt: img.alt });
  }
  const files = [];
  for (const s of sections) for (const b of (s.buttons || [])) {
    const id = driveId(b.href);
    if (id && /drive\.google\.com\/file/.test(b.href)) {
      const out = path.join(assetDir, `${cleanSlug(b.label || 'file')}-${id.slice(0, 8)}.pdf`);
      const r = await downloadDrivePdf(id, out);
      files.push({ label: b.label, url: b.href, drive_id: id, ok: r.ok, file: r.rel || null, bytes: r.bytes || null, pages: r.ok ? pdfPages(out) : null });
    }
  }
  pages[slug] = {
    url: BASE + p, http_status: resp.status(), html_title: x.html_title, meta_description: x.meta_description,
    in_sitemap: disc.sitemap.includes(BASE + p),
    linked_from_site_nav: null,
    sections, main: mainHtml, images, files,
    has_newsletter_section: x.sections.some((s) => s.type === 'lead_capture'),
    main_inner_text: x.main_inner_text,
  };
  console.log(slug, resp.status(), sections.map((s) => s.type).join(','), 'imgs', images.length, 'files', files.length);
}
await browser.close();

// where is each page linked from (raw hrefs on crawled pages)
const site = readJSON(path.join(DATA, 'site.json'), {});
const navHrefs = [...(site.header ? site.header.nav.map((n) => n.href) : []), ...(site.footer ? site.footer.links.map((l) => l.href) : [])];
for (const [slug, pg] of Object.entries(pages)) {
  pg.linked_from_site_nav = navHrefs.some((h) => { try { return new URL(h, BASE).pathname === `/pages/${slug}`; } catch { return false; } }) || (slug === 'terms' && navHrefs.some((h) => /\/pages\/terms/.test(h)));
}

// ---- chi siamo: mission / vision
const cs = pages['chi-siamo'];
if (cs) {
  const html = cs.sections.map((s) => s.html).join('');
  const paras = [...html.matchAll(/<p>([\s\S]*?)<\/p>/g)].map((m) => m[1]);
  const plain = (h) => h.replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').trim();
  const idx = (label) => paras.findIndex((p) => new RegExp(`^${label}\\s*:?$`, 'i').test(plain(p).replace(/ /g, ' ').trim()));
  const iM = idx('Mission'), iV = idx('Vision');
  const nextText = (i) => { for (let j = i + 1; j < paras.length; j++) { const t = plain(paras[j]).replace(/ /g, ' ').trim(); if (t) return { html: `<p>${paras[j].trim()}</p>`, text: plain(paras[j]).trim() }; } return null; };
  cs.intro_paragraphs = paras.slice(0, iM >= 0 ? iM : paras.length).map((p) => plain(p).trim()).filter((t) => t.replace(/ /g, '').trim());
  cs.mission = iM >= 0 ? { label: plain(paras[iM]), ...nextText(iM) } : null;
  cs.vision = iV >= 0 ? { label: plain(paras[iV]), ...nextText(iV) } : null;
  cs.notes = ['Pagina composta da un solo blocco rich_text senza titolo né immagini; i testi erano incollati con stili inline (Georgia 12pt/14pt, giustificato) rimossi in html.', 'Sulla pagina vendite GHL (sales.corsidiformazionecm.it) esiste una versione più ricca di "Chi siamo" (statistiche, mission/vision riscritte, citazione fondatrice): vedi chi_siamo_sales_version.'];
  if (sales) {
    const b = sales.blocks;
    const start = b.findIndex((x) => /^CHI SIAMO$/i.test(x.text));
    const end = b.findIndex((x, i) => i > start && /I nostri docenti/i.test(x.text));
    cs.chi_siamo_sales_version = start >= 0 ? { source: sales.url, blocks: b.slice(start, end > start ? end : start + 25).map((x) => ({ tag: x.tag, text: x.text })) } : null;
    const statRe = /^(\d+[k+]*\+?)\s*$/i;
    const full = sales.full_text || '';
    cs.sales_stats = [...full.matchAll(/(\d+[k]?\+?)\n([A-ZÀ-Ú ]{4,})/g)].map((m) => ({ value: m[1], label: m[2].trim() }));
  }
}

writeJSON(path.join(DATA, 'pages.json'), {
  scraped_at: new Date().toISOString(),
  pages_found: Object.keys(pages).map((slug) => ({ slug, url: pages[slug].url, status: pages[slug].http_status, title: pages[slug].html_title, in_sitemap: pages[slug].in_sitemap, linked_from_nav_or_footer: pages[slug].linked_from_site_nav })),
  notes: {
    docenti: 'Contenuto strutturato in data/scrape/docenti.json',
    terms_privacy: 'Pagine di sistema Thinkific (Terms of Use / Privacy Policy): vedi anche site.json > legal',
  },
  pages,
});
console.log('chi-siamo mission:', cs && cs.mission && cs.mission.text.slice(0, 80));
console.log('chi-siamo vision:', cs && cs.vision && cs.vision.text.slice(0, 80));
console.log('stats:', JSON.stringify(cs && cs.sales_stats));
