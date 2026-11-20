// Audit accessibilità automatico (axe-core, tag WCAG 2.0/2.1/2.2 A e AA) su desktop e mobile.
import { chromium } from 'playwright';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const axeSrc = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const BASE = process.env.ECM_BASE || 'https://soothing-khaki-okapi-74.hostingersite.com';
const paths = ['/', '/corsi/', '/courses/metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta/', '/courses/fitoterapia-sistemica/', '/docenti/', '/chi-siamo/', '/testimonianze/', '/contatti/', '/pagina-inesistente/'];
const browser = await chromium.launch();
let total = 0;
for (const vp of [{ width: 1440, height: 900 }, { width: 390, height: 844 }]) {
	const ctx = await browser.newContext({ viewport: vp, reducedMotion: 'reduce' });
	await ctx.addCookies([{ name: 'ecm_consent', value: encodeURIComponent('{"necessary":true,"v":1}'), url: BASE }]);
	const page = await ctx.newPage();
	for (const p of paths) {
		await page.goto(BASE + p + (p.includes('?') ? '&' : '?') + 'v=' + Date.now(), { waitUntil: 'networkidle' });
		await page.addScriptTag({ content: axeSrc });
		const res = await page.evaluate(async () => await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] } }));
		const v = res.violations;
		total += v.length;
		console.log(`${vp.width}px ${p} -> ${v.length} violazioni`);
		for (const x of v) console.log(`   [${x.impact}] ${x.id}: ${x.help} (${x.nodes.length}) e.g. ${x.nodes[0].target.join(' ')} ${x.nodes[0].failureSummary?.split('\n').slice(1, 2).join('')}`);
	}
	await ctx.close();
}
await browser.close();
console.log('TOTALE', total);
