/*
 * Maskot ayam 3D untuk halaman masuk (three.js), dibuat dari bentuk-bentuk bulat bergaya mainan balon
 * supaya terlihat empuk dan ramah, bukan kaku. Semua dibangun lewat kode: tanpa berkas model, tanpa unduhan tambahan.
 *
 * Perilaku:
 *  - Kepala dan mata mengikuti kursor (mata sedikit lebih jauh daripada kepala agar terasa hidup).
 *  - Bernapas, berkedip, mengepak sayap; bila kursor lama diam, matanya melirik ke sekitar.
 *  - Mengisi email: menoleh ke kartu masuk. Mengisi kata sandi: memejam dan membuang muka (tidak mengintip).
 *  - Diklik: melompat dengan gepeng-lonjong (squash and stretch), sayap mengepak.
 */
import * as THREE from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';

const klem = (v, a, b) => Math.min(b, Math.max(a, v));
const lerp = (a, b, k) => a + (b - a) * k;

export function mulaiAyam(wadah, { gerakMinimal = false } = {}) {
    const kanvas = wadah.querySelector('canvas');
    const tombol = wadah.querySelector('[data-colek]');

    const renderer = new THREE.WebGLRenderer({ canvas: kanvas, antialias: true, alpha: true, powerPreference: 'low-power' });
    renderer.setClearColor(0x000000, 0);
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.08;

    const adegan = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);
    adegan.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;

    const kamera = new THREE.PerspectiveCamera(28, 1, 0.1, 80);
    kamera.position.set(0, 1.7, 13);
    kamera.lookAt(0, 0.9, 0);

    const cahaya = new THREE.DirectionalLight(0xfff0d8, 1.5);
    cahaya.position.set(3.5, 6, 5);
    adegan.add(cahaya, new THREE.HemisphereLight(0xdff0ff, 0xffd9a8, 0.55));

    // ---------- bahan dan bentuk ----------
    const bahan = (warna, o = {}) => new THREE.MeshPhysicalMaterial({ color: warna, roughness: 0.4, metalness: 0, clearcoat: 0.85, clearcoatRoughness: 0.22, sheen: 0.5, sheenRoughness: 0.5, sheenColor: new THREE.Color(0xffffff), ...o });
    const geoBola = new THREE.SphereGeometry(1, 56, 40);

    const bola = (r, mat, sx = 1, sy = 1, sz = 1) => {
        const m = new THREE.Mesh(geoBola, mat);
        m.scale.set(r * sx, r * sy, r * sz);

        return m;
    };

    const putih = bahan(0xfff6e8, { roughness: 0.46 });
    const krem = bahan(0xffe9cf, { roughness: 0.5 });
    const merah = bahan(0xff4b5c, { roughness: 0.3, clearcoat: 1 });
    const jingga = bahan(0xffa726, { roughness: 0.32, clearcoat: 1 });
    const jinggaTua = bahan(0xf08a12, { roughness: 0.34 });
    const pipi = bahan(0xff9db1, { roughness: 0.7, clearcoat: 0.1 });
    const hitam = bahan(0x14110f, { roughness: 0.05, clearcoat: 1, clearcoatRoughness: 0.02 });
    const kilau = new THREE.MeshBasicMaterial({ color: 0xffffff });

    // ---------- ayam ----------
    const ayam = new THREE.Group();
    const lompat = new THREE.Group(); // dipakai untuk lompatan dan gepeng
    ayam.add(lompat);

    const tubuh = bola(0.98, putih, 1.08, 0.97, 1.06);
    tubuh.position.set(0, 0, -0.1);
    lompat.add(tubuh);

    const perut = bola(0.7, krem, 1, 0.9, 0.7);
    perut.position.set(0, -0.18, 0.55);
    lompat.add(perut);

    // ekor
    for (const [x, rz, s] of [[-0.28, 0.5, 0.9], [0, 0, 1.08], [0.28, -0.5, 0.9]]) {
        const e = bola(0.34 * s, putih, 0.8, 1.35, 0.5);
        e.position.set(x, 0.38, -1.12);
        e.rotation.z = rz;
        e.rotation.x = -0.35;
        lompat.add(e);
    }

    // sayap
    const sayapKiri = new THREE.Group();
    const sayapKanan = new THREE.Group();
    sayapKiri.position.set(-1.0, 0.12, -0.05);
    sayapKanan.position.set(1.0, 0.12, -0.05);
    for (const [grup, arah] of [[sayapKiri, -1], [sayapKanan, 1]]) {
        const s = bola(0.52, krem, 0.3, 0.62, 0.78);
        s.position.set(arah * 0.08, -0.32, 0);
        grup.add(s);
        grup.rotation.z = arah * -0.12;
        lompat.add(grup);
    }

    // kaki
    for (const x of [-0.38, 0.38]) {
        const betis = new THREE.Mesh(new THREE.CylinderGeometry(0.075, 0.09, 0.42, 20), jinggaTua);
        betis.position.set(x, -1.02, 0.1);
        const telapak = bola(0.22, jingga, 1, 0.34, 1.35);
        telapak.position.set(x, -1.2, 0.22);
        lompat.add(betis, telapak);
    }

    // kepala
    const kepala = new THREE.Group();
    kepala.position.set(0, 1.12, 0.22);
    lompat.add(kepala);

    kepala.add(bola(0.86, putih));

    for (const [x, y, r] of [[-0.32, 0.82, 0.2], [0, 0.9, 0.27], [0.32, 0.8, 0.21]]) {
        const j = bola(r, merah, 1, 1.15, 0.8);
        j.position.set(x, y, 0.02);
        kepala.add(j);
    }

    const paruhAtas = bola(0.24, jingga, 0.95, 0.55, 1.25);
    paruhAtas.position.set(0, -0.08, 0.86);
    const paruhBawah = bola(0.17, jinggaTua, 0.85, 0.5, 1.05);
    paruhBawah.position.set(0, -0.25, 0.8);
    const gelambir = bola(0.13, merah, 0.8, 1.5, 0.7);
    gelambir.position.set(0, -0.5, 0.68);
    kepala.add(paruhAtas, paruhBawah, gelambir);

    for (const arah of [-1, 1]) {
        const p = bola(0.16, pipi, 0.8, 0.55, 0.35);
        p.position.set(arah * 0.56, -0.2, 0.56);
        p.rotation.y = arah * 0.8;
        kepala.add(p);
    }

    // mata: tiap mata di engsel sendiri di pusat kepala agar bisa meluncur di permukaan kepala
    const mata = [];
    for (const arah of [-1, 1]) {
        const engsel = new THREE.Group();
        engsel.position.set(0, 0.04, 0);
        engsel.rotation.y = arah * 0.42;
        const m = new THREE.Group();
        m.position.set(0, 0.1, 0.835);
        const gelap = bola(0.17, hitam, 0.8, 1.25, 0.55);
        const k1 = bola(0.05, kilau);
        k1.position.set(0.04, 0.085, 0.07);
        const k2 = bola(0.02, kilau);
        k2.position.set(-0.04, -0.05, 0.06);
        m.add(gelap, k1, k2);
        engsel.add(m);
        kepala.add(engsel);
        mata.push({ engsel, m, dasar: arah * 0.42 });
    }

    // bayangan lembut di tanah
    const kanvasBayang = document.createElement('canvas');
    kanvasBayang.width = kanvasBayang.height = 128;
    const cb = kanvasBayang.getContext('2d');
    const gr = cb.createRadialGradient(64, 64, 4, 64, 64, 62);
    gr.addColorStop(0, 'rgba(20,40,30,0.5)');
    gr.addColorStop(1, 'rgba(20,40,30,0)');
    cb.fillStyle = gr;
    cb.fillRect(0, 0, 128, 128);
    const bayangan = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), new THREE.MeshBasicMaterial({ map: new THREE.CanvasTexture(kanvasBayang), transparent: true, depthWrite: false }));
    bayangan.rotation.x = -Math.PI / 2;
    bayangan.position.y = -1.32;
    bayangan.scale.set(3.6, 2.2, 1);
    adegan.add(bayangan);

    adegan.add(ayam);

    // ---------- benda melayang bergaya mainan ----------
    const kaca = new THREE.MeshPhysicalMaterial({ color: 0xffffff, transparent: true, opacity: 0.28, roughness: 0.04, metalness: 0, clearcoat: 1, clearcoatRoughness: 0, envMapIntensity: 2.4, side: THREE.DoubleSide, depthWrite: false });
    const emas = bahan(0xf2b632, { roughness: 0.22, metalness: 0.35 });
    const hijau = bahan(0x58c28a, { roughness: 0.3 });
    const hijauTua = bahan(0x2f8f77, { roughness: 0.3 });

    const benda = [];
    const tambah = (mesh, x, y, z, dalam, kecepatan, putar = 0.4) => {
        mesh.position.set(x, y, z);
        adegan.add(mesh);
        benda.push({ mesh, x, y, z, dalam, kecepatan, putar, fase: Math.random() * 6.28 });
    };

    tambah(bola(0.6, emas, 0.88, 1.2, 0.88), 3.2, 2.4, -1.0, 0.5, 0.9);
    tambah(bola(0.62, kaca), 2.2, 3.0, -0.4, 0.8, 0.7);
    tambah(new THREE.Mesh(new THREE.TorusGeometry(0.46, 0.2, 28, 56), hijauTua), 3.0, 0.1, 0.5, 0.9, 0.8, 0.7);
    tambah(bola(0.5, krem, 0.88, 1.2, 0.88), -2.7, -0.4, 0.8, 0.8, 1.1);
    tambah(bola(0.42, hijau), 0.2, 3.1, -1.4, 0.4, 0.6);
    tambah(new THREE.Mesh(new THREE.CapsuleGeometry(0.2, 0.42, 12, 24), jingga), -2.0, 2.4, -0.7, 0.6, 1.0, 0.9);
    tambah(bola(0.34, kaca), 3.7, 1.2, -1.6, 0.5, 0.8);

    // ---------- ukuran dan penempatan ----------
    let lebar = 1;
    let tinggi = 1;

    function ukur() {
        const r = wadah.getBoundingClientRect();
        lebar = Math.max(2, r.width);
        tinggi = Math.max(2, r.height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.setSize(lebar, tinggi, false);
        kamera.aspect = lebar / tinggi;
        // tinggi pandang dijaga tetap; bila wadah sempit, mundur sedikit agar benda tidak terpotong
        const perlu = 6.5 * Math.max(1, 1.18 / kamera.aspect);
        const jarak = Math.min(24, (perlu / 2) / Math.tan((kamera.fov * Math.PI) / 360));
        kamera.position.set(0, 1.7, jarak);
        kamera.lookAt(0, 1.0, 0);
        kamera.updateProjectionMatrix();
        // geser ayam ke kanan agar menjauh dari kandang di kiri
        const geser = klem(0.95 * kamera.aspect, 0.3, 2.3);
        ayam.position.x = geser;
        bayangan.position.x = geser;
        for (const b of benda) b.mesh.position.x = b.x + geser;
    }

    new ResizeObserver(ukur).observe(wadah);
    ukur();

    // ---------- masukan ----------
    const ptr = { x: 0, y: 0, ada: false, terakhir: -99 };
    const fokus = { mode: 'bebas' }; // bebas | email | sandi
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

    const lompatState = { mulai: -1 };
    function colek() {
        if (lompatState.mulai < 0 || performance.now() / 1000 - lompatState.mulai > 0.9) {
            lompatState.mulai = performance.now() / 1000;
            if (gerakMinimal) gambar();
        }
    }
    tombol.addEventListener('click', colek);

    // ---------- animasi ----------
    const kini = { vx: 0, vy: 0, mataTutup: 0, tengok: 0 };
    const kepalaScreen = new THREE.Vector3();
    let kedip = { mulai: -1, berikut: 2.5 };

    function gambar(waktu = performance.now()) {
        const t = waktu / 1000;
        const dt = 1 / 60;

        // posisi kepala di layar -> arah kursor relatif terhadap kepala
        kepala.getWorldPosition(kepalaScreen);
        kepalaScreen.project(kamera);
        const rect = kanvas.getBoundingClientRect();
        const hx = rect.left + ((kepalaScreen.x + 1) / 2) * rect.width;
        const hy = rect.top + ((1 - kepalaScreen.y) / 2) * rect.height;

        let tx = 0;
        let ty = 0;
        if (fokus.mode === 'email') {
            tx = 1;
            ty = 0.25;
        } else if (fokus.mode === 'sandi') {
            tx = -0.7; // membuang muka dari kartu
            ty = 0.1;
        } else if (ptr.ada && t - ptr.terakhir < 4) {
            tx = klem((ptr.x - hx) / (window.innerWidth * 0.35), -1, 1);
            ty = klem((ptr.y - hy) / (window.innerHeight * 0.35), -1, 1);
        } else if (!gerakMinimal) {
            tx = Math.sin(t * 0.7) * 0.5; // melirik ke sekitar saat tak ada gerakan
            ty = Math.sin(t * 0.43 + 1) * 0.25;
        }

        const k = gerakMinimal ? 1 : 0.12;
        kini.vx = lerp(kini.vx, tx, k);
        kini.vy = lerp(kini.vy, ty, k);

        // mata
        const tutup = fokus.mode === 'sandi' ? 1 : 0;
        kini.mataTutup = lerp(kini.mataTutup, tutup, gerakMinimal ? 1 : 0.2);

        let kedipSkala = 1;
        if (!gerakMinimal && fokus.mode !== 'sandi') {
            if (kedip.mulai < 0 && t > kedip.berikut) kedip.mulai = t;
            if (kedip.mulai >= 0) {
                const p = (t - kedip.mulai) / 0.16;
                kedipSkala = 1 - Math.sin(Math.min(1, p) * Math.PI) * 0.92;
                if (p >= 1) {
                    kedip.mulai = -1;
                    kedip.berikut = t + 2 + Math.random() * 3.5;
                }
            }
        }
        const skalaMata = Math.min(kedipSkala, 1 - kini.mataTutup * 0.9);

        for (const e of mata) {
            e.engsel.rotation.y = e.dasar + kini.vx * 0.34;
            e.engsel.rotation.x = kini.vy * 0.26;
            e.m.scale.y = Math.max(0.08, skalaMata);
        }

        // kepala dan tubuh menoleh
        kepala.rotation.y = lerp(kepala.rotation.y, kini.vx * 0.5, gerakMinimal ? 1 : 0.14);
        kepala.rotation.x = lerp(kepala.rotation.x, kini.vy * 0.24, gerakMinimal ? 1 : 0.14);
        kepala.rotation.z = lerp(kepala.rotation.z, -kini.vx * 0.08, gerakMinimal ? 1 : 0.14);
        ayam.rotation.y = lerp(ayam.rotation.y, kini.vx * 0.22, gerakMinimal ? 1 : 0.1);

        // napas, lompat, gepeng-lonjong
        let napas = gerakMinimal ? 0 : Math.sin(t * 2.2);
        let skY = 1 + napas * 0.022;
        let skXZ = 1 - napas * 0.012;
        let naik = 0;
        let kepakan = gerakMinimal ? 0 : Math.max(0, Math.sin(t * 0.9)) ** 14 * 0.5;

        if (lompatState.mulai >= 0) {
            const p = (t - lompatState.mulai) / 0.85;
            if (p >= 1) {
                lompatState.mulai = -1;
            } else {
                naik = Math.sin(p * Math.PI) * 1.05;
                const pegas = p < 0.14 ? p / 0.14 : p > 0.86 ? (p - 0.86) / 0.14 : 0;
                const gepeng = p < 0.14 ? -Math.sin(pegas * Math.PI / 2) * 0.16 : p > 0.86 ? -Math.sin((1 - pegas) * Math.PI / 2) * 0.14 : Math.sin(p * Math.PI) * 0.1;
                skY = 1 + gepeng;
                skXZ = 1 - gepeng * 0.55;
                kepakan = 0.55 + Math.sin(t * 38) * 0.45;
                ayam.rotation.z = Math.sin(p * Math.PI * 2) * 0.05;
            }
        } else {
            ayam.rotation.z = lerp(ayam.rotation.z, 0, 0.2);
        }

        lompat.scale.set(skXZ, skY, skXZ);
        lompat.position.y = naik + (skY - 1) * -0.9;
        bayangan.scale.set(3.6 - naik * 0.8, 2.2 - naik * 0.5, 1);
        bayangan.material.opacity = 1 - naik * 0.35;
        sayapKiri.rotation.z = -0.12 - kepakan * 0.9;
        sayapKanan.rotation.z = 0.12 + kepakan * 0.9;

        // benda melayang
        for (const b of benda) {
            if (gerakMinimal) continue;
            b.mesh.position.y = b.y + Math.sin(t * b.kecepatan + b.fase) * 0.16;
            b.mesh.rotation.y = t * 0.3 * b.putar + b.fase;
            b.mesh.rotation.x = Math.sin(t * 0.5 + b.fase) * 0.3 * b.putar;
            // paralaks halus mengikuti kursor
            const dx = ptr.ada ? (ptr.x / window.innerWidth - 0.5) : 0;
            b.mesh.position.z = b.z + dx * b.dalam * 0.8;
        }

        // area klik mengikuti posisi ayam di layar
        const pusat = new THREE.Vector3(ayam.position.x, 0.55, 0).project(kamera);
        const cx = ((pusat.x + 1) / 2) * lebar;
        const cy = ((1 - pusat.y) / 2) * tinggi;
        const ukuran = Math.min(lebar * 0.46, tinggi * 0.62);
        tombol.style.width = tombol.style.height = `${ukuran}px`;
        tombol.style.left = `${cx - ukuran / 2}px`;
        tombol.style.top = `${cy - ukuran / 2}px`;

        renderer.render(adegan, kamera);
        void dt;
    }

    let hidup = true;
    new IntersectionObserver(([e]) => { hidup = e.isIntersecting; }).observe(wadah);

    if (gerakMinimal) {
        gambar();
        window.addEventListener('resize', () => gambar());
    } else {
        const putar = (w) => {
            requestAnimationFrame(putar);
            if (document.hidden || !hidup) return;
            gambar(w);
        };
        requestAnimationFrame(putar);
    }

    wadah.style.opacity = '1';
}
