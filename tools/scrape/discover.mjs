// Step 1: discover catalog order + every course/page URL reachable on the site.
// Output: data/scrape/_discovery.json
import { BASE, DATA, fetchBuf, writeJSON, launch, jsContext, gotoIdle, scrollAll } from './lib.mjs';
import path from 'node:path';

const decodeEnt = (s) => s.replace(/&amp;/g, '&').replace(/&#39;/g, "'").replace(/&quot;/g, '"');
const hrefsOf = (html) => [...html.matchAll(/\shref=(["'])(.*?)\1/g)].map((m) => decodeEnt(m[2]));

async function getHtml(url, manual = false) {
  const r = await fetchBuf(url, manual ? { redirect: 'manual' } : {});
  return { status: r.status, url: r.url, location: r.headers.location || null, html: r.buf.toString('utf8') };
}

// ---- catalog pages in order
const catalog = [];
const catalogPages = [];
for (let n = 1; n < 30; n++) {
  const url = `${BASE}/collections?page=${n}`;
  const { status, html } = await getHtml(url);
  const cards = [];
  const re = /<a class="(card[^"]*)" href="([^"]*)">([\s\S]*?)<\/a>\s*<\/li>/g;
  let m;
  while ((m = re.exec(html))) {
    const block = m[3];
    const name = (block.match(/<h3 class="card__name">\s*([\s\S]*?)\s*<\/h3>/) || [])[1];
    const desc = (block.match(/<p class="card__description">\s*([\s\S]*?)\s*<\/p>/) || [])[1];
    const badge = (block.match(/<div class="card__badge[^"]*">\s*([\s\S]*?)\s*<\/div>/) || [])[1];
    const img = (block.match(/<img src="([^"]*)"/) || [])[1];
    const alt = (block.match(/<img [^>]*alt="([^"]*)"/) || [])[1];
    cards.push({ card_class: m[1], href_raw: m[2], name_html: name ?? null, desc_html: desc ?? null, badge_html: badge ?? null, img_src: img ? decodeEnt(img) : null, img_alt: alt ?? null });
  }
  catalogPages.push({ page: n, url, status, count: cards.length });
  if (!cards.length) break;
  cards.forEach((c) => catalog.push({ ...c, catalog_page: n, order: catalog.length + 1 }));
}

// ---- sitemap
const sm = await getHtml(`${BASE}/sitemap.xml`);
const sitemap = [...sm.html.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => decodeEnt(m[1]));

// ---- BFS crawl of server HTML (same host), record where each course link is found
const seen = new Map(); // url -> status
const linkSources = {}; // courseHref -> [pages]
const allExternal = new Set();
const queue = [BASE + '/', ...catalogPages.map((p) => p.url), ...sitemap];
const norm = (u) => { const x = new URL(u); x.hash = ''; return x.href; };
const allowed = (u) => u.host === new URL(BASE).host && /^\/($|collections|courses\/|pages\/|bundles\/|products\/)/.test(u.pathname);
while (queue.length) {
  const url = norm(queue.shift());
  if (seen.has(url)) continue;
  let res;
  try { res = await getHtml(url, true); } catch (e) { seen.set(url, 'ERR ' + e); continue; }
  seen.set(url, res.location ? `${res.status} -> ${res.location}` : res.status);
  if (res.location) { try { const lu = new URL(res.location, url); if (allowed(lu)) queue.push(lu.href); } catch { /* */ } }
  if (res.status !== 200) continue;
  for (const h of hrefsOf(res.html)) {
    if (!h || h.startsWith('#') || h.startsWith('mailto:') || h.startsWith('tel:') || h.startsWith('javascript:')) continue;
    let u;
    try { u = new URL(h, url); } catch { continue; }
    if (/\/courses\//.test(u.pathname) && u.host === new URL(BASE).host) {
      (linkSources[u.pathname] ||= new Set()).add(new URL(url).pathname + new URL(url).search);
    }
    if (allowed(u)) { if (!seen.has(norm(u.href))) queue.push(u.href); }
    else if (!/\.(css|js|ico|png|jpe?g|svg|woff2?)(\?|$)/i.test(u.pathname) && !/thinkific\.com\/assets|fonts\.googleapis|cdnjs/.test(u.href)) allExternal.add(u.href);
  }
}

// ---- links on the sales (GoHighLevel) testimonials page (JS rendered)
const b = await launch();
const ctx = await jsContext(b);
const p = await ctx.newPage();
await gotoIdle(p, 'https://sales.corsidiformazionecm.it/testimonianze');
await scrollAll(p);
const salesLinks = await p.evaluate(() => [...document.querySelectorAll('a[href]')].map((a) => ({ text: a.textContent.trim().replace(/\s+/g, ' '), href: a.getAttribute('href') })));
await b.close();
for (const l of salesLinks) {
  try { const u = new URL(l.href, 'https://sales.corsidiformazionecm.it/'); if (u.host === new URL(BASE).host && /\/courses\//.test(u.pathname)) (linkSources[u.pathname] ||= new Set()).add('sales:/testimonianze'); } catch { /* */ }
}

const catalogPaths = new Set(catalog.map((c) => new URL(c.href_raw, BASE).pathname));
const courseUrlsCrawled = [...seen.entries()].filter(([u]) => /\/courses\//.test(new URL(u).pathname)).map(([u, s]) => ({ url: u, status: s }));
const notInCatalog = courseUrlsCrawled.filter((c) => !catalogPaths.has(new URL(c.url).pathname));
const pagesFound = [...seen.entries()].filter(([u]) => /\/pages\//.test(new URL(u).pathname)).map(([u, s]) => ({ url: u, status: s }));

const out = {
  scraped_at: new Date().toISOString(),
  catalog_pages: catalogPages,
  catalog,
  sitemap,
  crawled: [...seen.entries()].map(([url, status]) => ({ url, status })),
  course_link_sources: Object.fromEntries(Object.entries(linkSources).map(([k, v]) => [k, [...v]])),
  courses_not_in_catalog: notInCatalog,
  pages_found: pagesFound,
  sales_page_links: salesLinks,
  external_links: [...allExternal].sort(),
};
writeJSON(path.join(DATA, '_discovery.json'), out);
console.log('catalog pages:', catalogPages.map((p) => `${p.page}:${p.count}`).join(' '));
console.log('catalog courses:', catalog.length);
console.log('crawled urls:', seen.size);
console.log('courses not in catalog:', JSON.stringify(notInCatalog));
console.log('pages found:', pagesFound.map((x) => x.url + ' ' + x.status).join('\n  '));
console.log('course link paths found anywhere:', Object.keys(linkSources).length);
for (const [k, v] of Object.entries(linkSources)) if (!catalogPaths.has(k)) console.log('  NOT IN CATALOG LINK:', k, [...v].join(', '));
