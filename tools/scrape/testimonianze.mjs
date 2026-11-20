// Step 5: testimonials from sales.corsidiformazionecm.it/testimonianze (GoHighLevel funnel) + home + course pages.
// Output: data/scrape/testimonianze.json, data/scrape/_sales_page.json, assets/testimonianze/
import path from 'node:path';
import { DATA, ASSETS, readJSON, writeJSON, launch, jsContext, gotoIdle, scrollAll, downloadBestImage, cleanSlug, fetchBuf } from './lib.mjs';

const SALES = 'https://sales.corsidiformazionecm.it/testimonianze';
const browser = await launch();
const ctx = await jsContext(browser);
const p = await ctx.newPage();
const resp = await gotoIdle(p, SALES);
await scrollAll(p);
await p.waitForTimeout(2000);
const headers = resp ? resp.headers() : {};
const raw = await p.evaluate(() => {
  const vis = (el) => !!(el.offsetParent || el.getClientRects().length);
  const txt = (el) => (el ? el.innerText.replace(/ /g, ' ').trim() : null);
  // --- review cards (custom HTML marquee; groups duplicated for the infinite scroll effect)
  const wrappers = [...document.querySelectorAll('.reviews-wrapper')];
  const prevHeading = (el) => {
    const all = [...document.querySelectorAll('h1,h2,h3,h4')].filter((h) => h.innerText.trim());
    let best = null;
    for (const h of all) { if (h.compareDocumentPosition(el) & Node.DOCUMENT_POSITION_FOLLOWING) best = h; else break; }
    return best ? best.innerText.replace(/\s+/g, ' ').trim() : null;
  };
  const cards = [];
  wrappers.forEach((w, wi) => {
    let group = null;
    const walker = document.createTreeWalker(w, NodeFilter.SHOW_COMMENT | NodeFilter.SHOW_ELEMENT);
    let n;
    while ((n = walker.nextNode())) {
      if (n.nodeType === 8) { const m = n.nodeValue.match(/GRUPPO\s*\d+[^=]*/i); if (m) group = m[0].trim(); continue; }
      if (n.classList && n.classList.contains('review-card')) {
        cards.push({
          widget: wi + 1, widget_heading: prevHeading(w), group,
          stars: txt(n.querySelector('.review-stars')), name: txt(n.querySelector('.review-name')), role: txt(n.querySelector('.review-role')),
          text_raw: n.querySelector('.review-text') ? n.querySelector('.review-text').innerHTML.trim() : null,
          text: txt(n.querySelector('.review-text')),
        });
      }
    }
  });
  // --- ordered text blocks of the whole page (visible at 1440px, outside review cards)
  const blocks = [];
  document.querySelectorAll('h1,h2,h3,h4,h5,h6,p,blockquote,li').forEach((el) => {
    if (el.closest('.review-card, script, style, noscript')) return;
    if (!vis(el)) return;
    if (el.closest('p,li,blockquote') && el.closest('p,li,blockquote') !== el) return;
    const t = txt(el); if (!t) return;
    const last = blocks[blocks.length - 1];
    if (last && last.text === t) return;
    blocks.push({ tag: el.tagName.toLowerCase(), text: t, html: el.innerHTML.trim().slice(0, 4000) });
  });
  // --- quotes rendered as GHL paragraphs (e.g. Marco Neri)
  const quotes = [...document.querySelectorAll('.c-paragraph')].filter((el) => /[“"].{20,}[”"]/.test(el.innerText)).map((el) => ({ text: txt(el), html: el.innerHTML.replace(/<!---->/g, '').trim().slice(0, 3000) }));
  // --- teachers list on the sales page (GHL image-feature blocks)
  const docenti = [...document.querySelectorAll('.c-image-feature')].map((el) => ({
    name: txt(el.querySelector('.featureHeadline')), text: txt(el.querySelector('.featureText')),
    html: el.querySelector('.featureText') ? el.querySelector('.featureText').innerHTML.trim() : null,
    image: el.querySelector('img') ? el.querySelector('img').getAttribute('src') : null,
  })).filter((d) => d.name);
  // --- media
  const images = [...new Set([...document.querySelectorAll('img[src]')].map((i) => i.getAttribute('src')).concat([...document.querySelectorAll('[data-bg-src]')].map((e) => e.getAttribute('data-bg-src'))))].filter((s) => s && !s.startsWith('data:'));
  const vimeo = new Set();
  document.querySelectorAll('iframe[src*="vimeo"]').forEach((f) => { const m = f.getAttribute('src').match(/video\/(\d+)/); if (m) vimeo.add(m[1]); });
  document.querySelectorAll('[onclick*="openModal"]').forEach((e) => { const m = e.getAttribute('onclick').match(/openModal\('(\d+)'\)/); if (m) vimeo.add(m[1]); });
  const videoEls = [...document.querySelectorAll('.c-video, [class*="video"]')].map((e) => e.getAttribute('data-video-url') || e.getAttribute('data-src') || null).filter(Boolean);
  const scriptsText = [...document.querySelectorAll('script:not([src])')].map((s) => s.textContent).join('\n');
  const rows = [...scriptsText.matchAll(/const\s+(videosRow\d)\s*=\s*\[([^\]]*)\]/g)].map((m) => ({ name: m[1], ids: [...m[2].matchAll(/'(\d+)'/g)].map((x) => x[1]) }));
  return {
    title: document.title, url: location.href,
    generator: (document.querySelector('meta[name="generator"]') || {}).content || null,
    ghl_markers: { has_hl_app: !!document.querySelector('#__nuxt, [data-v-app], .hl_page-preview--content'), nuxt: !!window.__NUXT__ || !!document.querySelector('#__nuxt') },
    cards, blocks, quotes, docenti, images, vimeo_ids: [...vimeo], video_rows_in_script: rows, video_elements: videoEls,
    links: [...document.querySelectorAll('a[href]')].map((a) => ({ text: a.innerText.trim().replace(/\s+/g, ' '), href: a.getAttribute('href') })),
    full_text: document.body.innerText,
  };
});
await browser.close();

// ---------------------------------------------------------------- download sales-page images
const imgs = [];
for (const [i, src] of raw.images.entries()) {
  let abs; try { abs = new URL(src, SALES).href; } catch { continue; }
  const name = `${String(i + 1).padStart(2, '0')}-${cleanSlug(decodeURIComponent(abs.split('?')[0]).split('/').pop()).slice(0, 50)}`;
  const d = await downloadBestImage(abs, path.join(ASSETS, 'testimonianze', name)).catch((e) => ({ ok: false, error: String(e) }));
  imgs.push({ url: abs, file: d.ok ? d.rel : null, width: d.width || null, height: d.height || null, ok: d.ok });
}
// vimeo metadata (public oEmbed, GET only)
const videos = [];
for (const id of raw.vimeo_ids) {
  let meta = null;
  try { const r = await fetchBuf(`https://vimeo.com/api/oembed.json?url=${encodeURIComponent('https://vimeo.com/' + id)}`); if (r.status === 200) meta = JSON.parse(r.buf.toString('utf8')); else meta = { http_status: r.status }; } catch (e) { meta = { error: String(e) }; }
  videos.push({ vimeo_id: id, url: `https://vimeo.com/${id}`, player_url: `https://player.vimeo.com/video/${id}`, title: meta && meta.title || null, author: meta && meta.author_name || null, duration_s: meta && meta.duration || null, thumbnail: meta && meta.thumbnail_url || null, width: meta && meta.width || null, height: meta && meta.height || null, oembed_status: meta && meta.http_status || (meta && meta.title ? 200 : null) });
}

writeJSON(path.join(DATA, '_sales_page.json'), { scraped_at: new Date().toISOString(), url: SALES, response_headers: Object.fromEntries(Object.entries(headers).filter(([k]) => /server|x-ghl|x-powered|via|cf-/i.test(k))), ...raw, images_downloaded: imgs, videos });

// ---------------------------------------------------------------- normalise + dedupe
const stripQuotes = (s) => (s || '').replace(/^\s*["“”«]+|["“”»]+\s*$/g, '').trim();
const norm = (s) => (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9 ]+/g, ' ').replace(/\s+/g, ' ').trim();
const words = (s) => new Set(norm(s).split(' ').filter((w) => w.length > 2));
const jacc = (a, b) => { const A = words(a), B = words(b); if (!A.size || !B.size) return 0; let i = 0; A.forEach((w) => { if (B.has(w)) i++; }); return i / Math.min(A.size, B.size); };
const surname = (n) => { const t = norm((n || '').replace(/\b(dott\.?ssa|dott\.?|dr\.?|prof\.?ssa|prof\.?|dottoressa|dottor)\b/gi, '')).split(' ').filter(Boolean); return t.length ? t[t.length - 1] : ''; };

const entries = [];
// sales cards: unique by name+text (the marquee repeats groups)
const seen = new Map();
for (const c of raw.cards) {
  const key = norm(c.name) + '|' + norm(c.text);
  if (seen.has(key)) { const e = seen.get(key); e._occurrences++; if (!e._widgets.includes(c.widget)) e._widgets.push(c.widget); continue; }
  const roleParts = (c.role || '').split('·').map((x) => x.trim()).filter(Boolean);
  const courseRef = roleParts.find((x) => /^corso\b/i.test(x)) || null;
  const textNoQ = stripQuotes(c.text);
  const m = textNoQ.match(/^((?:La |Corso )?[A-Za-zÀ-ú ]{3,60}?):\s+(.*)$/s); // "Corso Microbiota: ..." prefix
  const e = {
    source: 'sales', name: c.name, role_or_meta: c.role, role: roleParts.filter((x) => x !== courseRef).join(' · ') || null,
    course_referenced: courseRef || (m && /corso|dieta|microbiota|chetogenica/i.test(m[1]) ? m[1].trim() : null),
    rating: c.stars ? (c.stars.match(/★/g) || []).length : null, rating_raw: c.stars, date: null, photo: null,
    text: textNoQ, text_raw: c.text_raw, title: null,
    widget: c.widget, widget_heading: c.widget_heading, group_comment: c.group, _occurrences: 1, _widgets: [c.widget],
  };
  seen.set(key, e); entries.push(e);
}
const nonTestimonialQuotes = [];
for (const q of raw.quotes) {
  const lines = q.text.split('\n').map((x) => x.trim()).filter(Boolean);
  const quote = lines.find((l) => /[“"]/.test(l)) || lines[0];
  const author = lines.filter((l) => l !== quote).join(' ').trim();
  if (!author) { nonTestimonialQuotes.push({ text: q.text, note: 'Citazione senza autore nel blocco (citazione della fondatrice, sezione Chi siamo): esclusa dalle testimonianze' }); continue; }
  const [name, ...rest] = author.split(',');
  entries.push({ source: 'sales', name: name ? name.trim() : null, role_or_meta: rest.join(',').trim() || null, role: rest.join(',').trim() || null, course_referenced: null, rating: null, date: null, photo: null, text: stripQuotes(quote), text_raw: q.html, title: null, widget: 'quote-paragraph', note: 'Citazione in blocco paragrafo GHL (accompagnata da un video Vimeo nella stessa sezione)' });
}
const site = readJSON(path.join(DATA, 'site.json'), { home: { reviews: [] } });
for (const r of site.home.reviews) entries.push({ source: 'home', name: r.name, role_or_meta: r.course_or_meta, role: null, course_referenced: r.course_or_meta || null, rating: null, date: null, photo: r.image, photo_note: 'avatar generico (logo CDF)', text: r.text, text_raw: r.text_raw, title: null, position: r.position });
const courses = readJSON(path.join(DATA, 'courses.json'), []);
for (const c of courses) for (const t of c.testimonials) entries.push({ source: 'course', course_slug: c.clean_slug, course_title: c.title, name: t.name, role_or_meta: null, role: null, course_referenced: c.title, rating: null, date: null, photo: t.image_src, title: t.title, text: t.text, text_raw: t.text_raw });

// cluster duplicates across sources
const clusters = [];
for (const e of entries) {
  const full = [e.title, e.text].filter(Boolean).join(' ');
  let found = null;
  for (const cl of clusters) {
    const r = cl.items[0]; const rf = [r.title, r.text].filter(Boolean).join(' ');
    const sameName = surname(e.name) && surname(e.name) === surname(r.name);
    const sim = jacc(full, rf);
    if ((sameName && sim >= 0.5) || sim >= 0.85) { found = cl; break; }
  }
  if (found) found.items.push(e); else clusters.push({ items: [e] });
}
const unique = clusters.map((cl, i) => {
  const pri = cl.items.find((x) => x.source === 'sales') || cl.items.find((x) => x.source === 'home') || cl.items[0];
  const sources = [...new Set(cl.items.map((x) => x.source === 'course' ? `course:${x.course_slug}` : x.source))];
  return {
    id: i + 1, name: pri.name, role_or_meta: pri.role_or_meta, course_referenced: pri.course_referenced, rating: pri.rating, date: null,
    photo: pri.photo || null, title: pri.title || cl.items.find((x) => x.title)?.title || null, text: pri.text,
    sources, in_multiple_sources: sources.length > 1, in_sales_and_home: sources.includes('sales') && sources.includes('home'),
    variants: cl.items.map(({ _occurrences, _widgets, ...x }) => ({ ...x, occurrences_in_marquee: _occurrences || undefined, widgets: _widgets || undefined })),
  };
});
const bySource = (s) => entries.filter((e) => e.source === s).length;
const out = {
  scraped_at: new Date().toISOString(),
  sales_page: {
    url: SALES, final_url: raw.url, title: raw.title,
    platform: 'GoHighLevel / LeadConnector funnel (header x-ghl-cache / x-ghl-serve-region, asset CDN assets.cdn.filesafe.space, sezioni c-section/c-row/c-column) — NON Thinkific',
    response_headers: Object.fromEntries(Object.entries(headers).filter(([k]) => /x-ghl|server/i.test(k))),
    review_widgets: [...new Set(raw.cards.map((c) => c.widget))].map((w) => ({ widget: w, heading: (raw.cards.find((c) => c.widget === w) || {}).widget_heading, cards_in_dom: raw.cards.filter((c) => c.widget === w).length, unique_cards: new Set(raw.cards.filter((c) => c.widget === w).map((c) => norm(c.name) + '|' + norm(c.text))).size, unique_cards_only_in_this_widget: entries.filter((e) => e.source === 'sales' && e._widgets && e._widgets.length === 1 && e._widgets[0] === w).length })),
    video_testimonials: videos,
    non_testimonial_quotes: nonTestimonialQuotes,
    video_rows_in_script: raw.video_rows_in_script,
    other_content_note: 'La pagina contiene anche: hero "ECCELLENZA NELLA FORMAZIONE / TESTIMONIANZE", sezione CHI SIAMO con statistiche (200+ ore, 14 docenti, 10k+ corsisti), Mission/Vision, citazione della fondatrice, elenco "I nostri docenti" (vedi _sales_page.json: blocks, docenti).',
  },
  counts: { sales_unique: bySource('sales'), sales_cards_in_dom_with_marquee_duplicates: raw.cards.length, home: bySource('home'), course_pages: bySource('course'), all_entries: entries.length, unique_after_dedupe: unique.length, in_multiple_sources: unique.filter((u) => u.in_multiple_sources).length, in_sales_and_home: unique.filter((u) => u.in_sales_and_home).length, video_testimonials: videos.length },
  dedupe_rule: 'Stesso cognome + similarità parole >= 0.5 (min-overlap su parole >2 lettere, titolo+testo), oppure similarità >= 0.85 a prescindere dal nome. Ogni variante conserva il testo esatto della sua fonte.',
  testimonials: unique,
  entries,
};
writeJSON(path.join(DATA, 'testimonianze.json'), out);
console.log(JSON.stringify(out.counts));
console.log('widgets', JSON.stringify(out.sales_page.review_widgets));
console.log('videos', videos.map((v) => `${v.vimeo_id}:${v.title}`).join(' | '));
console.log('multi-source:', unique.filter((u) => u.in_multiple_sources).map((u) => `${u.name} [${u.sources.join(',')}]`).join(' ; '));
console.log('sales docenti:', raw.docenti.length, 'blocks', raw.blocks.length, 'images', imgs.length);
