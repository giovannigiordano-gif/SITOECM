// Taglia i banner compositi dei patrocini (più loghi in un'unica immagine) in loghi singoli.
// I nomi degli enti, in ordine da sinistra a destra, sono quelli letti dalle immagini (logos_index / bundle).
// Uso: node tools/migrate/split-banners.mjs   → data/scrape/assets/logos-split/<slug>.png + split.json
import sharp from '../build/node_modules/sharp/lib/index.js';
import { readdirSync, writeFileSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const LOGOS = join(root, 'data', 'scrape', 'assets', 'logos');
const OUT = join(root, 'data', 'scrape', 'assets', 'logos-split');
mkdirSync(OUT, { recursive: true });

const slugify = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/['’`]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

const BANNERS = [
	{
		match: 'jnrfa4dytpqpmiowcsg7',
		names: [
			'Ordine dei Medici Veterinari Caserta',
			'Ordine dei Medici Veterinari della Provincia di Parma',
			'Ordine dei Medici Veterinari della Provincia di Ancona',
			'Ordine dei Medici Veterinari della Provincia di Mantova',
			'Ordine dei Biologi Piemonte – Liguria – Valle d’Aosta',
			'Ordine dei Medici Veterinari della Provincia di Reggio Emilia',
		],
	},
	{
		match: 'iqeqynfsqhcyhi5mw6ti',
		names: [
			'Ordine Veterinari della Provincia di Bologna',
			'Ordine Biologi Sicilia',
			'Ordine dei Medici Veterinari della Provincia di Perugia',
			'Ordine dei Medici Veterinari della Provincia di Torino',
			'Ordine Veterinari della Provincia del Verbano-Cusio-Ossola',
		],
	},
];

// Colonne "con inchiostro": almeno un pixel non bianco e non trasparente.
const inkColumns = (data, w, h, ch) => {
	const cols = new Array(w).fill(0);
	for (let y = 0; y < h; y++) {
		for (let x = 0; x < w; x++) {
			const i = (y * w + x) * ch;
			const a = ch === 4 ? data[i + 3] : 255;
			if (a < 40) continue;
			if (data[i] < 225 || data[i + 1] < 225 || data[i + 2] < 225) cols[x]++;
		}
	}
	return cols;
};
// Gruppi di colonne separati da almeno minGap colonne vuote.
const segments = (cols, minGap, minInk) => {
	const segs = [];
	let start = -1;
	let gap = 0;
	for (let x = 0; x < cols.length; x++) {
		const ink = cols[x] >= minInk;
		if (ink) {
			if (start < 0) start = x;
			gap = 0;
		} else if (start >= 0) {
			gap++;
			if (gap >= minGap) {
				segs.push([start, x - gap]);
				start = -1;
				gap = 0;
			}
		}
	}
	if (start >= 0) segs.push([start, cols.length - 1 - gap]);
	return segs.filter(([a, b]) => b - a > 12);
};

// i loghi sono in sottocartelle per corso
const files = readdirSync(LOGOS, { recursive: true }).map(String);
const result = [];
for (const banner of BANNERS) {
	const file = files.find((f) => f.includes(banner.match));
	if (!file) throw new Error('Banner non trovato: ' + banner.match);
	const src = join(LOGOS, file);
	const { data, info } = await sharp(src).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
	const cols = inkColumns(data, info.width, info.height, info.channels);
	// soglia del vuoto adattiva: si cerca il primo valore che dà esattamente un gruppo per ente
	let segs = [];
	for (const minGap of [40, 32, 26, 20, 16, 12, 9, 6]) {
		segs = segments(cols, minGap, 2);
		if (segs.length === banner.names.length) break;
	}
	if (segs.length !== banner.names.length) throw new Error(`${file}: trovati ${segs.length} loghi, attesi ${banner.names.length}`);
	for (let i = 0; i < segs.length; i++) {
		const [x0, x1] = segs[i];
		const pad = 6;
		const left = Math.max(0, x0 - pad);
		const width = Math.min(info.width - left, x1 - x0 + 1 + pad * 2);
		const slug = slugify(banner.names[i]);
		const out = join(OUT, `${slug}.png`);
		// ritaglio della colonna, poi rifilatura dei margini bianchi in alto e in basso
		const crop = await sharp(src).extract({ left, top: 0, width, height: info.height }).png().toBuffer();
		await sharp(crop).trim({ background: '#ffffff', threshold: 18 }).extend({ top: 8, bottom: 8, left: 8, right: 8, background: { r: 255, g: 255, b: 255, alpha: 0 } }).png().toFile(out);
		const m = await sharp(out).metadata();
		result.push({ banner: file, name: banner.names[i], slug, file: `data/scrape/assets/logos-split/${slug}.png`, width: m.width, height: m.height });
		console.log(`${slug}  ${m.width}x${m.height}`);
	}
}
writeFileSync(join(OUT, 'split.json'), JSON.stringify(result, null, 1));
console.log(`ok: ${result.length} loghi`);
