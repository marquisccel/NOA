/*
 * Maskot ayam 3D untuk halaman masuk (three.js): ayam betina berbulu, dibangun seluruhnya lewat kode
 * (tanpa berkas model, tanpa unduhan tambahan). Ribuan helai bulu kecil berlapis (InstancedMesh) menutupi
 * badan, leher, dan paha supaya tampak berbulu, bukan mainan plastik.
 *
 * Perilaku:
 *  - Kepala menoleh dan kedua bola mata mengikuti kursor.
 *  - Bernapas, berkedip, mematuk sesekali, mengibas sayap; bila kursor diam, melirik ke sekitar.
 *  - Mengisi email: menoleh ke kartu masuk. Mengisi kata sandi: memejam dan membuang muka.
 *  - Dicolek: mengepak sayap dan melompat kecil.
 */
import * as THREE from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';

const klem = (v, a, b) => Math.min(b, Math.max(a, v));
const lerp = (a, b, k) => a + (b - a) * k;

function pengacak(benih) {
    let a = benih;

    return () => {
        a = (a + 0x6d2b79f5) | 0;
        let t = Math.imul(a ^ (a >>> 15), 1 | a);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;

        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** Menutup permukaan elipsoid dengan bulu-bulu pipih yang saling menumpuk, mengarah menurut `arah`. */
function selimut({ pusat, radii, jumlah, saring = () => true, arah, warna, l, w, t, bahan, rnd }) {
    const mesh = new THREE.InstancedMesh(new THREE.SphereGeometry(1, 9, 6), bahan, jumlah);
    const M = new THREE.Matrix4();
    const Q = new THREE.Quaternion();
    const S = new THREE.Vector3();
    const P = new THREE.Vector3();
    const u = new THREE.Vector3();
    const nrm = new THREE.Vector3();
    const d = new THREE.Vector3();
    const xa = new THREE.Vector3();
    const ya = new THREE.Vector3();
    const za = new THREE.Vector3();
    const c = new THREE.Color();
    const total = jumlah;
    const emas = Math.PI * (3 - Math.sqrt(5));
    let n = 0;

    for (let k = 0; k < total; k++) {
        const y = 1 - ((k + 0.5) / total) * 2;
        const r = Math.sqrt(1 - y * y);
        const th = emas * k;
        u.set(Math.cos(th) * r, y, Math.sin(th) * r);

        if (!saring(u, rnd)) continue;

        P.set(pusat.x + u.x * radii.x, pusat.y + u.y * radii.y, pusat.z + u.z * radii.z);
        nrm.set(u.x / radii.x, u.y / radii.y, u.z / radii.z).normalize();
        arah(u, d);
        d.addScaledVector(nrm, -d.dot(nrm));
        if (d.lengthSq() < 1e-4) d.set(0, -1, 0).addScaledVector(nrm, nrm.y);
        d.normalize().applyAxisAngle(nrm, (rnd() - 0.5) * 0.7);

        za.copy(d).addScaledVector(nrm, 0.14 + rnd() * 0.14).normalize();
        xa.crossVectors(nrm, za).normalize();
        ya.crossVectors(za, xa).normalize();
        M.makeBasis(xa, ya, za);
        Q.setFromRotationMatrix(M);

        const panjang = l * (0.75 + rnd() * 0.6);
        P.addScaledVector(za, panjang * 0.42).addScaledVector(nrm, t * 0.8);
        S.set(w * (0.85 + rnd() * 0.35), t, panjang);
        M.compose(P, Q, S);
        mesh.setMatrixAt(n, M);
        warna(u, rnd, c);
        mesh.setColorAt(n, c);
        n++;
    }

    mesh.count = n;
    mesh.instanceMatrix.needsUpdate = true;
    if (mesh.instanceColor) mesh.instanceColor.needsUpdate = true;

    return mesh;
}

export function mulaiAyam(wadah, { gerakMinimal = false } = {}) {
    const kanvas = wadah.querySelector('canvas');
    const tombol = wadah.querySelector('[data-colek]');

    const renderer = new THREE.WebGLRenderer({ canvas: kanvas, antialias: true, alpha: true, powerPreference: 'default' });
    renderer.setClearColor(0x000000, 0);
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 0.82;

    const adegan = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);
    adegan.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    adegan.environmentIntensity = 0.6;

    const kamera = new THREE.PerspectiveCamera(26, 1, 0.1, 80);

    const kunci = new THREE.DirectionalLight(0xfff1dc, 1.7);
    kunci.position.set(-3.5, 6, 6);
    const tepi = new THREE.DirectionalLight(0xcfe8ff, 1.5);
    tepi.position.set(5, 3.5, -5);
    adegan.add(kunci, tepi, new THREE.HemisphereLight(0xe8f4ff, 0xffd9a8, 0.45));

    const rnd = pengacak(20261119);
    const cA = new THREE.Color();
    const cB = new THREE.Color();

    // ---------- bahan ----------
    const bahanBulu = new THREE.MeshPhysicalMaterial({ color: 0xffffff, roughness: 0.62, metalness: 0, sheen: 0.5, sheenRoughness: 0.4, sheenColor: new THREE.Color(0xb4621f), clearcoat: 0.08, clearcoatRoughness: 0.6 });
    const bahanInti = new THREE.MeshStandardMaterial({ color: 0x6a2610, roughness: 0.85 });
    const bahanKulit = new THREE.MeshPhysicalMaterial({ color: 0xd24a3b, roughness: 0.5, clearcoat: 0.5, clearcoatRoughness: 0.3 });
    const bahanJengger = new THREE.MeshPhysicalMaterial({ color: 0xd1202c, roughness: 0.35, clearcoat: 1, clearcoatRoughness: 0.15 });
    const bahanParuh = new THREE.MeshPhysicalMaterial({ color: 0xe9b94c, roughness: 0.4, clearcoat: 0.6 });
    const bahanParuhBawah = new THREE.MeshPhysicalMaterial({ color: 0xd7a23a, roughness: 0.45, clearcoat: 0.5 });
    const bahanKaki = new THREE.MeshPhysicalMaterial({ color: 0xe3b53c, roughness: 0.5, clearcoat: 0.35 });
    const bahanCakar = new THREE.MeshStandardMaterial({ color: 0xd8c8a6, roughness: 0.5 });
    const bahanIris = new THREE.MeshPhysicalMaterial({ color: 0xe3871c, roughness: 0.15, clearcoat: 1, clearcoatRoughness: 0.03 });
    const bahanPupil = new THREE.MeshPhysicalMaterial({ color: 0x050403, roughness: 0.05, clearcoat: 1, clearcoatRoughness: 0.02 });
    const bahanKelopak = new THREE.MeshStandardMaterial({ color: 0x3b1a12, roughness: 0.7 });
    const kilau = new THREE.MeshBasicMaterial({ color: 0xffffff });
    const bahanSayap = [0x7d2e13, 0x652410, 0x4c1a0b].map((c) => new THREE.MeshPhysicalMaterial({ color: c, roughness: 0.55, sheen: 1, sheenRoughness: 0.4, sheenColor: new THREE.Color(0xff9a58), clearcoat: 0.2 }));
    const bahanEkor = new THREE.MeshPhysicalMaterial({ color: 0x142019, roughness: 0.32, metalness: 0.1, iridescence: 1, iridescenceIOR: 1.7, iridescenceThicknessRange: [150, 420], clearcoat: 0.6 });

    const geoBola = new THREE.SphereGeometry(1, 40, 28);
    const bola = (r, mat, sx = 1, sy = 1, sz = 1) => {
        const m = new THREE.Mesh(geoBola, mat);
        m.scale.set(r * sx, r * sy, r * sz);

        return m;
    };

    // ---------- warna bulu ----------
    const warnaBadan = (u, r, c) => {
        const dorsal = klem(u.y * 1.3, 0, 1);
        const dada = klem(u.z * 0.9 - u.y * 0.35, 0, 1);
        c.set(0x65200d).lerp(cB.set(0x9c4519), 0.25 + r() * 0.6);
        c.lerp(cB.set(0xbf7430), dada * 0.55 * (0.4 + r() * 0.6));
        c.lerp(cB.set(0x3a1509), dorsal * 0.5);
    };
    const warnaLeher = (u, r, c) => {
        c.set(0x93480f).lerp(cB.set(0xcf9138), 0.2 + r() * 0.7);
        c.lerp(cB.set(0x6a2c0f), klem(-u.y, 0, 1) * 0.25);
    };
    const warnaPaha = (u, r, c) => {
        c.set(0x70270e).lerp(cB.set(0x9c4519), r() * 0.7);
    };

    // ---------- ayam ----------
    const ayam = new THREE.Group();
    ayam.rotation.y = 0.42;
    adegan.add(ayam);

    const badan = new THREE.Group();
    ayam.add(badan);

    const pusatBadan = new THREE.Vector3(0, 0, -0.05);
    const radiiBadan = new THREE.Vector3(0.7, 0.72, 1.0);
    const inti = bola(1, bahanInti);
    inti.position.copy(pusatBadan);
    inti.scale.copy(radiiBadan).multiplyScalar(0.97);
    badan.add(inti);
    badan.add(selimut({
        pusat: pusatBadan, radii: radiiBadan, jumlah: 1500, rnd, bahan: bahanBulu,
        saring: (u) => !(u.z > 0.72 && u.y > 0.35) && !(u.y < -0.72),
        arah: (u, d) => d.set(0, -0.5, -0.9),
        warna: warnaBadan, l: 0.34, w: 0.13, t: 0.035,
    }));

    // ekor: kipas bulu panjang berkilau
    const ekor = new THREE.Group();
    ekor.position.set(0, 0.28, -0.98);
    ekor.rotation.x = 0.78;
    for (let i = -4; i <= 4; i++) {
        const sudut = new THREE.Group();
        sudut.rotation.y = i * 0.17;
        sudut.rotation.z = i * 0.045;
        const helai = new THREE.Mesh(geoBola, bahanEkor);
        const panjang = 0.98 - Math.abs(i) * 0.075;
        helai.scale.set(0.15, 0.022, panjang);
        helai.position.set(0, i % 2 ? 0.008 : 0, -panjang * 0.9);
        sudut.add(helai);
        ekor.add(sudut);
    }
    badan.add(ekor);

    // sayap terlipat dengan tiga lapis bulu
    const sayap = [];
    for (const sisi of [-1, 1]) {
        const pangkal = new THREE.Group();
        pangkal.position.set(sisi * 0.78, 0.3, 0.12);
        for (let baris = 0; baris < 3; baris++) {
            const jumlah = [7, 6, 5][baris];
            for (let i = 0; i < jumlah; i++) {
                const helai = new THREE.Mesh(geoBola, bahanSayap[baris]);
                const panjang = [0.7, 0.88, 1.0][baris] - i * 0.012;
                helai.scale.set(0.15, 0.03, panjang * 0.5);
                helai.position.set(sisi * (0.04 + baris * 0.022), -0.04 - baris * 0.2 - i * 0.012, -0.05 - i * 0.14 - baris * 0.1);
                helai.rotation.set(-0.2 + baris * 0.04, sisi * 0.06, sisi * (0.1 + baris * 0.04));
                pangkal.add(helai);
            }
        }
        badan.add(pangkal);
        sayap.push({ pangkal, sisi });
    }

    // kaki: paha berbulu, betis bersisik kuning, jari dan cakar
    for (const x of [-0.38, 0.38]) {
        const pusatPaha = new THREE.Vector3(x, -0.58, 0.1);
        const radiiPaha = new THREE.Vector3(0.28, 0.36, 0.34);
        const paha = bola(1, bahanInti);
        paha.position.copy(pusatPaha);
        paha.scale.copy(radiiPaha).multiplyScalar(0.96);
        ayam.add(paha);
        ayam.add(selimut({ pusat: pusatPaha, radii: radiiPaha, jumlah: 220, rnd, bahan: bahanBulu, saring: (u) => u.y < 0.5, arah: (u, d) => d.set(0, -1, -0.15), warna: warnaPaha, l: 0.26, w: 0.11, t: 0.03 }));

        const betis = new THREE.Mesh(new THREE.CylinderGeometry(0.046, 0.04, 0.8, 18), bahanKaki);
        betis.position.set(x, -1.16, 0.12);
        ayam.add(betis);
        for (let r = 0; r < 6; r++) {
            const cincin = new THREE.Mesh(new THREE.TorusGeometry(0.044 - r * 0.0006, 0.007, 8, 18), bahanKaki);
            cincin.rotation.x = Math.PI / 2;
            cincin.position.set(x, -0.9 - r * 0.115, 0.12);
            ayam.add(cincin);
        }
        for (const sudut of [-0.55, 0, 0.55]) {
            const jari = new THREE.Group();
            jari.position.set(x, -1.52, 0.12);
            jari.rotation.y = sudut;
            const ruas = new THREE.Mesh(new THREE.CapsuleGeometry(0.034, 0.3, 6, 12), bahanKaki);
            ruas.rotation.x = Math.PI / 2;
            ruas.position.set(0, 0, 0.17);
            const cakar = new THREE.Mesh(new THREE.ConeGeometry(0.03, 0.1, 10), bahanCakar);
            cakar.rotation.x = Math.PI / 2;
            cakar.position.set(0, -0.005, 0.38);
            jari.add(ruas, cakar);
            ayam.add(jari);
        }
        const belakang = new THREE.Mesh(new THREE.CapsuleGeometry(0.03, 0.12, 6, 12), bahanKaki);
        belakang.rotation.x = Math.PI / 2;
        belakang.position.set(x, -1.52, -0.08);
        ayam.add(belakang);
    }

    // leher dan kepala (satu rantai agar menoleh serempak, tetapi kepala lebih lincah)
    const engselLeher = new THREE.Vector3(0, 0.6, 0.8);
    const leher = new THREE.Group();
    leher.position.copy(engselLeher);
    ayam.add(leher);

    const leherBawah = { pusat: new THREE.Vector3(0, 0.82, 0.86), radii: new THREE.Vector3(0.34, 0.4, 0.36) };
    const lb = bola(1, bahanInti);
    lb.position.copy(leherBawah.pusat);
    lb.scale.copy(leherBawah.radii).multiplyScalar(0.96);
    ayam.add(lb);
    ayam.add(selimut({ pusat: leherBawah.pusat, radii: leherBawah.radii, jumlah: 380, rnd, bahan: bahanBulu, arah: (u, d) => d.set(0, -1, -0.3), warna: warnaLeher, l: 0.3, w: 0.11, t: 0.03 }));

    const bagianLeher = [
        { p: new THREE.Vector3(0, 1.18, 1.0), r: new THREE.Vector3(0.26, 0.38, 0.28), n: 300 },
        { p: new THREE.Vector3(0, 1.5, 1.1), r: new THREE.Vector3(0.2, 0.32, 0.22), n: 240 },
    ];
    for (const b of bagianLeher) {
        const rel = b.p.clone().sub(engselLeher);
        const ci = bola(1, bahanInti);
        ci.position.copy(rel);
        ci.scale.copy(b.r).multiplyScalar(0.95);
        leher.add(ci);
        leher.add(selimut({ pusat: rel, radii: b.r, jumlah: b.n, rnd, bahan: bahanBulu, arah: (u, d) => d.set(0, -1, -0.25), warna: warnaLeher, l: 0.26, w: 0.1, t: 0.028 }));
    }

    const kepala = new THREE.Group();
    const pusatKepala = new THREE.Vector3(0, 1.9, 1.2);
    kepala.position.copy(pusatKepala).sub(engselLeher);
    leher.add(kepala);

    kepala.scale.setScalar(1.45);
    kepala.add(bola(1, bahanKulit, 0.22, 0.25, 0.29));

    // jengger bergerigi
    [[-0.16, 0.2, 0.06], [-0.08, 0.27, 0.085], [0, 0.31, 0.1], [0.08, 0.28, 0.09], [0.15, 0.22, 0.07]].forEach(([z, y, r]) => {
        const j = bola(r, bahanJengger, 0.34, 1.25, 0.85);
        j.position.set(0, y, z);
        kepala.add(j);
    });

    const paruhAtas = new THREE.Mesh(new THREE.ConeGeometry(0.075, 0.3, 18), bahanParuh);
    paruhAtas.rotation.x = Math.PI / 2 + 0.08;
    paruhAtas.scale.x = 0.9;
    paruhAtas.position.set(0, -0.04, 0.45);
    const paruhBawah = new THREE.Mesh(new THREE.ConeGeometry(0.052, 0.22, 16), bahanParuhBawah);
    paruhBawah.rotation.x = Math.PI / 2 - 0.1;
    paruhBawah.position.set(0, -0.1, 0.4);
    kepala.add(paruhAtas, paruhBawah);

    for (const sisi of [-1, 1]) {
        const g = bola(1, bahanJengger, 0.032, 0.09, 0.04);
        g.position.set(sisi * 0.04, -0.27, 0.3);
        const telinga = bola(1, new THREE.MeshStandardMaterial({ color: 0xeedbc7, roughness: 0.5 }), 0.02, 0.06, 0.05);
        telinga.position.set(sisi * 0.205, -0.03, -0.03);
        kepala.add(g, telinga);
    }

    // mata: iris jingga, pupil hitam yang bergeser mengikuti kursor, kilau cahaya, dan kelopak
    const mata = [];
    for (const sisi of [-1, 1]) {
        const grup = new THREE.Group();
        grup.position.set(sisi * 0.185, 0.07, 0.12);
        grup.rotation.y = sisi * 1.0;
        grup.add(bola(0.062, bahanKelopak, 1, 1, 0.7));
        const pivot = new THREE.Group();
        const iris = bola(0.05, bahanIris, 1, 1, 0.75);
        iris.position.z = 0.012;
        const pupil = bola(0.026, bahanPupil, 1, 1, 0.5);
        pupil.position.z = 0.04;
        const k1 = bola(0.011, kilau);
        k1.position.set(0.016, 0.018, 0.052);
        pivot.add(iris, pupil, k1);
        grup.add(pivot);
        kepala.add(grup);
        mata.push({ grup, pivot });
    }

    // bayangan lembut di tanah
    const cb = document.createElement('canvas');
    cb.width = cb.height = 128;
    const cx = cb.getContext('2d');
    const gr = cx.createRadialGradient(64, 64, 4, 64, 64, 62);
    gr.addColorStop(0, 'rgba(20,40,30,0.55)');
    gr.addColorStop(1, 'rgba(20,40,30,0)');
    cx.fillStyle = gr;
    cx.fillRect(0, 0, 128, 128);
    const bayangan = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), new THREE.MeshBasicMaterial({ map: new THREE.CanvasTexture(cb), transparent: true, depthWrite: false }));
    bayangan.rotation.x = -Math.PI / 2;
    bayangan.position.y = -1.56;
    bayangan.scale.set(3.8, 2.6, 1);
    adegan.add(bayangan);

    // ---------- ukuran ----------
    let lebar = 1;
    let tinggi = 1;

    function ukur() {
        const r = wadah.getBoundingClientRect();
        lebar = Math.max(2, r.width);
        tinggi = Math.max(2, r.height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.setSize(lebar, tinggi, false);
        kamera.aspect = lebar / tinggi;
        const perlu = 5.5 * Math.max(1, 1.0 / kamera.aspect);
        const jarak = Math.min(30, perlu / 2 / Math.tan((kamera.fov * Math.PI) / 360));
        kamera.position.set(-0.2, 0.55, jarak);
        kamera.lookAt(-0.2, 0.5, 0);
        kamera.updateProjectionMatrix();
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

    const aksi = { kepak: -9, patuk: -9, patukBerikut: 4, kibas: -9, kibasBerikut: 9 };
    tombol.addEventListener('click', () => {
        aksi.kepak = performance.now() / 1000;
        if (gerakMinimal) gambar();
    });

    // ---------- animasi ----------
    const pandang = { yaw: 0, pitch: 0 };
    const kini = { mataTutup: 0, kedipMulai: -1, kedipBerikut: 2.2 };
    const T = new THREE.Vector3();
    const H = new THREE.Vector3();
    const arahKamera = new THREE.Vector3();
    const kopi = new THREE.Vector3();

    function targetDunia(t) {
        if (fokus.mode === 'email') {
            return T.set(3.5, 1.7, 3);
        }
        if (fokus.mode === 'sandi') {
            return T.set(-3.5, 1.2, 1.5);
        }

        let nx;
        let ny;
        if (ptr.ada && t - ptr.terakhir < 5) {
            const r = kanvas.getBoundingClientRect();
            nx = ((ptr.x - r.left) / r.width) * 2 - 1;
            ny = -(((ptr.y - r.top) / r.height) * 2 - 1);
        } else if (!gerakMinimal) {
            nx = Math.sin(t * 0.6) * 0.5;
            ny = 0.25 + Math.sin(t * 0.37 + 1) * 0.2;
        } else {
            nx = 0;
            ny = 0.2;
        }

        arahKamera.set(nx, ny, 0.5).unproject(kamera).sub(kamera.position).normalize();
        const jarak = (3 - kamera.position.z) / arahKamera.z;

        return T.copy(kamera.position).addScaledVector(arahKamera, Math.abs(jarak));
    }

    function gambar(waktu = performance.now()) {
        const t = waktu / 1000;
        const cepat = gerakMinimal ? 1 : 0.12;

        ayam.updateMatrixWorld(true);
        kepala.getWorldPosition(H);
        const sasaran = targetDunia(t);
        const dx = sasaran.x - H.x;
        const dy = sasaran.y - H.y;
        const dz = sasaran.z - H.z;
        const yawDunia = Math.atan2(dx, dz);
        const yawLokal = klem(yawDunia - ayam.rotation.y, -1.0, 1.0);
        const pitchTarget = klem(Math.atan2(dy, Math.hypot(dx, dz)), -0.5, 0.6);

        pandang.yaw = lerp(pandang.yaw, yawLokal, cepat);
        pandang.pitch = lerp(pandang.pitch, pitchTarget, cepat);

        // mematuk sesekali
        let tunduk = 0;
        if (!gerakMinimal && fokus.mode === 'bebas') {
            if (aksi.patuk < 0 && t > aksi.patukBerikut) aksi.patuk = t;
            if (aksi.patuk >= 0) {
                const p = (t - aksi.patuk) / 1.1;
                if (p >= 1) {
                    aksi.patuk = -9;
                    aksi.patukBerikut = t + 5 + Math.random() * 5;
                } else {
                    tunduk = Math.max(0, Math.sin(p * Math.PI * 3)) * Math.sin(p * Math.PI) * 0.75;
                }
            }
        }

        leher.rotation.order = 'YXZ';
        kepala.rotation.order = 'YXZ';
        leher.rotation.y = pandang.yaw * 0.4;
        leher.rotation.x = -pandang.pitch * 0.25 + tunduk * 0.45;
        kepala.rotation.y = pandang.yaw * 0.6;
        kepala.rotation.x = -pandang.pitch * 0.5 + tunduk * 0.6;
        kepala.rotation.z = -pandang.yaw * 0.07;

        // mata mengikuti kursor (lebih jauh daripada kepala)
        ayam.updateMatrixWorld(true);
        const tutup = fokus.mode === 'sandi' ? 1 : 0;
        kini.mataTutup = lerp(kini.mataTutup, tutup, gerakMinimal ? 1 : 0.2);
        let kedip = 1;
        if (!gerakMinimal && fokus.mode !== 'sandi') {
            if (kini.kedipMulai < 0 && t > kini.kedipBerikut) kini.kedipMulai = t;
            if (kini.kedipMulai >= 0) {
                const p = (t - kini.kedipMulai) / 0.17;
                kedip = 1 - Math.sin(Math.min(1, p) * Math.PI) * 0.94;
                if (p >= 1) {
                    kini.kedipMulai = -1;
                    kini.kedipBerikut = t + 2 + Math.random() * 3.5;
                }
            }
        }
        for (const m of mata) {
            kopi.copy(sasaran);
            m.grup.worldToLocal(kopi);
            const yawM = klem(Math.atan2(kopi.x, kopi.z), -0.62, 0.62);
            const pitchM = klem(Math.atan2(kopi.y, Math.hypot(kopi.x, kopi.z)), -0.5, 0.5);
            m.pivot.rotation.y = lerp(m.pivot.rotation.y, yawM, gerakMinimal ? 1 : 0.3);
            m.pivot.rotation.x = lerp(m.pivot.rotation.x, -pitchM, gerakMinimal ? 1 : 0.3);
            m.grup.scale.y = Math.max(0.07, Math.min(kedip, 1 - kini.mataTutup * 0.93));
        }

        // napas, goyang, kepak, lompat
        const napas = gerakMinimal ? 0 : Math.sin(t * 2.1);
        badan.scale.set(1 - napas * 0.008, 1 + napas * 0.014, 1 + napas * 0.006);
        ayam.position.y = 0;
        let kepak = 0;
        let lompat = 0;

        if (!gerakMinimal && aksi.kibas < 0 && t > aksi.kibasBerikut) aksi.kibas = t;
        if (aksi.kibas >= 0) {
            const p = (t - aksi.kibas) / 0.9;
            if (p >= 1) {
                aksi.kibas = -9;
                aksi.kibasBerikut = t + 8 + Math.random() * 6;
            } else {
                kepak = Math.sin(p * Math.PI * 4) * Math.sin(p * Math.PI) * 0.22;
            }
        }
        if (aksi.kepak >= 0) {
            const p = (t - aksi.kepak) / 1.1;
            if (p >= 1) {
                aksi.kepak = -9;
            } else {
                const env = Math.sin(p * Math.PI);
                kepak = (0.55 + Math.sin(t * 34) * 0.4) * env * 1.05;
                lompat = Math.sin(Math.min(1, p * 1.6) * Math.PI) * 0.42;
            }
        }
        ayam.position.y = lompat;
        bayangan.scale.set(3.8 - lompat * 1.5, 2.6 - lompat, 1);
        bayangan.material.opacity = 1 - lompat * 0.6;
        for (const s of sayap) {
            s.pangkal.rotation.z = s.sisi * (kepak * 0.9);
            s.pangkal.rotation.x = -Math.abs(kepak) * 0.12;
        }
        ekor.rotation.x = 0.78 + Math.sin(t * 1.3) * 0.02 + (aksi.kepak >= 0 ? 0.1 : 0);

        // area klik mengikuti posisi ayam di layar
        const pusat = new THREE.Vector3(0, 0.4, 0).project(kamera);
        const cxp = ((pusat.x + 1) / 2) * lebar;
        const cyp = ((1 - pusat.y) / 2) * tinggi;
        const ukuranBtn = Math.min(lebar * 0.55, tinggi * 0.78);
        tombol.style.width = tombol.style.height = `${ukuranBtn}px`;
        tombol.style.left = `${cxp - ukuranBtn / 2}px`;
        tombol.style.top = `${cyp - ukuranBtn / 2}px`;

        renderer.render(adegan, kamera);
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
