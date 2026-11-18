/*
 * Halaman tamu (masuk, daftar): hero interaktif berbasis kanvas.
 *  - Medan aliran: partikel mengikuti arus, berputar di sekitar kursor, memercik saat diklik.
 *  - Kartu kaca melayang dengan kemiringan 3D mengikuti kursor dan sorotan cahaya di kartu fitur.
 *  - Grafik contoh bobot vs standar yang tergambar bertahap, angka yang menghitung naik.
 * Semua mati sendiri saat tab tidak terlihat, dan disederhanakan bila pengguna memilih gerak minimal.
 */
const gerakMinimal = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const hero = document.querySelector('[data-hero]');

if (hero && hero.offsetParent !== null) {
    mulaiHero(hero);
}

function mulaiHero(hero) {
    const kanvas = hero.querySelector('[data-medan]');
    const kursor = { x: -9999, y: -9999, aktif: false };
    let tampil = true;

    if (kanvas) {
        medanAliran(kanvas, hero, kursor, () => tampil);
    }

    kemiringan(hero, kursor);
    sorotanKartu(hero);
    grafikContoh(hero.querySelector('[data-grafik]'), () => tampil);
    hitungNaik(hero);

    if ('IntersectionObserver' in window) {
        new IntersectionObserver(([e]) => { tampil = e.isIntersecting; }).observe(hero);
    }
}

// ---------- Medan aliran ----------

function medanAliran(kanvas, hero, kursor, boleh) {
    const ctx = kanvas.getContext('2d');
    const WARNA = ['126,214,176', '63,174,146', '231,193,107', '200,240,225'];
    let w = 0;
    let h = 0;
    let dpr = 1;
    let partikel = [];
    let t = 0;
    let rafId = 0;
    let terakhir = 0;

    const acak = (a, b) => a + Math.random() * (b - a);

    function ukur() {
        const r = hero.getBoundingClientRect();
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        w = r.width;
        h = r.height;
        kanvas.width = Math.round(w * dpr);
        kanvas.height = Math.round(h * dpr);
        kanvas.style.width = `${w}px`;
        kanvas.style.height = `${h}px`;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.fillStyle = '#05120d';
        ctx.fillRect(0, 0, w, h);

        const jumlah = Math.max(120, Math.min(520, Math.round((w * h) / 1900)));
        partikel = Array.from({ length: jumlah }, baru);
    }

    function baru() {
        return { x: acak(0, w), y: acak(0, h), vx: 0, vy: 0, w: WARNA[Math.floor(Math.random() * WARNA.length)], umur: acak(0, 300), maks: acak(220, 520), tebal: acak(0.6, 1.7) };
    }

    // Sudut arus: gelombang bertumpuk yang bergerak pelan, terasa organik tanpa pustaka derau.
    function sudut(x, y) {
        return (
            Math.sin(x * 0.0042 + t * 0.35) * Math.cos(y * 0.0051 - t * 0.28) * Math.PI * 2 +
            Math.sin((x + y) * 0.0021 - t * 0.2) * 1.6
        );
    }

    function langkah(waktu) {
        rafId = requestAnimationFrame(langkah);

        if (document.hidden || !boleh()) {
            return;
        }

        // batasi sekitar 45 fps agar hemat baterai di laptop dan ponsel
        if (waktu - terakhir < 22) {
            return;
        }
        terakhir = waktu;
        t += 0.012;

        ctx.globalCompositeOperation = 'source-over';
        ctx.fillStyle = 'rgba(5,18,13,0.085)';
        ctx.fillRect(0, 0, w, h);
        ctx.globalCompositeOperation = 'lighter';

        for (const p of partikel) {
            const a = sudut(p.x, p.y);
            p.vx += Math.cos(a) * 0.09;
            p.vy += Math.sin(a) * 0.09;

            if (kursor.aktif) {
                const dx = p.x - kursor.x;
                const dy = p.y - kursor.y;
                const d2 = dx * dx + dy * dy;
                if (d2 < 22000) {
                    const d = Math.sqrt(d2) + 0.001;
                    const k = (1 - d / 148) * 0.9;
                    // putaran di sekitar kursor + sedikit tolakan
                    p.vx += (-dy / d) * k + (dx / d) * k * 0.35;
                    p.vy += (dx / d) * k + (dy / d) * k * 0.35;
                }
            }

            p.vx *= 0.93;
            p.vy *= 0.93;
            const x0 = p.x;
            const y0 = p.y;
            p.x += p.vx * 2.2;
            p.y += p.vy * 2.2;
            p.umur += 1;

            const hidup = Math.sin((p.umur / p.maks) * Math.PI);
            ctx.strokeStyle = `rgba(${p.w},${(0.05 + hidup * 0.5).toFixed(3)})`;
            ctx.lineWidth = p.tebal;
            ctx.beginPath();
            ctx.moveTo(x0, y0);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();

            if (p.umur > p.maks || p.x < -20 || p.x > w + 20 || p.y < -20 || p.y > h + 20) {
                Object.assign(p, baru(), { umur: 0 });
            }
        }
    }

    function percikan(x, y) {
        for (let i = 0; i < 28; i++) {
            const a = (i / 28) * Math.PI * 2;
            const p = partikel[Math.floor(Math.random() * partikel.length)];
            p.x = x;
            p.y = y;
            p.vx = Math.cos(a) * acak(3, 7);
            p.vy = Math.sin(a) * acak(3, 7);
            p.umur = 0;
        }
    }

    hero.addEventListener('pointermove', (e) => {
        const r = hero.getBoundingClientRect();
        kursor.x = e.clientX - r.left;
        kursor.y = e.clientY - r.top;
        kursor.aktif = true;
    });
    hero.addEventListener('pointerleave', () => { kursor.aktif = false; });
    hero.addEventListener('pointerdown', (e) => {
        if (e.target.closest('a,button,input')) {
            return;
        }
        const r = hero.getBoundingClientRect();
        percikan(e.clientX - r.left, e.clientY - r.top);
    });

    new ResizeObserver(() => ukur()).observe(hero);
    ukur();

    if (gerakMinimal) {
        // gambar beberapa langkah lalu diam
        for (let i = 0; i < 140; i++) {
            terakhir = -100;
            langkah(i * 30);
            cancelAnimationFrame(rafId);
        }
        return;
    }

    rafId = requestAnimationFrame(langkah);
}

// ---------- Kemiringan 3D kartu kaca ----------

function kemiringan(hero) {
    if (gerakMinimal) {
        return;
    }

    const kartu = [...hero.querySelectorAll('[data-miring]')];
    const sasaran = { x: 0, y: 0 };
    const kini = { x: 0, y: 0 };

    hero.addEventListener('pointermove', (e) => {
        const r = hero.getBoundingClientRect();
        sasaran.x = ((e.clientX - r.left) / r.width - 0.5) * 2;
        sasaran.y = ((e.clientY - r.top) / r.height - 0.5) * 2;
    });
    hero.addEventListener('pointerleave', () => { sasaran.x = 0; sasaran.y = 0; });

    (function putar() {
        kini.x += (sasaran.x - kini.x) * 0.07;
        kini.y += (sasaran.y - kini.y) * 0.07;
        for (const el of kartu) {
            const kuat = Number(el.dataset.miring) || 1;
            el.style.transform = `perspective(1000px) rotateY(${(kini.x * 5 * kuat).toFixed(2)}deg) rotateX(${(-kini.y * 4 * kuat).toFixed(2)}deg)`;
        }
        requestAnimationFrame(putar);
    })();
}

// ---------- Sorotan cahaya mengikuti kursor di kartu ----------

function sorotanKartu(hero) {
    for (const el of hero.querySelectorAll('[data-sorot]')) {
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            el.style.setProperty('--mx', `${e.clientX - r.left}px`);
            el.style.setProperty('--my', `${e.clientY - r.top}px`);
        });
    }
}

// ---------- Grafik contoh: bobot aktual vs standar ----------

function grafikContoh(kanvas, boleh) {
    if (!kanvas) {
        return;
    }

    const ctx = kanvas.getContext('2d');
    const HARI = 35;
    const BERJALAN = 23;
    // kurva pertumbuhan (logistik) dalam kg; aktual sedikit di atas standar dengan riak kecil
    const std = Array.from({ length: HARI }, (_, i) => 0.045 + 2.35 / (1 + Math.exp(-(i - 22) / 5.2)));
    const aktual = std.map((v, i) => v * (1.03 + Math.sin(i * 1.7) * 0.012));
    let kemajuan = gerakMinimal ? 1 : 0;
    let w = 0;
    let h = 0;
    let dpr = 1;

    function ukur() {
        const r = kanvas.getBoundingClientRect();
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        w = r.width;
        h = r.height;
        kanvas.width = Math.round(w * dpr);
        kanvas.height = Math.round(h * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    const X = (i) => 6 + (i / (HARI - 1)) * (w - 12);
    const Y = (v) => h - 8 - (v / 2.6) * (h - 18);

    function jalur(arr, sampai) {
        ctx.beginPath();
        for (let i = 0; i <= sampai; i++) {
            const x = X(i);
            const y = Y(arr[i]);
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                const xp = X(i - 1);
                const yp = Y(arr[i - 1]);
                ctx.bezierCurveTo((xp + x) / 2, yp, (xp + x) / 2, y, x, y);
            }
        }
    }

    function gambar(waktu) {
        ctx.clearRect(0, 0, w, h);

        // garis bantu halus
        ctx.strokeStyle = 'rgba(255,255,255,0.07)';
        ctx.lineWidth = 1;
        for (let k = 1; k < 4; k++) {
            ctx.beginPath();
            ctx.moveTo(0, (h / 4) * k);
            ctx.lineTo(w, (h / 4) * k);
            ctx.stroke();
        }

        // standar (putus-putus)
        ctx.setLineDash([4, 5]);
        ctx.strokeStyle = 'rgba(231,193,107,0.75)';
        ctx.lineWidth = 1.5;
        jalur(std, HARI - 1);
        ctx.stroke();
        ctx.setLineDash([]);

        // aktual: tergambar sesuai kemajuan
        const sampai = Math.max(1, Math.floor(BERJALAN * kemajuan));
        const grad = ctx.createLinearGradient(0, 0, 0, h);
        grad.addColorStop(0, 'rgba(126,214,176,0.38)');
        grad.addColorStop(1, 'rgba(126,214,176,0)');
        jalur(aktual, sampai);
        ctx.strokeStyle = '#7ed6b0';
        ctx.lineWidth = 2.4;
        ctx.lineJoin = 'round';
        ctx.stroke();
        ctx.lineTo(X(sampai), h);
        ctx.lineTo(X(0), h);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();

        // titik "sekarang" berdenyut
        const px = X(sampai);
        const py = Y(aktual[sampai]);
        const denyut = gerakMinimal ? 0 : (Math.sin(waktu / 420) + 1) / 2;
        ctx.beginPath();
        ctx.arc(px, py, 5 + denyut * 6, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(126,214,176,${0.28 - denyut * 0.2})`;
        ctx.fill();
        ctx.beginPath();
        ctx.arc(px, py, 3.6, 0, Math.PI * 2);
        ctx.fillStyle = '#eafff6';
        ctx.fill();
    }

    function putar(waktu) {
        requestAnimationFrame(putar);
        if (document.hidden || !boleh()) {
            return;
        }
        if (kemajuan < 1) {
            kemajuan = Math.min(1, kemajuan + 0.011);
        }
        gambar(waktu);
    }

    new ResizeObserver(() => { ukur(); gambar(0); }).observe(kanvas);
    ukur();
    gambar(0);
    setTimeout(() => requestAnimationFrame(putar), 700);
}

// ---------- Angka menghitung naik ----------

function hitungNaik(hero) {
    for (const el of hero.querySelectorAll('[data-hitung]')) {
        const akhir = parseFloat(el.dataset.hitung);
        const desimal = Number(el.dataset.desimal || 0);
        const format = (v) => v.toLocaleString('id-ID', { minimumFractionDigits: desimal, maximumFractionDigits: desimal });

        if (gerakMinimal) {
            el.textContent = format(akhir);
            continue;
        }

        const mulai = performance.now() + 500;
        const durasi = 1400;
        (function langkah(skrg) {
            const p = Math.min(1, Math.max(0, (skrg - mulai) / durasi));
            const ease = 1 - Math.pow(1 - p, 3);
            el.textContent = format(akhir * ease);
            if (p < 1) {
                requestAnimationFrame(langkah);
            }
        })(performance.now());
    }
}
