// Step 8: enrich scraped JSON (logo names, logos index, cross references, extra notes). Idempotent.
import path from 'node:path';
import fs from 'node:fs';
import { DATA, ROOT, readJSON, writeJSON } from './lib.mjs';

const courses = readJSON(path.join(DATA, 'courses.json'));
const logoNames = readJSON(path.join(ROOT, 'tools', 'scrape', 'logo-names.json'), {});

// ---- logos
const index = {};
for (const c of courses) {
  for (const [i, l] of c.patrocini.entries()) {
    const info = logoNames[l.sha1] || {};
    l.name = info.name ?? null;
    l.name_source = info.name ? 'letto visivamente dall\'immagine del logo (nessun alt sul sito)' : null;
    if (info.composite) { l.composite_image = true; l.contains_entities = info.contains; }
    if (!l.sha1) continue;
    const e = (index[l.sha1] ||= { sha1: l.sha1, name: info.name ?? null, composite_image: !!info.composite, contains_entities: info.contains || null, canonical_file: l.file, width: l.width, height: l.height, filenames: [], urls: [], used_by: [] });
    if (!e.filenames.includes(l.filename)) e.filenames.push(l.filename);
    if (!e.urls.includes(l.image_url)) e.urls.push(l.image_url);
    e.used_by.push({ course: c.clean_slug, position: i + 1, file: l.file });
  }
}
const logos = Object.values(index);
writeJSON(path.join(DATA, 'logos_index.json'), {
  generated_at: new Date().toISOString(),
  unique_files: logos.length,
  unique_entities: [...new Set(logos.flatMap((l) => (l.contains_entities || [l.name]).filter(Boolean)))].sort(),
  note: 'Dedup per sha1 del file scaricato. Le due immagini del corso BUNDLE sono banner compositi con più loghi (contains_entities).',
  logos,
});

// ---- extra per-course notes / flags
const addNote = (c, n) => { if (!c.notes.includes(n)) c.notes.push(n); };
for (const c of courses) {
  if (!c.description_text) addNote(c, 'DESCRIZIONE VUOTA sulla pagina corso (sezione description senza testo): esiste solo il testo breve della card di catalogo.');
  if (/[A-ZÀ-Ú]{3,}(DEL|DELLA|NEL|E)[A-Z]/.test(c.title || '') && /ALIMENTAZIONEDEL/.test(c.title)) addNote(c, 'Titolo con parole unite: "ALIMENTAZIONEDEL" (manca spazio).');
  if (c.title_html && /<br>/i.test(c.title_html)) addNote(c, `Il titolo contiene <br> nel markup: "${c.title_html}"`);
  if (c.slug_needs_redirect) addNote(c, `Slug originale con maiuscole/spazi/punteggiatura: serve redirect 301 da "${c.original_path}" (decodificato: "${c.original_path_decoded}").`);
  if (c.catalog_title && c.catalog_title !== c.title) c.catalog_title_differs = true; else c.catalog_title_differs = false;
  c.cover_ok = !!c.cover_file;
}
// ---- brochure text hints (pdftotext): ECM credits, hours, delivery mode — kept separate from page-derived fields
import { execFileSync } from 'node:child_process';
const txtDir = path.join(DATA, 'assets', 'brochure-text');
fs.mkdirSync(txtDir, { recursive: true });
for (const c of courses) {
  if (!c.brochure_file) continue;
  const pdf = path.join(ROOT, c.brochure_file);
  let text = '';
  try { text = execFileSync('pdftotext', ['-enc', 'UTF-8', '-layout', pdf, '-'], { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 }); } catch { text = ''; }
  const out = path.join(txtDir, `${c.clean_slug}.txt`);
  fs.writeFileSync(out, text, 'utf8');
  const flat = text.replace(/\s+/g, ' ');
  const credits = [...flat.matchAll(/(\d+(?:[.,]\d+)?)\s*CREDITI\s*E\.?C\.?M/gi)].map((m) => Number(m[1].replace(',', '.')));
  const hours = [...flat.matchAll(/(\d+)\s*ORE\s*TOTALI/gi)].map((m) => Number(m[1]));
  const days = [...flat.matchAll(/(\d+)\s*GIORNAT[EA]/gi)].map((m) => Number(m[1]));
  const modes = [...new Set([...flat.matchAll(/\b(on[ -]?demand|in diretta|live|webinar|in presenza|FAD|e-learning|streaming)\b/gi)].map((m) => m[1].toUpperCase().replace(/^ON[ -]?DEMAND$/, 'ON DEMAND')))];
  const prices = [...new Set([...flat.matchAll(/€\s?\d[\d.,]*|\d[\d.,]*\s?(?:€|euro\b)/gi)].map((m) => m[0]))];
  c.brochure_text_file = path.relative(ROOT, out).split(path.sep).join('/');
  c.brochure_text_chars = text.trim().length;
  c.brochure_hints = {
    source: 'testo estratto dal PDF brochure (pdftotext), NON presente nella pagina web',
    ecm_credits: credits.length ? Math.max(...credits) : null,
    no_ecm_credits: /NO\s+CREDITI\s+ECM/i.test(flat),
    ecm_label_without_number: !credits.length && !/NO\s+CREDITI\s+ECM/i.test(flat) && /CREDITI\s+E\.?C\.?M/i.test(flat),
    total_hours: hours.length ? hours[0] : null,
    days: days.length ? days[0] : null,
    modes,
    price_mentions: prices,
  };
  if (!text.trim()) c.brochure_hints.note = 'PDF senza testo estraibile (solo immagini)';
}
writeJSON(path.join(DATA, 'courses.json'), courses);

// ---- docenti cross reference with the sales page list
const docJ = readJSON(path.join(DATA, 'docenti.json'));
const sales = readJSON(path.join(DATA, '_sales_page.json'), null);
const norm = (s) => (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z ]+/g, ' ').replace(/\s+/g, ' ').trim();
const strip = (s) => norm(s).replace(/\b(prof ssa|prof|professoressa|dott ssa|dott|dottoressa|dottor|dr)\b/g, '').trim();
if (sales && docJ) {
  const sd = sales.docenti.map((d) => ({ heading: d.name, text: d.text, image: d.image, tokens: strip(d.name).split(' ') }));
  for (const d of docJ.docenti) {
    const toks = strip(d.heading_exact).split(' ');
    const m = sd.find((s) => toks.filter((t) => t.length > 2).every((t) => s.tokens.includes(t)));
    d.sales_page = m ? { heading: m.heading, text: m.text, image: m.image } : null;
    if (m && norm(m.heading.split(' ')[0]) !== norm(d.heading_exact.split(' ')[0])) d.notes = [...new Set([...(d.notes || []), `Titolo diverso sulla pagina vendite GHL: "${m.heading}" vs pagina docenti "${d.heading_exact}".`])];
  }
  const onlySales = sd.filter((s) => !docJ.docenti.some((d) => d.sales_page && d.sales_page.heading === s.heading)).map((s) => ({ heading: s.heading, text: s.text, image: s.image }));
  docJ.sales_page_crossref = {
    source: sales.url,
    sales_page_teachers: sd.length,
    matched: docJ.docenti.filter((d) => d.sales_page).length,
    only_on_docenti_page: docJ.docenti.filter((d) => !d.sales_page).map((d) => d.heading_exact),
    only_on_sales_page: onlySales,
    note: 'La pagina vendite (GoHighLevel) ha un elenco "I nostri docenti" più lungo, con titoli (Prof./Dott.) spesso diversi dalla pagina /pages/docenti di Thinkific. Le foto sono su assets.cdn.filesafe.space e NON sono state scaricate (solo URL).',
  };
  writeJSON(path.join(DATA, 'docenti.json'), docJ);
}
console.log('logos unique files', logos.length, 'entities', [...new Set(logos.flatMap((l) => (l.contains_entities || [l.name]).filter(Boolean)))].length);
console.log('docenti crossref', docJ.sales_page_crossref && JSON.stringify({ m: docJ.sales_page_crossref.matched, onlyDoc: docJ.sales_page_crossref.only_on_docenti_page, onlySales: docJ.sales_page_crossref.only_on_sales_page.map((x) => x.heading) }));
