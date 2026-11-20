// Migrazione ripetibile: data/scrape (sito Thinkific) -> bundle ecm-import per ECM_Importer (plugin ecm-core).
// Uso:  node tools/migrate/build-bundle.mjs            (scrive dist/ecm-import/ e dist/ecm-import.zip)
// Formato: docs/import-bundle-format.md. Nessun contenuto inventato: testi, copertine, loghi e brochure sono quelli estratti.
import { readFileSync, writeFileSync, mkdirSync, copyFileSync, existsSync, rmSync, statSync } from 'node:fs';
import { join, dirname, extname, basename } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';

const here = dirname(fileURLToPath(import.meta.url));
const root = join(here, '..', '..');
const SCR = join(root, 'data', 'scrape');
const OUT = join(root, 'dist', 'ecm-import');
const rd = (f) => JSON.parse(readFileSync(join(SCR, f), 'utf8'));
const courses = rd('courses.json');
const site = rd('site.json');
const docData = rd('docenti.json');
const testiData = rd('testimonianze.json');
const pagesData = rd('pages.json');
const logosIndex = existsSync(join(SCR, 'logos_index.json')) ? rd('logos_index.json').logos || [] : [];
const overrides = existsSync(join(here, 'overrides.json')) ? JSON.parse(readFileSync(join(here, 'overrides.json'), 'utf8')) : {};

rmSync(OUT, { recursive: true, force: true });
mkdirSync(join(OUT, 'assets'), { recursive: true });

const slugify = (s) => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/['’`]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
const media = [];
const mediaKeys = new Set();
function addMedia(key, srcRel, sub, meta = {}) {
	if (!srcRel) return null;
	const src = join(root, srcRel);
	if (!existsSync(src)) {
		console.warn('MISSING', key, srcRel);
		return null;
	}
	if (mediaKeys.has(key)) return key;
	const file = `assets/${sub}/${key}${extname(src).toLowerCase()}`;
	mkdirSync(join(OUT, 'assets', sub), { recursive: true });
	copyFileSync(src, join(OUT, file));
	media.push({ key, file, ...meta, bytes: statSync(src).size });
	mediaKeys.add(key);
	return key;
}

/* ---------------- Opzioni globali ---------------- */
const contact = site.contact || {};
const emailSeg = (contact.emails || []).find((e) => /segreteria/i.test(e.label))?.email || 'info@corsidiformazionecm.it';
const emailSup = (contact.emails || []).find((e) => /supporto|studenti/i.test(e.label))?.email || 'responsabile@corsidiformazionecm.it';
const social = Object.fromEntries((site.footer.social || []).map((s) => [/insta/i.test(s.href) ? 'instagram_url' : /facebook/i.test(s.href) ? 'facebook_url' : 'youtube_url', s.href]));
const loginPath = (site.header.nav || []).find((n) => n.is_sign_in)?.href || '/users/sign_in';

// FAQ comuni: l'introduzione "email" è identica su tutti i corsi; per il bonifico usiamo la risposta più frequente
// come globale e manteniamo per corso le risposte diverse (sovrascrivono la globale).
const bonificoCount = {};
courses.forEach((c) => c.faq.filter((f) => /bonifico/i.test(f.question)).forEach((f) => (bonificoCount[f.answer_html] = (bonificoCount[f.answer_html] || 0) + 1)));
const bonificoGlobal = Object.entries(bonificoCount).sort((a, b) => b[1] - a[1])[0]?.[0] || '';
const bonificoQ = courses.flatMap((c) => c.faq).find((f) => /bonifico/i.test(f.question))?.question || 'È possibile pagare con Bonifico Bancario?';

const legal = site.legal || {};
const termsPdf = existsSync(join(SCR, 'assets/legal/termini-e-condizioni.pdf')) ? addMedia('legal-termini-e-condizioni', 'data/scrape/assets/legal/termini-e-condizioni.pdf', 'legal', { title: 'Termini e condizioni', kind: 'document' }) : null;
const privacyPdf = legal.cookie_privacy?.file ? addMedia('legal-cookie-privacy-policy', legal.cookie_privacy.file, 'legal', { title: 'Cookie & Privacy Policy', kind: 'document' }) : null;

const newsletterConsent = (site.newsletter?.form?.fields || []).find((f) => f.type === 'checkbox')?.label || '';

const options = {
	login_url: `https://www.corsidiformazionecm.it${loginPath}`,
	email_segreteria: emailSeg,
	email_supporto: emailSup,
	contact_recipient: emailSeg,
	whatsapp_number: (contact.whatsapp?.number_e164 || '+393394846020').replace(/\D/g, ''),
	whatsapp_display: contact.whatsapp?.number_display || '339 4846020',
	whatsapp_template: 'Salve, vorrei avere maggiori informazioni sul corso "%s"',
	whatsapp_generic: 'Salve, vorrei avere maggiori informazioni sui vostri corsi',
	faq_email_q: '',
	faq_email_a: courses[0].faq_intro || `Per qualsiasi informazione inviare una mail: ${emailSup}`,
	faq_bonifico_q: bonificoQ,
	faq_bonifico_a: bonificoGlobal,
	...social,
	piva: site.footer.piva || '02297020469',
	ragione_sociale: 'Corsi di FormazionEcm',
	termini_url: termsPdf ? `{media:${termsPdf}}` : legal.terms_url || '',
	privacy_url: privacyPdf ? `{media:${privacyPdf}}` : legal.cookie_privacy_url || '',
	cookie_url: privacyPdf ? `{media:${privacyPdf}}` : legal.cookie_privacy_url || '',
	newsletter_title: site.newsletter?.heading === 'Newsletter' ? 'Iscriviti alla newsletter' : site.newsletter?.heading || '',
	newsletter_text: site.newsletter?.subheading || '',
	newsletter_consent: newsletterConsent,
	newsletter_provider: 'none',
	stats_extra: (pagesData.pages['chi-siamo']?.sales_stats || []).map((x) => `${x.value}|${x.label.charAt(0) + x.label.slice(1).toLowerCase()}`).join(String.fromCharCode(10)),
	...(overrides.options || {}),
};

/* ---------------- Tassonomie ---------------- */
const AREAS = [
	{ slug: 'nutrizione-umana', name: 'Nutrizione umana', image_key: 'nutrizione-umana', order: 1 },
	{ slug: 'nutrizione-animale', name: 'Nutrizione animale', image_key: 'nutrizione-animale', order: 2 },
	{ slug: 'fitoterapia', name: 'Fitoterapia', image_key: 'fitoterapia', order: 3 },
	{ slug: 'microbiota-genomica', name: 'Microbiota e genomica', image_key: 'microbiota-genomica', order: 4 },
	{ slug: 'comunicazione-sanitaria', name: 'Comunicazione sanitaria', image_key: 'comunicazione-sanitaria', order: 5 },
];
// Brevi descrizioni di area: frasi di navigazione scritte per il sito (non contenuti dei corsi).
const AREA_SHORT = {
	'nutrizione-umana': 'Dalla nutrizione clinica allo sport, dall’età evolutiva all’invecchiamento: alimentazione e metabolismo per la pratica professionale.',
	'nutrizione-animale': 'Alimentazione, dietetica e microbiota del cane, del gatto e del cavallo atleta.',
	fitoterapia: 'Fitoterapia basata sulle evidenze, dalla teoria all’applicazione clinica.',
	'microbiota-genomica': 'Microbiota, eubiosi e disbiosi, nutrigenomica ed epigenetica applicate alla nutrizione.',
	'comunicazione-sanitaria': 'Counseling e comunicazione efficace con il paziente.',
};
AREAS.forEach((a) => (a.short = AREA_SHORT[a.slug]));

const AREA_OF = {
	'metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta': ['nutrizione-animale'],
	'alimentazione-nel-gatto-completo': ['nutrizione-animale'],
	'privato-corso-cane-e-gatto-230': ['nutrizione-animale'],
	'bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto': ['nutrizione-animale', 'microbiota-genomica'],
	caneegatto2: ['nutrizione-animale'],
	'evidence-based-phytotherapy': ['fitoterapia'],
	'fitoterapia-applicata': ['fitoterapia'],
	'fitoterapia-sistemica': ['fitoterapia'],
	'dall-eubiosi-alla-disbiosi': ['microbiota-genomica'],
	microbiota: ['microbiota-genomica', 'nutrizione-umana'],
	'nutrigenomica-nutrigenetica-ed-epigenetica': ['microbiota-genomica'],
	'counseling-nutrizionale': ['comunicazione-sanitaria'],
	'pnl-applicata-alla-comunicazione-sanitaria': ['comunicazione-sanitaria'],
};

const DEST = [
	{ slug: 'medici', name: 'Medici', re: /medic[io]\s+chirurg|\bmedici\b(?!\s+veterinari)/i },
	{ slug: 'biologi', name: 'Biologi', re: /biolog/i },
	{ slug: 'dietisti', name: 'Dietisti', re: /dietist/i },
	{ slug: 'farmacisti', name: 'Farmacisti', re: /farmacist/i },
	{ slug: 'veterinari', name: 'Veterinari', re: /veterinar/i },
	{ slug: 'infermieri', name: 'Infermieri', re: /infermier/i },
	{ slug: 'psicologi', name: 'Psicologi', re: /psicolog/i },
	{ slug: 'tecnologi-alimentari', name: 'Tecnologi alimentari', re: /tecnolog/i },
	{ slug: 'privati', name: 'Privati', re: /^$/ },
];
const destinatariFor = (c) => {
	if (/privato/i.test(c.clean_slug) || /CORSO PRIVATO/i.test(c.title)) return ['privati'];
	const txt = c.per_chi_e_adatto_text || '';
	return DEST.filter((d) => d.re.test(txt)).map((d) => d.slug);
};

/* ---------------- Corsi ---------------- */
const isPerChi = (q) => /per\s+chi/i.test(q);
const FEATURED = new Set(['metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta', 'bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto', 'nutrizione-e-dietetica-applicata', 'microbiota', 'fitoterapia-sistemica']);
const STATO = (c) => (c.status_hint === 'pre-ordine' ? 'preordine' : c.status_hint === 'in arrivo' ? 'in-arrivo' : 'disponibile');
const typoFixes = overrides.typos || {};
const fix = (slug, field, val) => {
	const list = (typoFixes[slug] && typoFixes[slug][field]) || [];
	return list.reduce((v, [a, b]) => v.split(a).join(b), val);
};

// Refusi evidenti corretti (ortografia/battitura, mai riscritture di contenuto). Elenco riportato in docs/report.md.
const TYPOS = [
	['non posso essere viste', 'non possono essere viste'],
	['discussine', 'discussione'],
	['nel trattamento della patologie', 'nel trattamento delle patologie'],
	['verranno presi in considerazione le modalità', 'verranno prese in considerazione le modalità'],
	['dall’ azione', 'dall’azione'],
	['donna,ma', 'donna, ma'],
	["si dovra' inviare", 'si dovrà inviare'],
	['responsabiltà', 'responsabilità'],
	['É possibile', 'È possibile'],
	["NELL'ALIMENTAZIONEDEL", "NELL'ALIMENTAZIONE DEL"],
	['esplicativo- affermativo', 'esplicativo-affermativo'],
	['mirata al biotipologia', 'mirata alla biotipologia'],
	['adeguati strategie', 'adeguate strategie'],
	['de sincronizzato', 'desincronizzato'],
	['E’ ormai noto', 'È ormai noto'],
	['nelle su dette categorie', 'nelle suddette categorie'],
	['Sì, La ricevuta', 'Sì, la ricevuta'],
];
// Caratteri incollati da PDF: legature, separatori di riga invisibili, spazi a larghezza zero, nbsp.
const normChars = (s) =>
	String(s || '')
		.replace(/\uFB01/g, 'fi')
		.replace(/\uFB02/g, 'fl')
		.replace(/\uFB00/g, 'ff')
		.replace(/\u2028/g, ' ')
		.replace(/\u200B/g, '')
		.replace(/\u00A0/g, ' ')
		.replace(/&nbsp;/g, ' ');
const clean = (s) => TYPOS.reduce((v, [a, b]) => v.split(a).join(b), normChars(s));

const logoKeyBySha = {};
const outCourses = courses.map((c0) => {
	const c = { ...c0, title: clean(c0.title) };
	const slug = c.clean_slug;
	const cover = addMedia(`cover-${slug}`, c.cover_file, 'covers', { title: c.title, alt: `Copertina del corso ${c.title}`, kind: 'cover' });
	const brochure = c.brochure_ok ? addMedia(`brochure-${slug}`, c.brochure_file, 'brochures', { title: `Brochure ${c.title}`, kind: 'brochure', pages: c.brochure_pages || null }) : null;
	const preview = c.brochure_preview ? addMedia(`preview-${slug}`, c.brochure_preview, 'previews', { title: `Anteprima brochure ${c.title}`, alt: '', kind: 'preview' }) : null;
	const patrocini = (c.patrocini || []).map((p0, i) => {
		// Banner compositi: il nome elenca gli enti contenuti (separati da "; ").
		const li = logosIndex.find((l) => l.sha1 === p0.sha1);
		const p = { ...p0, name: p0.name || (li && li.contains_entities ? li.contains_entities.join('; ') : null) };
		const key = logoKeyBySha[p.sha1] || `logo-${slugify(p0.name || p0.name_from_filename || basename(p0.file)).slice(0, 60) || p0.sha1.slice(0, 10)}`;
		logoKeyBySha[p.sha1] = key;
		addMedia(key, p.file, 'logos', { title: p.name || p.name_from_filename || '', alt: p.name ? `Logo ${p.name}` : '', kind: 'logo' });
		return { media: key, name: p.name || '' };
	});
	const faq = (c.faq || [])
		.filter((f) => !isPerChi(f.question))
		.filter((f) => !(/bonifico/i.test(f.question) && f.answer_html === bonificoGlobal))
		.map((f) => ({ q: clean(f.question), a_html: clean(f.answer_html) }));
	return {
		source_slug: c.original_slug_decoded || c.original_slug,
		slug,
		old_paths: [...new Set([c.original_path, c.original_path_decoded, c.original_path_percent_encoded].filter(Boolean))],
		title: clean(fix(slug, 'title', c.title)),
		content_html: clean(fix(slug, 'content', c.description_html || '')),
		excerpt: '',
		cover,
		brochure,
		brochure_preview: preview,
		patrocini,
		testimonianze: (c.testimonials || []).map((t) => ({ name: normChars(t.name || '').trim(), meta: normChars(t.role_or_meta || ''), text: normChars(t.text || '') })),
		per_chi_html: clean(c.per_chi_e_adatto_html || ''),
		faq,
		stato: STATO(c),
		crediti: c.ecm_credits ? String(c.ecm_credits) : '',
		link_iscrizione: '',
		in_evidenza: FEATURED.has(slug),
		menu_order: c.order,
		areas: AREA_OF[slug] || ['nutrizione-umana'],
		destinatari: destinatariFor(c),
		seo_title: '',
		// Le meta description Thinkific sono spesso copiate da altri corsi: lasciamo generare quella automatica dal testo.
		seo_desc: '',
		...(overrides.courses?.[slug] || {}),
	};
});

/* ---------------- Docenti ---------------- */
const firstSentence = (t) => {
	const s = t.replace(/\s+/g, ' ').trim();
	const m = s.match(/^(.{20,140}?[.;])(\s|$)/);
	return (m ? m[1] : s.slice(0, 140)).replace(/[.;]$/, '');
};
const honor = (d) => {
	const sales = d.sales_page?.heading || '';
	const m = sales.match(/^(Prof\.?ssa|Prof\.|Dott\.ssa|Dott\.|Dr\.)/i);
	if (m) return m[1];
	return /dottoressa/i.test(d.honorific || '') ? 'Dott.ssa' : 'Dott.';
};
const outDocenti = (docData.docenti || []).map((d, i) => {
	const key = slugify(d.name);
	const photo = d.photo_file ? addMedia(`docente-${key}`, d.photo_file, 'docenti', { title: d.name, alt: d.name, kind: 'photo' }) : null;
	const cv = d.cv_downloadable && d.cv_file ? addMedia(`cv-${key}`, d.cv_file, 'cv', { title: `CV ${d.name}`, kind: 'cv' }) : null;
	const bioText = (d.bio_text || '').trim();
	const firstLine = bioText.split(/\n+/)[0].trim();
	const cut = (t, n) => (t.length <= n ? t : t.slice(0, t.lastIndexOf(' ', n)).replace(/[,;:(\s-]+$/, '') + '…');
	const short = bioText.length <= 160;
	return {
		key,
		name: `${honor(d)} ${d.name}`,
		// I profili originali non hanno un campo "qualifica" separato: lasciamo vuoto (compilabile da admin) e mostriamo la bio completa.
		titolo: overrides.docenti?.[key]?.titolo || "",
		bio_html: normChars(d.bio_html || ''),
		photo,
		cv,
		cv_url: d.cv_url || '',
		menu_order: d.position || i + 1,
	};
});

/* ---------------- Testimonianze generali (home + pagina testimonianze) ---------------- */
const courseByTitle = (label) => {
	if (!label) return '';
	const n = slugify(label).replace(/^corso-/, '');
	const hit = outCourses.find((c) => slugify(c.title).includes(n) || n.includes(slugify(c.title)));
	return hit ? hit.slug : '';
};
const outTesti = (testiData.testimonials || [])
	.filter((t) => (t.sources || []).some((s) => /home|sales/i.test(typeof s === 'string' ? s : s.source || s.type || '')))
	.map((t, i) => {
		const srcs = (t.sources || []).map((s) => (typeof s === 'string' ? s : s.source || s.type || '')).join(' ');
		return {
			key: `t-${t.id}-${slugify(t.name || 'anonimo').slice(0, 30)}`,
			name: normChars(t.name || '').trim(),
			text: normChars(t.text || ''),
			corso_slug: courseByTitle(t.course_referenced),
			corso_label: t.course_referenced || '',
			fonte: /home/i.test(srcs) && /sales/i.test(srcs) ? 'entrambe' : /home/i.test(srcs) ? 'home' : 'testimonianze',
			rating: t.rating || '',
			menu_order: i + 1,
		};
	});

/* ---------------- Pagine ---------------- */
const pageObj = (k) => (Array.isArray(pagesData.pages) ? pagesData.pages.find((p) => p.slug === k) : pagesData.pages[k]) || {};
const chi = pageObj('chi-siamo');
// Chi siamo: testi attuali (introduzione, mission, vision) con refusi evidenti corretti (elencati nel report).
const chiFix = (t) => t.replace(/dell’\s+alta/g, 'dell’alta').replace(/del compartimenti medico/g, 'del comparto medico').replace(/Con la collaborazioni/g, 'Con la collaborazione').replace(/&nbsp;/g, ' ').trim();
const chiHtml = [
	...(chi.intro_paragraphs || []).map((p) => `<p>${chiFix(p)}</p>`),
	chi.mission ? `<h2>Mission</h2><p>${chiFix(chi.mission.text)}</p>` : '',
	chi.vision ? `<h2>Vision</h2><p>${chiFix(chi.vision.text)}</p>` : '',
].join('\n');
const htmlOf = (p) => p.content_html || p.html || (p.sections || []).map((s) => s.html || '').join('\n') || '';
const outPages = [
	{ slug: 'home', title: 'Home', template: '', content_html: '', menu_order: 0 },
	{ slug: 'chi-siamo', title: 'Chi siamo', template: '', content_html: overrides.pages?.['chi-siamo']?.content_html || chiHtml, excerpt: '', menu_order: 2 },
	{ slug: 'docenti', title: 'Docenti', template: '', content_html: `<p>${(docData.intro?.[0]?.subheading || '').trim()}</p>`, menu_order: 3 },
	{ slug: 'testimonianze', title: 'Testimonianze', template: '', content_html: '', menu_order: 4 },
	{ slug: 'contatti', title: 'Contatti', template: '', content_html: overrides.pages?.contatti?.content_html || '', menu_order: 5 },
];

const menus = {
	primary: [
		{ label: 'Corsi', type: 'archive', target: 'corso' },
		{ label: 'Docenti', type: 'page', target: 'docenti' },
		{ label: 'Chi siamo', type: 'page', target: 'chi-siamo' },
		{ label: 'Testimonianze', type: 'page', target: 'testimonianze' },
		{ label: 'Contatti', type: 'page', target: 'contatti' },
	],
	footer: [
		{ label: 'Chi siamo', type: 'page', target: 'chi-siamo' },
		{ label: 'Docenti', type: 'page', target: 'docenti' },
		{ label: 'Testimonianze', type: 'page', target: 'testimonianze' },
		{ label: 'Contatti', type: 'page', target: 'contatti' },
	],
};

const redirects = [
	{ from: '/collections', to: '/corsi/' },
	{ from: '/pages/chi-siamo', to: '/chi-siamo/' },
	{ from: '/pages/docenti', to: '/docenti/' },
	{ from: '/pages/contatti', to: '/contatti/' },
	{ from: '/pages/terms', to: termsPdf ? '{media:' + termsPdf + '}' : '/' },
	{ from: '/pages/richiesta-informazioni-ecm', to: '/contatti/' },
	{ from: '/pages/privacy', to: privacyPdf ? '{media:' + privacyPdf + '}' : '/' },
	...(overrides.redirects || []),
];

const data = {
	version: 1,
	generated: new Date().toISOString(),
	options,
	terms: { area_tematica: AREAS, destinatario: DEST.map((d, i) => ({ slug: d.slug, name: d.name, order: i + 1 })) },
	media,
	courses: outCourses,
	docenti: outDocenti,
	testimonianze: outTesti,
	pages: outPages,
	menus,
	redirects,
};
writeFileSync(join(OUT, 'data.json'), JSON.stringify(data, null, 1));

// Zip con bsdtar di Windows (percorsi con slash) o zip di sistema.
const zipPath = join(root, 'dist', 'ecm-import.zip');
rmSync(zipPath, { force: true });
try {
	execFileSync(process.platform === 'win32' ? 'C:\\Windows\\System32\\tar.exe' : 'zip', process.platform === 'win32' ? ['-a', '-cf', zipPath, 'ecm-import'] : ['-r', zipPath, 'ecm-import'], { cwd: join(root, 'dist') });
} catch (e) {
	console.error('zip failed', e.message);
}
const total = media.reduce((s, m) => s + m.bytes, 0);
console.log(`courses ${outCourses.length}, docenti ${outDocenti.length}, testimonianze ${outTesti.length}, media ${media.length} (${(total / 1048576).toFixed(1)} MB), pages ${outPages.length}`);
console.log('areas:', AREAS.map((a) => `${a.slug}=${outCourses.filter((c) => c.areas.includes(a.slug)).length}`).join(' '));
console.log('destinatari:', DEST.map((d) => `${d.slug}=${outCourses.filter((c) => c.destinatari.includes(d.slug)).length}`).join(' '));
