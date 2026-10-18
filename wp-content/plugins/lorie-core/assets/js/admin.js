/* Painel Lorié — interações do admin (sem dependências além do WordPress). */
(function () {
	'use strict';
	const A = window.LORIE_ADMIN || {};
	const $ = (s, r = document) => r.querySelector(s);
	const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

	function post(action, data) {
		const body = new FormData();
		body.append('action', action);
		body.append('nonce', A.nonce);
		Object.entries(data || {}).forEach(([k, v]) => body.append(k, v));
		return fetch(A.ajax, { method: 'POST', body, credentials: 'same-origin' }).then((r) => r.json());
	}

	function toast(msg, type) {
		let t = $('.lorie-toast');
		if (!t) {
			t = document.createElement('div');
			t.className = 'lorie-toast';
			document.body.appendChild(t);
		}
		t.textContent = msg;
		t.dataset.type = type || 'ok';
		t.classList.add('is-on');
		clearTimeout(t._h);
		t._h = setTimeout(() => t.classList.remove('is-on'), 3200);
	}

	/** Recarrega só o conteúdo do quadro (sem recarregar a página inteira). */
	function refreshRoot() {
		const root = $('#lorie-board-root');
		if (!root) return location.reload();
		root.classList.add('is-loading');
		return fetch(location.href, { credentials: 'same-origin' })
			.then((r) => r.text())
			.then((html) => {
				const doc = new DOMParser().parseFromString(html, 'text/html');
				const fresh = doc.querySelector('#lorie-board-root');
				if (fresh) root.innerHTML = fresh.innerHTML;
				const sub = doc.querySelector('.lorie-sub');
				if (sub && $('.lorie-sub')) $('.lorie-sub').innerHTML = sub.innerHTML;
				root.classList.remove('is-loading');
			});
	}

	/* ------------------------------------------------------------ Genéricos */
	document.addEventListener('click', (e) => {
		const c = e.target.closest('[data-confirm]');
		if (c && !window.confirm(c.dataset.confirm)) e.preventDefault();
	});
	document.addEventListener('submit', (e) => {
		const b = e.target.querySelector('[data-busy]');
		if (b) {
			b.disabled = true;
			b.textContent = b.dataset.busy;
		}
	});

	/* ------------------------------------------------------------ Media picker */
	function pickMedia(multiple, cb) {
		if (!window.wp || !wp.media) return;
		const frame = wp.media({ title: 'Escolher foto', multiple, library: { type: 'image' }, button: { text: 'Usar foto' } });
		frame.on('select', () => cb(frame.state().get('selection').toJSON()));
		frame.open();
	}

	// Instagram
	document.addEventListener('click', (e) => {
		const pick = e.target.closest('[data-pick-image]');
		if (pick) {
			const slot = pick.closest('.lorie-ig-slot');
			pickMedia(false, ([img]) => {
				const url = (img.sizes && img.sizes.thumbnail ? img.sizes.thumbnail : img).url;
				pick.style.backgroundImage = `url(${url})`;
				pick.textContent = '';
				slot.querySelector('input[type=hidden]').value = img.id;
			});
		}
		const clear = e.target.closest('[data-clear-image]');
		if (clear) {
			const slot = clear.closest('.lorie-ig-slot');
			const btn = slot.querySelector('[data-pick-image]');
			btn.style.backgroundImage = '';
			btn.textContent = '+ foto';
			slot.querySelector('input[type=hidden]').value = '';
			slot.querySelector('input[type=url]').value = '';
		}
	});

	/* ------------------------------------------------------------ Editor de produto */
	const gallery = $('[data-lorie-gallery]');
	if (gallery) {
		const addBtn = $('[data-add-images]', gallery);
		const addItem = (img) => {
			const fig = document.createElement('figure');
			fig.className = 'lorie-gallery__item';
			fig.draggable = true;
			const url = (img.sizes && img.sizes.thumbnail ? img.sizes.thumbnail : img).url;
			fig.innerHTML = `<img src="${url}" alt=""><input type="hidden" name="images[]" value="${img.id}"><button type="button" class="lorie-gallery__rm" aria-label="Remover foto">×</button>`;
			gallery.insertBefore(fig, addBtn);
		};
		addBtn.addEventListener('click', () => pickMedia(true, (imgs) => imgs.forEach(addItem)));
		gallery.addEventListener('click', (e) => {
			if (e.target.closest('.lorie-gallery__rm')) e.target.closest('figure').remove();
		});
		let drag = null;
		gallery.addEventListener('dragstart', (e) => {
			drag = e.target.closest('figure');
			if (drag) drag.classList.add('is-drag');
		});
		gallery.addEventListener('dragend', () => drag && drag.classList.remove('is-drag'));
		gallery.addEventListener('dragover', (e) => {
			e.preventDefault();
			const over = e.target.closest('figure');
			if (!drag || !over || over === drag) return;
			const r = over.getBoundingClientRect();
			gallery.insertBefore(drag, e.clientX < r.left + r.width / 2 ? over : over.nextSibling);
		});
	}

	const addColor = $('[data-add-color]');
	if (addColor) {
		addColor.addEventListener('click', () => {
			const nameI = $('[data-new-color-name]');
			const hexI = $('[data-new-color-hex]');
			const name = nameI.value.trim();
			if (!name) return nameI.focus();
			const lab = document.createElement('label');
			lab.className = 'lorie-swatch';
			lab.innerHTML = `<input type="checkbox" name="new_colors[]" checked><i></i><span></span>`;
			lab.querySelector('input').value = `${name}|${hexI.value}`;
			lab.querySelector('i').style.background = hexI.value;
			lab.querySelector('span').textContent = name;
			$('[data-lorie-colors]').appendChild(lab);
			nameI.value = '';
		});
	}
	const addSize = $('[data-add-size]');
	if (addSize) {
		addSize.addEventListener('click', () => {
			const i = $('[data-new-size-name]');
			const name = i.value.trim();
			if (!name) return i.focus();
			const lab = document.createElement('label');
			lab.innerHTML = `<input type="checkbox" name="new_sizes[]" checked><span></span>`;
			lab.querySelector('input').value = name;
			lab.querySelector('span').textContent = name;
			$('[data-lorie-sizes]').appendChild(lab);
			i.value = '';
		});
	}
	$$('.lorie-field--money input').forEach((inp) => {
		inp.addEventListener('blur', () => {
			let v = inp.value.replace(/[^\d,\.]/g, '');
			if (!v) return;
			if (v.includes(',')) v = v.replace(/\./g, '').replace(',', '.');
			const n = parseFloat(v);
			if (!isNaN(n)) inp.value = n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
		});
	});

	/* ------------------------------------------------------------ Encomendas / Envios */
	document.addEventListener('click', (e) => {
		const mv = e.target.closest('[data-move]');
		if (!mv) return;
		const card = mv.closest('[data-order]');
		const data = { order_id: card.dataset.order, to: mv.dataset.move };
		if (mv.hasAttribute('data-with-tracking')) {
			const t = card.querySelector('[data-tracking]');
			data.tracking = t ? t.value.trim() : '';
		}
		mv.disabled = true;
		card.classList.add('is-moving');
		post('lorie_order_status', data).then((r) => {
			toast(r.data && r.data.message ? r.data.message : 'Erro', r.success ? 'ok' : 'error');
			refreshRoot();
		});
	});

	document.addEventListener('change', (e) => {
		const cb = e.target.closest('[data-item-done]');
		if (!cb) return;
		cb.closest('li').classList.toggle('is-done', cb.checked);
		post('lorie_item_done', { item_id: cb.dataset.itemDone, done: cb.checked ? 1 : '' }).then(() => refreshRoot());
	});

	// Modais (<dialog>)
	document.addEventListener('click', (e) => {
		if (e.target.closest('[data-close]')) e.target.closest('dialog').close();
	});

	// Reembolso
	const rModal = $('#lorie-refund-modal');
	let rOrder = null;
	document.addEventListener('click', (e) => {
		const b = e.target.closest('[data-refund]');
		if (!b || !rModal) return;
		rOrder = b.closest('[data-order]').dataset.order;
		$('[data-r-num]', rModal).textContent = '#' + b.dataset.num;
		$('[data-r-total]', rModal).textContent = b.dataset.total;
		$('[data-r-method]', rModal).textContent = b.dataset.method || '—';
		$('[data-r-confirm]', rModal).checked = false;
		$('[data-r-go]', rModal).disabled = true;
		$('[data-r-msg]', rModal).textContent = '';
		rModal.showModal();
	});
	if (rModal) {
		$('[data-r-confirm]', rModal).addEventListener('change', (e) => ($('[data-r-go]', rModal).disabled = !e.target.checked));
		$('[data-r-go]', rModal).addEventListener('click', (e) => {
			const btn = e.currentTarget;
			btn.disabled = true; // trava contra clique duplo
			btn.textContent = 'Processando…';
			post('lorie_refund', { order_id: rOrder, reason: $('#lorie-refund-reason').value, confirm: 1 }).then((r) => {
				btn.textContent = 'Reembolsar';
				const msg = r.data && r.data.message ? r.data.message : 'Erro inesperado.';
				$('[data-r-msg]', rModal).textContent = msg;
				toast(msg, r.success ? 'ok' : 'error');
				if (r.success) {
					setTimeout(() => rModal.close(), 900);
					refreshRoot();
				} else {
					btn.disabled = false;
				}
			});
		});
	}

	/* ------------------------------------------------------------ Etiqueta (PNG 10×15 cm a 300 dpi) */
	const lModal = $('#lorie-label-modal');
	const logo = new Image();
	logo.src = A.logo;

	function wrap(ctx, text, x, y, maxW, lh) {
		const words = String(text || '').split(/\s+/);
		let line = '';
		for (const w of words) {
			const test = line ? line + ' ' + w : w;
			if (ctx.measureText(test).width > maxW && line) {
				ctx.fillText(line, x, y);
				line = w;
				y += lh;
			} else line = test;
		}
		if (line) ctx.fillText(line, x, y);
		return y + lh;
	}

	function roundRect(ctx, x, y, w, h, r) {
		ctx.beginPath();
		ctx.moveTo(x + r, y);
		ctx.arcTo(x + w, y, x + w, y + h, r);
		ctx.arcTo(x + w, y + h, x, y + h, r);
		ctx.arcTo(x, y + h, x, y, r);
		ctx.arcTo(x, y, x + w, y, r);
		ctx.closePath();
	}

	async function drawLabel(d) {
		const cv = $('#lorie-label-canvas');
		const ctx = cv.getContext('2d');
		const W = cv.width, H = cv.height;
		const gold = '#A87A2E', ink = '#2B201A', muted = '#7A6A5E';
		try { await document.fonts.ready; } catch (e) {}
		if (!logo.complete) await new Promise((r) => { logo.onload = r; logo.onerror = r; });
		const serif = '"Cormorant Garamond", Georgia, serif';
		const sans = 'Jost, "Segoe UI", Arial, sans-serif';

		ctx.fillStyle = '#fff';
		ctx.fillRect(0, 0, W, H);
		ctx.setLineDash([18, 14]);
		ctx.strokeStyle = '#cbb796';
		ctx.lineWidth = 5;
		roundRect(ctx, 28, 28, W - 56, H - 56, 36);
		ctx.stroke();
		ctx.setLineDash([]);

		// Cabeçalho com o logo
		if (logo.naturalWidth) ctx.drawImage(logo, 70, 66, 250, 250);
		ctx.fillStyle = gold;
		ctx.font = `600 34px ${sans}`;
		ctx.textAlign = 'right';
		ctx.fillText('PEDIDO', W - 74, 120);
		ctx.font = `600 96px ${serif}`;
		ctx.fillText('#' + d.order, W - 74, 210);
		ctx.fillStyle = muted;
		ctx.font = `400 34px ${sans}`;
		ctx.fillText(d.date + '  ·  ' + d.items, W - 74, 268);
		ctx.textAlign = 'left';

		// Destinatário
		const bx = 70, by = 360, bw = W - 140, bh = 760;
		ctx.fillStyle = '#FBF6EF';
		roundRect(ctx, bx, by, bw, bh, 28);
		ctx.fill();
		ctx.fillStyle = gold;
		ctx.font = `600 34px ${sans}`;
		ctx.fillText('DESTINATÁRIO', bx + 46, by + 74);
		ctx.fillStyle = ink;
		ctx.font = `600 70px ${sans}`;
		let y = wrap(ctx, d.name, bx + 46, by + 160, bw - 92, 78);
		ctx.font = `400 48px ${sans}`;
		y = wrap(ctx, d.street, bx + 46, y + 10, bw - 92, 60);
		if (d.comp) y = wrap(ctx, d.comp, bx + 46, y, bw - 92, 60);
		y = wrap(ctx, d.district, bx + 46, y, bw - 92, 60);
		y = wrap(ctx, `${d.city} — ${d.state}`, bx + 46, y, bw - 92, 60);
		ctx.font = `600 104px ${sans}`;
		ctx.fillText('CEP ' + d.cep, bx + 46, by + bh - 70);
		if (d.phone) {
			ctx.font = `400 36px ${sans}`;
			ctx.fillStyle = muted;
			ctx.fillText('Tel.: ' + d.phone, bx + 46, by + bh - 190);
		}

		// Remetente
		const s = A.sender || {};
		let ry = by + bh + 90;
		ctx.fillStyle = gold;
		ctx.font = `600 30px ${sans}`;
		ctx.fillText('REMETENTE', bx + 10, ry);
		ctx.fillStyle = ink;
		ctx.font = `500 40px ${sans}`;
		ry = wrap(ctx, s.name, bx + 10, ry + 56, 720, 50);
		ctx.font = `400 34px ${sans}`;
		ry = wrap(ctx, s.street + (s.district ? ' — ' + s.district : ''), bx + 10, ry, 720, 44);
		ry = wrap(ctx, `${s.city} — ${s.state}`, bx + 10, ry, 720, 44);
		ctx.font = `600 38px ${sans}`;
		ry = wrap(ctx, 'CEP ' + s.cep, bx + 10, ry, 720, 44);
		if (s.phone) {
			ctx.font = `400 32px ${sans}`;
			wrap(ctx, 'Tel.: ' + s.phone, bx + 10, ry, 720, 44);
		}

		// QR code com o número do pedido (e rastreio, se houver)
		if (window.qrcode) {
			const qr = window.qrcode(0, 'M');
			qr.addData(`${A.store} pedido #${d.order}` + (d.tracking ? ` rastreio ${d.tracking}` : ''));
			qr.make();
			const n = qr.getModuleCount(), size = 250, cell = size / n;
			const qx = W - 70 - size, qy = by + bh + 50;
			ctx.fillStyle = ink;
			for (let r = 0; r < n; r++) for (let c = 0; c < n; c++) if (qr.isDark(r, c)) ctx.fillRect(qx + c * cell, qy + r * cell, Math.ceil(cell), Math.ceil(cell));
			if (d.tracking) {
				ctx.font = `600 30px ${sans}`;
				ctx.textAlign = 'center';
				ctx.fillText(d.tracking, qx + size / 2, qy + size + 44);
				ctx.textAlign = 'left';
			}
		}

		// Rodapé
		ctx.strokeStyle = '#e6d8c2';
		ctx.lineWidth = 3;
		ctx.beginPath();
		ctx.moveTo(70, H - 150);
		ctx.lineTo(W - 70, H - 150);
		ctx.stroke();
		ctx.fillStyle = gold;
		ctx.textAlign = 'center';
		ctx.font = `italic 500 46px ${serif}`;
		ctx.fillText((A.tagline || 'De uma geração para outra.') + '  ♥', W / 2, H - 88);
		ctx.textAlign = 'left';
		return cv.toDataURL('image/png');
	}

	document.addEventListener('click', async (e) => {
		const b = e.target.closest('[data-make-label]');
		if (!b || !lModal) return;
		const card = b.closest('[data-label]');
		const d = JSON.parse(card.dataset.label);
		const url = await drawLabel(d);
		const dl = $('[data-label-download]', lModal);
		dl.href = url;
		dl.download = `etiqueta-pedido-${d.order}.png`;
		lModal.showModal();
	});
	if (lModal) {
		$('[data-label-print]', lModal).addEventListener('click', () => {
			const url = $('#lorie-label-canvas').toDataURL('image/png');
			const w = window.open('', '_blank');
			if (!w) return toast('Permita pop-ups para imprimir.', 'error');
			w.document.write(`<html><head><title>Etiqueta</title><style>@page{size:100mm 150mm;margin:0}body{margin:0}img{width:100mm;height:150mm;display:block}</style></head><body><img src="${url}" onload="setTimeout(()=>{print();},150)"></body></html>`);
			w.document.close();
		});
	}
})();
