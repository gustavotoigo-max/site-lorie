/* Lorié — interações do site */
(function ($) {
	'use strict';

	const L = window.LORIE || {};
	const $1 = (s, r = document) => r.querySelector(s);
	const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
	const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const store = {
		get(k, d) { try { const v = localStorage.getItem(k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
		set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
	};
	const wcUrl = (ep) => (L.wcAjax || '').replace('%%endpoint%%', ep);

	function wcPost(ep, data) {
		const body = data instanceof FormData ? data : new FormData();
		if (!(data instanceof FormData)) Object.entries(data || {}).forEach(([k, v]) => body.append(k, v));
		return fetch(wcUrl(ep), { method: 'POST', body, credentials: 'same-origin' }).then((r) => r.json());
	}
	function wpPost(action, form) {
		const body = form instanceof FormData ? form : new FormData();
		body.append('action', action);
		body.append('nonce', L.nonce);
		return fetch(L.ajax, { method: 'POST', body, credentials: 'same-origin' }).then((r) => r.json());
	}

	/* ------------------------------------------------------------ Toast */
	const toastEl = $1('[data-toast]');
	function toast(msg, type) {
		if (!toastEl) return;
		toastEl.textContent = msg;
		toastEl.dataset.type = type || 'ok';
		toastEl.classList.add('is-on');
		clearTimeout(toastEl._t);
		toastEl._t = setTimeout(() => toastEl.classList.remove('is-on'), 2800);
	}

	/* ------------------------------------------------------------ Rolagem suave */
	let lenis = null;
	if (!reduce && window.Lenis && !document.body.classList.contains('wp-admin')) {
		lenis = new Lenis({ lerp: 0.11, smoothWheel: true });
		const raf = (t) => { lenis.raf(t); requestAnimationFrame(raf); };
		requestAnimationFrame(raf);
		if (window.ScrollTrigger) lenis.on('scroll', ScrollTrigger.update);
	}
	const lockScroll = (on) => {
		document.documentElement.classList.toggle('is-locked', on);
		if (lenis) on ? lenis.stop() : lenis.start();
	};
	function scrollToEl(el, offset) {
		const y = el.getBoundingClientRect().top + window.scrollY - (offset || 0);
		if (lenis) lenis.scrollTo(y, { duration: 1.1 });
		else window.scrollTo({ top: y, behavior: reduce ? 'auto' : 'smooth' });
	}

	/* ------------------------------------------------------------ Cabeçalho */
	const header = $1('[data-header]');
	let lastY = 0;
	function onScroll() {
		const y = window.scrollY;
		if (header) {
			header.classList.toggle('is-solid', y > 40);
			header.classList.toggle('is-hidden', y > 300 && y > lastY + 4 && !document.documentElement.classList.contains('is-locked'));
			if (y < lastY - 4) header.classList.remove('is-hidden');
		}
		lastY = y;
	}
	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll();

	/* ------------------------------------------------------------ Menu mobile */
	const mm = $1('[data-mobile-menu]');
	function toggleMenu(open) {
		if (!mm) return;
		if (open) {
			mm.hidden = false;
			requestAnimationFrame(() => mm.classList.add('is-open'));
		} else {
			mm.classList.remove('is-open');
			setTimeout(() => (mm.hidden = true), 350);
		}
		$$('[data-menu-open]').forEach((b) => b.setAttribute('aria-expanded', open ? 'true' : 'false'));
		lockScroll(open);
	}
	document.addEventListener('click', (e) => {
		if (e.target.closest('[data-menu-open]')) toggleMenu(true);
		if (e.target.closest('[data-menu-close]')) toggleMenu(false);
	});

	/* ------------------------------------------------------------ Slider principal */
	const heroEl = $1('[data-hero]');
	if (heroEl && window.Swiper) {
		const slides = heroEl.querySelectorAll('.swiper-slide').length;
		const progress = $1('[data-hero-progress]');
		const current = $1('[data-hero-current]');
		const DELAY = 6500;
		const hero = new Swiper(heroEl, {
			effect: 'fade',
			fadeEffect: { crossFade: true },
			speed: 1300,
			loop: slides > 1,
			allowTouchMove: slides > 1,
			autoplay: slides > 1 && !reduce ? { delay: DELAY, disableOnInteraction: false, pauseOnMouseEnter: false } : false,
			navigation: { prevEl: '[data-hero-prev]', nextEl: '[data-hero-next]' },
			on: {
				autoplayTimeLeft(s, time, pct) {
					if (progress) progress.style.transform = `scaleX(${1 - pct})`;
				},
				slideChange(s) {
					if (current) current.textContent = String(s.realIndex + 1).padStart(2, '0');
				},
			},
		});
		// Parallax suave da imagem ao rolar.
		if (!reduce && window.gsap && window.ScrollTrigger) {
			gsap.to(heroEl.querySelectorAll('.hero__media'), {
				yPercent: 14,
				ease: 'none',
				scrollTrigger: { trigger: heroEl, start: 'top top', end: 'bottom top', scrub: true },
			});
		}
		window.LORIE_HERO = hero;
	}
	const scrollCue = $1('.hero__scroll');
	if (scrollCue) scrollCue.addEventListener('click', (e) => {
		const t = $1(scrollCue.getAttribute('href'));
		if (t) { e.preventDefault(); scrollToEl(t, 70); }
	});

	/* ------------------------------------------------------------ Carrosséis de produtos */
	$$('[data-carousel]').forEach((el) => {
		if (!window.Swiper) return;
		const sec = el.closest('section');
		new Swiper(el, {
			slidesPerView: 1.25,
			spaceBetween: 16,
			speed: 650,
			grabCursor: true,
			navigation: { prevEl: sec.querySelector('[data-car-prev]'), nextEl: sec.querySelector('[data-car-next]') },
			breakpoints: { 560: { slidesPerView: 2.2, spaceBetween: 20 }, 900: { slidesPerView: 3.2, spaceBetween: 24 }, 1200: { slidesPerView: 4, spaceBetween: 28 } },
		});
	});

	/* ------------------------------------------------------------ Galeria do produto */
	const pdpMain = $1('[data-pdp-main]');
	if (pdpMain && window.Swiper) {
		const thumbsEl = $1('[data-pdp-thumbs]');
		const thumbs = thumbsEl ? new Swiper(thumbsEl, { slidesPerView: 4.5, spaceBetween: 10, watchSlidesProgress: true, breakpoints: { 700: { slidesPerView: 5.5 } } }) : null;
		const main = new Swiper(pdpMain, {
			speed: 600,
			spaceBetween: 0,
			navigation: { prevEl: '.pdp__arrow--prev', nextEl: '.pdp__arrow--next' },
			thumbs: thumbs ? { swiper: thumbs } : undefined,
		});
		// Troca a foto quando a cliente escolhe uma variação com imagem própria.
		$(document).on('found_variation', '.variations_form', (e, v) => {
			if (!v || !v.image || !v.image.src) return;
			const idx = $$('.pdp__slide img', pdpMain).findIndex((img) => (img.getAttribute('src') || '').split('?')[0] === v.image.src.split('?')[0]);
			if (idx >= 0) main.slideTo(idx);
		});
		// Zoom: abre a foto grande num visualizador simples.
		pdpMain.addEventListener('click', (e) => {
			const a = e.target.closest('[data-zoom]');
			if (!a) return;
			e.preventDefault();
			const z = document.createElement('div');
			z.className = 'zoom';
			z.innerHTML = `<img src="${a.href}" alt=""><button class="icon-btn zoom__close" aria-label="Fechar">✕</button>`;
			document.body.appendChild(z);
			lockScroll(true);
			requestAnimationFrame(() => z.classList.add('is-on'));
			z.addEventListener('click', () => { z.remove(); lockScroll(false); });
		});
	}

	/* ------------------------------------------------------------ Animações de entrada */
	if (!reduce && window.gsap && window.ScrollTrigger) {
		gsap.registerPlugin(ScrollTrigger);
		ScrollTrigger.batch('[data-reveal]', {
			start: 'top 88%',
			once: true,
			onEnter: (els) => gsap.fromTo(els, { y: 34, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: 0.9, ease: 'power3.out', stagger: 0.09, overwrite: true }),
		});
		ScrollTrigger.batch('.products > .product-card, .product-carousel .product-card', {
			start: 'top 92%',
			once: true,
			onEnter: (els) => gsap.fromTo(els, { y: 28, autoAlpha: 0 }, { y: 0, autoAlpha: 1, duration: 0.8, ease: 'power3.out', stagger: 0.07, overwrite: true }),
		});
		document.documentElement.classList.add('has-motion');
		window.addEventListener('load', () => ScrollTrigger.refresh());
	}

	/* ------------------------------------------------------------ Catálogo: chips com destaque da seção visível */
	const catNav = $1('[data-cat-nav]');
	if (catNav) {
		catNav.addEventListener('click', (e) => {
			const a = e.target.closest('a[href^="#"]');
			if (!a) return;
			const t = $1(a.getAttribute('href'));
			if (t) { e.preventDefault(); scrollToEl(t, 150); }
		});
		const chips = $$('[data-spy]', catNav);
		const sections = chips.map((c) => (c.dataset.spy === 'top' ? null : document.getElementById(c.dataset.spy))).filter(Boolean);
		if (sections.length) {
			const setActive = (id) => chips.forEach((c) => {
				const on = c.dataset.spy === id;
				c.classList.toggle('is-active', on);
				if (on) c.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
			});
			let active = 'top';
			window.addEventListener('scroll', () => {
				let id = 'top';
				sections.forEach((s) => { if (s.getBoundingClientRect().top < 200) id = s.id; });
				if (id !== active) { active = id; setActive(id); }
			}, { passive: true });
		}
	}

	/* ------------------------------------------------------------ Carrinho lateral */
	const drawer = $1('[data-drawer]');
	const drawerBody = $1('[data-drawer-body]');
	function setCount(n) {
		$$('[data-cart-count]').forEach((b) => { b.textContent = n; b.hidden = !n; b.classList.remove('pop'); void b.offsetWidth; b.classList.add('pop'); });
		$$('[data-cart-count-text]').forEach((b) => (b.textContent = `(${n})`));
	}
	function renderCart(data) {
		if (!data) return;
		if (drawerBody && typeof data.html === 'string') drawerBody.innerHTML = data.html;
		if (typeof data.count !== 'undefined') setCount(data.count);
	}
	function openDrawer() {
		if (!drawer) return;
		drawer.classList.add('is-open');
		drawer.setAttribute('aria-hidden', 'false');
		lockScroll(true);
		setTimeout(() => { const f = $1('.drawer__panel button, .drawer__panel a', drawer); if (f) f.focus({ preventScroll: true }); }, 300);
	}
	function closeDrawer() {
		if (!drawer) return;
		drawer.classList.remove('is-open');
		drawer.setAttribute('aria-hidden', 'true');
		lockScroll(false);
	}
	document.addEventListener('click', (e) => {
		const open = e.target.closest('[data-cart-open]');
		if (open && drawer && !document.body.classList.contains('woocommerce-cart')) {
			e.preventDefault();
			openDrawer();
			wcPost('lorie_cart').then((r) => r.success && renderCart(r.data));
		}
		if (e.target.closest('[data-drawer-close]')) closeDrawer();
	});
	document.addEventListener('keydown', (e) => {
		if (e.key !== 'Escape') return;
		closeDrawer();
		closeModal();
		closeSearch();
		toggleMenu(false);
	});

	// Quantidade e remoção dentro do carrinho lateral.
	let qtyTimer = null;
	function updateQty(item, qty) {
		item.classList.add('is-busy');
		wcPost('lorie_qty', { key: item.dataset.key, qty }).then((r) => {
			if (r.success) renderCart(r.data);
			$(document.body).trigger('wc_fragment_refresh');
		});
	}
	if (drawerBody) {
		drawerBody.addEventListener('click', (e) => {
			const item = e.target.closest('.drawer__item');
			if (!item) return;
			const input = $1('input', item);
			const step = e.target.closest('[data-step]');
			if (step) {
				input.value = Math.max(0, (parseInt(input.value, 10) || 0) + parseInt(step.dataset.step, 10));
				clearTimeout(qtyTimer);
				qtyTimer = setTimeout(() => updateQty(item, input.value), 380);
			}
			if (e.target.closest('[data-remove]')) {
				item.style.height = item.offsetHeight + 'px';
				item.classList.add('is-leaving');
				updateQty(item, 0);
			}
		});
		drawerBody.addEventListener('change', (e) => {
			const item = e.target.closest('.drawer__item');
			if (item && e.target.matches('input')) updateQty(item, e.target.value);
		});
	}

	/** Adiciona ao carrinho via AJAX a partir de um formulário de produto (página ou compra rápida). */
	function addFromForm(form, btn) {
		const fd = new FormData(form);
		const atc = form.querySelector('[name="add-to-cart"]');
		const simpleId = fd.get('add-to-cart') || (atc ? atc.value : '');
		if (!fd.get('product_id') && simpleId) fd.append('product_id', simpleId);
		// O WooCommerce processaria "add-to-cart" sozinho e duplicaria o item.
		fd.delete('add-to-cart');
		if (fd.has('variation_id') && !fd.get('variation_id')) {
			toast(L.i18n.choose, 'error');
			return;
		}
		if (btn) btn.classList.add('is-loading');
		wcPost('lorie_add', fd).then((r) => {
			if (btn) btn.classList.remove('is-loading');
			if (!r.success) {
				toast((r.data && r.data.message) || L.i18n.error, 'error');
				return;
			}
			renderCart(r.data);
			closeModal();
			toast(L.i18n.added);
			openDrawer();
			$(document.body).trigger('added_to_cart', [{}, '', $(btn)]);
		}).catch(() => { if (btn) btn.classList.remove('is-loading'); toast(L.i18n.error, 'error'); });
	}
	// Sem alertas do navegador: avisa com um toast quando faltam opções.
	document.addEventListener('click', (e) => {
		const b = e.target.closest('.single_add_to_cart_button.disabled');
		if (!b) return;
		e.preventDefault();
		e.stopImmediatePropagation();
		const unavailable = b.classList.contains('wc-variation-is-unavailable');
		toast(unavailable ? 'Essa combinação não está disponível. Escolha outra.' : 'Escolha a cor e o tamanho antes de adicionar.', 'error');
		const sw = b.closest('form').querySelector('.swatches');
		if (sw && !unavailable) { sw.classList.remove('shake'); void sw.offsetWidth; sw.classList.add('shake'); }
	}, true);

	document.addEventListener('submit', (e) => {
		const form = e.target.closest('form.cart');
		if (!form || !drawer) return;
		const btn = form.querySelector('[type=submit]');
		if (btn && btn.classList.contains('disabled')) return; // o WooCommerce já avisa
		e.preventDefault();
		addFromForm(form, btn);
	});

	// "Comprar" direto no card (produto simples).
	document.addEventListener('click', (e) => {
		const b = e.target.closest('[data-add]');
		if (!b) return;
		e.preventDefault();
		b.classList.add('is-loading');
		wcPost('lorie_add', { product_id: b.dataset.add, quantity: 1 }).then((r) => {
			b.classList.remove('is-loading');
			if (!r.success) {
				if (r.data && r.data.url) location.href = r.data.url;
				return toast((r.data && r.data.message) || L.i18n.error, 'error');
			}
			renderCart(r.data);
			toast(L.i18n.added);
			openDrawer();
		});
	});

	/* ------------------------------------------------------------ Compra rápida (modal) */
	const modal = $1('[data-modal]');
	const modalBody = $1('[data-modal-body]');
	function closeModal() {
		if (!modal || modal.hidden) return;
		modal.classList.remove('is-open');
		setTimeout(() => { modal.hidden = true; modalBody.innerHTML = ''; }, 300);
		if (!drawer || !drawer.classList.contains('is-open')) lockScroll(false);
	}
	document.addEventListener('click', (e) => {
		const q = e.target.closest('[data-quick]');
		if (q && modal) {
			e.preventDefault();
			q.classList.add('is-loading');
			fetch(wcUrl('lorie_quick') + '&id=' + encodeURIComponent(q.dataset.quick), { credentials: 'same-origin' })
				.then((r) => r.json())
				.then((r) => {
					q.classList.remove('is-loading');
					if (!r.success) return;
					modalBody.innerHTML = r.data.html;
					modal.hidden = false;
					lockScroll(true);
					requestAnimationFrame(() => modal.classList.add('is-open'));
					initProductForm(modalBody);
					const sw = $1('[data-qv-swiper]', modalBody);
					if (sw && window.Swiper) new Swiper(sw, { pagination: { el: sw.querySelector('.swiper-pagination'), clickable: true } });
				});
		}
		if (e.target.closest('[data-modal-close]')) closeModal();
	});

	/* ------------------------------------------------------------ Amostras de cor/tamanho + personalização */
	function initProductForm(root) {
		const $form = $('.variations_form', root);
		if ($form.length && typeof $form.wc_variation_form === 'function' && !$form.data('lorie-init')) {
			$form.data('lorie-init', 1);
			if (root !== document) $form.wc_variation_form();
		}
		$$('.swatches', root).forEach((group) => {
			const select = group.closest('td, .value') ? group.closest('td, .value').querySelector('select') : null;
			if (!select) return;
			const label = group.closest('tr') ? group.closest('tr').querySelector('th label') : null;
			const base = label ? label.textContent.replace(/:.*$/, '') : '';
			const sync = () => {
				const avail = new Set(Array.from(select.options).filter((o) => o.value && !o.disabled).map((o) => o.value));
				$$('.sw', group).forEach((b) => {
					const on = b.dataset.value === select.value;
					b.classList.toggle('is-on', on);
					b.setAttribute('aria-checked', on ? 'true' : 'false');
					b.classList.toggle('is-off', !avail.has(b.dataset.value));
				});
				if (label) {
					const sel = $$('.sw', group).find((b) => b.dataset.value === select.value);
					label.innerHTML = base + (sel ? `: <b>${sel.dataset.label}</b>` : '');
				}
			};
			group.addEventListener('click', (e) => {
				const b = e.target.closest('.sw');
				if (!b) return;
				select.value = select.value === b.dataset.value ? '' : b.dataset.value;
				$(select).trigger('change');
				sync();
			});
			$(select).on('change', sync);
			$form.on('woocommerce_update_variation_values reset_data', () => setTimeout(sync, 0));
			sync();
		});
		$$('.lorie-custom__toggle', root).forEach((t) => {
			t.addEventListener('click', () => {
				const body = t.nextElementSibling;
				const open = t.getAttribute('aria-expanded') !== 'true';
				t.setAttribute('aria-expanded', open ? 'true' : 'false');
				body.hidden = !open;
				if (open) body.querySelector('textarea').focus();
			});
		});
		// Botões +/− na quantidade.
		$$('.quantity', root).forEach((q) => {
			if (q.dataset.enh) return;
			q.dataset.enh = 1;
			const input = q.querySelector('input.qty');
			if (!input || input.type === 'hidden') return;
			const mk = (d, icon) => {
				const b = document.createElement('button');
				b.type = 'button';
				b.className = 'qbtn';
				b.setAttribute('aria-label', d > 0 ? 'Aumentar' : 'Diminuir');
				b.textContent = icon;
				b.addEventListener('click', () => {
					const min = parseFloat(input.min) || 1;
					const max = parseFloat(input.max) || Infinity;
					input.value = Math.min(max, Math.max(min, (parseFloat(input.value) || 0) + d));
					$(input).trigger('change');
				});
				return b;
			};
			q.prepend(mk(-1, '−'));
			q.append(mk(1, '+'));
		});
	}
	initProductForm(document);
	$(document.body).on('updated_cart_totals', () => initProductForm(document));

	/* ------------------------------------------------------------ Favoritos */
	const WKEY = 'lorie_wishlist';
	const wish = new Set(store.get(WKEY, []));
	function paintWish() {
		$$('[data-wish]').forEach((b) => {
			const on = wish.has(b.dataset.wish);
			b.classList.toggle('is-on', on);
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
		$$('[data-wish-count]').forEach((c) => { c.textContent = wish.size; c.hidden = !wish.size; });
	}
	document.addEventListener('click', (e) => {
		const b = e.target.closest('[data-wish]');
		if (!b) return;
		e.preventDefault();
		const id = b.dataset.wish;
		if (wish.has(id)) { wish.delete(id); toast(L.i18n.wishRm); } else { wish.add(id); toast(L.i18n.wishAdd); }
		store.set(WKEY, Array.from(wish));
		paintWish();
		b.classList.remove('beat'); void b.offsetWidth; b.classList.add('beat');
		if ($1('[data-wishlist-page]') && !wish.has(id)) { const card = b.closest('.product-card'); if (card) card.remove(); if (!wish.size) loadWishlist(); }
	});
	function loadWishlist() {
		const page = $1('[data-wishlist-page]');
		if (!page) return;
		const empty = $1('[data-wishlist-empty]');
		if (!wish.size) { page.innerHTML = ''; empty.hidden = false; return; }
		fetch(wcUrl('lorie_wishlist') + '&ids=' + Array.from(wish).join(','), { credentials: 'same-origin' })
			.then((r) => r.json())
			.then((r) => { const has = r.data.html.includes('product-card'); page.innerHTML = has ? r.data.html : ''; empty.hidden = has; paintWish(); });
	}
	paintWish();
	loadWishlist();
	document.addEventListener('lorie:cards', paintWish);

	/* ------------------------------------------------------------ Busca */
	const search = $1('[data-search]');
	const sInput = $1('[data-search-input]');
	const sResults = $1('[data-search-results]');
	function openSearch() {
		if (!search) return;
		search.hidden = false;
		requestAnimationFrame(() => search.classList.add('is-open'));
		lockScroll(true);
		setTimeout(() => sInput && sInput.focus(), 120);
	}
	function closeSearch() {
		if (!search || search.hidden) return;
		search.classList.remove('is-open');
		setTimeout(() => (search.hidden = true), 300);
		lockScroll(false);
	}
	document.addEventListener('click', (e) => {
		if (e.target.closest('[data-search-open]')) openSearch();
		if (e.target.closest('[data-search-close]') || e.target === search) closeSearch();
	});
	let sTimer = null;
	if (sInput) sInput.addEventListener('input', () => {
		clearTimeout(sTimer);
		const q = sInput.value.trim();
		if (q.length < 2) { sResults.innerHTML = ''; return; }
		sTimer = setTimeout(() => {
			fetch(wcUrl('lorie_search') + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
				.then((r) => r.json())
				.then((r) => {
					const items = (r.data && r.data.items) || [];
					sResults.innerHTML = items.length
						? items.map((i) => `<a class="sr-item" href="${i.url}">${i.img ? `<img src="${i.img}" alt="">` : ''}<span><b>${i.name}</b><em class="price">${i.price}</em></span></a>`).join('') + `<a class="link-arrow sr-all" href="${r.data.all}">Ver todos os resultados →</a>`
						: '<p class="sr-empty">Nenhuma peça encontrada. Tente outra palavra.</p>';
				});
		}, 260);
	});

	/* ------------------------------------------------------------ Newsletter */
	document.addEventListener('submit', (e) => {
		const f = e.target.closest('[data-newsletter]');
		if (!f) return;
		e.preventDefault();
		const msg = $1('.newsletter__msg', f);
		const btn = $1('button', f);
		btn.disabled = true;
		wpPost('lorie_newsletter', new FormData(f)).then((r) => {
			btn.disabled = false;
			msg.textContent = (r.data && r.data.message) || L.i18n.error;
			if (r.success) f.reset();
		});
	});

	/* ------------------------------------------------------------ Máscaras e CEP */
	const masks = {
		cpf: (v) => v.replace(/\D/g, '').slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'),
		cep: (v) => v.replace(/\D/g, '').slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'),
		phone: (v) => {
			const d = v.replace(/\D/g, '').slice(0, 11);
			if (d.length <= 10) return d.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
			return d.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
		},
	};
	document.addEventListener('input', (e) => {
		const m = e.target.dataset && e.target.dataset.mask;
		if (m && masks[m]) {
			const pos = e.target.value.length;
			e.target.value = masks[m](e.target.value);
			if (e.target.value.length !== pos) e.target.setSelectionRange(e.target.value.length, e.target.value.length);
		}
		if (e.target.matches('[data-cep-lookup]')) lookupCep(e.target);
	});

	/** Preenche o endereço automaticamente pelo CEP (ViaCEP, serviço público e gratuito). */
	function lookupCep(input) {
		const cep = input.value.replace(/\D/g, '');
		if (cep.length !== 8 || input.dataset.last === cep) return;
		input.dataset.last = cep;
		input.classList.add('is-loading');
		fetch(`https://viacep.com.br/ws/${cep}/json/`)
			.then((r) => r.json())
			.then((d) => {
				input.classList.remove('is-loading');
				if (!d || d.erro) return;
				const form = input.closest('form') || document;
				const prefix = (input.name.match(/^(billing|shipping)_/) || [''])[0];
				const map = prefix
					? { [prefix + 'address_1']: d.logradouro, [prefix + 'neighborhood']: d.bairro, [prefix + 'city']: d.localidade, [prefix + 'state']: d.uf }
					: { street: d.logradouro, district: d.bairro, city: d.localidade, state: d.uf };
				Object.entries(map).forEach(([name, val]) => {
					const f = form.querySelector(`[name="${name}"]`);
					if (f && val) { f.value = val; $(f).trigger('change'); }
				});
				const num = form.querySelector(prefix ? `[name="${prefix}number"]` : '[name="number"]');
				if (num) num.focus();
			})
			.catch(() => input.classList.remove('is-loading'));
	}
	$(document.body).on('updated_checkout', () => {
		$$('input[data-mask]').forEach((i) => (i.value = masks[i.dataset.mask] ? masks[i.dataset.mask](i.value) : i.value));
	});

	/* ------------------------------------------------------------ Entrar / cadastro */
	document.addEventListener('click', (e) => {
		const t = e.target.closest('[data-auth-tab]');
		if (!t) return;
		const root = t.closest('.lorie-auth');
		const tab = t.dataset.authTab;
		$$('.lorie-auth__tabs button', root).forEach((b) => b.classList.toggle('is-active', b.dataset.authTab === tab));
		$$('[data-auth-panel]', root).forEach((p) => p.classList.toggle('is-active', p.dataset.authPanel === tab));
	});
	document.addEventListener('submit', (e) => {
		const f = e.target.closest('[data-lorie-form]');
		if (!f) return;
		e.preventDefault();
		const type = f.dataset.lorieForm;
		const action = { magic: 'lorie_magic_link', register: 'lorie_register', complete: 'lorie_complete_profile' }[type];
		const msg = $1('.lorie-form__msg', f);
		const btn = $1('button[type=submit]', f);
		$$('.has-error', f).forEach((el) => el.classList.remove('has-error'));
		$$('.lorie-field__err', f).forEach((el) => el.remove());
		btn.disabled = true;
		btn.classList.add('is-loading');
		msg.className = 'lorie-form__msg';
		msg.textContent = '';
		wpPost(action, new FormData(f)).then((r) => {
			btn.disabled = false;
			btn.classList.remove('is-loading');
			const d = r.data || {};
			msg.textContent = d.message || (r.success ? '' : L.i18n.error);
			msg.classList.add(r.success ? 'is-ok' : 'is-error');
			if (d.fields) {
				Object.entries(d.fields).forEach(([name, err]) => {
					const input = f.querySelector(`[name="${name}"]`);
					if (!input) return;
					const wrap = input.closest('.lorie-field, .lorie-check') || input.parentElement;
					wrap.classList.add('has-error');
					const s = document.createElement('small');
					s.className = 'lorie-field__err';
					s.textContent = err;
					wrap.appendChild(s);
				});
				const first = f.querySelector('.has-error input, .has-error select');
				if (first) first.focus();
			}
			if (r.success && d.redirect) setTimeout(() => (location.href = d.redirect), 500);
		}).catch(() => { btn.disabled = false; btn.classList.remove('is-loading'); msg.textContent = L.i18n.error; });
	});

	/* ------------------------------------------------------------ Aviso de cookies */
	const cookie = $1('[data-cookie]');
	if (cookie && !store.get('lorie_cookie_ok', false)) {
		setTimeout(() => { cookie.hidden = false; requestAnimationFrame(() => cookie.classList.add('is-on')); }, 1500);
		$1('[data-cookie-ok]', cookie).addEventListener('click', () => {
			store.set('lorie_cookie_ok', true);
			cookie.classList.remove('is-on');
			setTimeout(() => (cookie.hidden = true), 300);
		});
	}

	// Ícone do WhatsApp aparece depois de rolar um pouco.
	const wa = $1('.wa-float');
	if (wa) {
		const t = () => wa.classList.toggle('is-on', window.scrollY > 240 || !document.body.classList.contains('has-hero'));
		window.addEventListener('scroll', t, { passive: true });
		t();
	}
})(jQuery);
