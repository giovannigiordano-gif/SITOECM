// Step 9: validation summary + docs/inventario.md (Italian)
import fs from 'node:fs';
import path from 'node:path';
import { DATA, ROOT, readJSON, writeJSON } from './lib.mjs';

const courses = readJSON(path.join(DATA, 'courses.json'));
const site = readJSON(path.join(DATA, 'site.json'));
const doc = readJSON(path.join(DATA, 'docenti.json'));
const pages = readJSON(path.join(DATA, 'pages.json'));
const testi = readJSON(path.join(DATA, 'testimonianze.json'));
const disc = readJSON(path.join(DATA, '_discovery.json'));
const logos = readJSON(path.join(DATA, 'logos_index.json'));
const typos = readJSON(path.join(ROOT, 'tools', 'scrape', 'typos-manual.json'), []);
const shots = readJSON(path.join(ROOT, 'docs', 'screenshot', 'original', 'index.json'), { shots: [] });
const exists = (rel) => !!rel && fs.existsSync(path.join(ROOT, rel));
const kb = (b) => (b == null ? '' : b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.round(b / 1024) + ' KB');
const esc = (s) => String(s ?? '').replace(/\|/g, '\\|').replace(/\n/g, ' ');

// ---------------------------------------------------------------- validation
const checks = {
  title: (c) => !!c.title,
  cover_file: (c) => exists(c.cover_file),
  description: (c) => !!(c.description_text && c.description_text.trim()),
  whatsapp: (c) => !!c.whatsapp_url,
  whatsapp_text_matches_title: (c) => c.whatsapp_text_matches_title,
  brochure_ok: (c) => c.brochure_ok && exists(c.brochure_file),
  brochure_preview: (c) => exists(c.brochure_preview),
  faq: (c) => c.faq.length > 0,
  per_chi_e_adatto: (c) => !!c.per_chi_e_adatto_html,
  js_dom_consistent: (c) => c.js_dom_check && c.js_dom_check.same_sections,
};
const validation = {};
for (const [k, fn] of Object.entries(checks)) {
  const fail = courses.filter((c) => !fn(c)).map((c) => c.clean_slug);
  validation[k] = { ok: courses.length - fail.length, fail };
}
const logoFilesOk = courses.flatMap((c) => c.patrocini).every((l) => exists(l.file));
validation.patrocini_files = { ok: courses.flatMap((c) => c.patrocini).filter((l) => exists(l.file)).length, total: courses.flatMap((c) => c.patrocini).length };
validation.docenti_photos = { ok: doc.docenti.filter((d) => exists(d.photo_file)).length, total: doc.docenti.length };
validation.docenti_cv = { downloadable: doc.docenti.filter((d) => d.cv_downloadable && exists(d.cv_file)).length, total: doc.docenti.length };
validation.typos_snippets_verified = typos.map((t) => {
  const pool = t.course === '*' ? courses : courses.filter((c) => c.clean_slug === t.course);
  const hit = pool.some((c) => [c.title, c.subtitle, c.description_text, c.catalog_excerpt, ...c.faq.map((f) => f.question + '\n' + f.answer_text)].some((x) => x && x.includes(t.snippet)));
  return { ...t, found: hit };
});
writeJSON(path.join(DATA, 'validation.json'), { generated_at: new Date().toISOString(), courses: courses.length, validation });

console.log('\n=== VALIDATION SUMMARY ===');
for (const [k, v] of Object.entries(validation)) {
  if (Array.isArray(v)) continue;
  if ('fail' in v) console.log(`${k.padEnd(28)} ${v.ok}/${courses.length}${v.fail.length ? '  FAIL: ' + v.fail.join(', ') : ''}`);
  else console.log(`${k.padEnd(28)} ${JSON.stringify(v)}`);
}
const tv = validation.typos_snippets_verified.filter((t) => !t.found);
console.log(`typo snippets verified       ${typos.length - tv.length}/${typos.length}${tv.length ? '  NOT FOUND: ' + tv.map((t) => t.snippet).join(' | ') : ''}`);

// ---------------------------------------------------------------- special characters
const special = [];
const ODD = [[' ', 'NBSP U+00A0'], [' ', 'LINE SEPARATOR U+2028'], [' ', 'PARAGRAPH SEPARATOR U+2029'], ['​', 'ZERO WIDTH SPACE U+200B'], ['﻿', 'BOM U+FEFF'], ['­', 'SOFT HYPHEN U+00AD'], ['ﬀ', 'legatura ff U+FB00'], ['ﬁ', 'legatura fi U+FB01'], ['ﬂ', 'legatura fl U+FB02'], ['‎', 'LRM U+200E'], [' ', 'THIN SPACE U+2009']];
const scan = (label, text) => {
  if (!text) return;
  const t = text.replace(/&nbsp;/g, ' ');
  const found = ODD.map(([ch, name]) => [name, t.split(ch).length - 1]).filter(([, n]) => n);
  if (found.length) special.push(`${label}: ${found.map(([n, k]) => `${k}× ${n}`).join(', ')}`);
};
for (const c of courses) {
  scan(`corso ${c.clean_slug} (titolo/sottotitolo)`, [c.title, c.subtitle].join(' '));
  scan(`corso ${c.clean_slug} (descrizione)`, c.description_raw_html);
  scan(`corso ${c.clean_slug} (estratto catalogo)`, c.catalog_excerpt_raw);
  scan(`corso ${c.clean_slug} (FAQ)`, c.faq.map((f) => f.question + ' ' + f.answer_raw).join(' '));
  scan(`corso ${c.clean_slug} (testimonianze)`, c.testimonials.map((t) => (t.title || '') + ' ' + (t.text_raw || '')).join(' '));
  const dbl = (c.description_raw_html || '').match(/[^\s] {2,}[^\s]/g);
  if (dbl) special.push(`corso ${c.clean_slug}: ${dbl.length} doppi spazi nel sorgente descrizione`);
}
for (const d of doc.docenti) scan(`docente ${d.heading_exact} (bio)`, d.bio_raw);
for (const [slug, pg] of Object.entries(pages.pages)) scan(`pagina ${slug}`, (pg.sections || []).map((x) => x.raw_html || x.html || '').join(' '));
for (const r of site.home.reviews) scan(`home recensione ${r.name}`, r.text_raw);

// ---------------------------------------------------------------- markdown
const L = [];
const P = (s = '') => L.push(s);
const now = new Date().toISOString().slice(0, 10);
P('# Inventario contenuti — sito attuale corsidiformazionecm.it');
P();
P(`Scraping eseguito il ${now} (sola lettura, nessun form inviato, nessun login) con gli script in \`tools/scrape/\`. Dati completi in \`data/scrape/*.json\`, asset in \`data/scrape/assets/\`, screenshot in \`docs/screenshot/original/\`.`);
P();
P('Per rigenerare tutto: `cd tools/scrape && node discover.mjs && node courses.mjs && node site.mjs && node testimonianze.mjs && node docenti.mjs && node pages.mjs && node postprocess.mjs && node screenshots.mjs && node report.mjs`.');
P();
P('## 1. Riepilogo');
P();
const totalPatr = courses.reduce((a, c) => a + c.patrocini.length, 0);
P(`| Voce | Valore |`);
P(`|---|---|`);
P(`| Corsi nel catalogo (/collections, ${disc.catalog_pages.filter((p) => p.count).length} pagine × 6) | **${courses.length}** |`);
P(`| Corsi trovati fuori catalogo | ${disc.courses_not_in_catalog.length ? disc.courses_not_in_catalog.map((x) => x.url).join(', ') : 'nessuno (sitemap, crawl completo di tutti i link interni e pagina vendite coincidono con il catalogo)'} |`);
P(`| Cover scaricate | ${validation.cover_file.ok}/${courses.length} |`);
P(`| Brochure PDF scaricate (iniziano con %PDF) | ${validation.brochure_ok.ok}/${courses.length} |`);
P(`| Corsi con patrocini | ${courses.filter((c) => c.patrocini.length).length} (${totalPatr} immagini, ${logos.unique_files} file unici, ${logos.unique_entities.length} enti distinti) |`);
P(`| Corsi con testimonianze proprie | ${courses.filter((c) => c.testimonials.length).length} (${courses.reduce((a, c) => a + c.testimonials.length, 0)} testimonianze) |`);
P(`| Docenti (/pages/docenti) | ${doc.count} (CV scaricabili: ${doc.cv_downloadable_count}) |`);
P(`| Testimonianze uniche (tutte le fonti, deduplicate) | ${testi.counts.unique_after_dedupe} + ${testi.counts.video_testimonials} video Vimeo |`);
P(`| Pagine /pages/* trovate | ${pages.pages_found.length} |`);
P();
P('## 2. Validazione per campo (corsi)');
P();
P('| Controllo | OK | Problemi |');
P('|---|---|---|');
for (const [k, v] of Object.entries(validation)) { if ('fail' in v) P(`| ${k} | ${v.ok}/${courses.length} | ${v.fail.join(', ') || '—'} |`); }
P(`| file loghi patrocinio | ${validation.patrocini_files.ok}/${validation.patrocini_files.total} | — |`);
P();
P('## 3. Tabella corsi (ordine del catalogo)');
P();
P('| # | Titolo (pagina corso) | clean_slug | Path originale | Parole | Cover | Brochure | Patr. | Testim. | FAQ | Stato | ECM pagina / brochure | Ore · modalità (brochure) | Professioni citate |');
P('|---|---|---|---|---|---|---|---|---|---|---|---|---|---|');
for (const c of courses) {
  P(`| ${c.order} | ${esc(c.title)}${c.catalog_title_differs ? `<br>*catalogo: ${esc(c.catalog_title)}*` : ''} | \`${c.clean_slug}\` | \`${esc(c.original_path)}\` | ${c.word_count} | ${c.cover_file ? `✓ ${c.cover_width}×${c.cover_height}` : '✗'} | ${c.brochure_ok ? `✓ ${kb(c.brochure_bytes)}, ${c.brochure_pages} pp.` : '✗'} | ${c.patrocini.length} | ${c.testimonials.length} | ${c.faq.length} | ${c.status_hint || '—'} | ${c.ecm_credits ?? '—'} / ${c.brochure_hints ? (c.brochure_hints.ecm_credits ?? (c.brochure_hints.no_ecm_credits ? 'NO ECM' : c.brochure_hints.ecm_label_without_number ? 'etichetta senza numero' : '?')) : '?'} | ${c.brochure_hints ? [c.brochure_hints.total_hours ? c.brochure_hints.total_hours + ' h' : null, c.brochure_hints.days ? c.brochure_hints.days + ' gg' : null, c.brochure_hints.modes.join('/') || null].filter(Boolean).join(' · ') || (c.brochure_hints.note ? 'PDF solo immagini' : '—') : '—'} | ${esc(c.target_professions_mentioned.join(', '))} |`);
}
P();
P('Note: "Parole" = parole della sola descrizione (sezione `description`). Le professioni sono quelle nominate esplicitamente nel testo della pagina (quasi sempre nella FAQ "Per chi…"). "ECM pagina" = campo `ecm_credits`, valorizzato solo dove il numero di crediti compare nel testo della pagina/card (solo 2 casi, nel titolo della card). "ECM brochure", ore e modalità sono ricavati dal testo dei PDF brochure (`brochure_hints`, testo completo in `data/scrape/assets/brochure-text/`) e NON sono sul sito.');
P();
P('### 3.1 Redirect 301 necessari');
P();
P('| Corso | Path originale (come in href) | Path decodificato | Path percent-encoded completo | Nuovo slug |');
P('|---|---|---|---|---|');
for (const c of courses.filter((x) => x.slug_needs_redirect)) P(`| ${c.order} | \`${c.original_path}\` | \`${c.original_path_decoded}\` | \`${c.original_path_percent_encoded}\` | \`${c.clean_slug}\` |`);
P();
P('Tutti gli altri 25 slug originali sono già minuscoli/ASCII e coincidono con `clean_slug` (nessun redirect di slug necessario, a meno di rinominarli: es. `caneegatto2`, `privato-corso-cane-e-gatto-230`, `alimentazione-nel-gatto-completo` hanno slug poco coerenti col titolo).');
P();
P('### 3.2 Anomalie per corso (dal campo `notes`)');
P();
for (const c of courses) {
  const n = c.notes.filter((x) => !/^La descrizione (contiene|mescola)/.test(x) && !/^Slug originale/.test(x));
  if (n.length) { P(`- **${c.order}. ${c.clean_slug}**`); n.forEach((x) => P(`  - ${x}`)); }
}
P();
const excerptDup = [];
for (const c of courses) { const same = courses.filter((o) => o !== c && o.catalog_excerpt && o.catalog_excerpt === c.catalog_excerpt); if (same.length) excerptDup.push(`${c.clean_slug} = ${same.map((o) => o.clean_slug).join(', ')}`); }
const descDup = [];
for (const c of courses) { const same = courses.filter((o) => o !== c && o.description_text && o.description_text === c.description_text); if (same.length) descDup.push(`${c.clean_slug} = ${same.map((o) => o.clean_slug).join(', ')}`); }
P('Testi duplicati tra corsi:');
P();
P(`- Estratto card di catalogo identico: ${excerptDup.length ? excerptDup.join('; ') : 'nessuno'}`);
P(`- Descrizione identica: ${descDup.length ? descDup.join('; ') : 'nessuna'}`);
P(`- Estratti di catalogo palesemente sbagliati (copiati da altri corsi): \`microbiota\` usa l'estratto del corso cane&gatto; \`bundle-microbiota-ed-epigenetica…\` usa quello della dieta chetogenica. Estratti troncati a metà frase (limite Thinkific): ${courses.filter((c) => c.catalog_excerpt && !/[.!?…)]$/.test(c.catalog_excerpt.trim())).map((c) => c.clean_slug).join(', ')}.`);
P();
P('## 4. Struttura DOM / selettori (Thinkific)');
P();
P('Il contenuto è stato estratto dall\'HTML servito dal server (contesto Playwright con JavaScript disattivato) per evitare i cloni dei caroselli Owl; per ogni corso è stato verificato che il DOM con JS abbia la stessa sequenza di sezioni (`js_dom_check`). Le email sono offuscate da Cloudflare (`/cdn-cgi/l/email-protection`, `data-cfemail`) e sono state decodificate con lo stesso algoritmo di Cloudflare.');
P();
P('- Ogni pagina è una sequenza di `<section data-preview-item="…" data-section-uid="…">`.');
P('- **Cover**: prima sezione `rich_text` senza titolo → `.fr-view img` (file su `files.cdn.thinkific.com`, originale senza parametri di resize). La card di catalogo usa un\'immagine diversa su `import.cdn.thinkific.com` (scaricata in `assets/covers/cards/`).');
P('- **Titolo + sottotitolo + descrizione + WhatsApp**: sezione `description` → `.section__heading` (h2, a volte con `<br>`), `.section__subheading`, `.section__body` (testo semplice con `<br>` e a-capo; gli a-capo NON sono visibili sul sito perché `white-space: normal`), `.section__button-group a.button-primary` (wa.me).');
P('- **Testimonianze corso**: sezione `testimonials` → `.testimonials__list-item` con `h3.testimonial__title` (titolo/frase), `p.testimonial__author` (nome), `div` (testo).');
P('- **Brochure**: sezione `call-to-action` "Vuoi saperne di più?" → `a.button-secondary` "BROCHURE PDF" (Google Drive).');
P('- **Patrocini**: sezione `rich_text` con heading "Con il patrocinio di" e immagini inline (2 corsi), oppure (corso BUNDLE) sezione `description` vuota con heading "Con il Patrocinio di:" seguita da 2 sezioni `gallery` (banner compositi multi-logo). Nessun logo ha `alt`: i nomi degli enti sono stati letti dalle immagini (`logos_index.json`).');
P('- **FAQ**: sezione `faq` → sottotitolo con email, `.faq__list-item details[open] summary strong` (domanda) + `p` (risposta). Tutte le FAQ sono già aperte (`open`).');
P('- **Newsletter**: sezione `lead_capture` (form Thinkific `/tenant_leads`) su home, corsi e contatti.');
P();
P('| Corso | Sezioni nell\'ordine |');
P('|---|---|');
for (const c of courses) P(`| ${c.clean_slug} | ${c.section_outline.map((s) => s.type + (s.heading && !['description', 'faq', 'call-to-action', 'lead_capture'].includes(s.type) ? ` ("${esc(s.heading)}")` : '')).join(' → ')} |`);
P();
P('Corsi con patrocini: ' + courses.filter((c) => c.patrocini.length).map((c) => `\`${c.clean_slug}\` (${c.patrocini.length})`).join(', ') + '. Corsi con testimonianze: ' + courses.filter((c) => c.testimonials.length).map((c) => `\`${c.clean_slug}\` (${c.testimonials.length})`).join(', ') + '.');
P();
P('### 4.1 Enti patrocinanti (letti dai loghi)');
P();
P('| sha1 | File | Ente/i | Usato da |');
P('|---|---|---|---|');
for (const l of logos.logos) P(`| ${l.sha1.slice(0, 8)} | \`${l.canonical_file}\` | ${esc(l.name || (l.contains_entities || []).join('; '))}${l.composite_image ? ' *(banner composito)*' : ''} | ${l.used_by.map((u) => u.course + '#' + u.position).join(', ')} |`);
P();
P('## 5. Differenze rispetto a quanto noto (brief)');
P();
const diffs = [
  `Catalogo: ${courses.length} corsi su 5 pagine (6+6+6+6+3); \`?page=6\` è vuota. Nessun corso pubblicato fuori catalogo; esiste anche \`/collections/courses\` (stessa lista) e \`/courses\` fa 301 verso \`/collections\`.`,
  'Le descrizioni NON sono tutte 100–530 parole: vanno da 0 (`nutrigenomica-nutrigenetica-ed-epigenetica`, descrizione vuota) e 35 (`apparato-digerente`) a 421 (`ritmi-circadiani-e-metabolismo`). Nessun grassetto/elenco nelle descrizioni: solo testo con `<br>` e a-capo.',
  'Il pulsante WhatsApp esiste in tutti i corsi, ma il testo precompilato corrisponde al titolo solo in ' + validation.whatsapp_text_matches_title.ok + '/27 corsi: ' + courses.filter((c) => !c.whatsapp_text_matches_title).length + ' corsi non corrispondono: ' + courses.filter((c) => /CANE E GATTO$/.test(c.whatsapp_text || '') && !c.whatsapp_text_matches_title).length + ' usano "…sul corso NUTRIZIONE E DIETETICA CANE E GATTO" senza virgolette (copia-incolla, anche su corsi non veterinari), 1 usa il titolo abbreviato "NUTRIGENOMICA". Numero sempre 393394846020. Etichette pulsante: ' + [...new Set(courses.map((c) => '"' + c.whatsapp_label + '"'))].join(', ') + '.',
  'Il titolo nella pagina corso spesso differisce dal titolo della card in catalogo (' + courses.filter((c) => c.catalog_title_differs).length + ' casi: es. card "ALIMENTAZIONE NEL CANE & GATTO COMPLETO" → pagina "CORSO NUTRIZIONE E DIETETICA APPLICATA NEL CANE E NEL GATTO"; "- 20 CREDITI"/"- 30 CREDITI" solo nella card). Anche i `<title>` SEO sono spesso copiati da altri corsi (es. METABOLISMO ha `<title>` "NUTRIZIONE E DIETETICA APPLICATA").',
  'Patrocini: solo 3 corsi (METABOLISMO, ALIMENTAZIONE NEL CANE & GATTO COMPLETO = 7 loghi ciascuno; BUNDLE = 2 banner compositi con 11 loghi). Intestazione "Con il patrocinio di" / "Con il Patrocinio di:".',
  'Testimonianze nelle pagine corso: solo 5 corsi (2 cane/gatto con 6 ciascuno, 3 fitoterapia con 2 ciascuno); sono le stesse testimonianze ripetute tra corsi.',
  `FAQ: la domanda "per chi" è formulata in due modi ("Per chi è adatto questo corso?" in ${courses.filter((c) => c.per_chi_e_adatto_question === 'Per chi è adatto questo corso?').length} corsi, "Per chi sono adatti i nostri corsi?" in ${courses.filter((c) => c.per_chi_e_adatto_question === 'Per chi sono adatti i nostri corsi?').length} corsi: ${courses.filter((c) => c.per_chi_e_adatto_question === 'Per chi sono adatti i nostri corsi?').map((c) => c.clean_slug).join(', ')}); la risposta ha solo 2 varianti (con o senza "Veterinari,"). La FAQ "È possibile pagare con Bonifico Bancario?" ha ${new Set(courses.map((c) => (c.faq.find((f) => /bonifico/i.test(f.question)) || {}).answer_text)).size} risposte diverse; in ${courses.filter((c) => c.faq.some((f) => /IBAN/.test(f.answer_text))).length} corsi la risposta pubblica IBAN e intestatario (persona fisica). ${courses.filter((c) => c.faq.length === 3).length} corsi hanno una terza FAQ "É possibile pagare a rate?" (${courses.filter((c) => c.faq.length === 3).map((c) => c.clean_slug).join(', ')}). Sottotitolo FAQ sempre "Per qualsiasi informazione inviare una mail: responsabile@corsidiformazionecm.it".`,
  'Contatti: l\'email della segreteria è info@corsidiformazionecm.it ("eMail Segreteria Organizzativa") e quella studenti responsabile@corsidiformazionecm.it ("eMail Supporto Studenti"); WhatsApp "Cell. 339 4846020" è solo testo (nessun link). NON c\'è un form sulla pagina: "Contatta ora" porta a un Google Form (forms.gle/PRtiYheGg1FAqnKe8) con campi Nome*, Cognome*, Email*, Numero di telefono*, Richiesta crediti ECM, Vorresti partecipare ai corsi in presenza o online?, Messaggio.',
  'Footer: "Termini e Condizioni" punta a corsidiformazionecm.thinkific.com/pages/terms (301 → /pages/terms, che contiene solo un link al PDF Termini_e_Condizioni.pdf su CloudFront); "Cookie & Privacy Policy" è un PDF su Google Drive. Nessun indirizzo/ragione sociale: solo "© Copyright Corsi di Formazionecm 2022 - PARTITA IVA: 02297020469". Etichetta screen-reader del social "Facebooke" (refuso).',
  'Newsletter: form nativo Thinkific (POST /tenant_leads), non un servizio esterno. Campi: email (obbligatoria, placeholder "Digita il tuo indirizzo e-mail") + checkbox obbligatoria "Acconsento al trattamento dei miei dati e alla ricezione di e-mail"; pulsante "Invia".',
  'Home: nessuna sezione numeri/statistiche e nessun logo patrocinio. Sezioni: gallery (immagine hero "homepage claudia"), "Percorsi di Formazione" (15 corsi in evidenza + pulsante "Vedi tutti i Corsi"), "Testimonianze e Recensioni" (10 recensioni, nessun rating, avatar = logo), Newsletter.',
  'Pagina testimonianze: sales.corsidiformazionecm.it è un funnel **GoHighLevel** (header `x-ghl-*`, asset su assets.cdn.filesafe.space), non Thinkific. Contiene 12 recensioni testuali uniche (5 stelle, ripetute nel marquee: 42 card nel DOM), 1 citazione (Marco Neri) con video, 9 video-testimonianze Vimeo, più una sezione Chi siamo con statistiche (200+ ore di corsi, 14 docenti esperti, 10k+ corsisti), Mission/Vision riscritte, citazione della fondatrice Dott.ssa Claudia Petrazzuolo e un elenco di 26 docenti (8 in più rispetto a /pages/docenti).',
  'Docenti: 18 su /pages/docenti, tutti con foto e pulsante CV ("VEDI IL CV" per i primi 12, "Vedi il CV" per gli ultimi 6). 16 CV sono PDF su Google Drive, 2 sono documenti Google Docs (Candellone, Aulicino) esportati in PDF. Titoli "Dottor/Dottoressa" anche per professori ordinari (sulla pagina vendite gli stessi sono "Prof.").',
  'Pagine extra non linkate dal menu ma in sitemap: `/pages/nutrizione-e-dietetica-applicata-nel-cane-e-nel-gatto` (landing vecchia con brochure), `/pages/offerta-corso-allergie-e-intolleranze-200-coupon-sconto` (pulsante "Acquista €49" → carrello Thinkific, non cliccato), `/pages/richiesta-informazioni-ecm` (banner segnaposto "Add a concise subheading…"), `/pages/privacy` (vuota), `/pages/terms`.',
  'Nessun prezzo nelle pagine corso, nessun pulsante di acquisto/iscrizione (solo WhatsApp + brochure). Unico prezzo trovato: €49 nella pagina offerta.',
  'Header: logo + TUTTI I CORSI, CONTATTI, DOCENTI, TESTIMONIANZE (apre la pagina vendite in nuova scheda) + ACCEDI (`/users/sign_in`). Le voci sono in maiuscolo via CSS (sorgente: "Tutti i corsi", "Contatti", "DOCENTI", "TESTIMONIANZE", "Accedi"). "CHI SIAMO" è solo nel footer.',
  'Cookie banner iubenda (siteId 2782689, cookiePolicyId 13950599) con pulsanti Rifiuta/Accetta/"Scopri di più e personalizza".',
];
diffs.forEach((d) => P(`- ${d}`));
P();
P('## 6. Docenti');
P();
P(`Intro pagina: **${esc(doc.intro.map((i) => i.heading).join(' '))}** — "${esc(doc.intro.map((i) => i.subheading).join(' '))}"`);
P();
P('| # | Intestazione esatta | Nome | Titolo | Parole bio | Foto | CV | Tipo CV | Scaricabile |');
P('|---|---|---|---|---|---|---|---|---|');
for (const d of doc.docenti) P(`| ${d.position} | ${esc(d.heading_exact)} | ${esc(d.name)} | ${esc(d.title_or_role)} | ${(d.bio_text || '').split(/\s+/).filter(Boolean).length} | ${d.photo_file ? `✓ ${d.photo_width}×${d.photo_height}` : '✗'} | ${d.cv_pages ? d.cv_pages + ' pp., ' + kb(d.cv_bytes) : '—'} | ${d.cv_type} | ${d.cv_downloadable ? '✓' : '✗'} |`);
P();
if (doc.sales_page_crossref) {
  P(`Confronto con l'elenco docenti della pagina vendite (${doc.sales_page_crossref.sales_page_teachers} nomi): tutti i ${doc.sales_page_crossref.matched} docenti di /pages/docenti compaiono anche lì; solo sulla pagina vendite: ${doc.sales_page_crossref.only_on_sales_page.map((x) => x.heading).join(', ')}.`);
  P();
}
P('Nota: i nomi "Dottor Bergamaschi Gianguglielmo", "Dottoressa Milza Patrizia", "Dottor Spagnulo Stefano", "Dottor Loprete Fortunato" sono scritti cognome-nome sul sito (mantenuti così).');
P();
P('## 7. Testimonianze');
P();
P('| Fonte | Numero |');
P('|---|---|');
P(`| Pagina vendite GHL — recensioni testuali uniche | ${testi.counts.sales_unique - 1} (+1 citazione Marco Neri) |`);
P(`| Pagina vendite — card nel DOM (marquee duplicato) | ${testi.counts.sales_cards_in_dom_with_marquee_duplicates} |`);
P(`| Pagina vendite — video Vimeo | ${testi.counts.video_testimonials} (${testi.sales_page.video_testimonials.map((v) => esc(v.title)).join('; ')}) |`);
P(`| Home ("Testimonianze e Recensioni") | ${testi.counts.home} |`);
P(`| Pagine corso | ${testi.counts.course_pages} voci (${new Set(testi.entries.filter((e) => e.source === 'course').map((e) => (e.title || '') + e.text)).size} testi distinti) |`);
P(`| **Uniche dopo deduplica** | **${testi.counts.unique_after_dedupe}** |`);
P(`| Presenti in più fonti | ${testi.counts.in_multiple_sources} (sales∩home: ${testi.counts.in_sales_and_home}) |`);
P();
P('Le recensioni della pagina vendite hanno tutte 5 stelle; nessuna data. Le 10 recensioni della home non hanno rating né data, e nessuna coincide con quelle della pagina vendite. Clizia D’Angelo e Francesca Morganti compaiono sia sulla pagina vendite sia nelle pagine corso cane/gatto (testo leggermente diverso: conservato per fonte in `variants`). Home: il nome "Francesco L.Marco " è così nel sorgente (probabile refuso).');
P();
P('## 8. Informazioni globali');
P();
const ft = site.footer;
P(`| Voce | Valore |`);
P(`|---|---|`);
for (const e of site.contact.emails) P(`| ${esc(e.label)} | ${e.email} |`);
P(`| Email nelle FAQ dei corsi | responsabile@corsidiformazionecm.it |`);
P(`| WhatsApp | ${site.contact.whatsapp.text} → link usato nei corsi \`https://wa.me/393394846020?text=…\` |`);
P(`| Telefono/indirizzo | nessuno oltre al cellulare WhatsApp |`);
P(`| Social | Instagram ${ft.social_urls.instagram} · Facebook ${ft.social_urls.facebook} · YouTube ${ft.social_urls.youtube} |`);
P(`| P.IVA | ${ft.piva} ("${esc(ft.copyright)}") |`);
P(`| Login | ${site.header.login.absolute_url} (etichetta "${site.header.login.label_displayed}") |`);
P(`| Termini e Condizioni | ${site.legal.terms_url} → ${site.legal.terms.redirects_to}; PDF: ${(site.legal.page_terms.pdfs || []).map((x) => `${x.url} (${x.pages} pp., scaricato in \`${x.file}\`)`).join(', ')} |`);
P(`| Cookie & Privacy Policy | ${site.legal.cookie_privacy.url} (${site.legal.cookie_privacy.pages} pp., \`${site.legal.cookie_privacy.file}\`) |`);
P(`| Newsletter | ${esc(site.newsletter.provider)} |`);
P(`| Testo consenso newsletter | "${esc(site.newsletter.consent_text)}" |`);
P(`| Cookie banner | iubenda (Rifiuta/Accetta) |`);
P(`| Logo | \`${site.brand.logos.map((l) => l.file).join('`, `')}\` (solo PNG, nessun SVG; header ${site.brand.logos[0].width}×${site.brand.logos[0].height}) |`);
P(`| Favicon | \`${site.brand.favicon.file}\` (PNG ${site.brand.favicon.width}×${site.brand.favicon.height} servito come /favicon.ico) |`);
const c = site.brand.colors.computed;
P(`| Colori | rosso brand #BE1522 (link menu/footer, bordo pulsante secondario, iubenda); testo #212326; titoli #000000; sfondo header/footer #F2F2F2; sfondo sezioni #F1F1F1; pulsante primario (WhatsApp) sfondo #1E1C27 testo bianco, radius 100px; badge card nero (testo bianco, radius 4px); sfondo rosa #FBE4E7 in alcune aree della home. Logo (campionato): grigio scuro ≈#333333 e rosso ≈#BB1329 |`);
P(`| Font | Titoli "Libre Baskerville" 400 (h2 36px, card 20px, testimonianze 22px); testo "Source Sans Pro" 18px (menu 15.75px 500 uppercase); icone Font Awesome 4.7 |`);
P();
P('## 9. Refusi evidenti nei testi dei corsi (NON corretti nei dati)');
P();
P('| Corso | Campo | Testo esatto | Suggerimento | Verificato nel dato |');
P('|---|---|---|---|---|');
for (const t of validation.typos_snippets_verified) P(`| ${t.course === '*' ? 'tutti' : t.course} | ${t.field} | \`${esc(t.snippet)}\` | ${esc(t.suggestion)} | ${t.found ? '✓' : '✗'} |`);
P();
if (special.length) { P('Caratteri particolari:'); P(); special.forEach((s) => P(`- ${s}`)); P(); }
P('## 10. Download falliti');
P();
const failed = [];
courses.forEach((c) => { if (!c.cover_file) failed.push(`cover ${c.clean_slug}`); if (!c.brochure_ok) failed.push(`brochure ${c.clean_slug}`); c.patrocini.forEach((l) => { if (!l.file) failed.push(`logo ${c.clean_slug} ${l.image_url}`); }); });
doc.docenti.forEach((d) => { if (!d.photo_file) failed.push(`foto ${d.name}`); if (!d.cv_downloadable) failed.push(`CV ${d.name}`); });
P(failed.length ? failed.map((f) => `- ${f}`).join('\n') : 'Nessuno: tutte le cover, brochure (27/27, tutte `%PDF`), loghi, foto docenti e CV (18/18) sono stati scaricati. Le brochure sono state scaricate da `drive.google.com/uc?export=download` senza interstitial antivirus.');
P();
P('## 11. Elementi mancanti o ambigui');
P();
[
  '`nutrigenomica-nutrigenetica-ed-epigenetica`: descrizione vuota sulla pagina e card di catalogo senza estratto → testo da recuperare dalla brochure.',
  'Corsi cane/gatto: `alimentazione-nel-gatto-completo` e `privato-corso-cane-e-gatto-230` hanno descrizione identica (professionale), ma la card del secondo ("COME ALIMENTI TUOI AMICI A 4 ZAMPE?") parla a proprietari/allevatori: contenuto da chiarire.',
  'Stato corsi: l\'unico segnale esplicito è il badge "Pre-ordine disponibile ora!" (card `card--presell`) sul corso METABOLISMO; nessun "in arrivo"/"sold out". Non ci sono date, durate, prezzi o modalità (online/presenza) nelle pagine corso: sono solo nelle brochure.',
  'Crediti ECM: sul sito indicati solo in 2 titoli di card (20 e 30 crediti). Dalle brochure: ' + courses.filter((c) => c.brochure_hints && c.brochure_hints.ecm_credits).length + ' corsi con crediti indicati, ' + courses.filter((c) => c.brochure_hints && c.brochure_hints.no_ecm_credits && !c.brochure_hints.ecm_credits).length + ' con "NO CREDITI ECM", ' + courses.filter((c) => c.brochure_hints && !c.brochure_hints.ecm_credits && !c.brochure_hints.no_ecm_credits).length + ' senza numero estraibile: ' + courses.filter((c) => c.brochure_hints && c.brochure_hints.ecm_label_without_number).length + ' hanno l’etichetta "CREDITI ECM" ma il numero non è nel testo del PDF (probabilmente è grafico): ' + courses.filter((c) => c.brochure_hints && c.brochure_hints.ecm_label_without_number).map((c) => c.clean_slug).join(', ') + '; nessuna menzione: ' + (courses.filter((c) => c.brochure_hints && !c.brochure_hints.ecm_credits && !c.brochure_hints.no_ecm_credits && !c.brochure_hints.ecm_label_without_number).map((c) => c.clean_slug).join(', ') || 'nessuno') + '. Valori brochure da verificare a mano (estrazione automatica).',
  'Le immagini dei patrocini non hanno alt: i nomi sono stati letti visivamente dai loghi (vedi `tools/scrape/logo-names.json`).',
  'Gli a-capo nelle descrizioni/FAQ/bio non sono visibili sul sito attuale; `*_html` li interpreta (riga vuota = paragrafo; a-capo dopo punteggiatura = `<br>`; altrimenti spazio). Il sorgente esatto è in `description_raw_html`, `answer_raw`, `bio_raw`, `text_raw`; il testo esattamente come visualizzato oggi è in `description_rendered_text`.',
  'Copertine: le cover delle pagine corso sono già gli originali caricati (nessun parametro di resize su files.cdn.thinkific.com); per 6 corsi la cover è 1920×1080/2688×1520, per gli altri 1080×597 o 1200×675. Le immagini card del catalogo sono spesso versioni più piccole (800×450).',
  'Pagina /pages/richiesta-informazioni-ecm è un segnaposto con testo di default Thinkific in inglese; /pages/privacy è vuota (la privacy reale è il PDF su Drive).',
  'La pagina vendite GHL ha contenuti (Chi siamo esteso, statistiche, 8 docenti in più, video) che non esistono sul sito Thinkific: decidere se migrarli. Foto dei docenti GHL non scaricate (solo URL in `_sales_page.json`).',
].forEach((x) => P(`- ${x}`));
P();
P('## 12. File prodotti');
P();
[
  '`data/scrape/courses.json` — 27 corsi completi', '`data/scrape/site.json` — header, footer, brand, home, newsletter, contatti, legali', '`data/scrape/docenti.json` — 18 docenti + confronto pagina vendite',
  '`data/scrape/pages.json` — chi-siamo (mission/vision), contatti e altre /pages/*', '`data/scrape/testimonianze.json` — testimonianze deduplicate per fonte', '`data/scrape/logos_index.json` — loghi patrocinio unici (sha1)',
  '`data/scrape/_discovery.json` — catalogo, sitemap, crawl, link esterni', '`data/scrape/_sales_page.json` — dump strutturato della pagina vendite GHL', '`data/scrape/validation.json` — esito controlli',
  '`data/scrape/assets/` — covers (+cards), logos, brochures (PDF, esclusi da git), brochure-previews, brand, home, docenti, cv, legal, testimonianze, pages',
  `\`docs/screenshot/original/\` — ${shots.shots.map((s) => s.file.split('/').pop()).join(', ')}`,
].forEach((x) => P(`- ${x}`));
P();
fs.writeFileSync(path.join(ROOT, 'docs', 'inventario.md'), L.join('\n'), 'utf8');
console.log('\nwritten docs/inventario.md', L.length, 'lines');
