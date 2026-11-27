import './bootstrap';

const rupiah = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');

/* Tombol - / + pada kolom jumlah */
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-step]');
    if (!btn) return;
    const input = btn.closest('[data-stepper]').querySelector('input');
    const min = +input.min || 1;
    const max = +input.max || 99;
    input.value = Math.min(max, Math.max(min, (+input.value || min) + +btn.dataset.step));
    input.dispatchEvent(new Event('input', { bubbles: true }));
});

/* Hitung ulang subtotal di dalam elemen [data-totals] */
function recalc(scope) {
    let sum = 0, count = 0;
    scope.querySelectorAll('input[data-price]').forEach((input) => {
        const qty = +input.value || 0;
        const line = qty * +input.dataset.price;
        sum += line;
        count += qty;
        scope.querySelectorAll(`[data-line="${input.dataset.id}"]`).forEach((el) => (el.textContent = rupiah(line)));
    });
    scope.querySelectorAll('[data-sum]').forEach((el) => (el.textContent = rupiah(sum)));
    scope.querySelectorAll('[data-count]').forEach((el) => (el.textContent = count));
}
document.addEventListener('input', (e) => {
    const scope = e.target.closest('[data-totals]');
    if (scope) recalc(scope);
});

/* Salin nomor rekening */
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;
    try {
        await navigator.clipboard.writeText(btn.dataset.copy);
        const old = btn.textContent;
        btn.textContent = 'Tersalin';
        setTimeout(() => (btn.textContent = old), 2000);
    } catch (_) { /* clipboard tidak tersedia */ }
});

/* Tampilkan / sembunyikan kata sandi */
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-toggle-password]');
    if (!btn) return;
    const input = document.getElementById(btn.dataset.togglePassword);
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Sembunyikan' : 'Tampilkan';
});

/* Label tombol masuk mengikuti peran */
document.querySelectorAll('input[name="role"]').forEach((radio) => {
    radio.addEventListener('change', () => {
        const label = document.querySelector('[data-role-label]');
        if (label) label.textContent = radio.value === 'owner' ? 'Masuk sebagai pemilik usaha' : 'Masuk sebagai admin';
    });
});

/* Konfirmasi sebelum aksi berisiko */
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-confirm]');
    if (el && !confirm(el.dataset.confirm)) e.preventDefault();
});
