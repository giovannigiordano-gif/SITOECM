// Prepares logo variants from the original Thinkific PNGs (kept unchanged in shape and colour).
import sharp from 'sharp';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
const here = dirname(fileURLToPath(import.meta.url));
const [,, srcWide, srcMark] = process.argv;
const out = join(here, '..', '..', 'wp-content/themes/ecm/assets/img/brand');

async function variant(src, name, width, recolor) {
  const img = sharp(src).trim({ threshold: 2 }).resize({ width, withoutEnlargement: true });
  const { data, info } = await img.ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  if (recolor) {
    for (let i = 0; i < data.length; i += 4) {
      const [r, g, b, a] = [data[i], data[i + 1], data[i + 2], data[i + 3]];
      if (!a) continue;
      const max = Math.max(r, g, b), min = Math.min(r, g, b);
      const sat = max ? (max - min) / max : 0;
      if (sat < 0.18 && max < 170) { data[i] = 243; data[i + 1] = 242; data[i + 2] = 250; } // grey lettering -> on-ink
    }
  }
  const base = sharp(data, { raw: info });
  await base.clone().png({ compressionLevel: 9, palette: false }).toFile(join(out, `${name}.png`));
  await base.clone().webp({ quality: 92, alphaQuality: 100 }).toFile(join(out, `${name}.webp`));
  return info;
}
const a = await variant(srcWide, 'logo-dark', 840, false);
await variant(srcWide, 'logo-light', 840, true);
const m = await variant(srcMark, 'mark', 512, false);
for (const s of [32, 180, 192, 512]) await sharp(srcMark).trim({ threshold: 2 }).resize(s, s, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } }).png().toFile(join(out, `icon-${s}.png`));
console.log('logo', a.width, a.height, 'mark', m.width, m.height);
