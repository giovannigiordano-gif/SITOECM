// Build script for the ECM theme: vendor copy, icon sprite, CSS bundle.
// Usage: node tools/build/build.mjs   (run from repo root or anywhere)
import { readFileSync, writeFileSync, copyFileSync, readdirSync, mkdirSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const root = join(here, '..', '..');
const theme = join(root, 'wp-content', 'themes', 'ecm');
const nm = join(here, 'node_modules');
const mk = (p) => { if (!existsSync(p)) mkdirSync(p, { recursive: true }); };

// 1. Vendor JS (GSAP is free incl. plugins since 3.13; Lenis MIT)
mk(join(theme, 'assets/vendor'));
for (const f of ['gsap.min.js', 'ScrollTrigger.min.js', 'Flip.min.js', 'SplitText.min.js']) copyFileSync(join(nm, 'gsap/dist', f), join(theme, 'assets/vendor', f));
copyFileSync(join(nm, 'lenis/dist/lenis.min.js'), join(theme, 'assets/vendor/lenis.min.js'));

// 2. Fonts (Mona Sans variable: wght 200-900, wdth 75-125)
mk(join(theme, 'assets/fonts'));
for (const sub of ['latin', 'latin-ext']) for (const st of ['normal', 'italic'])
  copyFileSync(join(nm, '@fontsource-variable/mona-sans/files', `mona-sans-${sub}-standard-${st}.woff2`), join(theme, 'assets/fonts', `mona-sans-${sub}-${st}.woff2`));

// 3. Icon sprite (Phosphor regular)
const icons = {
  'arrow-right': 'arrow-right', 'arrow-up-right': 'arrow-up-right', 'arrow-left': 'arrow-left', 'whatsapp': 'whatsapp-logo',
  'pdf': 'file-pdf', 'download': 'download-simple', 'search': 'magnifying-glass', 'close': 'x', 'plus': 'plus', 'minus': 'minus',
  'mail': 'envelope-simple', 'instagram': 'instagram-logo', 'facebook': 'facebook-logo', 'youtube': 'youtube-logo',
  'sign-in': 'sign-in', 'check': 'check', 'caret-down': 'caret-down', 'menu': 'list', 'copy': 'copy', 'phone': 'phone',
  'quote': 'quotes', 'student': 'student', 'certificate': 'certificate', 'warning': 'warning-circle', 'funnel': 'funnel-simple',
  'chat': 'chat-circle-text', 'user': 'user', 'calendar': 'calendar-blank', 'cookie': 'cookie', 'sliders': 'sliders-horizontal',
  'arrow-down': 'arrow-down', 'star': 'fill/star-fill', 'play': 'fill/play-fill', 'pause': 'fill/pause-fill'
};
let sprite = '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden">';
for (const [id, file] of Object.entries(icons)) {
  const svg = readFileSync(join(nm, '@phosphor-icons/core/assets', file.includes('/') ? `${file}.svg` : `regular/${file}.svg`), 'utf8');
  const vb = (svg.match(/viewBox="([^"]+)"/) || [])[1] || '0 0 256 256';
  const inner = svg.replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>\s*$/, '');
  sprite += `<symbol id="i-${id}" viewBox="${vb}">${inner}</symbol>`;
}
sprite += '</svg>';
writeFileSync(join(theme, 'assets/icons/sprite.svg'), sprite);

// 4. CSS bundle: concatenate src in order, then a light minify
const order = ['tokens', 'fonts', 'base', 'layout', 'components', 'header-footer', 'hero', 'home', 'stories', 'patrons', 'catalog', 'course', 'pages', 'motion', 'utilities'];
const srcDir = join(theme, 'assets/css/src');
const files = order.map((n) => join(srcDir, `${n}.css`)).filter(existsSync);
const extra = readdirSync(srcDir).filter((f) => f.endsWith('.css') && !order.includes(f.replace('.css', '')));
if (extra.length) console.warn('CSS not in build order:', extra);
const css = files.map((f) => `/* ${f.split(/[\/]/).pop()} */\n` + readFileSync(f, 'utf8')).join('\n');
writeFileSync(join(theme, 'assets/css/main.css'), css);
const min = css.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\s+/g, ' ').replace(/\s*([{};:,>])\s*/g, '$1').replace(/;}/g, '}').trim();
writeFileSync(join(theme, 'assets/css/main.min.css'), min);
console.log(`CSS ${files.length} files, ${(css.length / 1024).toFixed(1)}KB -> ${(min.length / 1024).toFixed(1)}KB min; icons ${Object.keys(icons).length}`);
