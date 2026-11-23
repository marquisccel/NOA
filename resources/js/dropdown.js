/*
 * Daftar pilihan (dropdown) bertema untuk semua <select>.
 *
 * Daftar bawaan peramban (biru, bersudut) tidak bisa diberi gaya. Di perangkat dengan kursor, <select> diganti
 * tombol dan daftar yang mengikuti tema. <select> aslinya tetap ada (tersembunyi) sebagai sumber nilai, sehingga
 * formulir, Alpine (x-model, @change), dan validasi server berjalan seperti biasa. Di layar sentuh pemilih
 * bawaan ponsel dipertahankan. Untuk mengecualikan satu <select>, beri atribut data-native.
 */
const PANAH = '<svg class="dd-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
const CENTANG = '<svg class="dd-centang" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';

const daftar = new Set();
let dibuka = null; // { tombol, panel, tutup }
let urut = 0;

const teks = (opsi) => (opsi ? opsi.text.replace(/\s+/g, ' ').trim() : '');

function sinkron(item) {
    const { sel, tombol, label } = item;
    const terpilih = sel.options[sel.selectedIndex];

    label.textContent = teks(terpilih);
    tombol.disabled = sel.disabled;
    tombol.classList.toggle('dd-kosong', !sel.value);
}

function sinkronSemua() {
    daftar.forEach(sinkron);
}

function tutup(kembaliFokus = false) {
    if (!dibuka) {
        return;
    }

    const { tombol, panel } = dibuka;
    panel.remove();
    tombol.setAttribute('aria-expanded', 'false');
    tombol.removeAttribute('aria-activedescendant');
    document.removeEventListener('pointerdown', luarKlik, true);
    window.removeEventListener('resize', gulir);
    window.removeEventListener('scroll', gulir, true);
    dibuka = null;

    if (kembaliFokus) {
        tombol.focus();
    }
}

// Tempatkan di bawah tombol; bila tidak muat, di atasnya. Daftar melayang di <body> agar tidak terpotong kartu atau tabel.
function tempatkan(panel, tombol) {
    const r = tombol.getBoundingClientRect();

    // Tombol keluar dari layar (halaman digulir jauh): tutup saja.
    if (r.bottom < 0 || r.top > window.innerHeight) {
        return false;
    }

    panel.style.minWidth = `${Math.round(r.width)}px`;
    panel.style.maxHeight = '';
    const tinggi = Math.min(panel.scrollHeight, 288);
    const muatBawah = window.innerHeight - r.bottom - 12 >= tinggi;
    panel.style.left = `${Math.max(8, Math.min(r.left, window.innerWidth - panel.offsetWidth - 8))}px`;
    panel.style.top = `${muatBawah ? r.bottom + 6 : Math.max(8, r.top - 6 - tinggi)}px`;
    panel.style.maxHeight = `${Math.max(120, muatBawah ? window.innerHeight - r.bottom - 16 : r.top - 16)}px`;

    return true;
}

// Menggulir halaman menggeser daftar bersama tombolnya; menggulir isi daftar sendiri tidak berpengaruh.
const gulir = (e) => {
    if (dibuka && !dibuka.panel.contains(e.target) && !tempatkan(dibuka.panel, dibuka.tombol)) {
        tutup(false);
    }
};

const luarKlik = (e) => {
    if (dibuka && !dibuka.panel.contains(e.target) && !dibuka.tombol.contains(e.target)) {
        tutup(false);
    }
};

function pilih(item, nilai) {
    const { sel } = item;

    if (sel.value !== nilai) {
        sel.value = nilai;
        sel.dispatchEvent(new Event('input', { bubbles: true }));
        sel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    tutup(true);
    sinkronSemua();
    // Alpine dapat mengubah pilihan select lain setelah perubahan ini; sinkronkan sekali lagi.
    setTimeout(sinkronSemua, 0);
}

function buka(item) {
    if (item.tombol.disabled) {
        return;
    }

    tutup(false);

    const { sel, tombol } = item;
    const panel = document.createElement('ul');
    const idPanel = `dd-panel-${item.no}`;
    panel.id = idPanel;
    panel.className = 'dd-panel';
    panel.setAttribute('role', 'listbox');
    const aria = sel.getAttribute('aria-label') || item.namaLabel;
    if (aria) {
        panel.setAttribute('aria-label', aria);
    }

    const opsi = [...sel.options].map((o, i) => ({ o, i, id: `${idPanel}-${i}` }));
    let aktif = Math.max(0, sel.selectedIndex);

    const li = opsi.map(({ o, i, id }) => {
        const el = document.createElement('li');
        el.id = id;
        el.className = 'dd-opt';
        el.setAttribute('role', 'option');
        el.setAttribute('aria-selected', String(i === sel.selectedIndex));
        if (o.disabled) {
            el.setAttribute('aria-disabled', 'true');
        }
        el.innerHTML = `<span class="dd-teks"></span>${i === sel.selectedIndex ? CENTANG : ''}`;
        el.firstChild.textContent = teks(o);
        el.addEventListener('mousedown', (e) => e.preventDefault()); // fokus tetap di tombol
        el.addEventListener('mousemove', () => sorot(i));
        el.addEventListener('click', () => {
            if (!o.disabled) {
                pilih(item, o.value);
            }
        });
        panel.append(el);

        return el;
    });

    const sorot = (i) => {
        aktif = i;
        li.forEach((el, k) => el.toggleAttribute('data-aktif', k === i));
        tombol.setAttribute('aria-activedescendant', li[i]?.id ?? '');
        li[i]?.scrollIntoView({ block: 'nearest' });
    };

    document.body.append(panel);
    tempatkan(panel, tombol);

    tombol.setAttribute('aria-expanded', 'true');
    tombol.setAttribute('aria-controls', idPanel);
    dibuka = { tombol, panel, item, sorot, geser: (d) => { let k = aktif; do { k += d; } while (opsi[k] && opsi[k].o.disabled); if (opsi[k]) sorot(k); }, opsi, pilihAktif: () => { const o = opsi[aktif]?.o; if (o && !o.disabled) pilih(item, o.value); } };
    sorot(aktif);

    document.addEventListener('pointerdown', luarKlik, true);
    window.addEventListener('resize', gulir);
    window.addEventListener('scroll', gulir, true);
}

function tekanTombol(item, e) {
    const terbuka = dibuka && dibuka.tombol === item.tombol;

    if (!terbuka) {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
            e.preventDefault();
            buka(item);
        }

        return;
    }

    switch (e.key) {
        case 'ArrowDown': e.preventDefault(); dibuka.geser(1); break;
        case 'ArrowUp': e.preventDefault(); dibuka.geser(-1); break;
        case 'Home': e.preventDefault(); dibuka.sorot(dibuka.opsi.findIndex((x) => !x.o.disabled)); break;
        case 'End': e.preventDefault(); dibuka.sorot(dibuka.opsi.length - 1 - [...dibuka.opsi].reverse().findIndex((x) => !x.o.disabled)); break;
        case 'Enter':
        case ' ': e.preventDefault(); dibuka.pilihAktif(); break;
        case 'Escape': e.preventDefault(); tutup(true); break;
        case 'Tab': tutup(false); break;
        default:
            // Ketik huruf untuk melompat ke pilihan yang diawali huruf itu.
            if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                const huruf = e.key.toLowerCase();
                const k = dibuka.opsi.findIndex((x) => !x.o.disabled && teks(x.o).toLowerCase().startsWith(huruf));
                if (k >= 0) {
                    dibuka.sorot(k);
                }
            }
    }
}

function tingkatkan(sel) {
    if (sel.multiple || sel.size > 1 || 'native' in sel.dataset || sel.dataset.dd) {
        return;
    }

    sel.dataset.dd = '1';
    const no = ++urut;
    const tombol = document.createElement('button');
    tombol.type = 'button';
    tombol.className = `${sel.className} dd-btn`;
    tombol.setAttribute('role', 'combobox');
    tombol.setAttribute('aria-haspopup', 'listbox');
    tombol.setAttribute('aria-expanded', 'false');

    const label = document.createElement('span');
    label.className = 'dd-label';
    tombol.append(label);
    tombol.insertAdjacentHTML('beforeend', PANAH);

    // Nama yang dibacakan: aria-label bila ada, jika tidak <label for>.
    const labelUntuk = sel.id ? [...document.querySelectorAll(`label[for="${CSS.escape(sel.id)}"]`)] : [];
    const namaLabel = sel.getAttribute('aria-label') || labelUntuk.map((l) => l.textContent.trim()).join(' ');
    if (sel.getAttribute('aria-label')) {
        tombol.setAttribute('aria-label', sel.getAttribute('aria-label'));
    } else {
        labelUntuk.forEach((l, i) => {
            l.id ||= `dd-label-${no}-${i}`;
        });
        if (labelUntuk.length) {
            tombol.setAttribute('aria-labelledby', labelUntuk.map((l) => l.id).join(' '));
        }
    }

    // <select> asli disembunyikan tetapi tetap ikut dikirim bersama formulir.
    sel.classList.add('dd-native');
    sel.tabIndex = -1;
    sel.setAttribute('aria-hidden', 'true');
    sel.before(tombol);

    const item = { sel, tombol, label, no, namaLabel };
    daftar.add(item);

    tombol.addEventListener('click', () => (dibuka && dibuka.tombol === tombol ? tutup(false) : buka(item)));
    tombol.addEventListener('keydown', (e) => tekanTombol(item, e));
    // Mengeklik <label> membuka fokus ke tombol, bukan ke <select> yang tersembunyi.
    labelUntuk.forEach((l) => l.addEventListener('click', (e) => { e.preventDefault(); tombol.focus(); }));
    sel.form?.addEventListener('reset', () => setTimeout(sinkronSemua, 0));

    // Opsi atau status select dapat berubah oleh Alpine (x-for, :disabled).
    new MutationObserver(() => sinkron(item)).observe(sel, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'selected', 'label'] });
    sinkron(item);
}

export function mulaiDropdown() {
    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        return;
    }

    const proses = () => document.querySelectorAll('select').forEach(tingkatkan);

    // Setelah Alpine selesai membuat opsi dinamis.
    setTimeout(() => {
        proses();
        sinkronSemua();
    }, 0);

    document.addEventListener('change', () => setTimeout(sinkronSemua, 0));
    window.addEventListener('pageshow', sinkronSemua);
    window.addEventListener('load', sinkronSemua);
    // Select yang baru muncul belakangan (misalnya dalam modal) ikut ditingkatkan.
    new MutationObserver((rekam) => {
        if (rekam.some((r) => [...r.addedNodes].some((n) => n.nodeType === 1 && (n.matches?.('select') || n.querySelector?.('select'))))) {
            proses();
        }
    }).observe(document.body, { childList: true, subtree: true });
}
