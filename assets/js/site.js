/* Mercury Electrical QLD – site scripts. No dependencies.
   Motion rules: only transform and opacity are animated, nothing is hidden
   unless this script is running, and all motion is skipped when the
   visitor has asked their device to reduce motion. */
(function () {
	'use strict';

	var doc = document.documentElement;
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var canAnimate = !reduce && 'IntersectionObserver' in window;
	var each = function (sel, fn, root) { Array.prototype.forEach.call((root || document).querySelectorAll(sel), fn); };
	var isDesktop = function () { return window.innerWidth > 1099; };
	var isPhone = function () { return window.innerWidth < 768; };
	var clamp = function (v, a, b) { return Math.max(a, Math.min(b, v)); };

	/* ------------------------------------------------------------------
	   Count-up numbers (only real figures already in the text)
	   ------------------------------------------------------------------ */
	function prepCount(el, pattern) {
		if (!el) { return null; }
		var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
		var node;
		while ((node = walker.nextNode())) {
			var m = node.nodeValue.match(pattern);
			if (!m) { continue; }
			var start = node.nodeValue.indexOf(m[1], m.index);
			var numNode = node.splitText(start);
			numNode.splitText(m[1].length);
			var span = document.createElement('span');
			span.className = 'me-count';
			span.style.display = 'inline-block';
			span.style.minWidth = m[1].length + 'ch';
			span.style.textAlign = 'right';
			span.setAttribute('data-to', m[1]);
			span.textContent = '0';
			numNode.parentNode.replaceChild(span, numNode);
			return span;
		}
		return null;
	}
	function runCount(span) {
		if (!span || span.getAttribute('data-done')) { return; }
		span.setAttribute('data-done', '1');
		var to = parseInt(span.getAttribute('data-to'), 10);
		var t0 = null, dur = 1000;
		var step = function (t) {
			if (!t0) { t0 = t; }
			var k = Math.min((t - t0) / dur, 1);
			span.textContent = Math.round(to * (1 - Math.pow(1 - k, 3)));
			if (k < 1) { requestAnimationFrame(step); }
		};
		requestAnimationFrame(step);
	}

	/* The trust bar sits half over the hero and half over the quote band. */
	var trust = document.querySelector('.me-trust');
	function measureTrust() { if (trust) { doc.style.setProperty('--trust-h', trust.offsetHeight + 'px'); } }
	measureTrust();
	window.addEventListener('resize', measureTrust);
	if (document.fonts && document.fonts.ready) { document.fonts.ready.then(measureTrust); }

	/* ------------------------------------------------------------------
	   Before / after slider (works without scripts as two photos side by side)
	   ------------------------------------------------------------------ */
	each('.me-ba', function (ba) {
		var before = ba.querySelector('.me-ba__before');
		var after = ba.querySelector('.me-ba__after');
		if (!before || !after) { return; }
		var range = document.createElement('input');
		range.type = 'range';
		range.min = '0';
		range.max = '100';
		range.value = '50';
		range.className = 'me-ba__range';
		range.setAttribute('aria-label', 'Before and after comparison. Slide to reveal more of the before or after photo');
		var handle = document.createElement('span');
		handle.className = 'me-ba__handle';
		handle.setAttribute('aria-hidden', 'true');
		ba.appendChild(range);
		ba.appendChild(handle);
		ba.classList.add('is-slider');
		var set = function () { ba.style.setProperty('--pos', range.value + '%'); };
		range.addEventListener('input', set);
		set();
	});

	/* ------------------------------------------------------------------
	   Animation layer set-up
	   ------------------------------------------------------------------ */
	var heroCount = null;
	if (canAnimate) {
		// Hero headline: each word rises out of its own line.
		var title = document.querySelector('.me-hero__title');
		if (title && !title.querySelector('.me-line')) {
			title.innerHTML = title.innerHTML.split(/<br\s*\/?>/i).map(function (part) {
				return '<span class="me-line">' + part + '</span>';
			}).join('');
			var i = 0;
			var wrapWords = function (node) {
				Array.prototype.slice.call(node.childNodes).forEach(function (child) {
					if (child.nodeType === 3) {
						var frag = document.createDocumentFragment();
						child.nodeValue.split(/(\s+)/).forEach(function (bit) {
							if (!bit) { return; }
							if (/^\s+$/.test(bit)) { frag.appendChild(document.createTextNode(bit)); return; }
							var w = document.createElement('span');
							w.className = 'me-word';
							w.style.setProperty('--i', i++);
							w.textContent = bit;
							frag.appendChild(w);
						});
						child.parentNode.replaceChild(frag, child);
					} else if (child.nodeType === 1) {
						wrapWords(child);
					}
				});
			};
			wrapWords(title);
		}
		// Photo reveal wipes on a few key photos.
		var addWipe = function (fig, cls, delay) {
			if (!fig || fig.querySelector('.me-wipe')) { return; }
			var w = document.createElement('span');
			w.className = 'me-wipe' + (cls ? ' ' + cls : '');
			w.setAttribute('aria-hidden', 'true');
			fig.appendChild(w);
			fig.classList.add('me-wipe-host');
			if (delay) { fig.style.setProperty('--wd', delay + 'ms'); }
		};
		addWipe(document.querySelector('.me-about__main'), '', 100);
		addWipe(document.querySelector('.me-about__trench'), 'me-wipe--yellow', 380);
		addWipe(document.querySelector('.me-ba'), 'me-wipe--yellow', 0);

		var trustFirst = document.querySelector('.me-trust__list li strong');
		heroCount = prepCount(trustFirst, /(\d+)\+?\s*years/i);
		doc.classList.add('me-anim');
	}

	/* The hero plays its entrance as soon as the page and fonts are ready. */
	var heroStarted = false;
	function startHero() {
		if (heroStarted) { return; }
		heroStarted = true;
		doc.classList.add('me-hero-go');
		setTimeout(function () { runCount(heroCount); }, 900);
	}
	if (document.fonts && document.fonts.ready) {
		Promise.race([document.fonts.ready, new Promise(function (r) { setTimeout(r, 300); })]).then(function () { requestAnimationFrame(startHero); });
	} else { requestAnimationFrame(startHero); }
	setTimeout(startHero, reduce ? 0 : 500);

	/* ------------------------------------------------------------------
	   Scroll handler: header state and the phone process cable
	   ------------------------------------------------------------------ */
	var header = document.getElementById('me-header');
	var hero = document.querySelector('.me-hero');
	var steps = document.querySelector('.me-steps');
	var cable = steps ? steps.querySelector('.me-cable') : null;
	var ticking = false;

	function onScroll() {
		var y = window.pageYOffset || doc.scrollTop;
		var vh = window.innerHeight;
		if (header) { header.classList.toggle('is-scrolled', y > 40); }
		// Phones only: the process cable draws as you scroll down and un-draws going back up.
		if (canAnimate && steps && isPhone()) {
			var st = steps.getBoundingClientRect();
			steps.style.setProperty('--p', clamp((vh * 0.75 - st.top) / Math.max(st.height, 1), 0, 1).toFixed(3));
		}
		ticking = false;
	}
	window.addEventListener('scroll', function () {
		if (!ticking) { window.requestAnimationFrame(onScroll); ticking = true; }
	}, { passive: true });
	// How far the spark travels along the desktop cable.
	function setRun() { if (steps && cable) { steps.style.setProperty('--run', (cable.offsetWidth + 10) + 'px'); } }
	window.addEventListener('resize', function () { setRun(); onScroll(); });
	setRun();

	// Pause the slow looping touches while they're off-screen.
	if ('IntersectionObserver' in window) {
		var pauser = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) { e.target.classList.toggle('is-off', !e.isIntersecting); });
		});
		if (hero) { pauser.observe(hero); }
		if (steps) { pauser.observe(steps); }
	}

	/* ------------------------------------------------------------------
	   Mobile menu
	   ------------------------------------------------------------------ */
	var btn = document.querySelector('.me-menu-btn');
	var nav = document.getElementById('me-nav');
	function setMenu(open) {
		if (!btn) { return; }
		doc.classList.toggle('me-menu-open', open);
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		btn.querySelector('.screen-reader-text').textContent = open ? 'Close menu' : 'Open menu';
	}
	if (btn && nav) {
		btn.addEventListener('click', function () {
			var open = btn.getAttribute('aria-expanded') !== 'true';
			setMenu(open);
			if (open) { var first = nav.querySelector('a'); if (first) { first.focus(); } }
		});
		nav.addEventListener('click', function (e) { if (e.target.closest('a')) { setMenu(false); } });
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && doc.classList.contains('me-menu-open')) { setMenu(false); btn.focus(); }
		});
		window.addEventListener('resize', function () { if (isDesktop()) { setMenu(false); } });
	}

	/* ------------------------------------------------------------------
	   Scroll reveals: each section has its own entrance
	   ------------------------------------------------------------------ */
	if (canAnimate) {
		var plan = [
			['.me-quote__intro .me-h2, .me-quote__intro p', 'left', 0, 90],
			['.me-quote__form', 'right', 120, 0],
			['.me-about__title, .me-about__text p', '', 0, 70],
			['.me-about__badge', 'scale', 500, 0],
			['.me-services__head', 'left', 0, 0],
			['.me-service', '', 40, 90],
			['.me-services__foot', '', 0, 0],
			['.me-work__head .me-h2, .me-work__lead', '', 0, 100],
			['.me-gallery > figure', 'scale', 40, 55],
			['.me-process .me-h2', 'left', 0, 0],
			['.me-step', 'left', 120, 220],
			['.me-reviews .me-h2, .me-reviews .me-placeholder, .me-reviews__live, .me-reviews__buttons', '', 0, 100],
			['.me-contact .me-h2, .me-contact__intro p, .me-contact__intro .me-details, .me-contact__intro .wp-block-buttons', '', 0, 80],
			['.me-contact__form', 'right', 120, 0],
			['.me-contact__map', '', 0, 0]
		];
		var watch = [];
		plan.forEach(function (p) {
			each(p[0], function (el, i) {
				el.classList.add('me-r');
				if (p[1]) { el.classList.add('me-r--' + p[1]); }
				el.style.setProperty('--d', (p[2] + i * p[3]) + 'ms');
				watch.push(el);
			});
		});
		each('.me-wipe-host', function (el) { watch.push(el); });
		each('.me-quote .me-mark, .me-about .me-mark', function (el) { watch.push(el); });
		var aboutCount = prepCount(document.querySelector('.me-about__num'), /(\d+)/);
		if (aboutCount) { watch.push(aboutCount); }
		if (steps) { watch.push(steps); }

		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) { return; }
				var el = entry.target;
				io.unobserve(el);
				if (el.classList.contains('me-count')) { setTimeout(function () { runCount(el); }, 500); return; }
				if (el.classList.contains('me-wipe-host')) { el.classList.add('is-wiped'); return; }
				if (el === steps) { el.classList.add('is-drawn'); return; }
				el.classList.add('is-in');
				if (!el.classList.contains('me-r')) { return; }
				// Hand the element back to its normal hover styles once it has settled.
				var wait = 900 + (parseInt(el.style.getPropertyValue('--d'), 10) || 0);
				setTimeout(function () {
					el.classList.remove('me-r', 'is-in', 'me-r--left', 'me-r--right', 'me-r--scale');
					el.style.removeProperty('--d');
				}, wait);
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
		watch.forEach(function (el) { io.observe(el); });
	}
	onScroll();

	/* ------------------------------------------------------------------
	   Google Map: only loads when it scrolls near view
	   ------------------------------------------------------------------ */
	each('[data-me-map]', function (box) {
		var load = function () {
			if (box.querySelector('iframe')) { return; }
			var f = document.createElement('iframe');
			f.src = box.getAttribute('data-me-map');
			f.title = box.getAttribute('data-title') || 'Map';
			f.loading = 'lazy';
			f.referrerPolicy = 'no-referrer-when-downgrade';
			box.insertBefore(f, box.firstChild);
		};
		if (!('IntersectionObserver' in window)) { load(); return; }
		var mo = new IntersectionObserver(function (en) {
			if (en[0].isIntersecting) { mo.disconnect(); load(); }
		}, { rootMargin: '400px 0px' });
		mo.observe(box);
	});

	/* ------------------------------------------------------------------
	   Photo viewer for the Recent Work gallery
	   ------------------------------------------------------------------ */
	var figs = Array.prototype.slice.call(document.querySelectorAll('.me-gallery > figure'));
	if (figs.length) {
		var box = document.createElement('div');
		box.className = 'me-lightbox';
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-modal', 'true');
		box.setAttribute('aria-label', 'Photo viewer');
		box.hidden = true;
		var icon = function (d) { return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' + d + '"/></svg>'; };
		box.innerHTML = '<img alt=""><p class="me-lightbox__cap"></p>' +
			'<button type="button" class="me-lightbox__close" aria-label="Close">' + icon('M6 6l12 12M18 6L6 18') + '</button>' +
			'<button type="button" class="me-lightbox__prev" aria-label="Previous photo">' + icon('M15 5l-7 7 7 7') + '</button>' +
			'<button type="button" class="me-lightbox__next" aria-label="Next photo">' + icon('M9 5l7 7-7 7') + '</button>';
		document.body.appendChild(box);
		var bImg = box.querySelector('img');
		var bCap = box.querySelector('.me-lightbox__cap');
		var items = figs.map(function (f) { return f.querySelector('img'); }).filter(Boolean);
		var current = 0, opener = null;
		var bestSrc = function (img) {
			var set = img.getAttribute('srcset');
			if (!set) { return img.getAttribute('src'); }
			var best = null, bw = 0;
			set.split(',').forEach(function (c) {
				var parts = c.trim().split(/\s+/);
				var w = parseInt(parts[1], 10) || 0;
				if (w > bw && w <= 1400) { bw = w; best = parts[0]; }
			});
			return best || img.getAttribute('src');
		};
		var show = function (i) {
			current = (i + items.length) % items.length;
			var it = items[current];
			bImg.src = bestSrc(it);
			bImg.alt = it.alt;
			bCap.textContent = it.alt + ' (' + (current + 1) + ' of ' + items.length + ')';
		};
		var openViewer = function (i, from) {
			opener = from;
			show(i);
			box.hidden = false;
			requestAnimationFrame(function () { box.classList.add('is-open'); });
			document.body.style.overflow = 'hidden';
			box.querySelector('.me-lightbox__close').focus();
		};
		var close = function () {
			box.classList.remove('is-open');
			document.body.style.overflow = '';
			setTimeout(function () { box.hidden = true; }, 300);
			if (opener) { opener.focus(); }
		};
		box.querySelector('.me-lightbox__close').addEventListener('click', close);
		box.querySelector('.me-lightbox__prev').addEventListener('click', function () { show(current - 1); });
		box.querySelector('.me-lightbox__next').addEventListener('click', function () { show(current + 1); });
		box.addEventListener('click', function (e) { if (e.target === box || e.target === bCap) { close(); } });
		var sx = null, sy = null;
		box.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, { passive: true });
		box.addEventListener('touchend', function (e) {
			if (sx === null) { return; }
			var dx = e.changedTouches[0].clientX - sx, dy = e.changedTouches[0].clientY - sy;
			if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) { show(current + (dx < 0 ? 1 : -1)); }
			sx = sy = null;
		}, { passive: true });
		document.addEventListener('keydown', function (e) {
			if (box.hidden) { return; }
			if (e.key === 'Escape') { close(); }
			if (e.key === 'ArrowLeft') { show(current - 1); }
			if (e.key === 'ArrowRight') { show(current + 1); }
			if (e.key === 'Tab') {
				var f = box.querySelectorAll('button');
				var first = f[0], last = f[f.length - 1];
				if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
				else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
			}
		});
		figs.forEach(function (fig, i) {
			var img = fig.querySelector('img');
			var b = document.createElement('button');
			b.type = 'button';
			b.className = 'me-zoom';
			b.setAttribute('aria-label', 'View larger: ' + (img ? img.alt : 'photo'));
			b.addEventListener('click', function () { openViewer(i, b); });
			fig.appendChild(b);
		});
	}

	/* Stand-in quote form (before Fluent Forms is connected): don't submit. */
	each('form[data-me-demo]', function (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var note = form.querySelector('.me-form__note');
			var missing = [];
			Array.prototype.forEach.call(form.querySelectorAll('[required]'), function (f) {
				var bad = !f.value.trim() || (f.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value.trim()));
				f.setAttribute('aria-invalid', bad ? 'true' : 'false');
				if (bad) { missing.push(f); }
			});
			if (missing.length) {
				note.textContent = 'Please fill in your name, phone, email, suburb and the service you need.';
				missing[0].focus();
				return;
			}
			note.textContent = 'Preview only. The live form sends quote requests straight to Mitchell.';
		});
		form.addEventListener('input', function (e) {
			if (e.target.getAttribute('aria-invalid') === 'true' && e.target.value.trim()) { e.target.setAttribute('aria-invalid', 'false'); }
		});
	});
}());
