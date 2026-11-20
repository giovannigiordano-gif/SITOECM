// Step 2: scrape every course page (server HTML, JS disabled -> no carousel clones), download assets.
// Output: data/scrape/courses.json, data/scrape/logos_index.json, assets/{covers,logos,brochures,brochure-previews}
import fs from 'node:fs';
import path from 'node:path';
import {
  BASE, DATA, ASSETS, rel, readJSON, writeJSON, cleanSlug, launch, noJsContext, jsContext, installBrowserHelpers,
  downloadBestImage, driveId, downloadDrivePdf, pdfPages, pdfPreview, sha1, ensureDir, gotoIdle, scrollAll,
} from './lib.mjs';

const ONLY = process.argv[2] || null; // optional: substring filter on slug for debugging
const disc = readJSON(path.join(DATA, '_discovery.json'));
if (!disc) throw new Error('run discover.mjs first');

const decodeEnt = (s) => (s == null ? s : s.replace(/&amp;/g, '&').replace(/&#39;/g, "'").replace(/&quot;/g, '"').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&nbsp;/g, ' '));
const squash = (s) => (s == null ? s : decodeEnt(s).replace(/<br\s*\/?>/gi, ' ').replace(/<[^>]+>/g, '').replace(/[ \t\r\n]+/g, ' ').trim());

const PROFESSIONS = [
  ['medici', /\bmedic[oi](?!\s+veterinari)\b|\bmedici chirurghi\b/i],
  ['biologi', /\bbiolog[oiae]\b/i],
  ['nutrizionisti', /\bnutrizionist[aie]\b/i],
  ['dietisti', /\bdietist[aie]\b/i],
  ['farmacisti', /\bfarmacist[aie]\b/i],
  ['veterinari', /\bveterinar[io]\b/i],
  ['infermieri', /\binfermier[eia]\b/i],
  ['psicologi', /\bpsicolog(?:o|i|a|he)\b/i],
  ['tecnologi alimentari', /\btecnolog[oi] alimentar[ei]\b/i],
  ['fisioterapisti', /\bfisioterapist[aie]\b/i],
  ['odontoiatri', /\bodontoiatr[aie]\b/i],
  ['ostetriche', /\bostetric[aeh]+\b/i],
  ['chinesiologi / scienze motorie', /\bchinesiolog|scienze motorie|laureati in scienze dell'?attivit/i],
  ['personal trainer / preparatori atletici', /personal trainer|preparator[ei] atletic/i],
  ['erboristi', /\berborist[aie]\b/i],
  ['laureandi', /\blaureand[io]\b/i],
  ['studenti', /\bstudent[ie]\b/i],
  ['operatori del settore benessere', /operatori (?:nel|del) settore benessere|operatori del benessere/i],
  ['proprietari di animali', /\bproprietari\b/i],
  ['allevatori', /\ballevator[ie]\b/i],
  ['educatori (cinofili)', /\beducator[ie]\b/i],
  ['privati', /\bprivat[io]\b/i],
  ['professionisti sanitari', /professionisti sanitari|professionisti della salute|operatori sanitari/i],
  ['naturopati', /\bnaturopat[aie]\b/i],
  ['osteopati', /\bosteopat[aie]\b/i],
];

function professionsIn(texts) {
  const found = [];
  for (const [name, re] of PROFESSIONS) {
    const src = texts.filter(([, t]) => t && re.test(t)).map(([k, t]) => { const m = t.match(re); const i = Math.max(0, m.index - 40); return `${k}: …${t.slice(i, m.index + m[0].length + 40).replace(/\s+/g, ' ')}…`; });
    if (src.length) found.push({ profession: name, sources: src });
  }
  return found;
}

function ecmCredits(texts) {
  for (const [k, t] of texts) {
    if (!t) continue;
    const m = t.match(/(\d+(?:[.,]\d+)?)\s*(?:crediti|CREDITI|Crediti)(?:\s*(?:ECM|formativi))?/);
    if (m) { const i = Math.max(0, m.index - 50); return { value: Number(m[1].replace(',', '.')), source: `${k}: …${t.slice(i, m.index + m[0].length + 50).replace(/\s+/g, ' ')}…` }; }
  }
  for (const [k, t] of texts) {
    if (!t) continue;
    const m = t.match(/crediti\s*E\.?C\.?M\.?|accreditat[oi]\s+ECM|\bECM\b/i);
    if (m) { const i = Math.max(0, m.index - 60); return { value: null, source: `${k}: …${t.slice(i, m.index + m[0].length + 60).replace(/\s+/g, ' ')}…` }; }
  }
  return { value: null, source: null };
}

function statusHints(card, texts) {
  const signals = [];
  if (card && /presell/.test(card.card_class)) signals.push({ hint: 'pre-ordine', source: `catalog card class "${card.card_class}" badge "${squash(card.badge_html)}"` });
  const rules = [
    ['pre-ordine', /pre-?ordin[ei]|preordin[ei]|prevendita/i],
    ['in arrivo', /in arrivo|prossimamente|coming soon|presto disponibile|nuova edizione in arrivo/i],
    ['sold out', /sold ?out|esaurit[oi]|posti esauriti|iscrizioni chiuse/i],
    ['iscrizioni aperte', /iscrizioni aperte/i],
    ['on demand', /on[ -]demand|in differita|registrazion[ei] disponibil/i],
    ['live / diretta', /in diretta|live streaming|webinar live|in presenza/i],
  ];
  for (const [hint, re] of rules) for (const [k, t] of texts) { if (t && re.test(t)) { const m = t.match(re); signals.push({ hint, source: `${k}: …${t.slice(Math.max(0, m.index - 40), m.index + m[0].length + 40).replace(/\s+/g, ' ')}…` }); break; } }
  const primary = signals.find((s) => ['pre-ordine', 'in arrivo', 'sold out'].includes(s.hint));
  return { status_hint: primary ? primary.hint : null, status_signals: signals };
}

function priceIn(texts) {
  for (const [k, t] of texts) {
    if (!t) continue;
    const m = t.match(/€\s?\d[\d.,]*|\d[\d.,]*\s?(?:€|euro\b|EUR\b)/i);
    if (m) return { price_text: m[0], source: `${k}: …${t.slice(Math.max(0, m.index - 40), m.index + m[0].length + 40).replace(/\s+/g, ' ')}…` };
  }
  return { price_text: null, source: null };
}

function parseWhatsApp(href) {
  try {
    const u = new URL(href);
    let number = null, text = null;
    if (/wa\.me$/.test(u.host)) { number = u.pathname.replace(/\//g, '') || null; text = u.searchParams.get('text'); }
    else if (/whatsapp\.com$/.test(u.host)) { number = u.searchParams.get('phone'); text = u.searchParams.get('text'); }
    return { number, text };
  } catch { return { number: null, text: null }; }
}

function logoName(src) {
  let p = src.split('?')[0]; try { p = decodeURIComponent(p); } catch { /* */ }
  const file = p.split('/').pop();
  const base = file.replace(/\.[a-z0-9]+$/i, '');
  const human = base.replace(/[_]+/g, ' ').replace(/\s+-\s+/g, ' - ').replace(/\s+/g, ' ').trim();
  return { filename: file, humanized: human };
}

// ------------------------------------------------------------------ extraction (runs in page)
function extractCourse() {
  const S = window.__S;
  const cf = S.decodeCfEmails();
  const meta = (sel) => { const e = document.querySelector(sel); return e ? e.getAttribute('content') : null; };
  const secs = S.topSections().filter((s) => !['header', 'footer'].includes(s.getAttribute('data-preview-item')));
  const sections = secs.map((s, idx) => {
    const type = s.getAttribute('data-preview-item');
    const h = s.querySelector('.section__headings .section__heading, header .section__heading');
    const sub = s.querySelector('.section__headings .section__subheading, header .section__subheading');
    return {
      idx, type, uid: s.getAttribute('data-section-uid'),
      heading: S.flatText(h), heading_html: S.innerTrim(h), subheading: S.flatText(sub), subheading_html: S.innerTrim(sub),
      images: [...s.querySelectorAll('img')].map((i) => ({ src: i.getAttribute('src'), alt: i.getAttribute('alt'), style: i.getAttribute('style'), srcset: i.getAttribute('srcset') })),
      links: [...s.querySelectorAll('a[href]')].map((a) => ({ label: S.flatText(a), href: a.getAttribute('href'), class: a.getAttribute('class') })),
      text: s.innerText.replace(/ /g, ' ').trim(),
      _el: null,
    };
  });
  const elOf = (i) => secs[i];
  const descIdx = sections.findIndex((x) => x.type === 'description' && !/patrocin/i.test(x.heading || ''));
  // cover: rich_text sections with images, no heading, before description
  const coverSecs = sections.filter((x) => x.type === 'rich_text' && !x.heading && x.images.length && (descIdx < 0 || x.idx < descIdx));
  const cover = coverSecs.length ? coverSecs[0].images[0] : null;
  const coverExtra = coverSecs.length ? { other_images: coverSecs[0].images.slice(1), text: coverSecs[0].text, html: S.clean(elOf(coverSecs[0].idx).querySelector('.section__body') || elOf(coverSecs[0].idx), { keepImg: true }) } : null;

  let description = null;
  if (descIdx >= 0) {
    const el = elOf(descIdx);
    const body = el.querySelector('.section__body');
    const notes = [];
    description = {
      section_uid: sections[descIdx].uid,
      title: sections[descIdx].heading, title_html: sections[descIdx].heading_html,
      subtitle: sections[descIdx].subheading, subtitle_html: sections[descIdx].subheading_html,
      raw_html: S.innerTrim(body),
      html: body ? S.richTexty(body, { notes }) : null,
      rendered_text: body ? body.innerText.replace(/ /g, ' ').trim() : null,
      buttons: [...el.querySelectorAll('.section__button-group a')].map((a) => ({ label: S.flatText(a), href: a.getAttribute('href'), class: a.getAttribute('class') })),
      notes,
    };
    description.text = S.htmlToText(description.html || '');
  }

  const testimonialSections = sections.filter((x) => x.type === 'testimonials').map((x) => {
    const el = elOf(x.idx);
    return {
      heading: x.heading, subheading: x.subheading,
      items: [...el.querySelectorAll('.testimonials__list-item')].map((it) => {
        const title = it.querySelector('.testimonial__title');
        const author = it.querySelector('.testimonial__author');
        const img = it.querySelector('img');
        const divs = [...it.children].filter((c) => c.tagName === 'DIV' && !c.querySelector('img'));
        const body = divs[divs.length - 1] || null;
        const html = body ? S.richTexty(body) : null;
        return {
          title: S.flatText(title), name: S.flatText(author), meta_raw: author ? author.innerHTML.trim() : null,
          text_raw: body ? body.innerHTML.trim() : null, text_html: html, text: html ? S.htmlToText(html) : null,
          image_src: img ? img.getAttribute('src') : null,
        };
      }),
    };
  });

  const ctas = sections.filter((x) => x.type === 'call-to-action').map((x) => ({ heading: x.heading, subheading: x.subheading, buttons: x.links }));

  // patrocini: sections whose heading mentions patrocinio + following gallery sections
  const patrocini = [];
  sections.forEach((x, i) => {
    if (!/patrocin/i.test(x.heading || '')) return;
    const group = { heading: x.heading, section_types: [x.type], images: [...x.images], text_around: [] };
    const bodyEl = elOf(x.idx).querySelector('.section__body');
    const bodyText = bodyEl ? bodyEl.innerText.trim() : '';
    if (bodyText) group.text_around.push(bodyText);
    for (let j = i + 1; j < sections.length && sections[j].type === 'gallery' && !sections[j].heading; j++) {
      group.section_types.push('gallery'); group.images.push(...sections[j].images);
      if (sections[j].text) group.text_around.push(sections[j].text);
    }
    patrocini.push(group);
  });

  const faqSec = sections.find((x) => x.type === 'faq');
  let faq = null;
  if (faqSec) {
    const el = elOf(faqSec.idx);
    faq = {
      heading: faqSec.heading, intro: faqSec.subheading, intro_html: faqSec.subheading_html,
      items: [...el.querySelectorAll('.faq__list-item')].map((li) => {
        const sum = li.querySelector('summary');
        const det = li.querySelector('details') || li;
        const rawParts = [...det.children].filter((c) => c.tagName !== 'SUMMARY').map((c) => c.outerHTML).join('');
        const html = S.richTexty(det);
        return { question: S.flatText(sum), answer_raw: rawParts, answer_html: html, answer_text: S.htmlToText(html), open_by_default: det.hasAttribute('open') };
      }),
    };
  }
  const used = new Set();
  if (descIdx >= 0) used.add(descIdx);
  coverSecs.slice(0, 1).forEach((x) => used.add(x.idx));
  sections.forEach((x) => { if (['testimonials', 'call-to-action', 'faq', 'lead_capture'].includes(x.type)) used.add(x.idx); });
  sections.forEach((x, i) => { if (/patrocin/i.test(x.heading || '')) { used.add(i); for (let j = i + 1; j < sections.length && sections[j].type === 'gallery' && !sections[j].heading; j++) used.add(j); } });
  const extra = sections.filter((x) => !used.has(x.idx)).map((x) => ({ type: x.type, heading: x.heading, subheading: x.subheading, text: x.text, images: x.images, links: x.links, html: S.clean(elOf(x.idx), { keepImg: true }) }));

  const mainLinks = [];
  sections.forEach((x) => { if (x.type !== 'lead_capture') x.links.forEach((l) => mainLinks.push({ ...l, section: x.type })); });
  const pageText = sections.filter((x) => x.type !== 'lead_capture').map((x) => x.text).join('\n\n');

  return {
    cf_emails_decoded: cf,
    html_title: document.title, meta_description: meta('meta[name="description"]'), og_title: meta('meta[property="og:title"]'),
    og_image: meta('meta[property="og:image"]'), canonical: (document.querySelector('link[rel=canonical]') || {}).href || null,
    outline: sections.map(({ _el, text, ...r }) => ({ ...r, text_length: text.length })),
    cover, coverExtra, description, testimonialSections, ctas, patrocini, faq, extra, mainLinks, pageText,
    has_newsletter: sections.some((x) => x.type === 'lead_capture'),
  };
}

// optional JS-enabled consistency check
async function jsCheck(ctx, url) {
  const p = await ctx.newPage();
  try {
    await gotoIdle(p, url);
    await scrollAll(p);
    return await p.evaluate(() => {
      const secs = [...document.querySelectorAll('[data-section-uid]')].filter((el) => !el.parentElement.closest('[data-section-uid]'));
      const d = document.querySelector('[data-preview-item="description"] .section__body');
      return { section_types: secs.map((s) => s.getAttribute('data-preview-item')), description_rendered_text: d ? d.innerText.replace(/ /g, ' ').trim() : null };
    });
  } finally { await p.close(); }
}

// ------------------------------------------------------------------ main
const browser = await launch();
const ctx = await noJsContext(browser);
const jctx = await jsContext(browser);
const page = await ctx.newPage();
const courses = [];
const logosIndex = {};
const previous = readJSON(path.join(DATA, 'courses.json'), []);

for (const card of disc.catalog) {
  const original_path = card.href_raw;
  const original_slug = original_path.replace(/^\/courses\//, '');
  const clean_slug = cleanSlug(original_slug);
  if (ONLY && !clean_slug.includes(ONLY)) { const prev = previous.find((c) => c.clean_slug === clean_slug); if (prev) courses.push(prev); continue; }
  const url = BASE + original_path;
  console.log(`\n[${card.order}] ${clean_slug}`);
  const resp = await page.goto(url, { waitUntil: 'load', timeout: 120000 });
  await page.evaluate(installBrowserHelpers);
  const x = await page.evaluate(extractCourse);
  const notes = [];

  // --- slug forms
  let decodedSlug = original_slug; try { decodedSlug = decodeURIComponent(original_slug); } catch { /* */ }
  const slugIsClean = decodedSlug === clean_slug;
  const c = {
    order: card.order,
    catalog_page: card.catalog_page,
    original_url: url,
    original_path,
    original_path_decoded: '/courses/' + decodedSlug,
    original_path_percent_encoded: '/courses/' + encodeURIComponent(decodedSlug).replace(/'/g, '%27'),
    original_slug,
    original_slug_decoded: decodedSlug,
    slug_needs_redirect: !slugIsClean,
    clean_slug,
    http_status: resp.status(),
    final_url: page.url(),
    title: x.description ? x.description.title : null,
    title_html: x.description ? x.description.title_html : null,
    catalog_title: squash(card.name_html),
    subtitle: x.description ? x.description.subtitle : null,
    html_title: x.html_title,
    meta_description: x.meta_description,
    og_title: x.og_title,
    og_image: x.og_image,
    catalog_excerpt: squash(card.desc_html),
    catalog_excerpt_raw: card.desc_html,
    catalog_card_class: card.card_class,
    catalog_badge: squash(card.badge_html) || null,
    catalog_image_url_original: card.img_src,
  };
  if (c.html_title && c.html_title.trim() !== c.title) notes.push(`Titolo SEO <title> diverso dal titolo pagina: "${c.html_title}".`);
  if (c.title !== c.catalog_title) notes.push(`Titolo pagina diverso dal titolo nel catalogo ("${c.catalog_title}").`);

  // --- cover
  c.cover_url_original = x.cover ? x.cover.src : null;
  c.cover_alt = x.cover ? x.cover.alt : null;
  if (x.coverExtra && x.coverExtra.other_images.length) notes.push(`Sezione cover contiene altre ${x.coverExtra.other_images.length} immagini.`);
  if (x.cover) {
    const d = await downloadBestImage(x.cover.src, path.join(ASSETS, 'covers', clean_slug));
    c.cover_url_best = d.ok ? d.url_best : null;
    c.cover_file = d.ok ? d.rel : null;
    c.cover_width = d.ok ? d.width : null; c.cover_height = d.ok ? d.height : null;
    c.cover_bytes = d.ok ? d.bytes : null; c.cover_sha1 = d.ok ? d.sha1 : null;
    c.cover_download_tries = d.tries;
  } else { c.cover_url_best = c.cover_file = c.cover_width = c.cover_height = null; notes.push('Nessuna immagine cover trovata nella pagina.'); }
  // catalog card image (often a different/resized upload)
  if (card.img_src) {
    const d = await downloadBestImage(card.img_src, path.join(ASSETS, 'covers', 'cards', clean_slug));
    c.catalog_image_url_best = d.ok ? d.url_best : null;
    c.catalog_image_file = d.ok ? d.rel : null;
    c.catalog_image_width = d.ok ? d.width : null; c.catalog_image_height = d.ok ? d.height : null;
    c.catalog_image_sha1 = d.ok ? d.sha1 : null;
    if (d.ok && c.cover_sha1 && d.sha1 === c.cover_sha1) c.catalog_image_same_as_cover = true;
    else c.catalog_image_same_as_cover = false;
    if (d.ok && c.cover_width && d.width * d.height > c.cover_width * c.cover_height) notes.push(`Immagine card catalogo (${d.width}x${d.height}) più grande della cover pagina (${c.cover_width}x${c.cover_height}).`);
  }

  // --- description
  const D = x.description;
  c.description_raw_html = D ? D.raw_html : null;
  c.description_html = D ? D.html : null;
  c.description_text = D ? D.text : null;
  c.description_rendered_text = D ? D.rendered_text : null;
  c.word_count = D && D.text ? D.text.split(/\s+/).filter((w) => /[\p{L}\p{N}]/u.test(w)).length : 0;
  if (D && D.raw_html && /\n/.test(D.raw_html) && !/<br/i.test(D.raw_html)) notes.push('La descrizione contiene a-capo nel sorgente che il sito attuale NON mostra (white-space:normal): description_html: riga vuota => nuovo <p>; a-capo singolo => <br> se la riga precedente termina con punteggiatura, altrimenti spazio (a-capo "morbido").');
  else if (D && D.raw_html && /\n/.test(D.raw_html)) notes.push('La descrizione mescola <br> e a-capo nel sorgente; gli a-capo non sono visibili sul sito attuale (white-space:normal).');
  if (D && D.notes.length) notes.push('Pulizia HTML: ' + D.notes.join('; '));

  // --- WhatsApp
  const waLinks = x.mainLinks.filter((l) => /wa\.me|whatsapp/i.test(l.href));
  const wa = waLinks[0] || null;
  c.whatsapp_url = wa ? wa.href : null;
  c.whatsapp_label = wa ? wa.label : null;
  const pw = wa ? parseWhatsApp(wa.href) : { number: null, text: null };
  c.whatsapp_text = pw.text; c.whatsapp_number = pw.number;
  if (waLinks.length > 1) c.whatsapp_other = waLinks.slice(1);
  const expectedWa = `Salve, vorrei avere maggiori informazioni sul corso "${c.title}"`;
  c.whatsapp_text_matches_title = pw.text === expectedWa;
  if (wa && pw.text !== expectedWa) notes.push(`Testo WhatsApp precompilato NON corrisponde al titolo: "${pw.text}".`);

  // --- brochure
  const ctaButtons = x.ctas.flatMap((t) => t.buttons);
  const broch = ctaButtons.find((l) => /brochure/i.test(l.label) || /drive\.google|docs\.google/.test(l.href)) || x.mainLinks.find((l) => /brochure/i.test(l.label));
  c.brochure_section = x.ctas[0] ? { heading: x.ctas[0].heading, subheading: x.ctas[0].subheading } : null;
  c.brochure_label = broch ? broch.label : null;
  c.brochure_url_original = broch ? broch.href : null;
  c.brochure_drive_id = broch ? driveId(broch.href) : null;
  c.brochure_file = null; c.brochure_bytes = null; c.brochure_pages = null; c.brochure_ok = false; c.brochure_preview = null;
  if (c.brochure_drive_id) {
    const out = path.join(ASSETS, 'brochures', `${clean_slug}.pdf`);
    const r = await downloadDrivePdf(c.brochure_drive_id, out);
    c.brochure_download = { ok: r.ok, via: r.via || null, attempts: r.attempts };
    if (r.ok) {
      c.brochure_file = r.rel; c.brochure_bytes = r.bytes; c.brochure_sha1 = r.sha1;
      c.brochure_ok = fs.readFileSync(out).slice(0, 4).toString() === '%PDF';
      c.brochure_pages = pdfPages(out);
      try { c.brochure_preview = rel(pdfPreview(out, path.join(ASSETS, 'brochure-previews', clean_slug))); } catch (e) { notes.push('Anteprima brochure fallita: ' + e.message); }
    } else notes.push('Download brochure FALLITO.');
  } else if (broch) notes.push('Link brochure non è un file Google Drive: ' + broch.href);
  else notes.push('Nessun pulsante brochure trovato.');

  // --- patrocini
  c.patrocini = [];
  c.patrocini_note = x.patrocini.length ? x.patrocini.map((g) => [g.heading, ...g.text_around].filter(Boolean).join(' | ')).join(' || ') : null;
  c.patrocini_heading = x.patrocini.length ? x.patrocini[0].heading : null;
  c.patrocini_section_types = x.patrocini.length ? x.patrocini.flatMap((g) => g.section_types) : [];
  let n = 0;
  for (const g of x.patrocini) for (const img of g.images) {
    n++;
    const ln = logoName(img.src);
    const nameSlug = cleanSlug(ln.humanized).slice(0, 60) || 'logo';
    const d = await downloadBestImage(img.src, path.join(ASSETS, 'logos', clean_slug, `${String(n).padStart(2, '0')}-${nameSlug}`));
    const entry = { image_url: img.src, image_url_best: d.ok ? d.url_best : null, file: d.ok ? d.rel : null, width: d.ok ? d.width : null, height: d.ok ? d.height : null, sha1: d.ok ? d.sha1 : null, alt: img.alt, style: img.style, filename: ln.filename, name: null, name_from_filename: ln.humanized };
    c.patrocini.push(entry);
    if (d.ok) {
      const li = (logosIndex[d.sha1] ||= { sha1: d.sha1, first_file: d.rel, width: d.width, height: d.height, bytes: d.bytes, name: null, filenames: [], urls: [], used_by: [] });
      if (!li.filenames.includes(ln.filename)) li.filenames.push(ln.filename);
      if (!li.urls.includes(img.src)) li.urls.push(img.src);
      li.used_by.push({ course: clean_slug, file: d.rel, position: n });
    }
  }

  // --- testimonials
  c.testimonials_heading = x.testimonialSections.length ? x.testimonialSections[0].heading : null;
  c.testimonials = x.testimonialSections.flatMap((t) => t.items.map((it) => ({ title: it.title, name: it.name || null, role_or_meta: null, text: it.text, text_html: it.text_html, text_raw: it.text_raw, image_src: it.image_src })));

  // --- FAQ
  c.faq_heading = x.faq ? x.faq.heading : null;
  c.faq_intro = x.faq ? x.faq.intro : null;
  c.faq = x.faq ? x.faq.items.map((f) => ({ question: f.question, answer_html: f.answer_html, answer_text: f.answer_text, answer_raw: f.answer_raw })) : [];
  const perChi = c.faq.find((f) => /^per chi/i.test(f.question));
  c.per_chi_e_adatto_question = perChi ? perChi.question : null;
  c.per_chi_e_adatto_html = perChi ? perChi.answer_html : null;
  c.per_chi_e_adatto_text = perChi ? perChi.answer_text : null;
  if (perChi && perChi.question !== 'Per chi è adatto questo corso?') notes.push(`Domanda FAQ "per chi" formulata come: "${perChi.question}".`);

  // --- derived
  const texts = [['title', c.title], ['catalog_title', c.catalog_title], ['subtitle', c.subtitle], ['description', c.description_text], ['catalog_excerpt', c.catalog_excerpt],
    ...c.faq.map((f, i) => [`faq${i + 1}`, `${f.question} ${f.answer_text}`]), ['cover_section_text', x.coverExtra ? x.coverExtra.text : null], ['extra_sections', x.extra.map((e) => e.text).join(' ')]];
  const ecm = ecmCredits(texts);
  c.ecm_credits = ecm.value; c.ecm_credits_source = ecm.source;
  Object.assign(c, statusHints(card, texts));
  const price = priceIn([['page', x.pageText], ['title', c.title]]);
  c.price_text = price.price_text; c.price_source = price.source;
  const prof = professionsIn([['title', c.title], ['subtitle', c.subtitle], ['description', c.description_text], ['per_chi_e_adatto', c.per_chi_e_adatto_text], ...c.faq.filter((f) => f !== perChi).map((f, i) => [`faq`, f.answer_text])]);
  c.target_professions_mentioned = prof.map((p) => p.profession);
  c.target_professions_detail = prof;

  // --- other CTAs (everything except WhatsApp + brochure)
  c.other_ctas = x.mainLinks.filter((l) => !/wa\.me|whatsapp/i.test(l.href) && l !== broch && !(broch && l.href === broch.href)).map((l) => ({ label: l.label, href: l.href, section: l.section }));
  c.extra_sections = x.extra;
  if (x.extra.length) notes.push(`Sezioni non classificate: ${x.extra.map((e) => e.type + (e.heading ? ` "${e.heading}"` : '')).join(', ')}.`);
  c.section_outline = x.outline.map((o) => ({ type: o.type, uid: o.uid, heading: o.heading, subheading: o.subheading, images: o.images.length, links: o.links.map((l) => l.label), text_length: o.text_length }));
  c.cover_section_html = x.coverExtra ? x.coverExtra.html : null;
  c.has_newsletter_section = x.has_newsletter;
  c.cf_emails_decoded = x.cf_emails_decoded;

  // JS-enabled consistency check
  try {
    const j = await jsCheck(jctx, url);
    const jsTypes = j.section_types.filter((t) => !['header', 'footer'].includes(t)).join(',');
    const noJsTypes = x.outline.map((o) => o.type).join(',');
    c.js_dom_check = { same_sections: jsTypes === noJsTypes, js_section_types: jsTypes, description_rendered_same: j.description_rendered_text === c.description_rendered_text };
    if (!c.js_dom_check.same_sections) notes.push('DOM con JS diverso dal sorgente server: ' + jsTypes);
  } catch (e) { c.js_dom_check = { error: String(e) }; }

  c.notes = notes;
  courses.push(c);
  console.log(`  title="${c.title}" words=${c.word_count} cover=${!!c.cover_file} (${c.cover_width}x${c.cover_height}) brochure=${c.brochure_ok}/${c.brochure_pages}p patrocini=${c.patrocini.length} testi=${c.testimonials.length} faq=${c.faq.length} wa=${!!c.whatsapp_url}`);
}
await browser.close();

courses.sort((a, b) => a.order - b.order);
writeJSON(path.join(DATA, 'courses.json'), courses);
if (false) {
  const prevIdx = readJSON(path.join(DATA, 'logos_index.json'), { logos: [] });
  const prevNames = Object.fromEntries((prevIdx.logos || []).map((l) => [l.sha1, l.name]));
  const logos = Object.values(logosIndex).map((l) => ({ ...l, name: prevNames[l.sha1] ?? l.name }));
  writeJSON(path.join(DATA, 'logos_index.json'), { generated_at: new Date().toISOString(), unique_logos: logos.length, logos });
}
console.log('\nDONE', courses.length, 'courses');
