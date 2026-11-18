/*
 * Maskot ayam 3D untuk halaman masuk (three.js): ayam betina berbulu, dibangun seluruhnya lewat kode
 * (tanpa berkas model, tanpa unduhan tambahan). Kelopak bulu besar berlapis (InstancedMesh) menutupi
 * badan dan leher dengan gaya mainan tanah liat yang lembut dan cerah.
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
    // Penyaji warna netral menjaga warna tetap cerah dan jenuh seperti gambar mainan tanah liat.
    renderer.toneMapping = THREE.NeutralToneMapping;
    renderer.toneMappingExposure = 1.0;

    const adegan = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);
    adegan.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    adegan.environmentIntensity = 0.85;

    const kamera = new THREE.PerspectiveCamera(26, 1, 0.1, 80);

    const kunci = new THREE.DirectionalLight(0xfff3e2, 1.6);
    kunci.position.set(-3.5, 6, 6);
    const tepi = new THREE.DirectionalLight(0xffe2c4, 0.9);
    tepi.position.set(5, 3.5, -5);
    adegan.add(kunci, tepi, new THREE.HemisphereLight(0xfff6ea, 0xffcfa0, 0.55));

    const rnd = pengacak(20261119);
    const cB = new THREE.Color();

    // ---------- bahan: lembut dan matte seperti tanah liat ----------
    const mat = (warna, o = {}) => new THREE.MeshPhysicalMaterial({ color: warna, roughness: 0.58, metalness: 0, clearcoat: 0.12, clearcoatRoughness: 0.5, ...o });
    const bahanBulu = mat(0xffffff, { roughness: 0.5, clearcoat: 0.18, clearcoatRoughness: 0.4 });
    const bahanInti = mat(0xc6432a);
    const bahanKulit = mat(0xf2582b, { roughness: 0.55, clearcoat: 0.2 });
    const bahanJengger = mat(0xe5252b, { roughness: 0.3, clearcoat: 0.9, clearcoatRoughness: 0.15 });
    const bahanParuh = mat(0xf8b93a, { roughness: 0.42, clearcoat: 0.3 });
    const bahanKaki = mat(0xf6a623, { roughness: 0.5, clearcoat: 0.2 });
    const bahanMata = mat(0x050403, { roughness: 0.04, clearcoat: 1, clearcoatRoughness: 0.02 });
    const kilau = new THREE.MeshBasicMaterial({ color: 0xffffff });
    const bahanSayap = [0xe9622a, 0xdd5426, 0xc9441f].map((c) => mat(c, { roughness: 0.52 }));
    const bahanEkor = [0x1f6f52, 0x185a43, 0x2a8260].map((c) => mat(c, { roughness: 0.5, clearcoat: 0.2 }));

    const geoBola = new THREE.SphereGeometry(1, 40, 28);
    const bola = (r, m, sx = 1, sy = 1, sz = 1) => {
        const x = new THREE.Mesh(geoBola, m);
        x.scale.set(r * sx, r * sy, r * sz);

        return x;
    };
    const halus = (t, a, b) => {
        const k = klem((t - a) / (b - a), 0, 1);

        return k * k * (3 - 2 * k);
    };

    // ---------- warna kelopak bulu: leher dan dada kuning emas, sisi jingga, perut merah ----------
    const warnaBadan = (u, r, c) => {
        const emas = halus(u.z * 1.0 + u.y * 0.6, 0.62, 1.05);
        const bawah = halus(-u.y, 0.0, 0.8);
        c.set(0xea5a2b).lerp(cB.set(0xcc3b22), bawah * 0.75 + r() * 0.2);
        c.lerp(cB.set(0xf58a30), (1 - emas) * 0.3 * r());
        c.lerp(cB.set(0xf8b83c), emas * (0.75 + r() * 0.25));
    };
    const warnaLeher = (u, r, c) => {
        c.set(0xf7b535).lerp(cB.set(0xfbd05c), r() * 0.8);
        c.lerp(cB.set(0xf19a2c), halus(-u.y, 0.2, 1) * 0.5);
    };

    // ---------- ayam ----------
    const ayam = new THREE.Group();
    ayam.rotation.y = 1.0;
    adegan.add(ayam);

    const badan = new THREE.Group();
    ayam.add(badan);

    const pusatBadan = new THREE.Vector3(0, 0, -0.05);
    const radiiBadan = new THREE.Vector3(0.76, 0.8, 1.0);
    const inti = bola(1, bahanInti);
    inti.position.copy(pusatBadan);
    inti.scale.copy(radiiBadan).multiplyScalar(0.96);
    badan.add(inti);
    badan.add(selimut({
        pusat: pusatBadan, radii: radiiBadan, jumlah: 620, rnd, bahan: bahanBulu,
        saring: (u) => !(u.z > 0.7 && u.y > 0.4) && !(u.y < -0.78),
        arah: (u, d) => d.set(0, -1, -0.35),
        warna: warnaBadan, l: 0.44, w: 0.21, t: 0.07,
    }));

    // ekor: kipas daun hijau tebal
    const ekor = new THREE.Group();
    ekor.position.set(0, 0.3, -0.85);
    ekor.rotation.x = 0.62;
    for (let i = -3; i <= 3; i++) {
        const sudut = new THREE.Group();
        sudut.rotation.y = i * 0.27;
        sudut.rotation.z = i * 0.06;
        const helai = new THREE.Mesh(geoBola, bahanEkor[Math.abs(i) % 3]);
        const panjang = 0.98 - Math.abs(i) * 0.11;
        helai.scale.set(0.3, 0.06, panjang * 1.15);
        helai.position.set(0, i % 2 ? 0.02 : 0, -panjang * 0.85);
        sudut.add(helai);
        ekor.add(sudut);
    }
    badan.add(ekor);

    // sayap: tiga lapis kelopak jingga yang tebal
    const sayap = [];
    for (const sisi of [-1, 1]) {
        const pangkal = new THREE.Group();
        pangkal.position.set(sisi * 0.86, 0.34, 0.12);
        for (let baris = 0; baris < 3; baris++) {
            const jumlah = [5, 5, 4][baris];
            for (let i = 0; i < jumlah; i++) {
                const helai = new THREE.Mesh(geoBola, bahanSayap[baris]);
                const panjang = [0.5, 0.62, 0.72][baris];
                helai.scale.set(0.23, 0.07, panjang * 0.6);
                helai.position.set(sisi * (0.03 + baris * 0.03), -0.06 - baris * 0.2 - i * 0.014, -0.02 - i * 0.2 - baris * 0.08);
                helai.rotation.set(-0.16 + baris * 0.04, sisi * 0.07, sisi * (0.12 + baris * 0.05));
                pangkal.add(helai);
            }
        }
        badan.add(pangkal);
        sayap.push({ pangkal, sisi });
    }

    // kaki kuning sederhana dengan tiga jari
    for (const x of [-0.34, 0.34]) {
        const betis = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.05, 0.62, 18), bahanKaki);
        betis.position.set(x, -1.2, 0.12);
        ayam.add(betis);
        for (const sudut of [-0.6, 0, 0.6]) {
            const jari = new THREE.Group();
            jari.position.set(x, -1.5, 0.12);
            jari.rotation.y = sudut;
            const ruas = new THREE.Mesh(new THREE.CapsuleGeometry(0.052, 0.3, 6, 12), bahanKaki);
            ruas.rotation.x = Math.PI / 2;
            ruas.position.set(0, 0, 0.16);
            jari.add(ruas);
            ayam.add(jari);
        }
    }

    // leher dan kepala
    const engselLeher = new THREE.Vector3(0, 0.6, 0.8);
    const leher = new THREE.Group();
    leher.position.copy(engselLeher);
    ayam.add(leher);

    const leherBawah = { pusat: new THREE.Vector3(0, 0.82, 0.84), radii: new THREE.Vector3(0.46, 0.46, 0.42) };
    const lb = bola(1, bahanInti);
    lb.position.copy(leherBawah.pusat);
    lb.scale.copy(leherBawah.radii).multiplyScalar(0.95);
    ayam.add(lb);
    ayam.add(selimut({ pusat: leherBawah.pusat, radii: leherBawah.radii, jumlah: 260, rnd, bahan: bahanBulu, arah: (u, d) => d.set(0, -1, -0.2), warna: warnaLeher, l: 0.44, w: 0.2, t: 0.065 }));

    const bagianLeher = [
        { p: new THREE.Vector3(0, 1.22, 0.98), r: new THREE.Vector3(0.36, 0.38, 0.34), n: 200 },
    ];
    for (const b of bagianLeher) {
        const rel = b.p.clone().sub(engselLeher);
        const ci = bola(1, bahanInti);
        ci.position.copy(rel);
        ci.scale.copy(b.r).multiplyScalar(0.94);
        leher.add(ci);
        leher.add(selimut({ pusat: rel, radii: b.r, jumlah: b.n, rnd, bahan: bahanBulu, arah: (u, d) => d.set(0, -1, -0.15), warna: warnaLeher, l: 0.38, w: 0.18, t: 0.06 }));
    }

    const kepala = new THREE.Group();
    const pusatKepala = new THREE.Vector3(0, 1.88, 1.12);
    kepala.position.copy(pusatKepala).sub(engselLeher);
    leher.add(kepala);

    kepala.scale.setScalar(1.7);
    kepala.add(bola(1, bahanKulit, 0.24, 0.25, 0.27));

    // jengger: gumpalan merah mengilap seperti balon
    [[-0.12, 0.24, 0.08, 0.9], [-0.04, 0.3, 0.11, 1.1], [0.05, 0.31, 0.12, 1.2], [0.13, 0.27, 0.1, 1.05], [0.19, 0.2, 0.075, 0.9]].forEach(([z, y, r, k]) => {
        const j = bola(r, bahanJengger, 0.62, k, 0.9);
        j.position.set(0, y, z);
        kepala.add(j);
    });

    const paruh = new THREE.Mesh(new THREE.ConeGeometry(0.1, 0.27, 20), bahanParuh);
    paruh.rotation.x = Math.PI / 2 + 0.05;
    paruh.scale.set(1.0, 1, 0.65);
    paruh.position.set(0, -0.045, 0.37);
    kepala.add(paruh);

    for (const sisi of [-1, 1]) {
        const g = bola(1, bahanJengger, 0.045, 0.1, 0.05);
        g.position.set(sisi * 0.05, -0.27, 0.22);
        kepala.add(g);
    }

    // mata: bulat hitam besar dengan kilau; bergeser di permukaan kepala mengikuti kursor
    const mata = [];
    for (const sisi of [-1, 1]) {
        const grup = new THREE.Group();
        grup.position.set(0, 0.04, 0.02);
        grup.rotation.y = sisi * 0.72;
        const pivot = new THREE.Group();
        const m = new THREE.Group();
        m.position.set(0, 0.02, 0.24);
        const bola1 = bola(0.07, bahanMata, 0.85, 1.2, 0.5);
        const k1 = bola(0.021, kilau);
        k1.position.set(0.018, 0.04, 0.03);
        const k2 = bola(0.009, kilau);
        k2.position.set(-0.02, -0.025, 0.032);
        m.add(bola1, k1, k2);
        pivot.add(m);
        grup.add(pivot);
        kepala.add(grup);
        mata.push({ grup, pivot });
    }

    // bayangan lembut di tanah
    const cb = document.createElement('canvas');
    cb.width = cb.height = 128;
    const cx = cb.getContext('2d');
    const gr = cx.createRadialGradient(64, 64, 4, 64, 64, 62);
    gr.addColorStop(0, 'rgba(60,30,10,0.45)');
    gr.addColorStop(1, 'rgba(60,30,10,0)');
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
            const yawM = klem(Math.atan2(kopi.x, kopi.z) * 0.5, -0.4, 0.4);
            const pitchM = klem(Math.atan2(kopi.y, Math.hypot(kopi.x, kopi.z)) * 0.5, -0.3, 0.3);
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
        ekor.rotation.x = 0.7 + Math.sin(t * 1.3) * 0.02 + (aksi.kepak >= 0 ? 0.1 : 0);

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
