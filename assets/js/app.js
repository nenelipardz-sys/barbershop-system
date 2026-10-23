/* Cut & Schedule — shared UI behaviour */
(function () {
  'use strict';

  const BASE = document.body.dataset.base || '';
  const CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const peso = (n) => '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: Number(n) % 1 ? 2 : 0, maximumFractionDigits: 2 });
  window.CS = { BASE, CSRF, peso };

  /* ---------- Fit to screen ----------
     Desktop layouts are designed at 1536px wide. On smaller laptop screens the whole UI is
     zoomed down (never below 80%); pages marked data-fit (the home dashboards) shrink further,
     down to 62%, so everything is visible without scrolling — like the prototype screens. */
  function setZoom(z) {
    const root = document.documentElement;
    root.style.setProperty('--z', z);
    root.style.zoom = z < 1 ? z : '';
  }
  function fitScreen() {
    const w = window.innerWidth;
    if (w < 992) { setZoom(1); return; }
    let z = Math.min(1, Math.max(0.8, w / 1536));
    setZoom(z);
    const content = $('.content');
    if (!document.body.hasAttribute('data-fit') || !content) return;
    for (let i = 0; i < 3; i++) {
      // Visual bottom of the last block on the page (getBoundingClientRect already includes zoom).
      const bottom = Math.max(...Array.from(content.children)
        .filter((el) => !el.classList.contains('toasts') && el.offsetParent !== null)
        .map((el) => el.getBoundingClientRect().bottom + window.scrollY));
      const needed = bottom + 18 * z;
      if (needed <= window.innerHeight + 1) break;
      const next = Math.max(0.62, z * window.innerHeight / needed);
      if (Math.abs(next - z) < 0.005) break;
      z = next;
      setZoom(z);
    }
  }
  fitScreen();
  window.addEventListener('load', fitScreen);
  let fitTimer;
  window.addEventListener('resize', () => { clearTimeout(fitTimer); fitTimer = setTimeout(fitScreen, 120); });

  /* ---------- Sidebar (mobile) ---------- */
  $$('[data-open-sidebar]').forEach((b) => b.addEventListener('click', () => document.body.classList.add('sidebar-open')));
  $$('[data-close-sidebar]').forEach((b) => b.addEventListener('click', () => document.body.classList.remove('sidebar-open')));

  /* ---------- Dropdowns ---------- */
  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-dropdown-toggle]');
    $$('[data-dropdown].open').forEach((d) => {
      if (!toggle || !d.contains(toggle)) {
        if (!d.contains(e.target)) d.classList.remove('open');
      }
    });
    if (toggle) {
      const dd = toggle.closest('[data-dropdown]');
      dd.classList.toggle('open');
    }
  });

  /* ---------- Toasts ---------- */
  function dismiss(t) {
    t.classList.add('hide');
    setTimeout(() => t.remove(), 300);
  }
  $$('.toast').forEach((t) => {
    t.querySelector('.toast-x')?.addEventListener('click', () => dismiss(t));
    setTimeout(() => dismiss(t), t.classList.contains('toast-error') ? 9000 : 5000);
  });
  window.CS.toast = function (message, type = 'info') {
    const box = $('#toasts');
    if (!box) return alert(message);
    const t = document.createElement('div');
    t.className = 'toast toast-' + type;
    t.innerHTML = '<span></span><button type="button" class="toast-x" aria-label="Dismiss">✕</button>';
    t.querySelector('span').textContent = message;
    t.querySelector('.toast-x').addEventListener('click', () => dismiss(t));
    box.appendChild(t);
    setTimeout(() => dismiss(t), 5000);
  };

  /* ---------- Modals ---------- */
  function openModal(modal, trigger) {
    if (!modal) return;
    const form = $('form', modal);
    if (form && trigger && trigger.dataset.reset !== undefined) {
      form.reset();
      // reset() leaves hidden inputs untouched; clear ids left over from a previous edit.
      $$('input[type=hidden]', form).forEach((h) => { if (!['csrf', 'action'].includes(h.name) && h.dataset.keepValue === undefined) h.value = ''; });
      $$('[data-preview]', form).forEach((img) => (img.src = img.dataset.default || img.src));
    }
    if (trigger && trigger.dataset.fill) {
      try {
        const data = JSON.parse(trigger.dataset.fill);
        Object.entries(data).forEach(([name, value]) => {
          const fields = $$(`[name="${name}"], [name="${name}[]"]`, modal);
          fields.forEach((f) => {
            if (f.type === 'checkbox' || f.type === 'radio') {
              const values = Array.isArray(value) ? value.map(String) : [String(value)];
              f.checked = values.includes(f.value);
            } else if (f.tagName === 'IMG') {
              f.src = value;
            } else {
              f.value = value ?? '';
            }
          });
          $$(`[data-text="${name}"]`, modal).forEach((el) => (el.textContent = value ?? ''));
          $$(`img[data-src="${name}"]`, modal).forEach((el) => { if (value) el.src = value; });
        });
      } catch (err) { console.error(err); }
      const st = modal.querySelector('[name="start_time"]');
      if (st && st.value) st.dataset.keep = st.value;
    }
    if (trigger && trigger.dataset.title) {
      const h = $('.modal-head h3', modal);
      if (h) h.textContent = trigger.dataset.title;
    }
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    modal.dispatchEvent(new CustomEvent('modal:open', { detail: { trigger } }));
    const first = $('input:not([type=hidden]):not([readonly]), select, textarea', modal);
    if (first && window.innerWidth > 760) setTimeout(() => first.focus(), 60);
  }
  function closeModal(modal) {
    modal.classList.remove('open');
    if (!$('.modal.open')) document.body.style.overflow = '';
  }
  window.CS.openModal = openModal;
  window.CS.closeModal = closeModal;

  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-open]');
    if (opener) {
      e.preventDefault();
      openModal(document.getElementById(opener.dataset.modalOpen), opener);
      return;
    }
    const closer = e.target.closest('[data-modal-close]');
    if (closer) {
      closeModal(closer.closest('.modal'));
    } else if (e.target.classList.contains('modal-backdrop')) {
      closeModal(e.target.closest('.modal'));
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const open = $$('.modal.open').pop();
      if (open) closeModal(open);
      document.body.classList.remove('sidebar-open');
      $$('[data-dropdown].open').forEach((d) => d.classList.remove('open'));
    }
  });
  // Re-open a modal after a failed submit (server sets data-auto-open).
  $$('.modal[data-auto-open]').forEach((m) => openModal(m));

  /* ---------- Confirm before submit ---------- */
  const confirmModal = document.createElement('div');
  confirmModal.className = 'modal';
  confirmModal.innerHTML = `
    <div class="modal-backdrop"></div>
    <div class="modal-dialog sm">
      <div class="modal-head"><h3>Please confirm</h3></div>
      <div class="modal-body"><p class="confirm-text"></p></div>
      <div class="modal-foot">
        <button type="button" class="btn btn-light" data-modal-close>Go back</button>
        <button type="button" class="btn btn-gold" data-confirm-ok>Yes, continue</button>
      </div>
    </div>`;
  document.body.appendChild(confirmModal);
  let pendingForm = null;
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.dataset.confirm && !form.dataset.confirmed) {
      e.preventDefault();
      pendingForm = form;
      $('.confirm-text', confirmModal).textContent = form.dataset.confirm;
      const ok = $('[data-confirm-ok]', confirmModal);
      ok.className = 'btn ' + (form.dataset.confirmStyle === 'danger' ? 'btn-danger' : 'btn-gold');
      ok.textContent = form.dataset.confirmLabel || 'Yes, continue';
      openModal(confirmModal);
    } else if (!form.dataset.noLock) {
      $$('button[type=submit]', form).forEach((b) => setTimeout(() => (b.disabled = true), 0));
    }
  });
  $('[data-confirm-ok]', confirmModal).addEventListener('click', () => {
    if (!pendingForm) return;
    pendingForm.dataset.confirmed = '1';
    closeModal(confirmModal);
    if (pendingForm.requestSubmit) pendingForm.requestSubmit(); else pendingForm.submit();
  });

  /* ---------- Password visibility ---------- */
  $$('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.togglePassword);
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.classList.toggle('on', show);
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });

  /* ---------- Image preview for uploads ---------- */
  $$('input[type=file][data-preview-target]').forEach((input) => {
    input.addEventListener('change', () => {
      const img = document.getElementById(input.dataset.previewTarget);
      const file = input.files && input.files[0];
      if (img && file) img.src = URL.createObjectURL(file);
    });
  });

  /* ---------- Animated bar charts ---------- */
  function renderBars(container, labels, values) {
    const max = Math.max(...values, 1);
    container.innerHTML = labels.map((l, i) => {
      const h = Math.max(2, Math.round((values[i] / max) * 100));
      return `<div class="bar"><i style="height:0" data-h="${h}" data-v="${peso(values[i])}"></i><span>${l}</span></div>`;
    }).join('');
    requestAnimationFrame(() => requestAnimationFrame(() => {
      $$('i', container).forEach((b) => (b.style.height = b.dataset.h + '%'));
    }));
  }
  window.CS.renderBars = renderBars;
  $$('[data-bars]').forEach((el) => {
    try {
      const d = JSON.parse(el.dataset.bars);
      renderBars(el, d.labels, d.values);
    } catch (err) { console.error(err); }
  });

  /* ---------- Sales overview widget ---------- */
  $$('[data-sales]').forEach((widget) => {
    const bars = $('.bars', widget);
    const total = $('.chart-total', widget);
    const cap = $('.chart-cap', widget);
    const barber = widget.dataset.barber || '';
    async function load(mode) {
      $$('[data-mode]', widget).forEach((b) => b.classList.toggle('active', b.dataset.mode === mode));
      try {
        const res = await fetch(`${BASE}/api/sales.php?mode=${mode}&barber=${barber}`, { headers: { Accept: 'application/json' } });
        const d = await res.json();
        if (!d.ok) throw new Error(d.error);
        total.textContent = peso(d.headline);
        cap.textContent = d.caption;
        renderBars(bars, d.labels, d.values);
      } catch (err) {
        bars.innerHTML = '<p class="slots-msg">Could not load chart.</p>';
      }
    }
    $$('[data-mode]', widget).forEach((b) => b.addEventListener('click', () => load(b.dataset.mode)));
    load('daily');
  });

  /* ---------- Time slot picker ---------- */
  function initSlotPicker(picker) {
    const form = picker.closest('form');
    const get = (name) => form.querySelector(`[name="${name}"]:checked`) || form.querySelector(`select[name="${name}"], input[name="${name}"]:not([type=radio])`);
    const hidden = form.querySelector('input[name="start_time"]');
    const msg = picker.querySelector('.slots-msg');
    const grid = picker.querySelector('.slots');
    let lastKey = '';

    async function load(force) {
      const barber = get('barber_id')?.value || '';
      // Several services (checkboxes on the booking page) are booked back-to-back.
      const multi = $$('[name="service_ids[]"]:checked', form).map((i) => i.value);
      const service = multi.length ? multi.join(',') : (get('service_id')?.value || '');
      const date = get('appointment_date')?.value || '';
      const exclude = form.querySelector('[name="appointment_id"]')?.value || '';
      const key = [barber, service, date, exclude].join('|');
      if (!force && key === lastKey) return;
      lastKey = key;
      const keep = hidden.dataset.keep || '';
      hidden.value = '';
      grid.innerHTML = '';
      form.dispatchEvent(new Event('slotchange'));
      if (!barber || !service || !date) {
        msg.textContent = 'Select a service, barber and date to see available times.';
        msg.hidden = false;
        return;
      }
      msg.textContent = 'Loading available times…';
      msg.hidden = false;
      try {
        const res = await fetch(`${BASE}/api/slots.php?barber_id=${barber}&service_ids=${service}&date=${date}&exclude=${exclude}`, { headers: { Accept: 'application/json' } });
        const d = await res.json();
        if (!d.ok) { msg.textContent = d.error || 'No available times.'; return; }
        if (!d.slots.length) { msg.textContent = d.message || 'No time slots on this date.'; return; }
        const free = d.slots.filter((s) => s.available).length;
        msg.textContent = free ? `${free} time slot${free > 1 ? 's' : ''} available` : 'Fully booked on this date — please choose another date or barber.';
        grid.innerHTML = d.slots.map((s) => `<button type="button" class="slot" data-time="${s.time}" ${s.available ? '' : 'disabled title="Already booked"'}>${s.label}</button>`).join('');
        if (keep) {
          const b = grid.querySelector(`[data-time="${keep}"]:not(:disabled)`);
          if (b) select(b);
          hidden.dataset.keep = '';
        }
      } catch (err) {
        msg.textContent = 'Could not load time slots. Please try again.';
      }
    }
    function select(btn) {
      $$('.slot', grid).forEach((b) => b.classList.remove('selected'));
      btn.classList.add('selected');
      hidden.value = btn.dataset.time;
      form.dispatchEvent(new Event('slotchange'));
    }
    grid.addEventListener('click', (e) => {
      const b = e.target.closest('.slot');
      if (b && !b.disabled) select(b);
    });
    form.addEventListener('change', (e) => {
      if (['barber_id', 'service_id', 'service_ids[]', 'appointment_date'].includes(e.target.name)) load();
    });
    form.addEventListener('submit', (e) => {
      if (!hidden.value) {
        e.preventDefault();
        e.stopImmediatePropagation();
        msg.hidden = false;
        msg.textContent = 'Please choose an available time slot.';
        msg.style.color = '#d04545';
        setTimeout(() => (msg.style.color = ''), 2500);
        $$('button[type=submit]', form).forEach((b) => (b.disabled = false));
      }
    }, true);
    picker.reload = () => load(true);
    const modal = picker.closest('.modal');
    if (modal) modal.addEventListener('modal:open', () => setTimeout(() => load(true), 0));
    load(true);
  }
  $$('[data-slot-picker]').forEach(initSlotPicker);

  /* ---------- Client picker (new walk-in + automatic discount) ---------- */
  $$('[data-client-picker] select').forEach((sel) => {
    const form = sel.closest('form');
    const sync = () => {
      const isNew = sel.value === 'new';
      $$('.new-client', form).forEach((el) => el.classList.toggle('hidden', !isNew));
      const opt = sel.selectedOptions[0];
      const disc = form.querySelector('[name="discount_type"]');
      if (disc && opt && opt.dataset.discount) disc.value = opt.dataset.discount;
    };
    sel.addEventListener('change', sync);
    form.closest('.modal')?.addEventListener('modal:open', sync);
  });

  /* ---------- Favorites toggle ---------- */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-fav]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const [type, id] = btn.dataset.fav.split(':');
    try {
      const body = new URLSearchParams({ type, id, csrf: CSRF });
      const res = await fetch(`${BASE}/api/favorites.php`, { method: 'POST', body, headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' } });
      const d = await res.json();
      if (!d.ok) throw new Error(d.error);
      btn.classList.toggle('on', d.favorite);
      btn.innerHTML = d.icon;
      btn.setAttribute('aria-label', d.favorite ? 'Remove from favorites' : 'Add to favorites');
      window.CS.toast(d.favorite ? 'Added to favorites.' : 'Removed from favorites.', 'success');
      if (!d.favorite && btn.dataset.removeCard !== undefined) btn.closest('[data-fav-card]')?.remove();
    } catch (err) {
      window.CS.toast(err.message || 'Something went wrong.', 'error');
    }
  });

  /* ---------- Auto-submit filters ---------- */
  $$('[data-autosubmit]').forEach((form) => {
    $$('select, input[type=date]', form).forEach((el) => el.addEventListener('change', () => form.submit()));
  });
})();
