/*
 * Halaman tamu: paralaks halus (latar, lingkaran cahaya, dan kartu statistik bergeser mengikuti kursor)
 * dan maskot ayam berlapis. Ringan: hanya mengubah dua variabel CSS di <body>.
 */
const gerakMinimal = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!gerakMinimal && window.matchMedia('(pointer: fine)').matches) {
    const sasaran = { x: 0, y: 0 };
    const kini = { x: 0, y: 0 };
    let jalan = false;

    const putar = () => {
        kini.x += (sasaran.x - kini.x) * 0.07;
        kini.y += (sasaran.y - kini.y) * 0.07;
        document.body.style.setProperty('--px', kini.x.toFixed(4));
        document.body.style.setProperty('--py', kini.y.toFixed(4));

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

// Maskot ayam berlapis: hanya di layar lebar (di ponsel panel gambar disembunyikan).
const wadahAyam = document.querySelector('[data-ayam]');

if (wadahAyam && window.matchMedia('(min-width: 1024px)').matches) {
    import('./ayam2d.js')
        .then((m) => m.mulaiAyam2d(wadahAyam, { gerakMinimal }))
        .catch(() => { wadahAyam.style.display = 'none'; });
}
