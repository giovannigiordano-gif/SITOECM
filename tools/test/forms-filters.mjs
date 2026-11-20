// Test funzionali: filtri del catalogo (senza reload, URL condivisibili), form contatti (validazione) e newsletter.
// Il modulo contatti NON viene inviato con dati validi (manderebbe una email reale alla segreteria).
import { chromium } from 'playwright';

const BASE = process.env.ECM_BASE || 'https://soothing-khaki-okapi-74.hostingersite.com';
const out = [];
const ok = (name, cond, extra = '') => out.push(`${cond ? 'OK ' : 'KO '} ${name}${extra ? ' (' + extra + ')' : ''}`);

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
await ctx.addCookies([{ name: 'ecm_consent', value: encodeURIComponent('{"necessary":true,"statistics":false,"marketing":false,"v":1}'), url: BASE }]);
const page = await ctx.newPage();
const errors = [];
page.on('pageerror', (e) => errors.push(e.message));

// --- Catalogo ---
await page.goto(`${BASE}/corsi/`, { waitUntil: 'networkidle' });
await page.evaluate(() => { window.__noReload = true; });
await page.click('label.chip:has-text("Fitoterapia")');
await page.waitForTimeout(1200);
let visible = await page.locator('[data-course]:not([hidden])').count();
const same = await page.evaluate(() => window.__noReload === true);
ok('filtro area Fitoterapia senza reload', visible === 3 && same, `${visible} corsi, stessa pagina: ${same}`);
ok('URL condivisibile aggiornato', page.url().includes('area=fitoterapia'), page.url());
await page.fill('#f-q', 'sistemica');
await page.waitForTimeout(900);
visible = await page.locator('[data-course]:not([hidden])').count();
ok('ricerca "sistemica" dentro Fitoterapia', visible === 1, `${visible}`);
ok('URL con ricerca', page.url().includes('q=sistemica'));
await page.fill('#f-q', 'zzzz');
await page.waitForTimeout(900);
ok('stato vuoto mostrato', await page.locator('[data-empty]').isVisible());
await page.click('[data-filters-reset]');
await page.waitForTimeout(1000);
visible = await page.locator('[data-course]:not([hidden])').count();
ok('reset filtri', visible === 27, `${visible}`);
await page.selectOption('#f-per', 'veterinari');
await page.waitForTimeout(1000);
visible = await page.locator('[data-course]:not([hidden])').count();
ok('filtro professione Veterinari', visible > 0 && visible < 27, `${visible}`);
// link condiviso aperto a freddo
await page.goto(`${BASE}/corsi/?area=nutrizione-animale&q=gatto`, { waitUntil: 'networkidle' });
visible = await page.locator('[data-course]:not([hidden])').count();
ok('link condiviso applica i filtri al caricamento', visible > 0 && visible <= 5, `${visible}`);
ok('chip area attivo dal link', await page.locator('input[name="area"][value="nutrizione-animale"]').isChecked());

// --- Contatti: validazione ---
await page.goto(`${BASE}/contatti/`, { waitUntil: 'networkidle' });
await page.click('form[data-form="contact"] button[type="submit"]');
await page.waitForTimeout(500);
const errNome = await page.locator('[data-error-for="nome"]').textContent();
const errPriv = await page.locator('[data-error-for="privacy"]').textContent();
ok('contatti: errori inline sui campi obbligatori', !!errNome && !!errPriv, `${errNome} / ${errPriv}`);
ok('contatti: focus sul primo campo non valido', await page.evaluate(() => document.activeElement?.id === 'cf-nome'));
await page.fill('#cf-email', 'non-una-email');
await page.locator('#cf-email').blur();
ok('contatti: email non valida segnalata', ((await page.locator('[data-error-for="email"]').textContent()) || '').length > 5);
// validazione lato server (REST) senza invio email: payload incompleto
const rest = await page.evaluate(async () => {
	const fd = new FormData();
	fd.append('nome', 'Test');
	fd.append('_ts', String(Math.floor(Date.now() / 1000) - 60));
	const r = await fetch(window.ECM.rest + 'contact', { method: 'POST', headers: { 'X-WP-Nonce': window.ECM.nonce }, body: fd });
	return { status: r.status, json: await r.json() };
});
ok('contatti: il server rifiuta un invio incompleto con messaggi in italiano', rest.status === 422 && !!rest.json.errors, `${rest.status} ${JSON.stringify(rest.json.errors || {}).slice(0, 120)}`);

// --- Newsletter: iscrizione di prova ---
await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
const nl = page.locator('form[data-form="newsletter"]');
await nl.scrollIntoViewIfNeeded();
await page.waitForTimeout(3500); // time trap del server (invio < 3 s scartato)
await nl.locator('input[type="email"]').fill('verifica-newsletter@example.com');
await nl.locator('input[name="privacy"]').check();
await nl.locator('button[type="submit"]').click();
await page.waitForTimeout(2500);
const st = (await nl.locator('[data-form-status]').textContent()) || '';
ok('newsletter: iscrizione di prova accettata', /grazie|iscritt/i.test(st), st);

// --- Accedi ---
await page.goto(`${BASE}/`, { waitUntil: 'domcontentloaded' });
const accedi = await page.locator('.site-header .btn--login').getAttribute('href');
ok('link Accedi verso la piattaforma Thinkific', /\/users\/sign_in$/.test(accedi || ''), accedi);

ok('nessun errore JavaScript', errors.length === 0, errors.join(' | '));
await browser.close();
console.log(out.join('\n'));
