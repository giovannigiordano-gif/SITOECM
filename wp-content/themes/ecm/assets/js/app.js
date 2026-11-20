/**
 * ECM · La Lente — comportamento e motion del tema.
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
	/* Home: la lente diventa il palcoscenico delle aree tematiche          */
	/* ------------------------------------------------------------------ */
	function initStory() {
		const story = $('[data-story]');
		if (!story || REDUCED || !HAS_GSAP) return;
		const areasEl = $('[data-areas]', story);
		const areas = $$('[data-area]', story);
		if (!areasEl || !areas.length) return;

		const stage = $('[data-story-stage]', story);
		const slot = $('[data-lens-slot]', story);
		const lens = $('[data-lens]', story);
		const ringWrap = $('[data-lens-ringwrap]', story);
		const heroCopy = $('.hero__copy', story);
		const navBtns = $$('[data-areas-go]', story);
		const medias = areas.map((a) => $('[data-area-media]', a));
		const panels = areas.map((a) => $('[data-area-panel]', a));

		const mm = gsap.matchMedia();

		const buildAreasTimeline = (tl, start) => {
			// Ogni area si apre come una lente al centro dello schermo.
			let t = start;
			const labels = [t];
			tl.fromTo(panels[0], { autoAlpha: 0, y: 40 }, { autoAlpha: 1, y: 0, duration: 0.45, ease: 'power3.out' }, t);
			t += 0.6;
			for (let i = 1; i < areas.length; i++) {
				tl.fromTo(medias[i], { clipPath: 'circle(0% at 50% 50%)', '--ms': 1.18 }, { clipPath: 'circle(75% at 50% 50%)', '--ms': 1, duration: 1, ease: 'power2.inOut' }, t);
				tl.to(panels[i - 1], { autoAlpha: 0, y: -30, duration: 0.35, ease: 'power2.in' }, t);
				tl.fromTo(panels[i], { autoAlpha: 0, y: 40 }, { autoAlpha: 1, y: 0, duration: 0.45, ease: 'power3.out' }, t + 0.65);
				labels.push(t + 0.65);
				t += 1.35;
			}
			tl.to({}, { duration: 0.5 }, t); // pausa finale prima di sbloccare
			return labels;
		};

		const wireNav = (st, tl, labels, visibleFrom) => {
			const setCurrent = () => {
				const time = tl.time();
				areasEl.classList.toggle('is-visible', time >= visibleFrom - 0.05);
				let idx = 0;
				labels.forEach((l, i) => {
					if (time >= l - 0.35) idx = i;
				});
				navBtns.forEach((b, i) => b.setAttribute('aria-current', String(i === idx)));
			};
			tl.eventCallback('onUpdate', setCurrent);
			navBtns.forEach((b, i) => {
				b.onclick = () => {
					const p = (labels[i] + 0.3) / tl.duration();
					scrollToY(st.start + (st.end - st.start) * p);
				};
			});
			setCurrent();
		};

		// Desktop: la lente della hero cresce fino a coprire lo schermo.
		mm.add('(min-width: 900px) and (min-height: 560px)', () => {
			story.classList.add('is-story');
			areasEl.classList.add('is-pinned');
			const state = { p: 0, k: 0 };
			let g = null;
			const measure = () => {
				const s = slot.getBoundingClientRect();
				const st = stage.getBoundingClientRect();
				const W = st.width;
				const H = st.height;
				const cx = s.left - st.left + s.width / 2;
				const cy = s.top - st.top + s.height / 2;
				const r0 = s.width / 2 - s.width * 0.07;
				g = { W, H, cx, cy, r0, rMax: Math.hypot(Math.max(cx, W - cx), Math.max(cy, H - cy)) + 2, s0: (r0 * 2) / Math.max(W, H), sx: s.left - st.left, sy: s.top - st.top, sw: s.width };
				lens.style.setProperty('--rx', `${g.sx}px`);
				lens.style.setProperty('--ry', `${g.sy}px`);
				lens.style.setProperty('--rs', `${g.sw}px`);
				apply();
			};
			const lerp = (a, b, t) => a + (b - a) * t;
			const apply = () => {
				if (!g) return;
				const p = state.p;
				const e = p; // l'easing è nel tween
				lens.style.setProperty('--lr', `${lerp(g.r0 * state.k, g.rMax, e)}px`);
				lens.style.setProperty('--lx', `${lerp(g.cx, g.W / 2, e)}px`);
				lens.style.setProperty('--ly', `${lerp(g.cy, g.H / 2, e)}px`);
				lens.style.setProperty('--ix', `${lerp(g.cx - g.W / 2, 0, e)}px`);
				lens.style.setProperty('--iy', `${lerp(g.cy - g.H / 2, 0, e)}px`);
				lens.style.setProperty('--is', `${lerp(g.s0, 1, e)}`);
				const ringScale = lerp(1, g.rMax / g.r0, e);
				ringWrap.style.transform = `scale(${ringScale})`;
				ringWrap.style.opacity = String(Math.max(0, 1 - e * 2.2));
			};

			const tl = gsap.timeline({ defaults: { ease: 'none' } });
			tl.to(state, { p: 1, duration: 1.2, ease: 'power2.inOut', onUpdate: apply }, 0);
			tl.to(heroCopy, { y: () => -0.14 * w.innerHeight, autoAlpha: 0, duration: 0.6, ease: 'power2.in' }, 0.05);
			const labels = buildAreasTimeline(tl, 1.15);

			const st = ScrollTrigger.create({
				trigger: story,
				start: 'top top',
				end: () => `+=${Math.round(w.innerHeight * (tl.duration() * 0.7))}`,
				pin: stage,
				scrub: 0.9,
				animation: tl,
				invalidateOnRefresh: true,
				onRefreshInit: () => {
					g = null;
				},
				onRefresh: measure,
			});
			measure();
			wireNav(st, tl, labels, 1.15);
			// Ingresso: la lente si apre da zero al raggio della hero.
			gsap.to(state, { k: 1, duration: 1.6, ease: 'expo.out', delay: 0.4, onUpdate: apply });

			return () => {
				story.classList.remove('is-story');
				areasEl.classList.remove('is-pinned', 'is-visible');
				['--lr', '--lx', '--ly', '--ix', '--iy', '--is', '--rx', '--ry', '--rs'].forEach((p) => lens.style.removeProperty(p));
				ringWrap.removeAttribute('style');
			};
		});

		// Mobile e schermi bassi: hero in flusso normale, il palcoscenico delle aree si apre da una lente centrale.
		mm.add('(max-width: 899px), (max-height: 559px)', () => {
			areasEl.classList.add('is-pinned');
			const tl = gsap.timeline({ defaults: { ease: 'none' } });
			tl.fromTo(medias[0], { clipPath: 'circle(30% at 50% 42%)', '--ms': 1.25 }, { clipPath: 'circle(75% at 50% 50%)', '--ms': 1, duration: 1, ease: 'power2.inOut' }, 0);
			const labels = buildAreasTimeline(tl, 0.85);
			const st = ScrollTrigger.create({
				trigger: areasEl,
				start: 'top top',
				end: () => `+=${Math.round(w.innerHeight * (tl.duration() * 0.62))}`,
				pin: true,
				scrub: 0.6,
				animation: tl,
				invalidateOnRefresh: true,
			});
			wireNav(st, tl, labels, 0.85);
			return () => {
				areasEl.classList.remove('is-pinned', 'is-visible');
			};
		});
	}

	/* CTA finale: il disco cremisi si apre entrando nel viewport. */
	function initCtaDisc() {
		if (REDUCED || !HAS_GSAP) return;
		$$('[data-cta-final]').forEach((sec) => {
			const disc = $('.cta-final__disc', sec);
			if (!disc) return;
			const from = w.innerWidth < 700 ? '58%' : '40%';
			gsap.fromTo(disc, { '--disc': from }, { '--disc': '75%', ease: 'none', scrollTrigger: { trigger: sec, start: 'top 90%', end: 'top 20%', scrub: 0.6 } });
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

	/* Carosello testimonianze */
	function initVoices() {
		$$('[data-voices]').forEach((sec) => {
			const vp = $('[data-voices-viewport]', sec);
			const prev = $('[data-voices-prev]', sec);
			const next = $('[data-voices-next]', sec);
			if (!vp) return;
			const step = () => {
				const item = $('.voice', vp);
				return item ? item.getBoundingClientRect().width + 24 : vp.clientWidth * 0.8;
			};
			const sync = () => {
				prev && (prev.disabled = vp.scrollLeft < 8);
				next && (next.disabled = vp.scrollLeft + vp.clientWidth > vp.scrollWidth - 8);
			};
			prev && prev.addEventListener('click', () => vp.scrollBy({ left: -step(), behavior: REDUCED ? 'auto' : 'smooth' }));
			next && next.addEventListener('click', () => vp.scrollBy({ left: step(), behavior: REDUCED ? 'auto' : 'smooth' }));
			vp.addEventListener('scroll', sync, { passive: true });
			sync();
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

	/* Hero home: coreografia d'ingresso (lente che si apre, anello che si disegna). */
	function initHeroIntro() {
		const slot = $('[data-lens-slot]');
		if (!slot || REDUCED || !HAS_GSAP) return;
		const story = $('[data-story]');
		const isStory = story && story.classList.contains('is-story');
		const clipEl = isStory ? $('[data-lens-clip]') : $('.lens-static__clip', slot);
		const ring = isStory ? $('[data-lens-ringwrap] .lens-ring') : $('.lens-ring', slot);
		const strokes = ring ? $$('.lens-ring__stroke', ring) : [];
		const type = ring ? $('.lens-ring__type text', ring) : null;
		strokes.forEach((c) => {
			const len = 2 * Math.PI * parseFloat(c.getAttribute('r'));
			gsap.fromTo(c, { strokeDasharray: len, strokeDashoffset: len }, { strokeDashoffset: 0, duration: 1.8, ease: 'expo.inOut', delay: 0.1 });
		});
		type && gsap.from(type, { autoAlpha: 0, duration: 1.2, delay: 1.1 });
		if (clipEl && !isStory) gsap.fromTo(clipEl, { clipPath: 'circle(0% at 50% 50%)' }, { clipPath: 'circle(50% at 50% 50%)', duration: 1.5, ease: 'expo.out', delay: 0.45, clearProps: 'clipPath' });
	}

	/* ------------------------------------------------------------------ */
	/* Avvio                                                               */
	/* ------------------------------------------------------------------ */
	const boot = () => {
		try {
			initLenis();
			initHeader();
			initMenu();
			initStory();
			initReveals();
			initHeroIntro();
			initCounters();
			initCtaDisc();
			initCourse();
			initImgLens();
			initCatalog();
			initAccordions();
			initClamps();
			initVoices();
			initForms();
			initCopy();
			initCookies();
			initButtons();
			initPageTransitions();
			clearTimeout(failsafe);
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
