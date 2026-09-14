#!/usr/bin/env python3
"""Génère le site de la collection Les Carnets.

Entrées  : volumes.json (découpage éditorial) + corpus_complet.json (le fonds).
Sorties  : site/index.html — la collection ; site/volumes/<slug>.html — un volume.

Tous les chiffres du fonds (croquis, muets, années, lieux, carnets) sont
recalculés ici à partir du corpus ; seuls les choix éditoriaux (retenus,
pages estimées, angle, sections, notes) viennent de volumes.json.
"""

import html
import json
import os
import unicodedata
from collections import Counter

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, "site")
REPO = "https://github.com/supershivas/les-carnets-editions/blob/main/"

MOIS = ["janvier", "février", "mars", "avril", "mai", "juin", "juillet",
        "août", "septembre", "octobre", "novembre", "décembre"]
TEINTE = {"Europe": "eu", "Afrique": "af", "Amériques": "am", "Asie": "as"}


def e(s):
    return html.escape(str(s), quote=True)


def nb(n):
    """Espace fine insécable comme séparateur de milliers."""
    return f"{n:,}".replace(",", " ")


def fold(s):
    return "".join(c for c in unicodedata.normalize("NFD", s)
                   if unicodedata.category(c) != "Mn").lower()


# ---------- lecture du fonds ----------

def charger():
    with open(os.path.join(ROOT, "volumes.json"), encoding="utf-8") as f:
        v = json.load(f)
    with open(os.path.join(ROOT, "corpus_complet.json"), encoding="utf-8") as f:
        c = json.load(f)
    return v, c


def croquis_du_volume(vol, corpus):
    """Les croquis d'un volume, classement corrigé compris."""
    ex = set(vol.get("exclure_fichiers", []))
    inc = set(vol.get("inclure_fichiers", []))
    out = [o for o in corpus if o["c"] in vol["carnets"] and o["f"] not in ex]
    if inc:
        out += [o for o in corpus if o["f"] in inc and o["c"] not in vol["carnets"]]
    return sorted(out, key=lambda o: o["d"])


def stats(cro):
    muets = sum(1 for o in cro if not o.get("x"))
    signes = sum(len(o.get("x") or "") for o in cro)
    annees = sorted({o["y"] for o in cro})
    return {
        "n": len(cro),
        "muets": muets,
        "pc_muets": round(100 * muets / len(cro)) if cro else 0,
        "signes": signes,
        "densite": round(signes / len(cro)) if cro else 0,
        "an0": annees[0] if annees else None,
        "an1": annees[-1] if annees else None,
        "annees": annees,
        "lieux": len({o["l"] for o in cro if o.get("l")}),
        "par_an": Counter(o["y"] for o in cro),
    }


def date_fr(d):
    a, m, j = d.split("-")
    return f"{int(j)} {MOIS[int(m) - 1]} {a}"


# ---------- fragments ----------

def histogramme(par_an, y0, y1, teinte):
    """Croquis par année — une barre par an, l'échelle est le maximum du volume."""
    ans = list(range(y0, y1 + 1))
    haut = max(par_an.values())
    lg, gap, h = 26, 6, 92
    w = len(ans) * lg + (len(ans) - 1) * gap
    barres = []
    for i, a in enumerate(ans):
        n = par_an.get(a, 0)
        hb = max(2, round(h * n / haut)) if n else 1
        x = i * (lg + gap)
        cls = "b" if n else "b vide"
        barres.append(
            f'<rect class="{cls}" x="{x}" y="{h - hb}" width="{lg}" height="{hb}" rx="1"></rect>'
            f'<text class="hn" x="{x + lg / 2}" y="{h - hb - 7}">{n or ""}</text>'
            f'<text class="ha" x="{x + lg / 2}" y="{h + 17}">{str(a)[2:]}</text>')
    return (f'<figure class="histo t-{teinte}">'
            f'<svg viewBox="-2 -16 {w + 4} {h + 38}" width="100%" height="{h + 54}" '
            f'role="img" aria-label="Croquis par année, de {y0} à {y1}, maximum {haut}">'
            f'{"".join(barres)}</svg>'
            f'<figcaption>Croquis par année — plus haute barre&nbsp;: {haut} en '
            f'{max(par_an, key=par_an.get)}.</figcaption></figure>')


def barre_carnets(cro, teinte):
    """Composition du volume, carnet par carnet."""
    c = Counter(o["c"] for o in cro)
    tot = len(cro)
    lignes = []
    for nom, n in c.most_common():
        sous = [o for o in cro if o["c"] == nom]
        s = stats(sous)
        pc = 100 * n / tot
        lignes.append(f"""<li class="carnet">
  <span class="cn">{e(nom)}</span>
  <span class="cbar" aria-hidden="true"><i style="width:{pc:.1f}%"></i></span>
  <span class="cq">{n}</span>
  <span class="cy">{s['an0']}{'–' + str(s['an1']) if s['an1'] != s['an0'] else ''}</span>
  <span class="cm">{s['pc_muets']}&nbsp;% muet</span>
</li>""")
    return f'<ul class="carnets t-{teinte}">{"".join(lignes)}</ul>'


def lieux_frequents(cro, k=12):
    c = Counter(o["l"] for o in cro if o.get("l"))
    return [(l, n) for l, n in c.most_common(k) if n > 1]


# ---------- pages ----------

CSS = """
:root{
  --bg:#14171B; --bg2:#1B1F24; --bg3:#22272E; --line:#2C323A; --line2:#39414B;
  --ink:#E8E6E1; --dim:#8E959E; --dimmer:#5C636C; --sel:#F0EDE6;
  --eu:#7FB3D5; --af:#E0A458; --am:#C96480; --as:#6FBF9B;
  --t:var(--dim);
}
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--bg);color:var(--ink);
  font-family:Archivo,system-ui,sans-serif;font-size:15px;line-height:1.55;
  -webkit-font-smoothing:antialiased}
img{max-width:100%}
a{color:inherit}
.t-eu{--t:var(--eu)} .t-af{--t:var(--af)} .t-am{--t:var(--am)} .t-as{--t:var(--as)}

.wrap{max-width:1080px;margin:0 auto;padding-inline:20px}
.lede{max-width:66ch}

h1,h2,h3{font-family:Spectral,Georgia,serif;font-weight:300;letter-spacing:-.01em;
  text-wrap:balance;margin:0}
h1{font-size:clamp(30px,5.2vw,46px);line-height:1.06}
h1 em{font-style:italic}
h2{font-size:22px;line-height:1.2}
h3{font-size:17px;font-weight:400}
p{margin:0}
.serif{font-family:Spectral,Georgia,serif;font-weight:300;font-size:16.5px;line-height:1.68;color:#C9C7C2}
.label{font-size:11.5px;letter-spacing:.09em;text-transform:uppercase;color:var(--dimmer)}
.num{font-variant-numeric:tabular-nums}

/* barre de navigation */
.topbar{border-bottom:1px solid var(--line);background:var(--bg)}
.topbar .wrap{display:flex;justify-content:space-between;align-items:center;
  gap:16px;flex-wrap:wrap;padding-block:12px}
.topbar a{text-decoration:none;color:var(--dim);font-size:13px}
.topbar a:hover{color:var(--ink)}
.topbar .home{font-family:Spectral,Georgia,serif;font-size:16px;color:var(--ink)}
.topbar nav{display:flex;gap:18px;flex-wrap:wrap}

/* en-tête */
.masthead{border-bottom:1px solid var(--line);padding-block:56px 40px}
.masthead .kicker{margin-bottom:14px}
.masthead .lede{margin-top:18px}

/* bandeau de chiffres */
.figures{border-block:1px solid var(--line);background:var(--bg2)}
.figrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(148px,1fr))}
.fig{padding:17px 20px 16px;border-left:1px solid var(--line)}
.fig:first-child{border-left:none;padding-left:0}
.fig .v{font-family:Spectral,Georgia,serif;font-size:29px;font-weight:300;
  line-height:1.1;font-variant-numeric:tabular-nums}
.fig .v small{font-size:14px;color:var(--dim)}
.fig .k{margin-top:5px}

section.blk{padding-block:44px;border-bottom:1px solid var(--line)}
section.blk:last-of-type{border-bottom:none}
.blkhead{display:flex;justify-content:space-between;align-items:baseline;
  gap:16px;flex-wrap:wrap;margin-bottom:22px}
.blkhead p{color:var(--dim);font-size:13.5px;max-width:52ch}

/* le rayon : chaque volume est un dos de livre, largeur proportionnelle aux pages */
.rayon{display:flex;align-items:flex-end;gap:4px;overflow-x:auto;padding-bottom:10px}
.dos{flex:0 0 auto;text-decoration:none;background:var(--bg2);
  border:1px solid var(--line);border-bottom:3px solid var(--t);
  border-radius:2px 2px 0 0;padding:14px 0 12px;
  display:flex;flex-direction:column;justify-content:space-between;align-items:center;
  min-height:210px;transition:background .15s,transform .15s}
.dos:hover,.dos:focus-visible{background:var(--bg3);transform:translateY(-3px)}
.dos .dn{font-family:Spectral,Georgia,serif;font-size:13px;color:var(--dimmer);
  font-variant-numeric:tabular-nums}
.dos .dt{writing-mode:vertical-rl;transform:rotate(180deg);
  font-family:Spectral,Georgia,serif;font-size:15px;line-height:1;color:var(--ink);
  margin-block:10px;max-height:130px;overflow:hidden}
.dos .dp{font-size:10.5px;color:var(--dimmer);font-variant-numeric:tabular-nums}
.dos.encours{border-style:dashed;border-bottom-style:solid;background:transparent}
.rayon-cap{font-size:12.5px;color:var(--dimmer);margin-top:8px}

/* tableau chemin de fer */
.tbl-scroll{overflow-x:auto}
table{border-collapse:collapse;width:100%;min-width:620px;font-size:13.5px}
th{text-align:left;font-weight:500;font-size:11.5px;letter-spacing:.08em;
  text-transform:uppercase;color:var(--dimmer);padding:0 12px 9px;
  border-bottom:1px solid var(--line);white-space:nowrap}
td{padding:11px 12px;border-bottom:1px solid var(--line);vertical-align:baseline}
tr:hover td{background:var(--bg2)}
td.n,th.n{text-align:right;font-variant-numeric:tabular-nums}
td:first-child,th:first-child{padding-left:0}
td:last-child,th:last-child{padding-right:0}
td a{font-family:Spectral,Georgia,serif;font-size:16px;text-decoration:none;
  border-bottom:1px solid var(--line2);padding-bottom:1px}
td a:hover{border-bottom-color:var(--t)}
tr.vol{--t:var(--dim)}
.dotreg{display:inline-block;width:7px;height:7px;border-radius:50%;
  background:var(--t);margin-right:9px;vertical-align:middle}
.part{display:inline-block;width:58px;height:5px;background:var(--bg3);
  border-radius:3px;overflow:hidden;vertical-align:middle;margin-right:8px}
.part i{display:block;height:100%;background:var(--t);opacity:.85}
tfoot td{border-bottom:none;border-top:1px solid var(--line2);
  color:var(--dim);font-size:13px}

.statut{font-size:11px;letter-spacing:.05em;text-transform:uppercase;
  border:1px solid var(--line2);border-radius:999px;padding:2px 9px;color:var(--dim);
  white-space:nowrap}
.statut.fait{color:var(--as);border-color:rgba(111,191,155,.45)}
.statut.reserve{color:var(--af);border-color:rgba(224,164,88,.45)}

/* notes et encadrés */
.note{border-left:2px solid var(--t);background:var(--bg2);padding:14px 16px;
  font-size:14px;color:#C9C7C2;line-height:1.6}
.notes{display:flex;flex-direction:column;gap:10px;list-style:none;margin:0;padding:0}
.notes li{border-left:2px solid var(--line2);padding:2px 0 2px 14px;
  font-size:14px;color:#C9C7C2;max-width:70ch}
.cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:26px}

/* composition en carnets */
.carnets{list-style:none;margin:0;padding:0;display:flex;flex-direction:column}
.carnet{display:grid;grid-template-columns:minmax(120px,1.4fr) minmax(70px,2fr) 44px 84px 84px;
  gap:12px;align-items:center;padding:9px 0;border-bottom:1px solid var(--line);font-size:13.5px}
.carnet .cn{font-family:Spectral,Georgia,serif;font-size:16px}
.carnet .cbar{background:var(--bg3);height:6px;border-radius:3px;overflow:hidden}
.carnet .cbar i{display:block;height:100%;background:var(--t);opacity:.85}
.carnet .cq{text-align:right;font-variant-numeric:tabular-nums;color:var(--ink)}
.carnet .cy,.carnet .cm{color:var(--dimmer);font-size:12px;font-variant-numeric:tabular-nums}
@media(max-width:640px){
  .carnet{grid-template-columns:1fr 44px;grid-template-areas:"n q" "b b" "y m";row-gap:5px}
  .carnet .cn{grid-area:n}.carnet .cbar{grid-area:b}.carnet .cq{grid-area:q}
  .carnet .cy{grid-area:y}.carnet .cm{grid-area:m;text-align:right}
}

/* histogramme */
.histo{margin:0}
.histo svg{display:block;max-width:520px}
.histo .b{fill:var(--t);opacity:.8}
.histo .b.vide{fill:var(--line);opacity:1}
.histo .hn{fill:var(--dim);font:10.5px Archivo,sans-serif;text-anchor:middle}
.histo .ha{fill:var(--dimmer);font:10.5px Archivo,sans-serif;text-anchor:middle}
.histo figcaption{font-size:12.5px;color:var(--dimmer);margin-top:10px;max-width:52ch}

/* liste de lieux */
.lieux{display:flex;flex-wrap:wrap;gap:6px;list-style:none;margin:0;padding:0}
.lieux li{border:1px solid var(--line);border-radius:999px;padding:3px 11px;
  font-size:12.5px;color:var(--dim)}
.lieux li b{color:var(--ink);font-weight:500;font-variant-numeric:tabular-nums}

/* sections du sommaire */
.sections{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:1px}
.sections li{background:var(--bg2);border-left:2px solid var(--t);
  padding:15px 18px;display:flex;justify-content:space-between;
  gap:10px 24px;flex-wrap:wrap;align-items:baseline}
.sections .sl{display:flex;flex-direction:column;gap:3px;min-width:200px}
.sections .st{font-family:Spectral,Georgia,serif;font-size:18px}
.sections .sp{color:var(--dim);font-size:13.5px;font-style:italic;
  font-family:Spectral,Georgia,serif}
.sections .sr{text-align:right;font-size:13px;color:var(--dim);
  font-variant-numeric:tabular-nums;line-height:1.45}
.sections .sr b{color:var(--ink);font-weight:500}

.pager{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;
  padding-block:28px 64px}
.pager a{text-decoration:none;color:var(--dimmer);max-width:45%;
  display:flex;flex-direction:column;gap:3px}
.pager a:hover .pl{color:var(--ink)}
.pager .pk{font-size:11.5px;letter-spacing:.09em;text-transform:uppercase}
.pager .pl{font-family:Spectral,Georgia,serif;font-size:18px;color:var(--dim)}
.pager .nx{text-align:right;margin-left:auto}

footer.site{border-top:1px solid var(--line);padding-block:26px 44px;
  color:var(--dimmer);font-size:12.5px}
footer.site a{color:var(--dim)}
footer.site p+p{margin-top:6px}
a:focus-visible,button:focus-visible{outline:2px solid var(--sel);outline-offset:2px}
@media(prefers-reduced-motion:reduce){*{transition:none!important;animation:none!important}}
"""

HEAD = """<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{title}</title>
<meta name="description" content="{desc}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Archivo:wght@400;500&display=swap" rel="stylesheet">
<style>{css}</style>
</head>
<body>
<div class="topbar"><div class="wrap">
  <a class="home" href="{base}index.html">Les Carnets <em>— la collection</em></a>
  <nav>
    <a href="{base}index.html">Les 13 volumes</a>
    <a href="{base}../index.html">Index des croquis</a>
    <a href="https://lescarnets.fr">lescarnets.fr</a>
  </nav>
</div></div>
"""

FOOT = """<footer class="site"><div class="wrap">
  <p>Croquis de Jérôme Agostini, 2002-2019. L'archive complète vit sur <a href="https://lescarnets.fr">lescarnets.fr</a> — les livres en sont une sélection, pas un remplacement.</p>
  <p>Page générée depuis <code>volumes.json</code> et <code>corpus_complet.json</code> par <code>tools/build_site.py</code>. Les chiffres du fonds sont recalculés à chaque build.</p>
</div></footer>
</body>
</html>
"""


def page(title, desc, corps, base=""):
    return HEAD.format(title=e(title), desc=e(desc), css=CSS, base=base) + corps + FOOT


def index(V, corpus, vues):
    c = V["collection"]
    tot = len(corpus)
    retenus = sum(v["retenus"] for v in V["volumes"])
    pages = sum(v["pages"] for v in V["volumes"])
    faits = sum(1 for v in V["volumes"] if v["statut"] == "fait")
    carnets = len({o["c"] for o in corpus})
    an0, an1 = min(o["y"] for o in corpus), max(o["y"] for o in corpus)

    maxp = max(v["pages"] for v in V["volumes"])
    dos = []
    for v in V["volumes"]:
        w = 30 + round(46 * v["pages"] / maxp)
        cls = "dos t-" + TEINTE[v["region"]] + ("" if v["statut"] == "fait" else " encours")
        dos.append(
            f'<a class="{cls}" style="width:{w}px" href="volumes/{v["slug"]}.html" '
            f'title="{e(v["titre"])} — {v["pages"]} pages estimées">'
            f'<span class="dn">{v["n"]}</span>'
            f'<span class="dt">{e(v["titre"])}</span>'
            f'<span class="dp">{v["pages"]}</span></a>')

    lignes = []
    for v in V["volumes"]:
        s = vues[v["slug"]]
        pc = round(100 * v["retenus"] / s["n"])
        pg = f'{v["pages"]}'
        if v.get("pages_reelle"):
            pg += f' <span style="color:var(--dimmer)">({v["pages_reelle"]})</span>'
        st = "fait" if v["statut"] == "fait" else ""
        lignes.append(f"""<tr class="vol t-{TEINTE[v['region']]}">
<td class="n" style="color:var(--dimmer)">{v['n']}</td>
<td><span class="dotreg"></span><a href="volumes/{v['slug']}.html">{e(v['titre'])}</a></td>
<td class="n">{s['n']}</td>
<td class="n">{v['retenus']}</td>
<td><span class="part"><i style="width:{pc}%"></i></span><span class="num">{pc}&nbsp;%</span></td>
<td class="n">{pg}</td>
<td><span class="statut {st}">{e(v['statut'])}</span></td>
</tr>""")

    r = V["reserve"]
    rs = vues["reserve"]
    lignes.append(f"""<tr class="vol t-{TEINTE[r['region']]}">
<td class="n" style="color:var(--dimmer)">—</td>
<td><span class="dotreg"></span><a href="volumes/{r['slug']}.html">{e(r['titre'])}</a></td>
<td class="n">{rs['n']}</td><td class="n">—</td><td></td><td class="n">—</td>
<td><span class="statut reserve">réserve</span></td>
</tr>""")

    trans = "".join(
        f'<tr><td>{e(t["motif"])}</td><td class="n">{t["croquis"]}</td>'
        f'<td class="n">{t["destinations"]}</td></tr>' for t in V["transversales"])

    reserves = "".join(f"<li>{e(x)}</li>" for x in c["reserves"])

    corps = f"""
<header class="masthead"><div class="wrap">
  <div class="label kicker">{nb(tot)} croquis · {carnets} carnets · {an0}–{an1}</div>
  <h1>Treize volumes tirés<br>d'un fonds de {nb(tot)} croquis</h1>
  <p class="lede serif" style="margin-top:18px">Une destination fait un carnet, plusieurs carnets font un volume.
  Le découpage ci-dessous couvre l'intégralité du fonds&nbsp;: chaque croquis dessiné entre {an0} et {an1}
  appartient à un volume et à un seul. Ce que les livres retiennent est une sélection —
  environ {nb(retenus)} croquis sur {nb(tot)}.</p>
</div></header>

<div class="figures"><div class="wrap"><div class="figrid">
  <div class="fig"><div class="v">{nb(tot)}</div><div class="k label">croquis au fonds</div></div>
  <div class="fig"><div class="v">13</div><div class="k label">volumes géographiques</div></div>
  <div class="fig"><div class="v">{faits}</div><div class="k label">sommaires arrêtés</div></div>
  <div class="fig"><div class="v">~{nb(retenus)}</div><div class="k label">croquis retenus</div></div>
  <div class="fig"><div class="v">~{nb(pages)}</div><div class="k label">pages cumulées</div></div>
</div></div></div>

<section class="blk"><div class="wrap">
  <div class="blkhead">
    <h2>Le rayon</h2>
    <p>Chaque dos est un volume&nbsp;; sa largeur suit le nombre de pages estimé.
    Trait plein en bas&nbsp;: sommaire arrêté. Contour pointillé&nbsp;: volume à faire.</p>
  </div>
  <div class="rayon">{''.join(dos)}</div>
  <p class="rayon-cap">La couleur est celle de la région, reprise de l'index des croquis&nbsp;:
  <span style="color:var(--eu)">Europe</span>, <span style="color:var(--af)">Afrique</span>,
  <span style="color:var(--am)">Amériques</span>, <span style="color:var(--as)">Asie</span>.</p>
</div></section>

<section class="blk"><div class="wrap">
  <div class="blkhead">
    <h2>Le chemin de fer de la collection</h2>
    <p>{e(c['methode'])}</p>
  </div>
  <div class="tbl-scroll"><table>
  <thead><tr>
    <th class="n">#</th><th>Volume</th><th class="n">Au fonds</th><th class="n">Retenus</th>
    <th>Part retenue</th><th class="n">Pages est.</th><th>Statut</th>
  </tr></thead>
  <tbody>{''.join(lignes)}</tbody>
  <tfoot><tr>
    <td></td><td>13 volumes + réserve</td><td class="n">{nb(tot)}</td>
    <td class="n">~{nb(retenus)}</td><td></td><td class="n">~{nb(pages)}</td><td></td>
  </tr></tfoot>
  </table></div>
  <p class="note t-as" style="margin-top:22px;max-width:70ch">{e(c['reserve_note'])}</p>
</div></section>

<section class="blk"><div class="wrap">
  <div class="blkhead"><h2>Ce que le chiffrage ne dit pas encore</h2></div>
  <ul class="notes">{reserves}</ul>
</div></section>

<section class="blk"><div class="wrap">
  <div class="cols">
    <div>
      <div class="blkhead" style="margin-bottom:14px"><h2>Les transversales</h2></div>
      <p class="serif" style="margin-bottom:18px;font-size:15px">{e(V['transversales_note'])}</p>
      <div class="tbl-scroll"><table style="min-width:0">
        <thead><tr><th>Motif</th><th class="n">Croquis</th><th class="n">Destinations</th></tr></thead>
        <tbody>{trans}</tbody>
      </table></div>
    </div>
    <div>
      <div class="blkhead" style="margin-bottom:14px"><h2>Le best of, en clôture</h2></div>
      <h3 style="margin-bottom:8px"><em>{e(V['best_of']['titre'])}</em></h3>
      <p class="serif" style="font-size:15px">{e(V['best_of']['resume'])}</p>
      <p class="note t-af" style="margin-top:26px">{e(c['avertissement'])}</p>
    </div>
  </div>
</div></section>
"""
    return page("Les Carnets — la collection",
                f"Le découpage éditorial du fonds Les Carnets : 13 volumes, {nb(tot)} croquis, "
                f"{carnets} carnets, {an0}-{an1}.", corps)


def page_volume(vol, cro, V, prec, suiv, reserve=False):
    s = stats(cro)
    t = TEINTE[vol["region"]]
    num = "Réserve" if reserve else f"Volume {vol['n']}"

    figs = [("croquis au fonds", nb(s["n"])), ]
    if not reserve:
        pc = round(100 * vol["retenus"] / s["n"])
        pg = str(vol["pages"])
        if vol.get("pages_reelle"):
            pg = f'{vol["pages"]} <small>({vol["pages_reelle"]})</small>'
        figs += [("retenus au livre", f'{vol["retenus"]} <small>{pc}&nbsp;%</small>'),
                 ("pages estimées", pg)]
    figs += [("sans texte", f'{s["muets"]} <small>{s["pc_muets"]}&nbsp;%</small>'),
             ("signes/croquis", nb(s["densite"])),
             ("lieux distincts", nb(s["lieux"]))]
    bandeau = "".join(f'<div class="fig"><div class="v">{v}</div>'
                      f'<div class="k label">{k}</div></div>' for k, v in figs)

    premier, dernier = cro[0], cro[-1]
    bornes = f"""<section class="blk"><div class="wrap">
  <div class="blkhead"><h2>Le gisement</h2>
  <p>{len(vol['carnets'])} carnet{'s' if len(vol['carnets']) > 1 else ''}, {s['n']} croquis,
  du {date_fr(premier['d'])} au {date_fr(dernier['d'])}.</p></div>
  {barre_carnets(cro, t)}
  <div class="cols" style="margin-top:32px">
    {histogramme(s['par_an'], s['an0'], s['an1'], t)}
    <div>
      <div class="label" style="margin-bottom:10px">Le premier et le dernier</div>
      <p class="serif" style="font-size:15px"><em>{e(premier['t'])}</em> — {date_fr(premier['d'])},
      {e(premier['l'] or premier['c'])}.<br>
      <em>{e(dernier['t'])}</em> — {date_fr(dernier['d'])}, {e(dernier['l'] or dernier['c'])}.</p>
      <div class="label" style="margin:22px 0 10px">Lieux les plus dessinés</div>
      <ul class="lieux">{''.join(f'<li>{e(l)} <b>{n}</b></li>' for l, n in lieux_frequents(cro))}</ul>
    </div>
  </div>
</div></section>"""

    sections = ""
    if vol.get("sections"):
        li = "".join(
            f'<li><span class="sl"><span class="st">{e(x["titre"])}</span>'
            f'<span class="sp">{e(x["periode"])}</span></span>'
            f'<span class="sr">{x["bruts"]} au fonds<br><b>{e(x["retenus"])}</b> retenus</span></li>'
            for x in vol["sections"])
        lien = ""
        if vol.get("sommaire"):
            lien = (f'<p style="margin-top:18px;font-size:13.5px;color:var(--dim)">'
                    f'Sélection croquis par croquis, textes d\'ouverture et de clôture&nbsp;: '
                    f'<a href="{REPO}{vol["sommaire"]}" style="color:var(--t)">{e(vol["sommaire"])}</a>.</p>')
        sections = f"""<section class="blk"><div class="wrap">
  <div class="blkhead"><h2>La structure</h2>
  <p>Le découpage arrêté du volume, section par section.</p></div>
  <ul class="sections t-{t}">{li}</ul>{lien}
</div></section>"""

    notes = ""
    if vol.get("notes"):
        notes = f"""<section class="blk"><div class="wrap">
  <div class="blkhead"><h2>À savoir avant d'y aller</h2></div>
  <ul class="notes">{''.join(f'<li>{e(x)}</li>' for x in vol['notes'])}</ul>
</div></section>"""

    angle = ""
    if vol.get("angle"):
        angle = (f'<p class="label" style="margin-top:26px">L\'angle</p>'
                 f'<p class="serif" style="font-size:22px;color:var(--ink);max-width:30ch;'
                 f'font-family:Spectral,Georgia,serif;font-style:italic;margin-top:6px">'
                 f'«&nbsp;{e(vol["angle"])}&nbsp;»</p>')

    statut_cls = {"fait": "fait", "réserve": "reserve"}.get(vol["statut"], "")
    nav = []
    if prec:
        nav.append(f'<a href="{prec["slug"]}.html"><span class="pk">← Volume précédent</span>'
                   f'<span class="pl">{e(prec["titre"])}</span></a>')
    else:
        nav.append("<span></span>")
    if suiv:
        nav.append(f'<a class="nx" href="{suiv["slug"]}.html"><span class="pk">Volume suivant →</span>'
                   f'<span class="pl">{e(suiv["titre"])}</span></a>')

    corps = f"""
<header class="masthead t-{t}"><div class="wrap">
  <div class="label kicker">{num} · {e(vol['region'])} · {s['an0']}–{s['an1']}
    <span class="statut {statut_cls}" style="margin-left:10px">{e(vol['statut'])}</span></div>
  <h1 style="border-left:3px solid var(--t);padding-left:18px;margin-left:-21px">{e(vol['titre'])}</h1>
  <p class="lede serif" style="margin-top:20px">{e(vol['resume'])}</p>
  {angle}
</div></header>

<div class="figures t-{t}"><div class="wrap"><div class="figrid">{bandeau}</div></div></div>

{bornes}
{sections}
{notes}

<div class="wrap"><div class="pager">{''.join(nav)}</div></div>
"""
    return page(f"{vol['titre']} — Les Carnets",
                f"{num} de la collection Les Carnets : {s['n']} croquis, {s['an0']}-{s['an1']}. "
                f"{vol['resume'][:120]}", corps, base="../")


def main():
    V, corpus = charger()
    os.makedirs(os.path.join(OUT, "volumes"), exist_ok=True)

    tous = V["volumes"] + [V["reserve"]]
    cro = {v["slug"]: croquis_du_volume(v, corpus) for v in tous}
    vues = {k: stats(v) for k, v in cro.items()}

    with open(os.path.join(OUT, "index.html"), "w", encoding="utf-8") as f:
        f.write(index(V, corpus, vues))

    for i, v in enumerate(tous):
        prec = tous[i - 1] if i else None
        suiv = tous[i + 1] if i + 1 < len(tous) else None
        with open(os.path.join(OUT, "volumes", v["slug"] + ".html"), "w", encoding="utf-8") as f:
            f.write(page_volume(v, cro[v["slug"]], V, prec, suiv,
                                reserve=(v is V["reserve"])))

    couv = sum(len(c) for c in cro.values())
    print(f"site/ : 1 page collection + {len(tous)} pages volumes")
    print(f"couverture du fonds : {couv}/{len(corpus)} croquis")
    if couv != len(corpus):
        raise SystemExit("ATTENTION : le découpage ne couvre pas exactement le fonds.")


if __name__ == "__main__":
    main()
