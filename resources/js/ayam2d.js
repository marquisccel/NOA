/*
 * Maskot ayam untuk halaman masuk.
 * Gambar ayam dipecah menjadi tiga bagian: badan utuh (termasuk kepala, tanpa mata), ekor hijau, dan mata.
 * Kepala tidak digeser terpisah supaya tidak ada tepi ganda; yang bergerak adalah mata asli di dalam wajah,
 * ekor pada pangkalnya, dan seluruh badan sedikit miring mengikuti kursor. Tanpa WebGL dan langsung tampil.
 *
 *  - Kepala menoleh dan mata mengikuti kursor; bila kursor diam, melirik sendiri.
 *  - Bernapas, berkedip. Mengisi email: menoleh ke kartu masuk. Mengisi kata sandi: memejam dan membuang muka.
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
        const lebar = Math.min(w, h * RASIO);
        tombol.style.width = `${lebar}px`;
        tombol.style.height = `${lebar / RASIO}px`;
        adegan.style.setProperty('--s', (lebar / 928).toFixed(4));
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

    // ---------- animasi ----------
    const kini = { x: 0, y: 0, tutup: 0, kedipMulai: -1, kedipBerikut: 2.4 };

    function gambar(waktu = performance.now()) {
        const t = waktu / 1000;
        const r = kepalaEl.getBoundingClientRect();
        const hx = r.left + r.width * 0.62;
        const hy = r.top + r.height * 0.4;

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
            tx = Math.sin(t * 0.6) * 0.55;
            ty = Math.sin(t * 0.37 + 1) * 0.25;
        }

        const k = gerakMinimal ? 1 : 0.1;
        kini.x = lerp(kini.x, tx, k);
        kini.y = lerp(kini.y, ty, k);

        // berkedip, atau memejam saat mengisi kata sandi
        let kedip = 1;
        if (!gerakMinimal && fokus.mode !== 'sandi') {
            if (kini.kedipMulai < 0 && t > kini.kedipBerikut) kini.kedipMulai = t;
            if (kini.kedipMulai >= 0) {
                const p = (t - kini.kedipMulai) / 0.18;
                kedip = 1 - Math.sin(Math.min(1, p) * Math.PI) * 0.93;
                if (p >= 1) {
                    kini.kedipMulai = -1;
                    kini.kedipBerikut = t + 2 + Math.random() * 3.5;
                }
            }
        }
        kini.tutup = lerp(kini.tutup, fokus.mode === 'sandi' ? 1 : 0, gerakMinimal ? 1 : 0.2);
        const blink = Math.max(0.06, Math.min(kedip, 1 - kini.tutup * 0.94));

        const nap = gerakMinimal ? 0 : Math.sin(t * 2.0);

        // ekor bergoyang pelan; setelah dicolek bergoyang cepat lalu mereda
        let goyang = gerakMinimal ? 0 : Math.sin(t * 1.4) * 1.8 + Math.sin(t * 0.55) * 0.8;
        if (aksi.lompat >= 0 || t - aksi.lompat < 1.8) {
            goyang += Math.sin((t - aksi.lompat) * 15) * 5 * Math.exp(-(t - aksi.lompat) * 2.4);
        }

        const set = (n, v) => adegan.style.setProperty(n, v);
        set('--ry', `${(kini.x * 5).toFixed(2)}deg`);
        set('--rx', `${(-kini.y * 3).toFixed(2)}deg`);
        set('--rz', `${(gerakMinimal ? 0 : Math.sin(t * 0.9) * 0.8).toFixed(2)}deg`);
        set('--ek', `${goyang.toFixed(2)}deg`);
        set('--ex', (kini.x * 17).toFixed(1));
        set('--ey', (kini.y * 13).toFixed(1));
        set('--blink', blink.toFixed(3));
        set('--mo', klem((blink - 0.25) / 0.45, 0, 1).toFixed(3));
        set('--kl', klem((0.75 - blink) / 0.45, 0, 1).toFixed(3));
        set('--nap', (1 + nap * 0.008).toFixed(4));
        set('--nx', (1 - nap * 0.003).toFixed(4));

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
        lompatEl.style.transform = `translateY(${(-y * 100).toFixed(2)}%) scale(${sx.toFixed(3)}, ${sy.toFixed(3)})`;
    }

    gambar();

    if (!gerakMinimal) {
        let hidup = true;
        new IntersectionObserver(([e]) => { hidup = e.isIntersecting; }).observe(wadah);
        const putar = (w) => {
            requestAnimationFrame(putar);
            if (document.hidden || !hidup) return;
            gambar(w);
        };
        requestAnimationFrame(putar);
    }

    wadah.style.opacity = '1';
}
