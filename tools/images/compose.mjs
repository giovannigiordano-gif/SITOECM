// Immagini hero ricavate dai materiali reali del sito (nessuna generazione):
// - hero-chi-siamo: foto di un corso dal vivo, ritagliata dentro la cornice grafica del vecchio sito
// - hero-testimonianze: la foto della hero della pagina testimonianze originale
// - hero-docenti: mosaico delle foto dei docenti
// Uso: node tools/images/compose.mjs   (poi node tools/build/images.mjs)
import sharp from '../build/node_modules/sharp/lib/index.js';
import { readdirSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const A = join(root, 'data', 'scrape', 'assets');
const OUT = join(root, 'tools', 'images', 'src');
mkdirSync(OUT, { recursive: true });

// Corso dal vivo: fascia centrale tra la curva superiore e il logo in basso a sinistra.
const ev = join(A, 'testimonianze', '04-687f3b25bf6969ec05137960-jpeg.jpg');
const em = await sharp(ev).metadata();
const top = Math.round(em.height * 0.2);
const bottom = Math.round(em.height * 0.765);
await sharp(ev).extract({ left: 0, top, width: em.width, height: bottom - top }).jpeg({ quality: 92 }).toFile(join(OUT, 'hero-chi-siamo.jpg'));
// la stessa foto ritagliata va anche nella Libreria media (pagina Testimonianze), tramite la migrazione
mkdirSync(join(A, 'derived'), { recursive: true });
await sharp(ev).extract({ left: 0, top, width: em.width, height: bottom - top }).resize({ width: 2400 }).jpeg({ quality: 86 }).toFile(join(A, 'derived', 'evento-dal-vivo.jpg'));

// Hero testimonianze originale.
await sharp(join(A, 'testimonianze', '02-687e0b95d5539649bcd17846-jpeg.jpg')).jpeg({ quality: 92 }).toFile(join(OUT, 'hero-testimonianze.jpg'));

// Mosaico docenti 6x3, 2560x1440: ritratti in scala di grigi (la lente della hero li rivela a colori).
const files = readdirSync(join(A, 'docenti')).filter((f) => /\.(jpe?g|png)$/i.test(f)).sort();
const cols = 6, rows = 3, W = 2560, H = 1440, cw = Math.ceil(W / cols), ch = Math.ceil(H / rows);
const tiles = await Promise.all(
	files.slice(0, cols * rows).map(async (f, i) => ({
		input: await (async () => {
			// via la cornice rossa e il logo del vecchio sito stampati sulla foto
			const src = join(A, 'docenti', f);
			const m = await sharp(src).metadata();
			const box = { left: Math.round(m.width * 0.07), top: Math.round(m.height * 0.14), width: Math.round(m.width * 0.86), height: Math.round(m.height * 0.6) };
			return sharp(src).extract(box).resize(cw, ch, { fit: 'cover', position: 'attention' }).toBuffer();
		})(),
		left: (i % cols) * cw,
		top: Math.floor(i / cols) * ch,
	}))
);
await sharp({ create: { width: cols * cw, height: rows * ch, channels: 3, background: '#1d1630' } })
	.composite(tiles)
	.resize(W, H, { fit: 'cover' })
	.jpeg({ quality: 90 })
	.toFile(join(OUT, 'hero-docenti.jpg'));
console.log('ok: hero-chi-siamo, hero-testimonianze, hero-docenti');
