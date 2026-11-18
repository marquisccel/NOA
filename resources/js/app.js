import Alpine from 'alpinejs';

window.Alpine = Alpine;

const KUNCI_SIDEBAR = 'noa.sidebar';

// Status shell: sidebar diciutkan (diingat di perangkat) dan drawer menu di ponsel.
Alpine.data('shell', () => ({
    collapsed: false,
    drawer: false,

    init() {
        try {
            this.collapsed = localStorage.getItem(KUNCI_SIDEBAR) === 'ciut';
        } catch (e) {
            // penyimpanan tidak tersedia: tampilan tetap berfungsi tanpa ingatan
        }
    },

    alternatif() {
        this.collapsed = !this.collapsed;

        try {
            localStorage.setItem(KUNCI_SIDEBAR, this.collapsed ? 'ciut' : 'lebar');
        } catch (e) {
            // diabaikan
        }
    },
}));

// Jam dan tanggal di topbar, mengikuti zona waktu aplikasi (bukan jam perangkat yang bisa keliru).
Alpine.data('jam', (zona = 'Asia/Jakarta') => ({
    waktu: '',
    tanggal: '',
    penghitung: null,

    init() {
        this.perbarui();
        this.penghitung = setInterval(() => this.perbarui(), 1000);
    },

    perbarui() {
        const sekarang = new Date();

        try {
            this.waktu = sekarang.toLocaleTimeString('id-ID', { timeZone: zona, hour12: false });
            this.tanggal = sekarang.toLocaleDateString('id-ID', { timeZone: zona, weekday: 'short', day: 'numeric', month: 'short' }).replace(/\./g, '');
        } catch (e) {
            this.waktu = sekarang.toLocaleTimeString('id-ID', { hour12: false });
            this.tanggal = sekarang.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });
        }
    },
}));

// Tema terang atau gelap; pilihan diingat di perangkat dan mengikuti pengaturan sistem bila belum dipilih.
Alpine.data('tema', () => ({
    gelap: document.documentElement.dataset.theme === 'dark',

    alternatif() {
        this.gelap = !this.gelap;
        document.documentElement.dataset.theme = this.gelap ? 'dark' : 'light';

        try {
            localStorage.setItem('noa.tema', this.gelap ? 'dark' : 'light');
        } catch (e) {
            // diabaikan
        }
    },
}));

// Tur panduan: menyorot elemen bertanda data-tur="Judul|Penjelasan" (urutan: data-tur-urut) satu per satu.
const KUNCI_TUR = 'noa.tur.selesai';

Alpine.data('tur', () => ({
    aktif: false,
    langkah: [],
    i: 0,
    kotak: { top: 0, left: 0, width: 0, height: 0 },
    popover: { top: 0, left: 0 },

    init() {
        let sudah = false;
        try {
            sudah = localStorage.getItem(KUNCI_TUR) === '1';
        } catch (e) {
            sudah = true;
        }

        if (!sudah && document.body.dataset.tur === 'otomatis') {
            setTimeout(() => this.mulai(), 900);
        }
    },

    mulai() {
        this.langkah = [...document.querySelectorAll('[data-tur]')]
            .filter((el) => el.offsetParent !== null)
            .sort((a, b) => Number(a.dataset.turUrut || 9) - Number(b.dataset.turUrut || 9));

        if (!this.langkah.length) {
            return;
        }

        this.i = 0;
        this.aktif = true;
        this.sorot();
    },

    sorot() {
        const el = this.langkah[this.i];
        el.scrollIntoView({ block: 'center', behavior: 'auto' });

        setTimeout(() => {
            const r = el.getBoundingClientRect();
            const pad = 6;
            this.kotak = { top: r.top - pad, left: r.left - pad, width: r.width + pad * 2, height: r.height + pad * 2 };

            const lebar = Math.min(320, window.innerWidth - 24);
            const bawah = r.bottom + 14 + 170 < window.innerHeight;
            this.popover = {
                top: bawah ? r.bottom + 14 : Math.max(12, r.top - 14 - 170),
                left: Math.min(Math.max(12, r.left), window.innerWidth - lebar - 12),
            };
        }, 60);
    },

    get teks() {
        const [judul, isi] = (this.langkah[this.i]?.dataset.tur || '|').split('|');

        return { judul, isi };
    },

    berikut() {
        if (this.i >= this.langkah.length - 1) {
            return this.selesai();
        }
        this.i++;
        this.sorot();
    },

    kembali() {
        if (this.i > 0) {
            this.i--;
            this.sorot();
        }
    },

    selesai() {
        this.aktif = false;
        try {
            localStorage.setItem(KUNCI_TUR, '1');
        } catch (e) {
            // diabaikan
        }
    },
}));

// Umpan balik saat berpindah halaman atau mengirim formulir: bilah kemajuan di atas dan tombol kirim tampak sibuk.
const bilah = () => document.getElementById('bilah-kemajuan');
const tampilkanBilah = () => {
    const b = bilah();
    if (b) {
        b.classList.remove('opacity-0');
        b.classList.add('opacity-100', 'bilah-jalan');
    }
};

document.addEventListener('submit', (e) => {
    tampilkanBilah();
    const tombol = e.submitter || e.target.querySelector('button[type=submit], button:not([type])');
    if (tombol && !tombol.dataset.tanpaSibuk) {
        setTimeout(() => {
            tombol.setAttribute('aria-busy', 'true');
            tombol.classList.add('opacity-70', 'cursor-wait');
        }, 0);
    }
});

document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (a && !e.defaultPrevented && !e.ctrlKey && !e.metaKey && a.target !== '_blank' && a.origin === location.origin && !a.hash && !a.hasAttribute('download')) {
        tampilkanBilah();
    }
});

window.addEventListener('pageshow', () => {
    const b = bilah();
    if (b) {
        b.classList.add('opacity-0');
        b.classList.remove('opacity-100', 'bilah-jalan');
    }
});

Alpine.start();
