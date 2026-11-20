"""
"Il mondo della lente": scene 3D procedurali per le hero del sito (Blender 5.x, Cycles).

Un anello cremisi laccato (il cerchio del logo, con un secondo anello sottile) sospeso nel buio
viola-inchiostro, attraversato da un flusso di "campioni" diversi per ogni sezione del sito.
Il soggetto sta nella metà destra: la metà sinistra resta in ombra per il titolo.

Uso (dalla radice del progetto):
  blender -b -P tools/render/lens_world.py -- --scene home --out tools/images/src/hero-home.png
  blender -b -P tools/render/lens_world.py -- --scene microbiota --tall --preview --out /tmp/p.png

Scene: home, corsi, nutrizione-umana, microbiota, fitoterapia, nutrizione-animale, comunicazione, contatti, docenti, chi-siamo
Opzioni: --tall (9:16), --preview (bassa risoluzione e pochi campioni), --width/--height, --samples, --seed
"""
import argparse
import math
import random
import sys

import bmesh
import bpy
from mathutils import Euler, Matrix, Vector

# ---------------------------------------------------------------------------
# Argomenti
# ---------------------------------------------------------------------------
argv = sys.argv[sys.argv.index("--") + 1:] if "--" in sys.argv else []
ap = argparse.ArgumentParser()
ap.add_argument("--scene", default="home")
ap.add_argument("--out", required=True)
ap.add_argument("--tall", action="store_true")
ap.add_argument("--preview", action="store_true")
ap.add_argument("--width", type=int, default=0)
ap.add_argument("--height", type=int, default=0)
ap.add_argument("--samples", type=int, default=0)
ap.add_argument("--seed", type=int, default=7)
args = ap.parse_args(argv)
rng = random.Random(args.seed)

# ---------------------------------------------------------------------------
# Palette (lineare) dal design system: inchiostro viola, cremisi del logo, porcellana
# ---------------------------------------------------------------------------
CRIMSON = (0.578, 0.006, 0.028, 1)
CRIMSON_DEEP = (0.30, 0.004, 0.018, 1)
VIOLET = (0.055, 0.025, 0.16, 1)
PORCELAIN = (0.86, 0.85, 0.83, 1)

# ---------------------------------------------------------------------------
# Scena pulita
# ---------------------------------------------------------------------------
bpy.ops.wm.read_factory_settings(use_empty=True)
scene = bpy.context.scene
col = scene.collection


def link(obj):
    col.objects.link(obj)
    return obj


def smooth(mesh):
    mesh.polygons.foreach_set("use_smooth", [True] * len(mesh.polygons))


def mesh_from_bm(name, bm):
    me = bpy.data.meshes.new(name)
    bm.to_mesh(me)
    bm.free()
    smooth(me)
    return me


def add_subsurf(obj, levels=1, render=2):
    m = obj.modifiers.new("subsurf", "SUBSURF")
    m.levels = levels
    m.render_levels = render
    return m


# ---------------------------------------------------------------------------
# Materiali
# ---------------------------------------------------------------------------
def principled(name, **kw):
    mat = bpy.data.materials.new(name)
    mat.use_nodes = True
    nt = mat.node_tree
    bsdf = nt.nodes.get("Principled BSDF")
    names = {
        "base": "Base Color", "rough": "Roughness", "metal": "Metallic", "sss": "Subsurface Weight",
        "sss_radius": "Subsurface Radius", "sss_scale": "Subsurface Scale", "coat": "Coat Weight",
        "coat_rough": "Coat Roughness", "trans": "Transmission Weight", "ior": "IOR", "sheen": "Sheen Weight",
        "sheen_tint": "Sheen Tint", "emit": "Emission Color", "emit_str": "Emission Strength", "spec": "Specular IOR Level",
        "disp": "Dispersion",
    }
    for k, v in kw.items():
        if k in ("bump", "volume"):
            continue
        sock = bsdf.inputs.get(names[k])
        if sock is not None:
            sock.default_value = v
    if kw.get("bump"):
        tex = nt.nodes.new("ShaderNodeTexNoise")
        tex.inputs["Scale"].default_value = kw["bump"][0]
        tex.inputs["Detail"].default_value = 8
        bump = nt.nodes.new("ShaderNodeBump")
        bump.inputs["Strength"].default_value = kw["bump"][1]
        nt.links.new(tex.outputs["Fac"], bump.inputs["Height"])
        nt.links.new(bump.outputs["Normal"], bsdf.inputs["Normal"])
    if kw.get("volume"):
        vol = nt.nodes.new("ShaderNodeVolumeAbsorption")
        vol.inputs["Color"].default_value = kw["volume"][0]
        vol.inputs["Density"].default_value = kw["volume"][1]
        out = nt.nodes.get("Material Output")
        nt.links.new(vol.outputs["Volume"], out.inputs["Volume"])
    return mat


M = {
    "lacquer": principled("lacquer", base=CRIMSON, rough=0.22, coat=1.0, coat_rough=0.04, spec=0.6),
    "lacquer_thin": principled("lacquer_thin", base=CRIMSON, rough=0.12, metal=0.85),
    "rbc": principled("rbc", base=(0.40, 0.005, 0.016, 1), rough=0.4, sss=1.0, sss_radius=(1.0, 0.09, 0.05), sss_scale=0.09, coat=0.25, coat_rough=0.25),
    "aril": principled("aril", base=(0.95, 0.06, 0.09, 1), rough=0.04, trans=0.92, ior=1.36, sss=0.1, volume=((0.9, 0.02, 0.05, 1), 6.0)),
    "seed": principled("seed", base=(0.82, 0.74, 0.62, 1), rough=0.4, sss=0.4, sss_radius=(1, 0.8, 0.6), sss_scale=0.02),
    "bacteria": principled("bacteria", base=(0.16, 0.04, 0.26, 1), rough=0.48, sss=0.9, sss_radius=(0.6, 0.3, 1.0), sss_scale=0.05, coat=0.4, coat_rough=0.15, bump=(45, 0.18)),
    "bacteria_pink": principled("bacteria_pink", base=(0.48, 0.05, 0.12, 1), rough=0.45, sss=0.9, sss_radius=(1.0, 0.35, 0.45), sss_scale=0.05, coat=0.4, bump=(45, 0.18)),
    "cocci": principled("cocci", base=(0.6, 0.02, 0.06, 1), rough=0.3, sss=0.8, sss_radius=(1, 0.2, 0.2), sss_scale=0.04, coat=0.3),
    "leaf": principled("leaf", base=(0.02, 0.07, 0.015, 1), rough=0.42, sss=0.12, sss_radius=(0.4, 1.0, 0.3), sss_scale=0.02, sheen=0.12, bump=(60, 0.08)),
    "sage": principled("sage", base=(0.035, 0.06, 0.03, 1), rough=0.6, sss=0.0, sss_radius=(0.5, 1.0, 0.5), sss_scale=0.02, sheen=0.3, bump=(90, 0.12)),
    "amber": principled("amber", base=(1.0, 0.55, 0.12, 1), rough=0.02, trans=1.0, ior=1.47, volume=((0.95, 0.45, 0.06, 1), 3.0)),
    "glass": principled("glass", base=(1, 1, 1, 1), rough=0.0, trans=1.0, ior=1.5, disp=0.3),
    "kibble": principled("kibble", base=(0.20, 0.085, 0.03, 1), rough=0.75, bump=(28, 0.35)),
    "kibble_red": principled("kibble_red", base=(0.32, 0.05, 0.03, 1), rough=0.7, bump=(30, 0.3)),
    "porcelain": principled("porcelain", base=PORCELAIN, rough=0.18, sss=0.25, sss_radius=(1, 0.95, 0.9), sss_scale=0.05, coat=0.6, coat_rough=0.05),
    "blueberry": principled("blueberry", base=(0.035, 0.025, 0.09, 1), rough=0.55, sheen=1.0, sheen_tint=(0.55, 0.6, 0.85, 1)),
    "violet_cell": principled("violet_cell", base=(0.12, 0.05, 0.30, 1), rough=0.3, sss=0.9, sss_radius=(0.5, 0.3, 1.0), sss_scale=0.06, coat=0.3),
}

# ---------------------------------------------------------------------------
# Geometrie
# ---------------------------------------------------------------------------
def torus_mesh(name, R, r, seg_u=256, seg_v=48):
    bm = bmesh.new()
    rows = []
    for i in range(seg_u):
        u = 2 * math.pi * i / seg_u
        row = []
        for j in range(seg_v):
            v = 2 * math.pi * j / seg_v
            x = (R + r * math.cos(v)) * math.cos(u)
            y = (R + r * math.cos(v)) * math.sin(u)
            z = r * math.sin(v)
            row.append(bm.verts.new((x, y, z)))
        rows.append(row)
    for i in range(seg_u):
        for j in range(seg_v):
            a = rows[i][j]
            b = rows[(i + 1) % seg_u][j]
            c = rows[(i + 1) % seg_u][(j + 1) % seg_v]
            d = rows[i][(j + 1) % seg_v]
            bm.faces.new((a, b, c, d))
    return mesh_from_bm(name, bm)


def rbc_mesh():
    """Globulo rosso biconcavo (modello di Evans-Fung, raggio 1)."""
    bm = bmesh.new()
    bmesh.ops.create_uvsphere(bm, u_segments=64, v_segments=40, radius=1.0)
    for v in bm.verts:
        x, y, z = v.co
        rho = min(1.0, math.hypot(x, y))
        half = 0.5 * math.sqrt(max(0.0, 1 - rho * rho)) * (0.207 + 2.003 * rho * rho - 1.123 * rho ** 4)
        v.co.z = math.copysign(max(half, 0.004), z if abs(z) > 1e-6 else 1)
    return mesh_from_bm("rbc", bm)


def capsule_mesh(name, r=0.12, h=0.42):
    bm = bmesh.new()
    bmesh.ops.create_uvsphere(bm, u_segments=40, v_segments=24, radius=r)
    for v in bm.verts:
        v.co.z += (h / 2) if v.co.z > 0 else -(h / 2)
        # leggera curvatura
        v.co.x += 0.06 * (v.co.z / (h / 2 + r)) ** 2
    return mesh_from_bm(name, bm)


def sphere_mesh(name, r=0.1, sx=1.0, sy=1.0, sz=1.0, u=48, v=32, jitter=0.0):
    bm = bmesh.new()
    bmesh.ops.create_uvsphere(bm, u_segments=u, v_segments=v, radius=r)
    for vert in bm.verts:
        vert.co.x *= sx
        vert.co.y *= sy
        vert.co.z *= sz
        if jitter:
            n = vert.co.normalized()
            vert.co += n * rng.uniform(-jitter, jitter) * r
    return mesh_from_bm(name, bm)


def aril_mesh():
    """Arillo di melograno: goccia sfaccettata, traslucida."""
    bm = bmesh.new()
    bmesh.ops.create_icosphere(bm, subdivisions=2, radius=0.11)
    for v in bm.verts:
        v.co.z *= 1.35
        if v.co.z > 0:
            v.co.x *= 1 - 0.25 * (v.co.z / 0.15)
            v.co.y *= 1 - 0.25 * (v.co.z / 0.15)
        v.co += v.co.normalized() * rng.uniform(-0.01, 0.01)
    return mesh_from_bm("aril", bm)


def leaf_mesh(name, L=0.7, W=0.22, nu=28, nv=10, curl=0.18):
    bm = bmesh.new()
    grid = []
    for i in range(nu + 1):
        u = i / nu
        w = W * (math.sin(math.pi * u) ** 0.75) + 0.002
        row = []
        for j in range(nv + 1):
            s = -1 + 2 * j / nv
            x = L * u - L / 2
            y = s * w
            z = curl * (s * s) * (w / W) - 0.05 * math.sin(math.pi * u) - 0.012 * (1 - abs(s)) ** 4
            row.append(bm.verts.new((x, y, z)))
        grid.append(row)
    for i in range(nu):
        for j in range(nv):
            bm.faces.new((grid[i][j], grid[i + 1][j], grid[i + 1][j + 1], grid[i][j + 1]))
    me = mesh_from_bm(name, bm)
    return me


def kibble_mesh():
    bm = bmesh.new()
    bmesh.ops.create_uvsphere(bm, u_segments=40, v_segments=24, radius=0.13)
    for v in bm.verts:
        v.co.z *= 0.5
        v.co += v.co.normalized() * rng.uniform(-0.006, 0.006)
    return mesh_from_bm("kibble", bm)


# ---------------------------------------------------------------------------
# Oggetti
# ---------------------------------------------------------------------------
def place(me, mat, loc, rot, scale, sub=1, solid=0.0):
    ob = bpy.data.objects.new(me.name + "_i", me)
    ob.location = loc
    ob.rotation_euler = rot
    ob.scale = (scale, scale, scale)
    if not ob.data.materials:
        ob.data.materials.append(mat)
    if solid:
        m = ob.modifiers.new("solid", "SOLIDIFY")
        m.thickness = solid
        m.offset = 0
    if sub:
        add_subsurf(ob, 1, sub)
    return link(ob)


def rand_rot():
    return Euler((rng.uniform(0, math.tau), rng.uniform(0, math.tau), rng.uniform(0, math.tau)))


# ---------------------------------------------------------------------------
# Composizione: anello + flusso di campioni che lo attraversa
# ---------------------------------------------------------------------------
TALL = args.tall
if TALL:
    RING_C = Vector((0.0, 0.0, 0.85))
    RING_R = 0.82
    A = RING_C + Vector((1.2, 7.0, 1.9))     # lontano, in alto
    B = RING_C + Vector((-0.55, -5.2, -1.15))  # vicino, in basso
else:
    RING_C = Vector((1.05, 0.0, 0.1))
    RING_R = 1.0
    A = RING_C + Vector((2.6, 8.5, 1.6))
    B = RING_C + Vector((0.35, -5.2, -0.8))

RING_ROTS = {
    "home": (70, 10, -40), "corsi": (80, -8, -22), "microbiota": (62, 18, -55), "nutrizione-umana": (75, 0, -30),
    "fitoterapia": (66, -12, -48), "nutrizione-animale": (82, 6, -16), "comunicazione": (72, 8, -36), "contatti": (78, 4, -26),
}
ring_rot = Euler(tuple(math.radians(a) for a in RING_ROTS.get(args.scene, (70, 10, -40))))


def build_ring(rot=ring_rot, center=RING_C, R=RING_R, thin=True):
    me = torus_mesh("ring", R, R * 0.095)
    ob = bpy.data.objects.new("ring", me)
    ob.data.materials.append(M["lacquer"])
    ob.location = center
    ob.rotation_euler = rot
    link(ob)
    if thin:
        me2 = torus_mesh("ring_thin", R * 1.2, R * 0.012, 256, 16)
        ob2 = bpy.data.objects.new("ring_thin", me2)
        ob2.data.materials.append(M["lacquer_thin"])
        ob2.location = center
        ob2.rotation_euler = rot
        link(ob2)
    return ob


CAM_LOC = Vector((0, -10, 0.25 if TALL else 0.15))
CAM_LENS = 58 if TALL else 70


def project(p):
    """Coordinate normalizzate [-1, 1] sullo schermo (x orizzontale, z verticale)."""
    depth = p.y - CAM_LOC.y
    if depth <= 0.1:
        return 9, 9
    half = 18 / CAM_LENS  # metà del sensore da 36 mm sul lato lungo
    if TALL:
        half_w, half_h = half * 9 / 16, half
    else:
        half_w, half_h = half, half * 9 / 16
    return (p.x - CAM_LOC.x) / depth / half_w, (p.z - CAM_LOC.z) / depth / half_h


def stream(n, kinds, spread=(1.25, 0.42, 0.62), min_d=0.24, near_bias=1.0):
    """Distribuisce n campioni lungo il percorso A -> anello -> B, a imbuto attraverso l'anello."""
    t_ring = (RING_C - A).length / ((RING_C - A).length + (B - RING_C).length)
    pts = []
    tries = 0
    while len(pts) < n and tries < n * 400:
        tries += 1
        t = rng.random() ** near_bias
        if t < t_ring:
            base = A.lerp(RING_C, t / t_ring)
            k = 1 - t / t_ring
            s = spread[1] + (spread[0] - spread[1]) * k
        else:
            base = RING_C.lerp(B, (t - t_ring) / (1 - t_ring))
            k = (t - t_ring) / (1 - t_ring)
            s = spread[1] + (spread[2] - spread[1]) * k
        p = base + Vector((rng.gauss(0, s), rng.gauss(0, s * 0.5), rng.gauss(0, s)))
        # proiezione sullo schermo: la zona del titolo resta libera
        sx, sz = project(p)
        if not TALL and (sx < -0.02 or (sz < -0.45 and sx < 0.35)):
            continue
        if TALL and (sz < -0.12 or abs(sx) > 1.05):
            continue
        if any((p - q).length < min_d for q, _ in pts):
            continue
        pts.append((p, rng.choice(kinds)))
    return pts


def populate(n, kinds, **kw):
    for p, (me, mat, smin, smax, sub) in stream(n, kinds, **kw):
        place(me, mat, p, rand_rot(), rng.uniform(smin, smax), sub)


S = args.scene
build_ring()

rbc = rbc_mesh()
if S in ("home", "chi-siamo"):
    populate(50, [(rbc, M["rbc"], 0.15, 0.2, 2)] * 14 + [(sphere_mesh("vcell", 0.1, jitter=0.05), M["violet_cell"], 0.55, 0.75, 1)], min_d=0.36)
elif S == "corsi":
    cap = capsule_mesh("cap")
    leaf = leaf_mesh("leaf", 0.55, 0.16)
    populate(80, [
        (rbc, M["rbc"], 0.14, 0.19, 2),
        (aril_mesh(), M["aril"], 0.9, 1.15, 1),
        (cap, M["bacteria"], 0.7, 0.95, 1),
        (sphere_mesh("drop", 0.08, sz=1.15), M["amber"], 0.9, 1.2, 1),
        (leaf, M["leaf"], 0.6, 0.85, 1),
        (kibble_mesh(), M["kibble"], 0.75, 0.95, 1),
        (sphere_mesh("pball", 0.07), M["porcelain"], 0.8, 1.2, 1),
    ], min_d=0.28)
elif S == "nutrizione-umana":
    aril = aril_mesh()
    seeds = []
    for p, _ in stream(85, [0], min_d=0.2):
        r = rand_rot()
        s = rng.uniform(0.85, 1.2)
        place(aril, M["aril"], p, r, s, 1)
        # il seme bianco dentro l'arillo
        seed = place(sphere_mesh("seed", 0.04, sz=1.6), M["seed"], p, r, s, 1)
    populate(10, [(sphere_mesh("blue", 0.12), M["blueberry"], 0.9, 1.1, 1)], min_d=0.4)
elif S == "microbiota":
    cap = capsule_mesh("cap")
    cap2 = capsule_mesh("cap2", 0.09, 0.6)
    populate(70, [
        (cap, M["bacteria"], 0.75, 1.0, 1),
        (cap2, M["bacteria_pink"], 0.7, 0.95, 1),
        (sphere_mesh("coc", 0.075), M["cocci"], 0.9, 1.15, 1),
    ], min_d=0.27)
elif S == "fitoterapia":
    leaf = leaf_mesh("leaf", 0.62, 0.18)
    sage = leaf_mesh("sage", 0.5, 0.16, curl=0.1)
    drop = sphere_mesh("drop", 0.075, sz=1.2)
    for p, (me, mat) in stream(60, [(leaf, M["leaf"]), (sage, M["sage"]), (drop, M["amber"]), (drop, M["amber"])], min_d=0.3):
        place(me, mat, p, rand_rot(), rng.uniform(0.85, 1.2), 1, solid=0.008 if me is not drop else 0.0)
elif S == "nutrizione-animale":
    kib = kibble_mesh()
    populate(75, [(kib, M["kibble"], 0.85, 1.15, 1)] * 3 + [(kib, M["kibble_red"], 0.85, 1.1, 1), (sphere_mesh("pb", 0.06), M["porcelain"], 0.8, 1.1, 1)], min_d=0.27)
elif S in ("comunicazione", "contatti"):
    # due anelli intrecciati: il dialogo
    second = build_ring(rot=Euler((math.radians(12), math.radians(64), math.radians(30))), center=RING_C + Vector((0.55, 0.15, -0.25)), R=RING_R * 0.78, thin=False)
    pb = sphere_mesh("pb", 0.07)
    populate(38 if S == "contatti" else 55, [(pb, M["porcelain"], 0.7, 1.3, 1), (rbc, M["rbc"], 0.12, 0.16, 2)], min_d=0.34)
elif S == "docenti":
    pb = sphere_mesh("pb", 0.07)
    populate(45, [(pb, M["porcelain"], 0.7, 1.4, 1), (rbc, M["rbc"], 0.13, 0.17, 2)], min_d=0.34)
else:
    populate(60, [(rbc, M["rbc"], 0.15, 0.2, 2)], min_d=0.3)

# ---------------------------------------------------------------------------
# Fondale, luci, camera
# ---------------------------------------------------------------------------
world = bpy.data.worlds.new("world")
scene.world = world
world.use_nodes = True
bg = world.node_tree.nodes.get("Background")
bg.inputs["Color"].default_value = (0.0012, 0.0009, 0.003, 1)
bg.inputs["Strength"].default_value = 1.0

# fondale: grande piano emissivo viola, più chiaro dietro il soggetto (vignettatura naturale)
bd_me = bpy.data.meshes.new("backdrop")
bm = bmesh.new()
bmesh.ops.create_grid(bm, x_segments=1, y_segments=1, size=30)
bm.to_mesh(bd_me)
bm.free()
bd = bpy.data.objects.new("backdrop", bd_me)
bd.location = (RING_C.x * 1.9, 16, RING_C.z + 0.4)
bd.rotation_euler = (math.radians(90), 0, 0)
link(bd)
bdm = bpy.data.materials.new("backdrop")
bdm.use_nodes = True
nt = bdm.node_tree
for n in list(nt.nodes):
    nt.nodes.remove(n)
out = nt.nodes.new("ShaderNodeOutputMaterial")
em = nt.nodes.new("ShaderNodeEmission")
grad = nt.nodes.new("ShaderNodeTexGradient")
grad.gradient_type = "SPHERICAL"
mapping = nt.nodes.new("ShaderNodeMapping")
tc = nt.nodes.new("ShaderNodeTexCoord")
ramp = nt.nodes.new("ShaderNodeValToRGB")
mapping.inputs["Scale"].default_value = (0.11, 0.11, 0.11)
nt.links.new(tc.outputs["Object"], mapping.inputs["Vector"])
nt.links.new(mapping.outputs["Vector"], grad.inputs["Vector"])
nt.links.new(grad.outputs["Fac"], ramp.inputs["Fac"])
ramp.color_ramp.elements[0].color = (0.0006, 0.0004, 0.0015, 1)
ramp.color_ramp.elements[1].color = (0.034, 0.012, 0.075, 1)
ramp.color_ramp.elements[1].position = 0.97
nt.links.new(ramp.outputs["Color"], em.inputs["Color"])
em.inputs["Strength"].default_value = 1.0
nt.links.new(em.outputs["Emission"], out.inputs["Surface"])
bd.data.materials.append(bdm)


def area_light(name, loc, target, power, size, color):
    ld = bpy.data.lights.new(name, "AREA")
    ld.energy = power
    ld.size = size
    ld.color = color
    lo = bpy.data.objects.new(name, ld)
    lo.location = loc
    d = Vector(target) - Vector(loc)
    lo.rotation_euler = d.to_track_quat("-Z", "Y").to_euler()
    return link(lo)


area_light("key", RING_C + Vector((2.6, -2.6, 4.6)), RING_C, 1100, 2.2, (1.0, 0.94, 0.88))
area_light("rim", RING_C + Vector((-1.2, 5.0, 2.6)), RING_C, 1600, 2.0, (1.0, 0.26, 0.32))
area_light("fill", RING_C + Vector((-6.0, -3.0, 0.5)), RING_C, 35, 6.0, (0.45, 0.38, 1.0))
area_light("top", RING_C + Vector((0.5, 1.0, 5.0)), RING_C, 60, 4.0, (0.85, 0.8, 1.0))

cam_d = bpy.data.cameras.new("cam")
cam_d.lens = CAM_LENS
cam_d.sensor_width = 36
cam_d.dof.use_dof = True
cam_d.dof.aperture_fstop = 0.55
cam = bpy.data.objects.new("cam", cam_d)
cam.location = CAM_LOC
cam.rotation_euler = (math.radians(90), 0, 0)
link(cam)
scene.camera = cam
cam_d.dof.focus_distance = (RING_C - cam.location).length

# ---------------------------------------------------------------------------
# Render
# ---------------------------------------------------------------------------
scene.render.engine = "CYCLES"
try:
    prefs = bpy.context.preferences.addons["cycles"].preferences
    for kind in ("OPTIX", "CUDA"):
        try:
            prefs.compute_device_type = kind
            prefs.get_devices()
            ok = False
            for dev in prefs.devices:
                dev.use = dev.type == kind
                ok = ok or dev.use
            if ok:
                scene.cycles.device = "GPU"
                break
        except Exception:
            continue
except Exception as exc:  # CPU come ripiego
    print("GPU non disponibile:", exc)

if args.preview:
    w, h = (360, 640) if TALL else (960, 540)
    samples = 48
else:
    w, h = (1440, 2560) if TALL else (2560, 1440)
    samples = 160
scene.render.resolution_x = args.width or w
scene.render.resolution_y = args.height or h
scene.render.resolution_percentage = 100
scene.cycles.samples = args.samples or samples
scene.cycles.use_adaptive_sampling = True
scene.cycles.use_denoising = True
scene.cycles.max_bounces = 16
scene.cycles.transmission_bounces = 16
scene.cycles.transparent_max_bounces = 16
scene.cycles.caustics_reflective = False
scene.cycles.caustics_refractive = False
scene.cycles.blur_glossy = 0.5
scene.render.film_transparent = False
scene.render.image_settings.file_format = "PNG"
scene.render.image_settings.color_depth = "8"
try:
    scene.view_settings.view_transform = "AgX"
    scene.view_settings.look = "AgX - Medium High Contrast"
except Exception:
    pass
scene.view_settings.exposure = -0.4
scene.render.filepath = args.out
bpy.ops.render.render(write_still=True)
print("RENDER_OK", args.out)
