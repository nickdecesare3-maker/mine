#!/usr/bin/env python3
"""
Build an accurate, antimeridian-safe equirectangular world SVG (viewBox
0 0 1000 500) from Natural Earth 110m admin-0 countries GeoJSON (public
domain), for the Office Location Map WordPress plugin. One <path> per
country (split into pieces where a country crosses +/-180 longitude, e.g.
Russia/Fiji/USA), sharing a single "olm-land" class so the plugin's
existing CSS (land/sea/border color settings) applies unchanged, plus a
<title> per path with the country name.

Projection matches olm_project_marker_position() in the plugin exactly:
    x = (lon + 180) / 360 * 1000
    y = (90 - lat) / 180 * 500
so lat/lng markers line up with the coastlines.
"""
import json

SRC = "ne_110m_countries.geojson"
OUT = "world-map.svg"

VIEW_W, VIEW_H = 1000, 500


def project(lon, lat):
    return (lon + 180) / 360 * VIEW_W, (90 - lat) / 180 * VIEW_H


def unwrap_ring(ring):
    """Remove artificial +/-360 jumps so a ring that really does cross the
    antimeridian (Russia, Fiji, USA's Aleutians, ...) becomes one
    continuous line in longitude space instead of snapping across the
    whole map."""
    out = [list(ring[0])]
    offset = 0.0
    for i in range(1, len(ring)):
        lon, lat = ring[i]
        prev_lon = out[-1][0]
        cur = lon + offset
        while cur - prev_lon > 180:
            offset -= 360
            cur = lon + offset
        while cur - prev_lon < -180:
            offset += 360
            cur = lon + offset
        out.append([cur, lat])
    return out


def line_intersect_x(a, b, x):
    dx = b[0] - a[0]
    t = 0.0 if dx == 0 else (x - a[0]) / dx
    return [x, a[1] + t * (b[1] - a[1])]


def line_intersect_y(a, b, y):
    dy = b[1] - a[1]
    t = 0.0 if dy == 0 else (y - a[1]) / dy
    return [a[0] + t * (b[0] - a[0]), y]


def clip_edge(poly, inside_fn, intersect_fn):
    if not poly:
        return []
    output = []
    prev = poly[-1]
    prev_in = inside_fn(prev)
    for cur in poly:
        cur_in = inside_fn(cur)
        if cur_in:
            if not prev_in:
                output.append(intersect_fn(prev, cur))
            output.append(cur)
        elif prev_in:
            output.append(intersect_fn(prev, cur))
        prev, prev_in = cur, cur_in
    return output


def clip_rect(poly, xmin, xmax, ymin, ymax):
    poly = clip_edge(poly, lambda p: p[0] >= xmin, lambda a, b: line_intersect_x(a, b, xmin))
    poly = clip_edge(poly, lambda p: p[0] <= xmax, lambda a, b: line_intersect_x(a, b, xmax))
    poly = clip_edge(poly, lambda p: p[1] >= ymin, lambda a, b: line_intersect_y(a, b, ymin))
    poly = clip_edge(poly, lambda p: p[1] <= ymax, lambda a, b: line_intersect_y(a, b, ymax))
    return poly


def ring_area(ring):
    area = 0.0
    n = len(ring)
    for i in range(n):
        x1, y1 = ring[i]
        x2, y2 = ring[(i + 1) % n]
        area += x1 * y2 - x2 * y1
    return area / 2.0


def polygon_pieces(rings):
    """rings: [exterior, hole1, hole2, ...] in raw lon/lat.
    Returns dict shift -> list of clipped rings (still lon/lat), so a
    country that crosses the antimeridian yields more than one bucket
    (i.e. more than one <path>)."""
    buckets = {-360: [], 0: [], 360: []}
    for ring in rings:
        unwrapped = unwrap_ring(ring)
        for shift in (-360, 0, 360):
            shifted = [[lon + shift, lat] for lon, lat in unwrapped]
            clipped = clip_rect(shifted, -180, 180, -90, 90)
            if len(clipped) >= 3 and abs(ring_area(clipped)) > 1e-7:
                buckets[shift].append(clipped)
    return {k: v for k, v in buckets.items() if v}


def ring_to_path(ring):
    pts = [project(lon, lat) for lon, lat in ring]
    d = "M" + " L".join("%.2f,%.2f" % (x, y) for x, y in pts) + " Z"
    return d


def escape_xml(s):
    return (
        s.replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
        .replace('"', "&quot;")
    )


def main():
    with open(SRC, "r", encoding="utf-8") as f:
        data = json.load(f)

    path_count = 0
    country_count = 0
    paths = []

    for feature in data["features"]:
        props = feature.get("properties", {})
        name = props.get("NAME") or props.get("ADMIN") or props.get("SOVEREIGNT") or ""
        geom = feature.get("geometry")
        if not geom:
            continue

        if geom["type"] == "Polygon":
            polygons = [geom["coordinates"]]
        elif geom["type"] == "MultiPolygon":
            polygons = geom["coordinates"]
        else:
            continue

        country_count += 1
        country_had_output = False

        for poly_rings in polygons:
            buckets = polygon_pieces(poly_rings)
            for shift, rings in buckets.items():
                d = " ".join(ring_to_path(r) for r in rings)
                title = "<title>%s</title>" % escape_xml(name) if name else ""
                paths.append(
                    '\t\t<path class="olm-land" fill-rule="evenodd" d="%s">%s</path>' % (d, title)
                )
                path_count += 1
                country_had_output = True

        if not country_had_output:
            print("WARNING: no output for", name)

    svg = []
    svg.append(
        '<svg class="olm-world-svg" viewBox="0 0 %d %d" preserveAspectRatio="xMidYMid meet" '
        'xmlns="http://www.w3.org/2000/svg" role="img" aria-label="World map">' % (VIEW_W, VIEW_H)
    )
    svg.append('\t<rect class="olm-sea" x="0" y="0" width="%d" height="%d"></rect>' % (VIEW_W, VIEW_H))
    svg.append('\t<g class="olm-landmasses">')
    svg.extend(paths)
    svg.append('\t</g>')
    svg.append('</svg>')
    svg.append('')

    with open(OUT, "w", encoding="utf-8") as f:
        f.write("\n".join(svg))

    print("countries:", country_count, "  path pieces:", path_count)
    import os

    print("output size:", os.path.getsize(OUT), "bytes")


if __name__ == "__main__":
    main()
