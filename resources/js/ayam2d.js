/*
 * Maskot ayam untuk halaman masuk.
 * Badan, kepala, ekor, dan jengger adalah satu gambar utuh (piksel asli, tanpa sambungan yang bisa terlihat). Hanya mata
 * yang dipisah: bola mata asli bergerak di dalam wajah, dan kelopak berupa kulit kepala yang turun menutupinya. Badan
 * bernapas dan condong ke arah kursor dengan kaki sebagai tumpuan. Semua memakai transformasi 2D supaya gambar tetap
 * tajam. Tanpa WebGL dan langsung tampil.
 *
 *  - Mata mengikuti kursor; bila kursor diam, melirik sendiri. Berkedip (kadang dua kali).
 *  - Mengisi email: menoleh ke kartu masuk. Mengisi kata sandi: memejam dan membuang muka.
 *  - Dicolek: melompat dengan gepeng-lonjong.
 */
const klem = (v, a, b) => Math.min(b, Math.max(a, v));
const lerp = (a, b, k) => a + (b - a) * k;

export function mulaiAyam2d(wadah, { gerakMinimal = false } = {}) {
    const tombol = wadah.querySelector('[data-colek]');
    const lompatEl = wadah.querySelector('[data-lompat]');
    const adegan = wadah.querySelector('[data-adegan2]');
    const kepalaEl = wadah.querySelector('[data-kepala]');
    const RASIO = 928 / 1311;

    function ukur() {
        const w = wadah.clientWidth;
        const h = wadah.clientHeight;
        const lebar = Math.round(Math.min(w, h * RASIO));
        tombol.style.width = `${lebar}px`;
        tombol.style.height = `${Math.round(lebar / RASIO)}px`;
    }

    new ResizeObserver(ukur).observe(wadah);
    ukur();

    // ---------- masukan ----------
    const ptr = { x: 0, y: 0, ada: false, terakhir: -99 };
    const fokus = { mode: 'bebas' };

    window.addEventListener('pointermove', (e) => {
        ptr.x = e.clientX;
        ptr.y = e.clientY;
        ptr.ada = true;
        ptr.terakhir = performance.now() / 1000;
        if (gerakMinimal) gambar();
    });
    document.addEventListener('focusin', (e) => {
        const t = e.target;
        if (!(t instanceof HTMLInputElement)) return;
        fokus.mode = t.type === 'password' || t.name === 'password' ? 'sandi' : t.type === 'checkbox' ? 'bebas' : 'email';
        if (gerakMinimal) gambar();
    });
    document.addEventListener('focusout', () => {
        fokus.mode = 'bebas';
        if (gerakMinimal) gambar();
    });

    const aksi = { lompat: -9 };
    tombol.addEventListener('click', () => {
        aksi.lompat = performance.now() / 1000;
        if (gerakMinimal) gambar();
    });

    // ---------- keadaan animasi ----------
    const kini = {
        x: 0, y: 0,            // arah pandang mata (-1..1)
        badan: 0,              // condong badan (-1..1), lebih lambat dari mata
        tutup: 0,
        kedipMulai: -1, kedipBerikut: 2.4, kedipLagi: false,
        tLalu: null,
    };

    function gambar(waktu = performance.now()) {
        const t = waktu / 1000;
        const dt = kini.tLalu === null ? 0.016 : klem(t - kini.tLalu, 0.001, 0.05);
        kini.tLalu = t;

        const r = kepalaEl.getBoundingClientRect();
        const hx = r.left + r.width * 0.64;
        const hy = r.top + r.height * 0.24;

        let tx = 0;
        let ty = 0;
        if (fokus.mode === 'email') {
            tx = 1;
            ty = 0.15;
        } else if (fokus.mode === 'sandi') {
            tx = -0.9;
            ty = 0.1;
        } else if (ptr.ada && t - ptr.terakhir < 5) {
            tx = klem((ptr.x - hx) / (window.innerWidth * 0.3), -1, 1);
            ty = klem((ptr.y - hy) / (window.innerHeight * 0.3), -1, 1);
        } else if (!gerakMinimal) {
            tx = Math.sin(t * 0.6) * 0.55 + Math.sin(t * 1.7 + 2) * 0.12;
            ty = Math.sin(t * 0.37 + 1) * 0.25;
        }

        // pegas bebas-kecepatan-bingkai: mata cepat, badan lambat
        const ke = gerakMinimal ? 1 : 1 - Math.exp(-dt * 13);
        const kb = gerakMinimal ? 1 : 1 - Math.exp(-dt * 3.6);
        kini.x = lerp(kini.x, tx, ke);
        kini.y = lerp(kini.y, ty, ke);
        kini.badan = lerp(kini.badan, tx, kb);

        // berkedip (kadang dua kali berturut-turut), atau memejam saat mengisi kata sandi
        let kedip = 1;
        if (!gerakMinimal && fokus.mode !== 'sandi') {
            if (kini.kedipMulai < 0 && t > kini.kedipBerikut) kini.kedipMulai = t;
            if (kini.kedipMulai >= 0) {
                const p = (t - kini.kedipMulai) / 0.16;
                kedip = 1 - Math.sin(Math.min(1, p) * Math.PI) * 0.97;
                if (p >= 1) {
                    kini.kedipMulai = -1;
                    if (!kini.kedipLagi && Math.random() < 0.22) {
                        kini.kedipLagi = true;
                        kini.kedipBerikut = t + 0.12;
                    } else {
                        kini.kedipLagi = false;
                        kini.kedipBerikut = t + 2 + Math.random() * 3.5;
                    }
                }
            }
        }
        kini.tutup = lerp(kini.tutup, fokus.mode === 'sandi' ? 1 : 0, gerakMinimal ? 1 : 1 - Math.exp(-dt * 14));
        const blink = Math.max(0.04, Math.min(kedip, 1 - kini.tutup * 0.96));

        const nap = gerakMinimal ? 0 : Math.sin(t * 2.0);

        // lompat dengan gepeng-lonjong
        let y = 0;
        let sy = 1;
        let sx = 1;
        if (aksi.lompat >= 0) {
            const p = (t - aksi.lompat) / 0.8;
            if (p >= 1) {
                aksi.lompat = -9;
            } else {
                y = Math.sin(p * Math.PI) * 0.1;
                const gep = p < 0.14 ? -Math.sin((p / 0.14) * Math.PI / 2) * 0.1 : p > 0.86 ? -Math.sin(((1 - p) / 0.14) * Math.PI / 2) * 0.09 : Math.sin(p * Math.PI) * 0.06;
                sy = 1 + gep;
                sx = 1 - gep * 0.6;
            }
        }
        const set = (n, v) => adegan.style.setProperty(n, v);
        set('--rz', `${(kini.badan * 1.7 + (gerakMinimal ? 0 : Math.sin(t * 0.8) * 0.6)).toFixed(3)}deg`);
        set('--sk', `${(-kini.badan * 0.8).toFixed(3)}deg`);
        set('--ex', (kini.x * 17).toFixed(2));
        set('--ey', (kini.y * 13).toFixed(2));
                set('--lid', klem((1 - blink) / 0.96, 0, 1).toFixed(3));
        set('--nap', (1 + nap * 0.008).toFixed(4));
        set('--nx', (1 - nap * 0.003).toFixed(4));
        lompatEl.style.setProperty('--bx', (1 - y * 4).toFixed(3));
        lompatEl.style.setProperty('--bo', (1 - y * 5).toFixed(3));
        lompatEl.style.transform = `translateY(${(-y * 100).toFixed(2)}%) scale(${sx.toFixed(3)}, ${sy.toFixed(3)})`;
    }

    gambar();

    if (!gerakMinimal) {
        let hidup = true;
        new IntersectionObserver(([e]) => { hidup = e.isIntersecting; }).observe(wadah);
        const putar = (w) => {
            requestAnimationFrame(putar);
            if (document.hidden || !hidup) {
                kini.tLalu = null;
                return;
            }
            gambar(w);
        };
        requestAnimationFrame(putar);
    }

    wadah.style.opacity = '1';
}
