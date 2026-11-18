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

Alpine.start();
