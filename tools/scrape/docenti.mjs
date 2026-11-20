// Step 4: teachers page. Output: data/scrape/docenti.json + assets/{docenti,cv}
import fs from 'node:fs';
import path from 'node:path';
import { BASE, DATA, ASSETS, rel, writeJSON, launch, noJsContext, installBrowserHelpers, downloadBestImage, driveId, downloadDrivePdf, pdfPages, cleanSlug, fetchBuf } from './lib.mjs';
import { extractPage } from './sections.mjs';

const browser = await launch();
const ctx = await noJsContext(browser);
const page = await ctx.newPage();
const url = `${BASE}/pages/docenti`;
const resp = await page.goto(url, { waitUntil: 'load', timeout: 120000 });
await page.evaluate(installBrowserHelpers);
const x = await page.evaluate(extractPage, {});
await browser.close();

const HONORIFIC = /^(Prof\.ssa|Prof\.|Professoressa|Professore|Dott\.ssa|Dott\.|Dottoressa|Dottor|Dr\.ssa|Dr\.)\s+/i;
const intro = x.sections.filter((s) => s.type !== 'text-image' && s.type !== 'lead_capture').map((s) => ({ type: s.type, heading: s.heading, subheading: s.subheading, text: s.text, html: s.html }));
const docenti = [];
let n = 0;
for (const s of x.sections.filter((s) => s.type === 'text-image')) {
  n++;
  const heading = s.heading;
  const hm = heading.match(HONORIFIC);
  const name = hm ? heading.slice(hm[0].length).trim() : heading;
  const slug = cleanSlug(name);
  const notes = [];
  const img = s.images[0] || null;
  let photo = { ok: false };
  if (img) photo = await downloadBestImage(img.src, path.join(ASSETS, 'docenti', `${String(n).padStart(2, '0')}-${slug}`));
  const cvBtn = s.buttons.find((b) => /cv/i.test(b.label)) || s.buttons[0] || null;
  const cvUrl = cvBtn ? cvBtn.href : null;
  let cv_type = null, cv_file = null, cv_downloadable = false, cv_bytes = null, cv_pages = null, cv_download = null;
  if (cvUrl) {
    const id = driveId(cvUrl);
    const out = path.join(ASSETS, 'cv', `${String(n).padStart(2, '0')}-${slug}.pdf`);
    if (/drive\.google\.com\/file/.test(cvUrl) && id) {
      const r = await downloadDrivePdf(id, out);
      cv_download = { ok: r.ok, via: r.via || null, attempts: r.attempts };
      if (r.ok) { cv_type = 'drive-pdf'; cv_file = r.rel; cv_downloadable = true; cv_bytes = r.bytes; cv_pages = pdfPages(out); }
      else {
        const last = (r.attempts || []).slice(-1)[0] || {};
        cv_type = /html/.test(last.ct || '') ? 'drive-file (non scaricabile/privato o non PDF)' : `drive-file (${last.ct || 'sconosciuto'})`;
        notes.push('CV su Drive non scaricabile come PDF: ' + JSON.stringify((r.attempts || []).map((a) => [a.status, a.ct, a.bytes])));
      }
    } else if (/docs\.google\.com\/document/.test(cvUrl) && id) {
      cv_type = 'google-doc';
      const r = await downloadDrivePdf(id, out, { docsType: 'document' });
      cv_download = { ok: r.ok, via: r.via || null, attempts: r.attempts };
      if (r.ok) { cv_file = r.rel; cv_downloadable = true; cv_bytes = r.bytes; cv_pages = pdfPages(out); notes.push('CV è un documento Google Docs (file Office "rtpof=true"): PDF ottenuto via export?format=pdf.'); }
      else notes.push('Google Doc non esportabile in PDF (probabilmente non pubblico).');
    } else if (/\.pdf(\?|$)/i.test(cvUrl)) {
      cv_type = 'pdf';
      const r = await fetchBuf(cvUrl);
      if (r.status === 200 && r.buf.slice(0, 4).toString() === '%PDF') { fs.mkdirSync(path.dirname(out), { recursive: true }); fs.writeFileSync(out, r.buf); cv_file = rel(out); cv_downloadable = true; cv_bytes = r.buf.length; cv_pages = pdfPages(out); }
    } else cv_type = 'web-page';
  } else notes.push('Nessun link CV.');
  if (/\n/.test(s.body_raw || '')) notes.push('Bio con a-capo nel sorgente non visibili sul sito (white-space:normal); bio_html: a-capo dopo punteggiatura => <br>, altrimenti spazio.');
  if (img && img.alt === '') notes.push('Foto senza alt.');
  docenti.push({
    position: n,
    heading_exact: heading,
    name,
    title_or_role: hm ? hm[1] : null,
    honorific: hm ? hm[1] : null,
    bio_html: s.body_html,
    bio_text: s.body_html ? s.body_html.replace(/<br>/g, '\n').replace(/<\/p><p>/g, '\n\n').replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').trim() : null,
    bio_raw: s.body_raw,
    photo_url: img ? img.src : null,
    photo_url_best: photo.ok ? photo.url_best : null,
    photo_file: photo.ok ? photo.rel : null,
    photo_width: photo.ok ? photo.width : null,
    photo_height: photo.ok ? photo.height : null,
    cv_label: cvBtn ? cvBtn.label : null,
    cv_url: cvUrl,
    cv_drive_id: cvUrl ? driveId(cvUrl) : null,
    cv_type,
    cv_file,
    cv_downloadable,
    cv_bytes,
    cv_pages,
    cv_download,
    section_uid: s.uid,
    notes,
  });
  console.log(`${n}. ${heading} | photo=${photo.ok} ${photo.width}x${photo.height} | cv=${cv_type} ${cv_downloadable} ${cv_pages}p`);
}

writeJSON(path.join(DATA, 'docenti.json'), {
  scraped_at: new Date().toISOString(),
  url,
  http_status: resp.status(),
  html_title: x.html_title,
  intro,
  count: docenti.length,
  cv_downloadable_count: docenti.filter((d) => d.cv_downloadable).length,
  docenti,
});
console.log('teachers', docenti.length, 'cv downloadable', docenti.filter((d) => d.cv_downloadable).length);
