/*
 * Halaman tamu: adegan peternakan berlapis yang bergeser halus mengikuti kursor (paralaks).
 * Ringan: hanya mengubah dua variabel CSS, berhenti saat tidak ada gerakan, mati bila pengguna memilih gerak minimal.
 */
const adegan = document.querySelector('[data-adegan]');

if (adegan && !window.matchMedia('(prefers-reduced-motion: reduce)').matches && window.matchMedia('(pointer: fine)').matches) {
    const sasaran = { x: 0, y: 0 };
    const kini = { x: 0, y: 0 };
    let jalan = false;

    const putar = () => {
        kini.x += (sasaran.x - kini.x) * 0.075;
        kini.y += (sasaran.y - kini.y) * 0.075;
        adegan.style.setProperty('--px', kini.x.toFixed(4));
        adegan.style.setProperty('--py', kini.y.toFixed(4));

        if (!document.hidden && (Math.abs(sasaran.x - kini.x) > 0.001 || Math.abs(sasaran.y - kini.y) > 0.001)) {
            requestAnimationFrame(putar);
        } else {
            jalan = false;
        }
    };

    window.addEventListener('pointermove', (e) => {
        sasaran.x = (e.clientX / window.innerWidth - 0.5) * -2;
        sasaran.y = (e.clientY / window.innerHeight - 0.5) * -2;

        if (!jalan) {
            jalan = true;
            requestAnimationFrame(putar);
        }
    });
}

// Maskot ayam 3D: dimuat malas (three.js) hanya di layar lebar dan bila WebGL tersedia.
const wadahAyam = document.querySelector('[data-ayam3d]');

if (wadahAyam && window.matchMedia('(min-width: 1024px)').matches) {
    const bisaWebgl = (() => {
        try {
            const c = document.createElement('canvas');

            return !!(c.getContext('webgl2') || c.getContext('webgl'));
        } catch (e) {
            return false;
        }
    })();

    if (bisaWebgl) {
        import('./ayam3d.js')
            .then((m) => m.mulaiAyam(wadahAyam, { gerakMinimal: window.matchMedia('(prefers-reduced-motion: reduce)').matches }))
            .catch(() => { wadahAyam.style.display = 'none'; });
    } else {
        wadahAyam.style.display = 'none';
    }
}
