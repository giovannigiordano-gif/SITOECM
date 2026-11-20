// Verifica puntuale dei 27 corsi rispetto allo scraping del sito originale.
// Uso: node tools/test/verify-courses.mjs   (scrive docs/verifica-corsi.md e docs/verifica-corsi.json)
import { chromium } from 'playwright';
import { readFileSync, writeFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const root = join(here, '..', '..');
const BASE = process.env.ECM_BASE || 'https://soothing-khaki-okapi-74.hostingersite.com';
const scraped = JSON.parse(readFileSync(join(root, 'data/scrape/courses.json'), 'utf8'));
const bundle = JSON.parse(readFileSync(join(root, 'dist/ecm-import/data.json'), 'utf8'));

const norm = (s) =>
	String(s || '')
		.normalize('NFC')
		.replace(/[\u00A0\u2028\u200B]/g, ' ')
		.replace(/[’‘]/g, "'")
		.replace(/[“”]/g, '"')
		.replace(/\s+/g, ' ')
		.trim();

const browser = await chromium.launch();
const ctx = await browser.newContext();
const page = await ctx.newPage();
const rows = [];

for (const sc of scraped) {
	const b = bundle.courses.find((c) => c.slug === sc.clean_slug);
	const url = `${BASE}/courses/${sc.clean_slug}/`;
	const r = { n: sc.order, slug: sc.clean_slug, checks: {}, notes: [] };
	const res = await page.goto(url, { waitUntil: 'domcontentloaded' });
	r.checks.http200 = res.status() === 200;
	await page.waitForLoadState('load').catch(() => {});
	const dom = await page.evaluate(() => {
		const q = (s) => document.querySelector(s);
		const prose = q('.prose--course');
		return {
			h1: q('h1.course-title')?.textContent || '',
			desc: prose ? prose.innerText : '',
			cover: q('.cover-frame img')?.currentSrc || q('.cover-frame img')?.src || '',
			coverNatural: q('.cover-frame img')?.naturalWidth || 0,
			brochure: q('.brochure a.btn')?.href || '',
			patrons: document.querySelectorAll('.patrons__grid > li').length,
			voices: document.querySelectorAll('.course-voices__grid > li').length,
			wa: q('.sticky-cta a[href*="wa.me"]')?.href || '',
			perChi: q('.fit .prose')?.innerText || '',
			faqQ: [...document.querySelectorAll('.acc__q')].map((e) => e.textContent.trim()),
			jsonld: [...document.querySelectorAll('script[type="application/ld+json"]')].map((s) => s.textContent).join(' '),
			title: document.title,
			metaDesc: q('meta[name="description"]')?.content || '',
		};
	});
	// Titolo: identico a quello originale (con i soli refusi evidenti corretti nel bundle).
	r.checks.title = norm(dom.h1) === norm(b.title);
	if (norm(b.title) !== norm(sc.title)) r.notes.push(`titolo corretto: "${sc.title}" → "${b.title}"`);
	// Descrizione: testo identico (confronto sul testo, la formattazione è in paragrafi).
	const expected = norm(b.content_html.replace(/<br\s*\/?>/g, ' ').replace(/<\/p>\s*<p>/g, ' ').replace(/<[^>]+>/g, '').replace(/&amp;/g, '&').replace(/&#8217;/g, "'"));
	const got = norm(dom.desc);
	r.checks.description = expected === '' ? got.includes('brochure') : got === expected;
	if (!r.checks.description) r.notes.push(`descrizione diversa (attesa ${expected.length} car., trovata ${got.length})`);
	if (norm(sc.description_text) !== expected && expected) r.notes.push('descrizione con refusi corretti');
	// Copertina
	r.checks.cover = !!dom.cover && dom.coverNatural > 0;
	// Brochure: file sul sito (non Drive), PDF, stessa dimensione del file originale.
	if (dom.brochure) {
		const head = await ctx.request.fetch(dom.brochure, { method: 'HEAD' });
		const len = parseInt(head.headers()['content-length'] || '0', 10);
		r.checks.brochure = head.status() === 200 && /pdf/i.test(head.headers()['content-type'] || '') && dom.brochure.startsWith(BASE) && len === sc.brochure_bytes;
		if (len !== sc.brochure_bytes) r.notes.push(`brochure ${len} B vs originale ${sc.brochure_bytes} B`);
	} else r.checks.brochure = false;
	// Patrocini e testimonianze presenti dove previsti
	r.checks.patrocini = dom.patrons === (sc.patrocini || []).length;
	r.checks.testimonianze = dom.voices === (sc.testimonials || []).length;
	// WhatsApp: testo generato dal titolo
	const waText = dom.wa ? decodeURIComponent(new URL(dom.wa).searchParams.get('text') || '') : '';
	r.checks.whatsapp = waText === `Salve, vorrei avere maggiori informazioni sul corso "${b.title}"` && dom.wa.includes('393394846020');
	if (!sc.whatsapp_text_matches_title) r.notes.push('WhatsApp corretto (sul sito originale il testo non corrispondeva al titolo)');
	// Per chi è adatto
	r.checks.perChi = norm(dom.perChi).length > 20;
	// SEO: title, meta description, schema Course
	r.checks.seo = !!dom.title && dom.metaDesc.length > 50 && /"@type"\s*:\s*"Course"/.test(dom.jsonld);
	// Redirect 301 dal vecchio URL (solo per gli slug con spazi/maiuscole)
	if (sc.slug_needs_redirect) {
		const rr = await ctx.request.fetch(BASE + sc.original_path, { maxRedirects: 0 });
		r.checks.redirect301 = rr.status() === 301 && (rr.headers()['location'] || '').includes(`/courses/${sc.clean_slug}/`);
	}
	r.ok = Object.values(r.checks).every(Boolean);
	rows.push(r);
	process.stdout.write(r.ok ? '.' : 'x');
}
await browser.close();

const keys = ['http200', 'title', 'description', 'cover', 'brochure', 'patrocini', 'testimonianze', 'whatsapp', 'perChi', 'seo', 'redirect301'];
const tick = (v) => (v === undefined ? '' : v ? '✓' : '✗');
let md = `# Verifica dei 27 corsi\n\nGenerato da \`tools/test/verify-courses.mjs\` il ${new Date().toISOString().slice(0, 16).replace('T', ' ')} su ${BASE}.\n\n`;
md += `Esito: **${rows.filter((r) => r.ok).length}/${rows.length} corsi superano tutti i controlli.**\n\n`;
md += '| # | Corso | 200 | Titolo | Descrizione | Copertina | Brochure | Patrocini | Testim. | WhatsApp | Per chi | SEO | 301 | Note |\n|---|---|---|---|---|---|---|---|---|---|---|---|---|---|\n';
for (const r of rows) md += `| ${r.n} | ${r.slug} | ${keys.map((k) => tick(r.checks[k])).join(' | ')} | ${r.notes.join('; ')} |\n`;
md += `\nControlli: titolo identico all'originale (salvo refusi evidenti corretti); testo della descrizione identico; copertina caricata; brochure servita dal sito (non da Drive), PDF, stesso peso in byte del file originale; numero di loghi patrocinio e di testimonianze uguale all'originale; link WhatsApp con testo "Salve, vorrei avere maggiori informazioni sul corso \\"TITOLO\\""; sezione "Per chi è adatto" presente; title, meta description e schema.org Course presenti; redirect 301 dai vecchi URL con spazi.\n`;
writeFileSync(join(root, 'docs/verifica-corsi.md'), md);
writeFileSync(join(root, 'docs/verifica-corsi.json'), JSON.stringify(rows, null, 1));
console.log(`\n${rows.filter((r) => r.ok).length}/${rows.length} ok`);
for (const r of rows.filter((x) => !x.ok)) console.log(r.n, r.slug, JSON.stringify(r.checks), r.notes.join('; '));
