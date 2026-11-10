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

Alpine.start();
