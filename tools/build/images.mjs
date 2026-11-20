// Ottimizza le immagini art-directed (generate con Higgsfield) per il tema.
// Input:  tools/images/src/<key>.(png|jpg|webp)   +   tools/images/images.json (alt, dir, larghezze opzionali)
// Output: wp-content/themes/ecm/assets/img/<dir>/<key>-<w>.avif|webp  +  assets/img/manifest.json
// Uso:    node tools/build/images.mjs
import sharp from 'sharp';
import { readFileSync, writeFileSync, readdirSync, mkdirSync, existsSync } from 'node:fs';
import { join, dirname, extname, basename } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const root = join(here, '..', '..');
const SRC = join(root, 'tools', 'images', 'src');
const CFG = existsSync(join(root, 'tools', 'images', 'images.json')) ? JSON.parse(readFileSync(join(root, 'tools', 'images', 'images.json'), 'utf8')) : {};
const OUT = join(root, 'wp-content', 'themes', 'ecm', 'assets', 'img');
const DEFAULT_WIDTHS = [480, 800, 1200, 1600, 2000];

const manifest = {};
const files = existsSync(SRC) ? readdirSync(SRC).filter((f) => /\.(png|jpe?g|webp)$/i.test(f)) : [];
for (const f of files) {
	const key = basename(f, extname(f));
	const cfg = CFG[key] || {};
	const dir = cfg.dir || 'art';
	mkdirSync(join(OUT, dir), { recursive: true });
	const img = sharp(join(SRC, f), { failOn: 'none' }).rotate();
	const meta = await img.metadata();
	const widths = (cfg.widths || DEFAULT_WIDTHS).filter((w) => w <= meta.width);
	if (!widths.length) widths.push(meta.width);
	for (const w of widths) {
		const base = sharp(join(SRC, f)).rotate().resize({ width: w, withoutEnlargement: true });
		await base.clone().avif({ quality: cfg.avifQ || 52, effort: 6, chromaSubsampling: '4:2:0' }).toFile(join(OUT, dir, `${key}-${w}.avif`));
		await base.clone().webp({ quality: cfg.webpQ || 76, effort: 6, smartSubsample: true }).toFile(join(OUT, dir, `${key}-${w}.webp`));
	}
	manifest[key] = { dir, widths, width: meta.width, height: meta.height, alt: cfg.alt || '' };
	console.log(key, `${meta.width}x${meta.height}`, widths.join(','));
}
writeFileSync(join(OUT, 'manifest.json'), JSON.stringify(manifest, null, 1));
console.log(`manifest: ${Object.keys(manifest).length} immagini`);
