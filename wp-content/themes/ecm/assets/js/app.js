/**
 * ECM La Lente: comportamento e motion del tema.
 * Dipendenze globali (caricate localmente): gsap, ScrollTrigger, SplitText, Flip (solo catalogo), Lenis.
 * Tutto degrada: senza JS il sito è completo; con prefers-reduced-motion niente smooth scroll, pin o reveal.
 */
(() => {
	'use strict';

	const d = document;
	const w = window;
	const html = d.documentElement;
	const CFG = w.ECM || { i18n: {} };
	const T = CFG.i18n || {};
	const $ = (s, c = d) => c.querySelector(s);
	const $$ = (s, c = d) => Array.from(c.querySelectorAll(s));
	const mqReduce = w.matchMedia('(prefers-reduced-motion: reduce)');
	const REDUCED = mqReduce.matches;
	const FINE = w.matchMedia('(hover: hover) and (pointer: fine)').matches;
	const HAS_GSAP = typeof w.gsap !== 'undefined' && typeof w.ScrollTrigger !== 'undefined';
	const EASE = 'expo.out';

	// Failsafe: se qualcosa va storto, i contenuti tornano visibili.
	const failsafe = setTimeout(() => html.classList.add('motion-failsafe'), 3000);

	if (HAS_GSAP) {
		gsap.registerPlugin(ScrollTrigger);
		if (w.SplitText) gsap.registerPlugin(SplitText);
		if (w.Flip) gsap.registerPlugin(Flip);
		ScrollTrigger.config({ ignoreMobileResize: true });
		gsap.defaults({ ease: EASE, duration: 0.9 });
	}

	/* ------------------------------------------------------------------ */
	/* Smooth scroll (Lenis) solo su desktop con puntatore fine            */
	/* ------------------------------------------------------------------ */
	let lenis = null;
	function initLenis() {
		if (REDUCED || !FINE || typeof w.Lenis === 'undefined' || !HAS_GSAP) return;
		lenis = new Lenis({ lerp: 0.1, wheelMultiplier: 1, smoothWheel: true });
		lenis.on('scroll', ScrollTrigger.update);
		gsap.ticker.add((t) => lenis.raf(t * 1000));
		gsap.ticker.lagSmoothing(0);
	}
	const scrollToY = (y) => {
		if (lenis) lenis.scrollTo(y, { duration: 1.4 });
		else w.scrollTo({ top: y, behavior: REDUCED ? 'auto' : 'smooth' });
	};

	/* ------------------------------------------------------------------ */
	/* Header: compatto dopo lo scroll, si nasconde scendendo               */
	/* ------------------------------------------------------------------ */
	function initHeader() {
		const header = $('[data-header]');
		if (!header) return;
		let lastY = w.scrollY;
		let ticking = false;
		const update = () => {
			const y = w.scrollY;
			header.classList.toggle('is-scrolled', y > 24);
			const menuOpen = d.body.classList.contains('menu-open');
			if (!menuOpen && y > lastY + 6 && y > 240) header.classList.add('is-hidden');
			else if (y < lastY - 6 || y < 240) header.classList.remove('is-hidden');
			if (Math.abs(y - lastY) > 6) lastY = y;
			ticking = false;
		};
		if (HAS_GSAP) {
			ScrollTrigger.create({ start: 0, end: 'max', onUpdate: () => update() });
		} else {
			w.addEventListener('scroll', () => {
				if (!ticking) {
					ticking = true;
					requestAnimationFrame(update);
				}
			}, { passive: true });
		}
		update();
		header.addEventListener('focusin', () => header.classList.remove('is-hidden'));
	}

	/* ------------------------------------------------------------------ */
	/* Menu mobile: pannello che si apre come una lente dal bottone         */
	/* ------------------------------------------------------------------ */
	function initMenu() {
		const btn = $('[data-menu-toggle]');
		const panel = $('[data-menu-panel]');
		if (!btn || !panel) return;
		let open = false;
		const focusables = () => $$('a, button', panel).filter((el) => el.offsetParent !== null);

		const setOrigin = () => {
			const r = btn.getBoundingClientRect();
			panel.style.setProperty('--mx', `${r.left + r.width / 2}px`);
			panel.style.setProperty('--my', `${r.top + r.height / 2}px`);
			return Math.hypot(w.innerWidth, w.innerHeight);
		};
		const toggle = (state) => {
			open = state;
			btn.setAttribute('aria-expanded', String(open));
			d.body.classList.toggle('menu-open', open);
			const max = setOrigin();
			if (open) {
				panel.hidden = false;
				lenis && lenis.stop();
				if (HAS_GSAP && !REDUCED) {
					gsap.fromTo(panel, { '--r': '0px' }, { '--r': `${max}px`, duration: 0.8, ease: 'expo.inOut' });
					gsap.fromTo($$('.menu-panel__list li, .menu-panel__foot > *', panel), { y: 40, autoAlpha: 0 }, { y: 0, autoAlpha: 1, stagger: 0.05, duration: 0.8, delay: 0.25 });
				} else {
					panel.style.setProperty('--r', `${max}px`);
				}
				const f = focusables();
				f.length && setTimeout(() => f[0].focus(), 50);
			} else {
				lenis && lenis.start();
				const done = () => {
					panel.hidden = true;
				};
				if (HAS_GSAP && !REDUCED) gsap.to(panel, { '--r': '0px', duration: 0.55, ease: 'expo.in', onComplete: done });
				else done();
				btn.focus();
			}
		};
		btn.addEventListener('click', () => toggle(!open));
		d.addEventListener('keydown', (e) => {
			if (!open) return;
			if (e.key === 'Escape') toggle(false);
			if (e.key === 'Tab') {
				const f = [btn, ...focusables()];
				const first = f[0];
				const last = f[f.length - 1];
				if (e.shiftKey && d.activeElement === first) {
					e.preventDefault();
					last.focus();
				} else if (!e.shiftKey && d.activeElement === last) {
					e.preventDefault();
					first.focus();
				}
			}
		});
		panel.addEventListener('click', (e) => {
			if (e.target.closest('a')) toggle(false);
		});
		w.addEventListener('resize', () => {
			if (open && w.innerWidth >= 1024) toggle(false);
		});
	}

	/* ------------------------------------------------------------------ */
	/* Reveal tipografici: righe mascherate + "respiro" della larghezza     */
	/* ------------------------------------------------------------------ */
	function revealLines(el, opts = {}) {
		if (!HAS_GSAP || !w.SplitText) {
			el.classList.add('is-split');
			return null;
		}
		const split = SplitText.create(el, {
			type: 'lines',
			mask: 'lines',
			linesClass: 'line',
			autoSplit: true,
			onSplit(self) {
				el.classList.add('is-split');
				return gsap.from(self.lines, {
					yPercent: 112,
					fontStretch: '78%',
					duration: opts.duration || 1.3,
					stagger: 0.09,
					ease: 'expo.out',
					delay: opts.delay || 0,
					scrollTrigger: opts.immediate ? undefined : { trigger: el, start: 'top 88%', once: true },
					onComplete: () => gsap.set(self.lines, { clearProps: 'fontStretch' }),
				});
			},
		});
		return split;
	}

	function initReveals() {
		const lines = $$('[data-reveal="lines"]');
		const fades = $$('[data-reveal="fade"]');
		if (REDUCED) {
			lines.forEach((el) => el.classList.add('is-split'));
			fades.forEach((el) => el.classList.add('is-in'));
			return;
		}
		lines.forEach((el) => {
			const inHero = !!el.closest('.hero, .course-hero, .about-hero, .page-head');
			revealLines(el, { immediate: inHero, delay: inHero ? 0.15 : 0 });
		});
		const io = new IntersectionObserver(
			(entries) => {
				entries.forEach((en) => {
					if (!en.isIntersecting) return;
					const el = en.target;
					const sib = el.parentElement ? $$(':scope > [data-reveal="fade"]', el.parentElement) : [];
					const i = Math.max(0, sib.indexOf(el));
					el.style.transitionDelay = `${Math.min(i, 6) * 70 + (el.closest('.hero, .page-head, .course-hero') ? 350 : 0)}ms`;
					el.classList.add('is-in');
					io.unobserve(el);
				});
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.05 }
		);
		fades.forEach((el) => io.observe(el));
	}

	/* ------------------------------------------------------------------ */
	/* Contatori (numeri reali)                                             */
	/* ------------------------------------------------------------------ */
	function initCounters() {
		const nums = $$('[data-count]');
		if (!nums.length || REDUCED || !HAS_GSAP) return;
		const fmt = new Intl.NumberFormat('it-IT');
		nums.forEach((el) => {
			const end = parseInt(el.dataset.count, 10) || 0;
			const obj = { v: 0 };
			el.textContent = '0';
			gsap.to(obj, {
				v: end,
				duration: 1.6,
				ease: 'power3.out',
				scrollTrigger: { trigger: el, start: 'top 90%', once: true },
				onUpdate: () => {
					el.textContent = fmt.format(Math.round(obj.v));
				},
			});
		});
	}

	/* ------------------------------------------------------------------ */
	/* Hero: luce radente WebGL che segue il cursore con un leggero ritardo  */
	/* e lieve parallasse (deriva lenta automatica sui dispositivi touch)   */
	/* ------------------------------------------------------------------ */
	const LENS_VERT = 'attribute vec2 p;varying vec2 v;void main(){v=p*.5+.5;gl_Position=vec4(p,0.,1.);}';
	const LENS_FRAG = [
		'precision highp float;',
		'varying vec2 v;',
		'uniform sampler2D t;uniform vec2 res;uniform vec2 img;uniform vec2 focus;uniform vec2 mouse;uniform vec2 par;',
		'uniform float rad;uniform float mono;uniform float kz;uniform float shift;',
		'vec2 cover(vec2 uv){',
		'  uv=(uv-.5)/kz+.5+par;uv.y+=shift;',
		'  float rc=res.x/res.y, ri=img.x/img.y;',
		'  vec2 s=rc>ri?vec2(1.,ri/rc):vec2(rc/ri,1.);',
		'  vec2 f=vec2(focus.x,1.-focus.y);',
		'  return (uv-.5)*s+.5+(f-.5)*(1.-s);',
		'}',
		'void main(){',
		'  vec2 px=vec2(v.x*res.x,(1.-v.y)*res.y);',
		'  float d=distance(px,mouse)/max(rad,1.);',
		'  float light=1.-smoothstep(0.,1.,d);',
		'  light*=light;',
		'  vec3 c=texture2D(t,cover(v)).rgb;',
		'  float l=dot(c,vec3(.299,.587,.114));',
		'  c=mix(c,vec3(l*1.03),mono*(1.-light));',
		'  c*=mix(.86,1.16,light);',
		'  gl_FragColor=vec4(c,1.);',
		'}',
	].join('\n');

	function createHeroLens(hero) {
		const canvas = $('[data-hero-gl]', hero);
		const img = $('[data-hero-img]', hero);
		const ring = $('[data-hero-ring]', hero);
		const media = $('[data-hero-media]', hero);
		if (!canvas || !img || !media) return null;
		let gl;
		try {
			gl = canvas.getContext('webgl', { alpha: false, antialias: false, premultipliedAlpha: false, preserveDrawingBuffer: false });
		} catch (_) {
			gl = null;
		}
		if (!gl) return null;
		const compile = (type, src) => {
			const s = gl.createShader(type);
			gl.shaderSource(s, src);
			gl.compileShader(s);
			if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(s) || 'shader');
			return s;
		};
		const prog = gl.createProgram();
		try {
			gl.attachShader(prog, compile(gl.VERTEX_SHADER, LENS_VERT));
			gl.attachShader(prog, compile(gl.FRAGMENT_SHADER, LENS_FRAG));
			gl.linkProgram(prog);
			if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) return null;
		} catch (err) {
			// eslint-disable-next-line no-console
			console.warn('[ECM] lente non disponibile', err);
			return null;
		}
		gl.useProgram(prog);
		gl.bindBuffer(gl.ARRAY_BUFFER, gl.createBuffer());
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
		const aP = gl.getAttribLocation(prog, 'p');
		gl.enableVertexAttribArray(aP);
		gl.vertexAttribPointer(aP, 2, gl.FLOAT, false, 0, 0);
		const U = {};
		['t', 'res', 'img', 'focus', 'mouse', 'par', 'rad', 'mono', 'kz', 'shift'].forEach((n) => (U[n] = gl.getUniformLocation(prog, n)));
		gl.bindTexture(gl.TEXTURE_2D, gl.createTexture());
		gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);

		const mono = hero.hasAttribute('data-hero-mono') ? 1 : 0;
		const dpr = Math.min(w.devicePixelRatio || 1, 1.5);
		const st = { x: 0, y: 0, tx: 0, ty: 0, r: 0, tr: 0, hover: false, ready: false, running: false, W: 0, H: 0, base: 120, focus: [0.72, 0.5], kz: 1.025, shift: 0, px: 0, py: 0 };

		const readFocus = () => {
			const op = (getComputedStyle(img).objectPosition || '50% 50%').split(' ');
			const pct = (s) => (s && s.endsWith('%') ? parseFloat(s) / 100 : 0.5);
			st.focus = [pct(op[0]), pct(op[1] || op[0])];
		};
		const rest = () => (st.W >= 900 ? [st.W * 0.7, st.H * 0.44] : [st.W * 0.6, st.H * 0.3]);
		const resize = () => {
			const r = hero.getBoundingClientRect();
			st.W = r.width;
			st.H = r.height;
			canvas.width = Math.round(st.W * dpr);
			canvas.height = Math.round(st.H * dpr);
			gl.viewport(0, 0, canvas.width, canvas.height);
			// raggio ampio e morbido: una luce, non una lente
			st.base = Math.max(st.W, st.H) * (st.W >= 900 ? 0.4 : 0.5);
			if (st.ready && !st.hover) st.tr = st.base;
			readFocus();
			if (!st.x) {
				const [rx, ry] = rest();
				st.x = st.tx = rx;
				st.y = st.ty = ry;
			}
		};
		const upload = () => {
			try {
				gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGB, gl.RGB, gl.UNSIGNED_BYTE, img);
				gl.uniform2f(U.img, img.naturalWidth, img.naturalHeight);
				return true;
			} catch (_) {
				return false;
			}
		};
		const draw = () => {
			// canvas = dimensioni della hero: coordinate della lente dirette, nessuna misura del layout per frame
			gl.uniform2f(U.res, canvas.width, canvas.height);
			gl.uniform1f(U.kz, st.kz);
			gl.uniform1f(U.shift, st.shift);
			gl.uniform2f(U.focus, st.focus[0], st.focus[1]);
			gl.uniform2f(U.mouse, st.x * dpr, st.y * dpr);
			gl.uniform2f(U.par, st.px, st.py);
			gl.uniform1f(U.rad, st.r * dpr);
			gl.uniform1f(U.mono, mono);
			gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
			if (ring) {
				ring.style.setProperty('--rx', `${st.x.toFixed(1)}px`);
				ring.style.setProperty('--ry', `${st.y.toFixed(1)}px`);
				ring.style.setProperty('--rs', `${(st.r * 2.36).toFixed(1)}px`);
			}
		};
		const t0 = performance.now();
		const frame = (now) => {
			if (!st.running) return;
			const t = (now - t0) / 1000;
			if (!st.hover) {
				const [rx, ry] = rest();
				st.tx = rx + Math.sin(t * 0.21) * st.W * 0.06;
				st.ty = ry + Math.sin(t * 0.17 + 1.3) * st.H * 0.07;
			}
			// inseguimento lento: la luce arriva dopo il cursore, mai a scatti
			st.x += (st.tx - st.x) * 0.055;
			st.y += (st.ty - st.y) * 0.055;
			st.r += (st.tr - st.r) * 0.04;
			// parallasse: l'immagine scivola di poco nella direzione opposta
			st.px += ((0.5 - st.x / (st.W || 1)) * 0.016 - st.px) * 0.05;
			st.py += ((st.y / (st.H || 1) - 0.5) * 0.016 - st.py) * 0.05;
			draw();
			requestAnimationFrame(frame);
		};
		const start = () => {
			if (st.running || !st.ready) return;
			st.running = true;
			requestAnimationFrame(frame);
		};
		const stop = () => (st.running = false);

		const boot = () => {
			resize();
			if (!upload()) return;
			st.ready = true;
			hero.classList.add('is-gl');
			setTimeout(() => (st.tr = st.base), 650);
			start();
		};
		const whenLoaded = img.complete && img.naturalWidth ? Promise.resolve() : new Promise((res) => img.addEventListener('load', res, { once: true }));
		whenLoaded.then(() => (img.decode ? img.decode().catch(() => {}) : null)).then(boot);
		// cambio di sorgente responsive (rotazione, resize importante)
		img.addEventListener('load', () => st.ready && upload());

		if (FINE) {
			hero.addEventListener('pointerenter', () => {
				st.hover = true;
				st.tr = st.base * 1.06;
			});
			hero.addEventListener('pointermove', (e) => {
				const r = hero.getBoundingClientRect();
				st.tx = e.clientX - r.left;
				st.ty = e.clientY - r.top;
			});
			hero.addEventListener('pointerleave', () => {
				st.hover = false;
				st.tr = st.base;
			});
		}
		let rT;
		w.addEventListener('resize', () => {
			clearTimeout(rT);
			rT = setTimeout(resize, 120);
		});
		new IntersectionObserver(([en]) => (en.isIntersecting ? start() : stop())).observe(hero);
		d.addEventListener('visibilitychange', () => (d.hidden ? stop() : start()));
		return st;
	}

	function initHeroes() {
		$$('[data-hero]').forEach((hero) => {
			const media = $('[data-hero-media]', hero);
			const body = $('.hero__body', hero);
			const lens = !REDUCED && hero.hasAttribute('data-hero-lens') ? createHeroLens(hero) : null;
			if (HAS_GSAP && !REDUCED && media) {
				if (lens) {
					// con la lente attiva zoom e parallasse avvengono nello shader
					ScrollTrigger.create({ trigger: hero, start: 'top top', end: 'bottom top', onUpdate: (s) => (lens.shift = s.progress * 0.1) });
				} else {
					gsap.fromTo(media, { scale: 1.12 }, { scale: 1, duration: 2.6, ease: 'expo.out' });
					gsap.to(media, { yPercent: 12, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
				}
				body && gsap.to(body, { y: -70, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
			}
		});
		$$('[data-scroll-to]').forEach((a) =>
			a.addEventListener('click', (e) => {
				const target = $(a.getAttribute('href'));
				if (!target) return;
				e.preventDefault();
				scrollToY(target.getBoundingClientRect().top + w.scrollY);
				target.setAttribute('tabindex', '-1');
				target.focus({ preventScroll: true });
			})
		);
	}

	/* ------------------------------------------------------------------ */
	/* Home: aree tematiche, ognuna si apre come una lente al centro        */
	/* ------------------------------------------------------------------ */
	function initAreas() {
		const areasEl = $('[data-areas]');
		if (!areasEl || REDUCED || !HAS_GSAP) return;
		const areas = $$('[data-area]', areasEl);
		if (!areas.length) return;
		const medias = areas.map((a) => $('[data-area-media]', a));
		const panels = areas.map((a) => $('[data-area-panel]', a));
		const intro = $('[data-areas-intro]', areasEl);
		const index = $$('[data-areas-index]', areasEl);
		const navBtns = $$('[data-areas-go]', areasEl);
		areasEl.classList.add('is-pinned');

		// Apertura: l'indice tipografico sale e si dissolve, la prima area si apre con un cerchio pulito dal centro.
		const tl = gsap.timeline({ defaults: { ease: 'none' } });
		if (index.length) tl.to(index, { autoAlpha: 0, y: -36, stagger: 0.05, duration: 0.4, ease: 'power2.in' }, 0);
		intro && tl.to(intro, { autoAlpha: 0, y: -50, duration: 0.45, ease: 'power2.in' }, 0.2);
		tl.fromTo(medias[0], { clipPath: 'circle(0% at 50% 50%)', '--ms': 1.25 }, { clipPath: 'circle(75% at 50% 50%)', '--ms': 1, duration: 1.05, ease: 'power2.inOut' }, 0.15);
		let t = 0.95;
		const labels = [t];
		tl.fromTo(panels[0], { autoAlpha: 0, y: 50 }, { autoAlpha: 1, y: 0, duration: 0.45, ease: 'power3.out' }, t);
		t += 0.6;
		for (let i = 1; i < areas.length; i++) {
			tl.fromTo(medias[i], { clipPath: 'circle(0% at 50% 50%)', '--ms': 1.2 }, { clipPath: 'circle(75% at 50% 50%)', '--ms': 1, duration: 1, ease: 'power2.inOut' }, t);
			tl.to(panels[i - 1], { autoAlpha: 0, y: -40, duration: 0.35, ease: 'power2.in' }, t);
			tl.fromTo(panels[i], { autoAlpha: 0, y: 50 }, { autoAlpha: 1, y: 0, duration: 0.45, ease: 'power3.out' }, t + 0.65);
			labels.push(t + 0.65);
			t += 1.35;
		}
		tl.to({}, { duration: 0.45 }, t);

		const st = ScrollTrigger.create({
			trigger: areasEl,
			start: 'top top',
			end: () => `+=${Math.round(w.innerHeight * tl.duration() * 0.68)}`,
			pin: true,
			scrub: 0.7,
			animation: tl,
			invalidateOnRefresh: true,
		});
		const setCurrent = () => {
			const time = tl.time();
			areasEl.classList.toggle('is-visible', time >= 0.9);
			let idx = 0;
			labels.forEach((l, i) => {
				if (time >= l - 0.35) idx = i;
			});
			navBtns.forEach((b, i) => b.setAttribute('aria-current', String(i === idx)));
		};
		tl.eventCallback('onUpdate', setCurrent);
		navBtns.forEach((b, i) => {
			b.addEventListener('click', () => {
				const p = (labels[i] + 0.3) / tl.duration();
				scrollToY(st.start + (st.end - st.start) * p);
			});
		});
		setCurrent();
	}

	/* Home: corsi in evidenza in scorrimento orizzontale (desktop) */
	function initFeatured() {
		const sec = $('[data-featured]');
		if (!sec || REDUCED || !HAS_GSAP) return;
		const vp = $('[data-featured-viewport]', sec);
		const track = $('[data-featured-track]', sec);
		const mm = gsap.matchMedia();
		mm.add('(min-width: 900px) and (min-height: 600px)', () => {
			sec.classList.add('is-horizontal');
			const dist = () => Math.max(0, track.scrollWidth - vp.clientWidth);
			const tween = gsap.to(track, { x: () => -dist(), ease: 'none' });
			ScrollTrigger.create({ trigger: sec, start: 'top top', end: () => `+=${dist()}`, pin: true, scrub: 0.6, animation: tween, invalidateOnRefresh: true });
			$$('.card__mat img', track).forEach((im) => {
				gsap.fromTo(im, { xPercent: -6, scale: 1.12 }, { xPercent: 6, scale: 1.12, ease: 'none', scrollTrigger: { trigger: im.closest('.card'), containerAnimation: tween, start: 'left right', end: 'right left', scrub: true } });
			});
			return () => {
				sec.classList.remove('is-horizontal');
				gsap.set(track, { clearProps: 'transform' });
			};
		});
	}

	/* Home: i ritratti dei docenti seguono il cursore sui nomi */
	function initFaculty() {
		const list = $('[data-faculty-list]');
		const float = $('[data-faculty-float]');
		if (!list || !float || !FINE || REDUCED) return;
		const pics = $$('[data-faculty-pic]', float);
		let x = 0;
		let y = 0;
		let tx = 0;
		let ty = 0;
		let raf = 0;
		let on = false;
		const loop = () => {
			x += (tx - x) * 0.14;
			y += (ty - y) * 0.14;
			float.style.transform = `translate3d(${x}px, ${y}px, 0)`;
			raf = on || Math.abs(tx - x) > 0.5 ? requestAnimationFrame(loop) : 0;
		};
		list.addEventListener('pointermove', (e) => {
			tx = e.clientX;
			ty = e.clientY;
			if (!on) {
				x = tx;
				y = ty;
			}
			if (!raf) raf = requestAnimationFrame(loop);
		});
		$$('[data-faculty-item]', list).forEach((item) => {
			item.addEventListener('pointerenter', () => {
				on = true;
				float.classList.add('is-on');
				const i = item.dataset.facultyItem;
				pics.forEach((p) => p.classList.toggle('is-current', p.dataset.facultyPic === i));
			});
		});
		list.addEventListener('pointerleave', () => {
			on = false;
			float.classList.remove('is-on');
		});
	}

	/* Recensioni scorrevoli: pausa (WCAG 2.2.2) e "leggi tutte" */
	function initReviews() {
		$$('[data-reviews]').forEach((sec) => {
			const pause = $('[data-reviews-pause]', sec);
			pause &&
				pause.addEventListener('click', () => {
					const paused = sec.classList.toggle('is-paused');
					pause.setAttribute('aria-pressed', String(paused));
					const label = $('.btn__label', pause) || pause;
					label.textContent = paused ? T.play || 'Riprendi lo scorrimento' : T.pause || 'Metti in pausa';
				});
			const more = $('[data-reviews-more]', sec);
			const wall = $('[data-reviews-wall]', sec);
			more &&
				wall &&
				more.addEventListener('click', () => {
					const open = wall.hidden;
					wall.hidden = !open;
					more.setAttribute('aria-expanded', String(open));
					const label = $('.btn__label', more) || more;
					label.textContent = open ? T.reviewsLess || 'Nascondi le recensioni' : T.reviewsAll || 'Leggi tutte le recensioni';
					if (open && HAS_GSAP && !REDUCED) gsap.from($$('.review', wall), { y: 30, autoAlpha: 0, stagger: 0.03, duration: 0.7, ease: 'expo.out' });
					if (HAS_GSAP) ScrollTrigger.refresh();
				});
		});
	}

	/* Video: si caricano (Vimeo/YouTube) solo quando il visitatore preme play */
	function initVideos() {
		$$('[data-video]').forEach((btn) =>
			btn.addEventListener('click', () => {
				const src = btn.dataset.video;
				if (!src) return;
				// dentro uno slider: lo scorrimento si ferma mentre si guarda il video
				const slider = btn.closest('[data-reviews]');
				if (slider && !slider.classList.contains('is-paused')) {
					const pause = $('[data-reviews-pause]', slider);
					if (pause) pause.click();
					else slider.classList.add('is-paused');
				}
				const f = d.createElement('iframe');
				f.src = src;
				f.title = btn.dataset.videoTitle || btn.getAttribute('aria-label') || 'Video';
				f.allow = 'autoplay; fullscreen; picture-in-picture';
				f.allowFullscreen = true;
				f.className = 'video__frame';
				btn.replaceWith(f);
				f.focus();
			})
		);
	}

	/* Frasi che si "accendono" parola per parola allo scroll */
	function initScrubWords() {
		if (REDUCED || !HAS_GSAP) return;
		$$('[data-scrub-words]').forEach((el) => {
			const units = [];
			Array.from(el.childNodes).forEach((n) => {
				if (n.nodeType === 3) {
					const frag = d.createDocumentFragment();
					n.textContent.split(/(\s+)/).forEach((part) => {
						if (!part) return;
						if (/^\s+$/.test(part)) {
							frag.appendChild(d.createTextNode(part));
							return;
						}
						const s = d.createElement('span');
						s.className = 'w';
						s.textContent = part;
						frag.appendChild(s);
						units.push(s);
					});
					n.replaceWith(frag);
				} else if (n.nodeType === 1) {
					units.push(n);
				}
			});
			el.classList.add('is-scrub');
			let lit = -1;
			ScrollTrigger.create({
				trigger: el,
				start: 'top 85%',
				end: 'bottom 55%',
				onUpdate: (s) => {
					const n = Math.round(s.progress * units.length);
					if (n === lit) return;
					lit = n;
					units.forEach((u, i) => u.classList.toggle('is-lit', i < n));
				},
				onLeave: () => units.forEach((u) => u.classList.add('is-lit')),
			});
		});
	}

	/* CTA finale: il disco cremisi si apre entrando nel viewport. */
	function initCtaDisc() {
		if (REDUCED || !HAS_GSAP) return;
		$$('[data-cta-final]').forEach((sec) => {
			const radius = () => Math.hypot(sec.offsetWidth, sec.offsetHeight) / 2 + 2;
			const start = () => {
				const inner = $('.cta-final__inner', sec);
				const r = inner.getBoundingClientRect();
				return Math.hypot(r.width, r.height) / 2 + 24; // il testo è sempre dentro il cerchio
			};
			gsap.fromTo(sec, { '--hole': () => `${start()}px` }, { '--hole': () => `${radius()}px`, ease: 'none', immediateRender: true, scrollTrigger: { trigger: sec, start: 'top 90%', end: 'top 20%', scrub: 0.6, invalidateOnRefresh: true } });
		});
	}

	/* ------------------------------------------------------------------ */
	/* Scheda corso: copertina che si apre dalla lente + parallasse         */
	/* ------------------------------------------------------------------ */
	function initCourse() {
		const page = $('[data-course-page]');
		if (!page) return;
		const frame = $('[data-cover-frame]', page);
		const ring = $('.cover-ring', page);
		if (frame && HAS_GSAP && !REDUCED) {
			gsap.fromTo(frame, { clipPath: 'circle(18% at 50% 50%)', scale: 0.94 }, { clipPath: 'circle(75% at 50% 50%)', scale: 1, duration: 1.6, ease: 'expo.out', delay: 0.15, clearProps: 'clipPath' });
			ring && gsap.from(ring, { scale: 0.4, autoAlpha: 0, duration: 1.4, ease: 'expo.out', delay: 0.35 });
			gsap.to(frame, { yPercent: -6, ease: 'none', scrollTrigger: { trigger: page, start: 'top top', end: '+=700', scrub: 0.5 } });
			ring && gsap.to(ring, { yPercent: 18, rotate: 12, ease: 'none', scrollTrigger: { trigger: page, start: 'top top', end: '+=700', scrub: 0.5 } });
		}

		// Barra CTA: compare quando i bottoni della hero escono, si ritira vicino al footer.
		const bar = $('[data-sticky-cta]');
		const heroCtas = $('[data-hero-ctas]', page);
		const footer = $('[data-footer]');
		if (bar && heroCtas) {
			let pastHero = false;
			let nearFooter = false;
			const sync = () => bar.classList.toggle('is-visible', pastHero && !nearFooter);
			new IntersectionObserver(([en]) => {
				pastHero = !en.isIntersecting && en.boundingClientRect.top < 0;
				sync();
			}).observe(heroCtas);
			footer &&
				new IntersectionObserver(([en]) => {
					nearFooter = en.isIntersecting;
					sync();
				}, { rootMargin: '0px 0px -10% 0px' }).observe(footer);
		}
	}

	/* Lenti immagine (chi siamo): apertura circolare. */
	function initImgLens() {
		if (REDUCED || !HAS_GSAP) return;
		$$('[data-img-lens]').forEach((el) => {
			gsap.fromTo(el, { clipPath: 'circle(10% at 50% 50%)' }, { clipPath: 'circle(50% at 50% 50%)', duration: 1.6, ease: 'expo.out', delay: 0.2 });
		});
	}

	/* ------------------------------------------------------------------ */
	/* Catalogo: filtri senza reload, URL condivisibili, transizioni Flip   */
	/* ------------------------------------------------------------------ */
	function initCatalog() {
		const root = $('[data-catalog]');
		if (!root) return;
		const form = $('[data-filters]', root);
		const grid = $('[data-grid]', root);
		const cards = $$('[data-course]', grid);
		const empty = $('[data-empty]', root);
		const label = $('[data-count-label]', root);
		const q = $('input[name="q"]', form);
		const clear = $('[data-search-clear]', form);
		const chips = $('[data-chips]', form);
		const indicator = $('[data-chips-indicator]', form);
		const norm = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

		const moveIndicator = (instant) => {
			if (!chips || !indicator) return;
			const checked = $('input:checked', chips);
			const ui = checked ? checked.closest('.chip') : null;
			if (!ui) return;
			chips.classList.add('has-indicator');
			if (instant) indicator.style.transition = 'none';
			indicator.style.setProperty('--x', `${ui.offsetLeft}px`);
			indicator.style.setProperty('--w', `${ui.offsetWidth}px`);
			if (instant) {
				indicator.getBoundingClientRect();
				indicator.style.transition = '';
			}
			const parent = chips;
			const left = ui.offsetLeft - parent.clientWidth / 2 + ui.offsetWidth / 2;
			parent.scrollTo({ left, behavior: instant || REDUCED ? 'auto' : 'smooth' });
		};

		const state = () => {
			const fd = new FormData(form);
			return { area: fd.get('area') || '', per: fd.get('per') || '', q: (fd.get('q') || '').toString() };
		};
		const matches = (card, s) => {
			if (s.area && !card.dataset.areas.split(' ').includes(s.area)) return false;
			if (s.per && !card.dataset.per.split(' ').includes(s.per)) return false;
			if (s.q) {
				const hay = card.dataset.search || '';
				return norm(s.q).split(/\s+/).every((word) => !word || hay.includes(word));
			}
			return true;
		};
		const writeUrl = (s) => {
			const url = new URL(CFG.catalog || w.location.href, w.location.origin);
			// Sulle pagine area restiamo sull'URL del catalogo con i parametri.
			Object.entries(s).forEach(([k, v]) => v && url.searchParams.set(k, v));
			history.replaceState(null, '', url.pathname + url.search);
		};
		const countText = (n) => (n === 0 ? T.results_0 || 'Nessun corso' : n === 1 ? T.results_one || '1 corso' : (T.results || '%d corsi').replace('%d', n));

		const apply = (animate = true) => {
			const s = state();
			const flipState = animate && HAS_GSAP && w.Flip && !REDUCED ? Flip.getState(cards) : null;
			let n = 0;
			cards.forEach((c) => {
				const ok = matches(c, s);
				c.hidden = !ok;
				n += ok ? 1 : 0;
			});
			label && (label.textContent = countText(n));
			empty && (empty.hidden = n !== 0);
			clear && (clear.hidden = !s.q);
			writeUrl(s);
			if (flipState) {
				Flip.from(flipState, {
					duration: 0.7,
					ease: 'expo.out',
					absolute: true,
					stagger: 0.02,
					onEnter: (els) => gsap.fromTo(els, { autoAlpha: 0, scale: 0.92, y: 24 }, { autoAlpha: 1, scale: 1, y: 0, duration: 0.7, ease: 'expo.out', stagger: 0.03 }),
					onLeave: (els) => gsap.to(els, { autoAlpha: 0, scale: 0.92, duration: 0.35, ease: 'power2.in' }),
					onComplete: () => ScrollTrigger.refresh(),
				});
			} else if (HAS_GSAP) {
				ScrollTrigger.refresh();
			}
		};

		let tId;
		form.addEventListener('change', (e) => {
			if (e.target.name === 'area') moveIndicator(false);
			apply(true);
		});
		q &&
			q.addEventListener('input', () => {
				clearTimeout(tId);
				tId = setTimeout(() => apply(true), 220);
			});
		form.addEventListener('submit', (e) => {
			e.preventDefault();
			apply(true);
			q && q.blur();
		});
		clear &&
			clear.addEventListener('click', () => {
				q.value = '';
				apply(true);
				q.focus();
			});
		const reset = $('[data-filters-reset]', root);
		reset &&
			reset.addEventListener('click', (e) => {
				e.preventDefault();
				form.reset();
				$$('input[name="area"]', form).forEach((r) => (r.checked = r.value === ''));
				if (q) q.value = '';
				moveIndicator(false);
				apply(true);
			});

		moveIndicator(true);
		w.addEventListener('resize', () => moveIndicator(true));
		if (d.fonts && d.fonts.ready) d.fonts.ready.then(() => moveIndicator(true));

		// Ombra quando i filtri sono incollati in alto.
		if (HAS_GSAP) ScrollTrigger.create({ trigger: form, start: 'top top+=80', end: 'max', toggleClass: { targets: form, className: 'is-stuck' } });

		// Ingresso della griglia.
		if (HAS_GSAP && !REDUCED) {
			ScrollTrigger.batch($$('[data-course]:not([hidden])', grid), {
				start: 'top 92%',
				once: true,
				onEnter: (els) => gsap.from(els, { y: 40, autoAlpha: 0, duration: 1, stagger: 0.07, ease: 'expo.out' }),
			});
		}
	}

	/* ------------------------------------------------------------------ */
	/* Accordion                                                            */
	/* ------------------------------------------------------------------ */
	function initAccordions() {
		$$('[data-acc]').forEach((acc) => {
			const btn = $('.acc__btn', acc);
			if (!btn) return;
			btn.addEventListener('click', () => {
				const open = !acc.classList.contains('is-open');
				acc.classList.toggle('is-open', open);
				btn.setAttribute('aria-expanded', String(open));
				if (HAS_GSAP) setTimeout(() => ScrollTrigger.refresh(), 450);
			});
		});
	}

	/* Testi lunghi: "Leggi tutto" */
	function initClamps() {
		$$('[data-clamp]').forEach((el) => {
			const lines = parseInt(el.dataset.clamp, 10) || 7;
			el.style.setProperty('--clamp', lines);
			el.classList.add('is-clamped');
			requestAnimationFrame(() => {
				if (el.scrollHeight - el.clientHeight < 8) {
					el.classList.remove('is-clamped');
					return;
				}
				const b = d.createElement('button');
				b.type = 'button';
				b.className = 'clamp-toggle';
				b.textContent = T.readMore || 'Leggi tutto';
				b.setAttribute('aria-expanded', 'false');
				b.addEventListener('click', () => {
					const isOpen = el.classList.toggle('is-clamped') === false;
					b.textContent = isOpen ? T.readLess || 'Mostra meno' : T.readMore || 'Leggi tutto';
					b.setAttribute('aria-expanded', String(isOpen));
					if (HAS_GSAP) ScrollTrigger.refresh();
				});
				el.insertAdjacentElement('afterend', b);
			});
		});
	}

	/* ------------------------------------------------------------------ */
	/* Form (contatti, newsletter) via REST di ECM Core                     */
	/* ------------------------------------------------------------------ */
	function initForms() {
		$$('[data-form]').forEach((form) => {
			const kind = form.dataset.form;
			const status = $('[data-form-status]', form);
			const submit = $('[type="submit"]', form);
			const setErr = (name, msg) => {
				const p = $(`[data-error-for="${name}"]`, form);
				const field = form.elements[name];
				const wrap = field && (field.closest ? field.closest('.field') : null);
				if (p) p.textContent = msg || '';
				if (wrap) wrap.classList.toggle('has-error', !!msg);
				if (field && field.setAttribute) field.setAttribute('aria-invalid', msg ? 'true' : 'false');
			};
			const localValidate = () => {
				const errs = {};
				$$('[required]', form).forEach((f) => {
					if (f.type === 'checkbox' && !f.checked) errs[f.name] = 'Per continuare serve il consenso privacy.';
					else if (f.type !== 'checkbox' && !f.value.trim()) errs[f.name] = 'Campo obbligatorio.';
					else if (f.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(f.value.trim())) errs[f.name] = 'Inserisci un indirizzo email valido, ad esempio nome@dominio.it.';
				});
				return errs;
			};
			$$('input, textarea, select', form).forEach((f) =>
				f.addEventListener('blur', () => {
					if (!f.name || !f.hasAttribute('required')) return;
					const e = localValidate();
					setErr(f.name, e[f.name]);
				})
			);
			form.addEventListener('submit', async (e) => {
				e.preventDefault();
				$$('[data-error-for]', form).forEach((p) => setErr(p.dataset.errorFor, ''));
				status.className = 'form__status';
				status.textContent = '';
				const errs = localValidate();
				const names = Object.keys(errs);
				if (names.length) {
					names.forEach((n) => setErr(n, errs[n]));
					const first = form.elements[names[0]];
					first && first.focus();
					return;
				}
				submit.setAttribute('aria-busy', 'true');
				status.textContent = T.sending || 'Invio in corso…';
				try {
					const fd = new FormData(form);
					const res = await fetch(`${CFG.rest}${kind === 'newsletter' ? 'newsletter' : 'contact'}`, {
						method: 'POST',
						headers: { 'X-WP-Nonce': CFG.nonce },
						body: fd,
						credentials: 'same-origin',
					});
					const json = await res.json().catch(() => ({}));
					if (res.ok && json.ok) {
						status.className = 'form__status is-success';
						status.textContent = json.message || 'Grazie, abbiamo ricevuto la tua richiesta.';
						form.reset();
					} else {
						const fe = json.errors || {};
						Object.keys(fe).forEach((n) => setErr(n, fe[n]));
						status.className = 'form__status is-error';
						status.textContent = json.message || T.error;
						const first = Object.keys(fe)[0];
						first && form.elements[first] && form.elements[first].focus();
					}
				} catch (_) {
					status.className = 'form__status is-error';
					status.textContent = T.error || 'Errore di rete.';
				} finally {
					submit.removeAttribute('aria-busy');
				}
			});
		});
	}

	/* Copia negli appunti + toast */
	function toast(msg) {
		const t = $('[data-toast]');
		if (!t) return;
		t.textContent = msg;
		t.hidden = false;
		clearTimeout(toast._t);
		toast._t = setTimeout(() => (t.hidden = true), 2600);
	}
	function initCopy() {
		$$('[data-copy]').forEach((b) =>
			b.addEventListener('click', async () => {
				try {
					await navigator.clipboard.writeText(b.dataset.copy);
					toast(T.copied || 'Copiato');
				} catch (_) {
					/* clipboard non disponibile: il link mailto resta utilizzabile */
				}
			})
		);
	}

	/* ------------------------------------------------------------------ */
	/* Cookie: consenso granulare, script attivati solo dopo il consenso    */
	/* ------------------------------------------------------------------ */
	function initCookies() {
		const box = $('[data-cookie]');
		if (!box) return;
		const KEY = 'ecm_consent';
		const read = () => {
			const m = d.cookie.match(new RegExp(`(?:^|; )${KEY}=([^;]*)`));
			try {
				return m ? JSON.parse(decodeURIComponent(m[1])) : null;
			} catch (_) {
				return null;
			}
		};
		const write = (c) => {
			const v = encodeURIComponent(JSON.stringify({ ...c, t: Date.now(), v: 1 }));
			d.cookie = `${KEY}=${v}; Max-Age=${60 * 60 * 24 * 180}; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
		};
		const activate = (c) => {
			$$('script[type="text/plain"][data-ecm-consent]').forEach((s) => {
				if (!c[s.dataset.ecmConsent] || s.dataset.done) return;
				const n = d.createElement('script');
				[...s.attributes].forEach((a) => a.name !== 'type' && a.name !== 'data-ecm-consent' && n.setAttribute(a.name, a.value));
				n.text = s.text;
				s.dataset.done = '1';
				s.after(n);
			});
			w.dispatchEvent(new CustomEvent('ecm:consent', { detail: c }));
		};
		const prefs = $('[data-cookie-prefs]', box);
		const custom = $('[data-cookie-custom]', box);
		const save = $('[data-cookie-save]', box);
		const accept = $('[data-cookie-accept]', box);
		const show = () => {
			const c = read();
			$$('[data-cookie-cat]', box).forEach((i) => (i.checked = !!(c && c[i.dataset.cookieCat])));
			box.hidden = false;
		};
		const hide = (c) => {
			write(c);
			activate(c);
			box.hidden = true;
		};
		accept.addEventListener('click', () => hide({ necessary: true, statistics: true, marketing: true }));
		$('[data-cookie-reject]', box).addEventListener('click', () => hide({ necessary: true, statistics: false, marketing: false }));
		custom.addEventListener('click', () => {
			const open = prefs.hidden;
			prefs.hidden = !open;
			custom.setAttribute('aria-expanded', String(open));
			save.hidden = !open;
			accept.hidden = open;
		});
		save.addEventListener('click', () => {
			const c = { necessary: true };
			$$('[data-cookie-cat]', box).forEach((i) => (c[i.dataset.cookieCat] = i.checked));
			hide(c);
		});
		$$('[data-cookie-open]').forEach((b) => b.addEventListener('click', show));
		const c = read();
		if (c) activate(c);
		else setTimeout(show, 900);
	}

	/* ------------------------------------------------------------------ */
	/* Bottoni: il disco parte dal punto d'ingresso del puntatore           */
	/* ------------------------------------------------------------------ */
	function initButtons() {
		if (!FINE) return;
		const setXY = (e) => {
			const b = e.currentTarget;
			const r = b.getBoundingClientRect();
			b.style.setProperty('--x', `${e.clientX - r.left}px`);
			b.style.setProperty('--y', `${e.clientY - r.top}px`);
		};
		$$('.btn').forEach((b) => {
			b.addEventListener('pointerenter', setXY);
			b.addEventListener('pointerleave', setXY);
		});
	}

	/* Transizione di pagina: memorizza il punto del click per il cerchio. */
	function initPageTransitions() {
		d.addEventListener('click', (e) => {
			const a = e.target.closest('a[href]');
			if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || a.target === '_blank' || a.hasAttribute('download')) return;
			if (a.origin !== location.origin) return;
			try {
				sessionStorage.setItem('ecm-vt', JSON.stringify({ x: e.clientX, y: e.clientY }));
			} catch (_) {
				/* storage non disponibile */
			}
		}, true);
	}

	/* ------------------------------------------------------------------ */
	/* Avvio                                                               */
	/* ------------------------------------------------------------------ */
	const boot = () => {
		try {
			initLenis();
			initHeader();
			initMenu();
			initHeroes();
			initReviews();
			initVideos();
			initReveals();
			initCounters();
			initCtaDisc();
			initCourse();
			initImgLens();
			initCatalog();
			initAccordions();
			initClamps();
			initForms();
			initCopy();
			initCookies();
			initButtons();
			initPageTransitions();
			clearTimeout(failsafe);
			// Scene sotto la piega (palcoscenici fissati, nastri, parole): preparate quando il browser è libero,
			// dopo il caricamento, per non rallentare il primo disegno della pagina.
			const scenes = () => {
				try {
					initAreas();
					initFeatured();
					initFaculty();
					initScrubWords();
					if (HAS_GSAP) {
						ScrollTrigger.sort();
						ScrollTrigger.refresh();
					}
				} catch (err) {
					// eslint-disable-next-line no-console
					console.error('[ECM]', err);
				}
			};
			const idle = w.requestIdleCallback ? (cb) => w.requestIdleCallback(cb, { timeout: 1500 }) : (cb) => setTimeout(cb, 200);
			const hasScenes = $('[data-areas], [data-featured], [data-faculty-list], [data-scrub-words]');
			if (hasScenes) {
				if (d.readyState === 'complete') idle(scenes);
				else w.addEventListener('load', () => idle(scenes), { once: true });
			}
			if (HAS_GSAP) {
				if (d.fonts && d.fonts.ready) d.fonts.ready.then(() => ScrollTrigger.refresh());
				w.addEventListener('load', () => ScrollTrigger.refresh());
			}
		} catch (err) {
			html.classList.add('motion-failsafe');
			// eslint-disable-next-line no-console
			console.error('[ECM]', err);
		}
	};
	if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', boot);
	else boot();
})();
