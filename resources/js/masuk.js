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
