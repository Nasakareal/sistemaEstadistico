const fs = require('fs');
const path = require('path');

const root = process.cwd();
const cpPolygonsInputPath = path.join(root, 'storage', 'app', '16-Mich.geojson');
const downloadsCpPolygonsInputPath = process.env.USERPROFILE
    ? path.join(process.env.USERPROFILE, 'Downloads', '16-Mich.geojson')
    : null;
const sepomexCatalogPath = path.join(root, 'storage', 'app', 'sepomex_michoacan_catalog.txt');
const coloniasInputPath = path.join(root, 'storage', 'app', 'morelia_colonias_overpass_full.json');
const fallbackColoniasInputPath = path.join(root, 'storage', 'app', 'morelia_colonias_overpass.json');
const roadsInputPath = path.join(root, 'storage', 'app', 'morelia_sector_roads_overpass.json');
const libramientoInputPath = path.join(root, 'storage', 'app', 'morelia_libramiento_exact_overpass.json');
const coloniasOutputPath = path.join(root, 'public', 'geo', 'morelia_colonias.geojson');
const sectorOutputPath = path.join(root, 'public', 'geo', 'morelia_sector_lines.geojson');
const libramientoOutputPath = path.join(root, 'public', 'geo', 'morelia_libramiento.geojson');

const MORELIA_MUNICIPIO = 'MORELIA';
const MADERO_ACUEDUCTO_START_LNG = -101.1865;

function toCoord(point) {
    return [
        Number(Number(point.lon).toFixed(7)),
        Number(Number(point.lat).toFixed(7)),
    ];
}

function coordKey(coord) {
    return `${Number(coord[0]).toFixed(7)},${Number(coord[1]).toFixed(7)}`;
}

function sameCoord(a, b) {
    return a && b && coordKey(a) === coordKey(b);
}

function closeRing(ring) {
    if (ring.length && !sameCoord(ring[0], ring[ring.length - 1])) {
        ring.push([...ring[0]]);
    }

    return ring;
}

function signedArea(ring) {
    let sum = 0;

    for (let i = 0; i < ring.length - 1; i++) {
        const [x1, y1] = ring[i];
        const [x2, y2] = ring[i + 1];
        sum += (x2 - x1) * (y2 + y1);
    }

    return sum;
}

function orientOuter(ring) {
    const closed = closeRing([...ring]);
    return signedArea(closed) > 0 ? closed.reverse() : closed;
}

function ringCentroid(ring) {
    const points = closeRing([...ring]).slice(0, -1);

    if (!points.length) {
        return null;
    }

    const total = points.reduce((acc, coord) => {
        acc[0] += coord[0];
        acc[1] += coord[1];
        return acc;
    }, [0, 0]);

    return [total[0] / points.length, total[1] / points.length];
}

function geometryCentroid(geometry) {
    if (!geometry) {
        return null;
    }

    if (geometry.type === 'Point') {
        return geometry.coordinates;
    }

    if (geometry.type === 'LineString') {
        return ringCentroid(geometry.coordinates);
    }

    if (geometry.type === 'Polygon') {
        return ringCentroid(geometry.coordinates[0] || []);
    }

    if (geometry.type === 'MultiPolygon') {
        return ringCentroid((geometry.coordinates[0] || [])[0] || []);
    }

    return null;
}

function polygonContainsPoint(ring, point) {
    const [x, y] = point;
    let inside = false;

    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const xi = ring[i][0];
        const yi = ring[i][1];
        const xj = ring[j][0];
        const yj = ring[j][1];
        const divisor = yj - yi || 1e-12;
        const intersects = ((yi > y) !== (yj > y))
            && (x < ((xj - xi) * (y - yi)) / divisor + xi);

        if (intersects) {
            inside = !inside;
        }
    }

    return inside;
}

function stitchSegments(segments) {
    const remaining = segments
        .filter(segment => Array.isArray(segment) && segment.length >= 2)
        .map(segment => segment.map(coord => [...coord]));
    const rings = [];

    while (remaining.length) {
        let ring = remaining.shift();
        let changed = true;

        while (changed && remaining.length) {
            changed = false;

            for (let i = 0; i < remaining.length; i++) {
                const segment = remaining[i];
                const ringStart = ring[0];
                const ringEnd = ring[ring.length - 1];
                const segmentStart = segment[0];
                const segmentEnd = segment[segment.length - 1];

                if (sameCoord(ringEnd, segmentStart)) {
                    ring = ring.concat(segment.slice(1));
                } else if (sameCoord(ringEnd, segmentEnd)) {
                    ring = ring.concat(segment.slice(0, -1).reverse());
                } else if (sameCoord(ringStart, segmentEnd)) {
                    ring = segment.slice(0, -1).concat(ring);
                } else if (sameCoord(ringStart, segmentStart)) {
                    ring = segment.slice(1).reverse().concat(ring);
                } else {
                    continue;
                }

                remaining.splice(i, 1);
                changed = true;
                break;
            }
        }

        if (ring.length >= 4) {
            rings.push(orientOuter(ring));
        }
    }

    return rings;
}

function normalizeName(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .toUpperCase();
}

function cleanName(value) {
    return String(value || '')
        .replace(/^col\.?\s+/i, '')
        .replace(/^colonia\s+/i, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function displayName(value) {
    const cleaned = cleanName(value);
    const lower = cleaned.toLocaleLowerCase('es-MX');

    return lower.replace(/(^|\s|\.|-|\/)([a-záéíóúñü])/g, match => match.toLocaleUpperCase('es-MX'));
}

function geometryFromElement(element) {
    if (element.type === 'node' && Number.isFinite(element.lat) && Number.isFinite(element.lon)) {
        return {
            type: 'Point',
            coordinates: [Number(element.lon), Number(element.lat)],
        };
    }

    if (element.type === 'way' && Array.isArray(element.geometry) && element.geometry.length >= 2) {
        const coordinates = element.geometry.map(toCoord);

        if (coordinates.length >= 4 && sameCoord(coordinates[0], coordinates[coordinates.length - 1])) {
            return { type: 'Polygon', coordinates: [orientOuter(coordinates)] };
        }

        return { type: 'LineString', coordinates };
    }

    if (element.type === 'relation' && Array.isArray(element.members)) {
        const outerSegments = [];
        const innerSegments = [];

        for (const member of element.members) {
            if (!Array.isArray(member.geometry) || member.geometry.length < 2) {
                continue;
            }

            const coordinates = member.geometry.map(toCoord);

            if ((member.role || 'outer') === 'inner') {
                innerSegments.push(coordinates);
            } else {
                outerSegments.push(coordinates);
            }
        }

        const outerRings = stitchSegments(outerSegments);
        const innerRings = stitchSegments(innerSegments);

        if (!outerRings.length) {
            return null;
        }

        const polygons = outerRings.map(outer => {
            const holes = innerRings.filter(inner => {
                const centroid = ringCentroid(inner);
                return centroid ? polygonContainsPoint(outer, centroid) : false;
            });

            return [outer, ...holes];
        });

        return polygons.length === 1
            ? { type: 'Polygon', coordinates: polygons[0] }
            : { type: 'MultiPolygon', coordinates: polygons };
    }

    return null;
}

function isColoniaGeometry(tags) {
    return Boolean(
        tags.name
        && (
            ['suburb', 'quarter', 'neighbourhood'].includes(tags.place)
            || tags.landuse === 'residential'
            || (tags.boundary === 'administrative' && ['9', '10', '11'].includes(String(tags.admin_level || '')))
        )
    );
}

function addressColoniaName(tags) {
    return tags['addr:suburb']
        || tags['addr:neighbourhood']
        || tags['addr:district']
        || null;
}

function addLabel(labelMap, name, coord, source) {
    const cleaned = cleanName(name);

    if (!cleaned || !coord || !Number.isFinite(coord[0]) || !Number.isFinite(coord[1])) {
        return;
    }

    const key = normalizeName(cleaned);

    if (!key || key.length < 3) {
        return;
    }

    if (!labelMap.has(key)) {
        labelMap.set(key, {
            nombre: displayName(cleaned),
            key,
            lngTotal: 0,
            latTotal: 0,
            count: 0,
            sources: new Set(),
        });
    }

    const item = labelMap.get(key);
    item.lngTotal += coord[0];
    item.latTotal += coord[1];
    item.count += 1;
    item.sources.add(source);
}

function normalizeCp(value) {
    return String(value || '').trim().padStart(5, '0');
}

function catalogName(value) {
    return cleanName(value);
}

function safeSlug(value) {
    return normalizeName(value)
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function cpPolygonInputPath() {
    const candidates = [cpPolygonsInputPath, downloadsCpPolygonsInputPath].filter(Boolean);
    return candidates.find(candidate => fs.existsSync(candidate)) || null;
}

function parseSepomexCatalog() {
    if (!fs.existsSync(sepomexCatalogPath)) {
        return new Map();
    }

    const lines = fs.readFileSync(sepomexCatalogPath, 'latin1').split(/\r?\n/);
    const headerIndex = lines.findIndex(line => line.startsWith('d_codigo|'));

    if (headerIndex < 0) {
        return new Map();
    }

    const headers = lines[headerIndex].split('|');
    const byCp = new Map();
    const seen = new Set();

    for (const line of lines.slice(headerIndex + 1)) {
        if (!/^\d{5}\|/.test(line)) {
            continue;
        }

        const values = line.split('|');
        const row = {};

        headers.forEach((header, index) => {
            row[header] = values[index] || '';
        });

        if (normalizeName(row.D_mnpio) !== MORELIA_MUNICIPIO) {
            continue;
        }

        const cp = normalizeCp(row.d_codigo);
        const nombre = catalogName(row.d_asenta);
        const tipo = catalogName(row.d_tipo_asenta || 'Asentamiento');

        if (!cp || !nombre) {
            continue;
        }

        const key = `${cp}/${normalizeName(nombre)}/${normalizeName(tipo)}`;

        if (seen.has(key)) {
            continue;
        }

        seen.add(key);

        if (!byCp.has(cp)) {
            byCp.set(cp, []);
        }

        byCp.get(cp).push({
            nombre,
            tipo,
            municipio: row.D_mnpio || 'Morelia',
            ciudad: row.d_ciudad || 'Morelia',
            zona: row.d_zona || '',
            id_asenta_cpcons: row.id_asenta_cpcons || '',
        });
    }

    for (const asentamientos of byCp.values()) {
        asentamientos.sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));
    }

    return byCp;
}

function forEachCoordinate(coordinates, callback) {
    if (!Array.isArray(coordinates)) {
        return;
    }

    if (typeof coordinates[0] === 'number' && typeof coordinates[1] === 'number') {
        callback(coordinates);
        return;
    }

    coordinates.forEach(item => forEachCoordinate(item, callback));
}

function geometryBbox(geometry) {
    const bbox = [Infinity, Infinity, -Infinity, -Infinity];

    forEachCoordinate(geometry?.coordinates, coord => {
        bbox[0] = Math.min(bbox[0], coord[0]);
        bbox[1] = Math.min(bbox[1], coord[1]);
        bbox[2] = Math.max(bbox[2], coord[0]);
        bbox[3] = Math.max(bbox[3], coord[1]);
    });

    return Number.isFinite(bbox[0]) ? bbox : null;
}

function pointInPolygonCoordinates(polygon, point) {
    const [outer, ...holes] = polygon || [];

    if (!outer || !polygonContainsPoint(outer, point)) {
        return false;
    }

    return !holes.some(hole => polygonContainsPoint(hole, point));
}

function pointInGeometry(geometry, point) {
    if (!geometry || !point) {
        return false;
    }

    if (geometry.type === 'Polygon') {
        return pointInPolygonCoordinates(geometry.coordinates, point);
    }

    if (geometry.type === 'MultiPolygon') {
        return geometry.coordinates.some(polygon => pointInPolygonCoordinates(polygon, point));
    }

    return false;
}

function fallbackPointForGeometry(geometry) {
    const center = geometryCentroid(geometry);

    if (center && pointInGeometry(geometry, center)) {
        return center;
    }

    const bbox = geometryBbox(geometry);

    if (!bbox) {
        return center || null;
    }

    const [minLng, minLat, maxLng, maxLat] = bbox;

    for (let parts = 3; parts <= 18; parts += 3) {
        for (let y = 0; y < parts; y++) {
            for (let x = 0; x < parts; x++) {
                const point = [
                    minLng + ((x + .5) * (maxLng - minLng) / parts),
                    minLat + ((y + .5) * (maxLat - minLat) / parts),
                ];

                if (pointInGeometry(geometry, point)) {
                    return point;
                }
            }
        }
    }

    return center || [(minLng + maxLng) / 2, (minLat + maxLat) / 2];
}

function labelPointsForGeometry(geometry, count) {
    if (count <= 0) {
        return [];
    }

    const bbox = geometryBbox(geometry);
    const fallback = fallbackPointForGeometry(geometry);

    if (!bbox || !fallback) {
        return Array.from({ length: count }, () => fallback).filter(Boolean);
    }

    const [minLng, minLat, maxLng, maxLat] = bbox;
    const width = Math.max(maxLng - minLng, 1e-6);
    const height = Math.max(maxLat - minLat, 1e-6);
    const ratio = Math.max(.45, Math.min(2.4, width / height));
    const baseColumns = Math.max(1, Math.ceil(Math.sqrt(count * ratio)));
    const baseRows = Math.max(1, Math.ceil(count / baseColumns));
    const points = [];

    for (let multiplier = 1; multiplier <= 5 && points.length < count; multiplier++) {
        const columns = baseColumns * multiplier;
        const rows = baseRows * multiplier;

        for (let row = 0; row < rows; row++) {
            for (let column = 0; column < columns; column++) {
                const point = [
                    minLng + ((column + .5) * width / columns),
                    minLat + ((row + .5) * height / rows),
                ];

                if (pointInGeometry(geometry, point)) {
                    points.push(point);

                    if (points.length >= count) {
                        break;
                    }
                }
            }

            if (points.length >= count) {
                break;
            }
        }
    }

    while (points.length < count) {
        points.push(fallback);
    }

    return points.slice(0, count);
}

function buildColoniasGeojsonFromPostalCodes() {
    const inputPath = cpPolygonInputPath();
    const byCp = parseSepomexCatalog();

    if (!inputPath || !byCp.size) {
        return null;
    }

    const source = JSON.parse(fs.readFileSync(inputPath, 'utf8'));
    const features = [];
    const matchedCp = new Set();

    for (const feature of source.features || []) {
        const cp = normalizeCp(feature.properties?.d_codigo);
        const asentamientos = byCp.get(cp);

        if (!asentamientos?.length || !feature.geometry) {
            continue;
        }

        matchedCp.add(cp);

        features.push({
            type: 'Feature',
            geometry: feature.geometry,
            properties: {
                id: `cp-${cp}`,
                nombre: `CP ${cp} · ${asentamientos.length} colonias`,
                tipo: 'codigo_postal',
                codigo_postal: cp,
                total_colonias: asentamientos.length,
                asentamientos: asentamientos.map(item => item.nombre),
                source: '16-Mich.geojson + Catálogo Nacional de Códigos Postales SEPOMEX',
            },
        });

        const labelPoints = labelPointsForGeometry(feature.geometry, asentamientos.length);

        asentamientos.forEach((asentamiento, index) => {
            const point = labelPoints[index];

            if (!point) {
                return;
            }

            features.push({
                type: 'Feature',
                geometry: {
                    type: 'Point',
                    coordinates: [
                        Number(point[0].toFixed(7)),
                        Number(point[1].toFixed(7)),
                    ],
                },
                properties: {
                    id: `colonia-${cp}-${safeSlug(asentamiento.nombre)}-${index}`,
                    nombre: asentamiento.nombre,
                    tipo: asentamiento.tipo || 'Asentamiento',
                    codigo_postal: cp,
                    label_rank: index,
                    labels_in_cp: asentamientos.length,
                    municipio: asentamiento.municipio,
                    ciudad: asentamiento.ciudad,
                    zona: asentamiento.zona,
                    has_geometry: true,
                    source: 'Catálogo Nacional de Códigos Postales SEPOMEX + polígono CP 16-Mich.geojson',
                },
            });
        });
    }

    features.sort((a, b) => {
        if (a.geometry.type !== b.geometry.type) {
            return a.geometry.type === 'Point' ? 1 : -1;
        }

        return String(a.properties.nombre).localeCompare(String(b.properties.nombre), 'es');
    });

    return {
        type: 'FeatureCollection',
        name: 'Colonias y asentamientos de Morelia por código postal',
        metadata: {
            source: '16-Mich.geojson y Catálogo Nacional de Códigos Postales SEPOMEX',
            license: 'Datos de Correos de México para uso particular; polígonos CP proporcionados localmente.',
            municipio: 'Morelia',
            generated_at: new Date().toISOString(),
            postal_codes: matchedCp.size,
            catalog_colonias: [...byCp.values()].reduce((total, items) => total + items.length, 0),
            note: 'Los límites son polígonos de código postal; las etiquetas muestran asentamientos/colonias oficiales dentro de cada CP.',
        },
        features,
    };
}

function buildColoniasGeojsonFromOverpass() {
    const inputPath = fs.existsSync(coloniasInputPath) ? coloniasInputPath : fallbackColoniasInputPath;
    const overpass = JSON.parse(fs.readFileSync(inputPath, 'utf8'));
    const features = [];
    const labelMap = new Map();
    const polygonNames = new Set();

    for (const element of overpass.elements || []) {
        const tags = element.tags || {};
        const geometry = geometryFromElement(element);

        if (!geometry) {
            continue;
        }

        if (isColoniaGeometry(tags)) {
            const nombre = displayName(tags.name);
            const tipo = tags.place || tags.landuse || tags.boundary || 'colonia';

            if (geometry.type !== 'Point') {
                features.push({
                    type: 'Feature',
                    geometry,
                    properties: {
                        id: `${element.type}/${element.id}`,
                        osm_id: element.id,
                        osm_type: element.type,
                        nombre,
                        tipo,
                        place: tags.place || null,
                        landuse: tags.landuse || null,
                        boundary: tags.boundary || null,
                        admin_level: tags.admin_level || null,
                        source: 'OpenStreetMap Overpass API',
                    },
                });
            }

            polygonNames.add(normalizeName(nombre));
            addLabel(labelMap, nombre, geometryCentroid(geometry), 'geometry');
        }

        const addressName = addressColoniaName(tags);

        if (addressName) {
            const center = element.center
                ? [Number(element.center.lon), Number(element.center.lat)]
                : geometryCentroid(geometry);
            addLabel(labelMap, addressName, center, 'address');
        }
    }

    for (const item of labelMap.values()) {
        const lng = Number((item.lngTotal / item.count).toFixed(7));
        const lat = Number((item.latTotal / item.count).toFixed(7));

        features.push({
            type: 'Feature',
            geometry: {
                type: 'Point',
                coordinates: [lng, lat],
            },
            properties: {
                id: `label/${item.key.toLowerCase().replace(/[^a-z0-9]+/gi, '-')}`,
                nombre: item.nombre,
                tipo: 'etiqueta_colonia',
                source: [...item.sources].sort().join(','),
                has_geometry: polygonNames.has(item.key),
            },
        });
    }

    features.sort((a, b) => {
        if (a.geometry.type !== b.geometry.type) {
            return a.geometry.type === 'Point' ? 1 : -1;
        }

        return String(a.properties.nombre).localeCompare(String(b.properties.nombre), 'es');
    });

    return {
        type: 'FeatureCollection',
        name: 'Colonias y fraccionamientos de Morelia',
        metadata: {
            source: 'OpenStreetMap Overpass API',
            license: 'Open Database License (ODbL)',
            bbox: [19.58, -101.32, 19.77, -101.08],
            generated_at: new Date().toISOString(),
            note: 'Incluye poligonos residenciales y etiquetas de colonia desde place/landuse/addr:suburb de OpenStreetMap.',
        },
        features,
    };
}

function buildColoniasGeojson() {
    return buildColoniasGeojsonFromPostalCodes() || buildColoniasGeojsonFromOverpass();
}

function roadName(element) {
    return String(element.tags?.name || '').trim();
}

function roadCoordinates(element) {
    return Array.isArray(element.geometry) && element.geometry.length >= 2
        ? element.geometry.map(toCoord)
        : null;
}

function isMadero(name) {
    const normalized = normalizeName(name);

    if (normalized.includes('PRIVADA') || normalized.includes('CICLOVIA')) {
        return false;
    }

    return normalized.includes('MADERO')
        && (
            normalized.includes('AVENIDA')
            || normalized.includes('CALZADA')
            || normalized === 'FRANCISCO I. MADERO'
            || normalized === 'FRANCISCO I MADERO'
        );
}

function isAcueducto(name) {
    const normalized = normalizeName(name);

    if (normalized.includes('PRIVADA') || normalized.includes('CICLOVIA')) {
        return false;
    }

    return normalized.includes('ACUEDUCTO');
}

function isMorelos(name) {
    const normalized = normalizeName(name);

    if (normalized.includes('PRIVADA') || normalized.includes('CICLOVIA')) {
        return false;
    }

    return normalized === 'MORELOS NORTE'
        || normalized === 'MORELOS SUR'
        || normalized === 'AVENIDA MORELOS NORTE'
        || normalized === 'AVENIDA MORELOS SUR'
        || normalized === 'AVENIDA MORELOS';
}

function lineCenterLng(coordinates) {
    const total = coordinates.reduce((sum, coord) => sum + coord[0], 0);
    return total / coordinates.length;
}

function buildSectorLinesGeojson() {
    const roadData = fs.existsSync(roadsInputPath)
        ? JSON.parse(fs.readFileSync(roadsInputPath, 'utf8'))
        : { elements: [] };

    const morelosLines = [];
    const maderoLines = [];

    for (const element of roadData.elements || []) {
        const name = roadName(element);
        const coordinates = roadCoordinates(element);

        if (!coordinates) {
            continue;
        }

        if (isMorelos(name)) {
            morelosLines.push(coordinates);
        }

        if (isMadero(name) && lineCenterLng(coordinates) <= MADERO_ACUEDUCTO_START_LNG) {
            maderoLines.push(coordinates);
        }

        if (isAcueducto(name)) {
            maderoLines.push(coordinates);
        }
    }

    const fallbackMorelos = [
        [-101.19098, 19.71945],
        [-101.19086, 19.71600],
        [-101.19074, 19.71185],
        [-101.19056, 19.70790],
        [-101.19017, 19.70370],
        [-101.19044, 19.69950],
        [-101.19115, 19.69535],
        [-101.19194, 19.69075],
        [-101.19258, 19.68610],
        [-101.19328, 19.68125],
    ];

    return {
        type: 'FeatureCollection',
        name: 'Lineas iniciales de sectorizacion Morelia',
        metadata: {
            source: 'OpenStreetMap Overpass API y trazo manual inicial',
            generated_at: new Date().toISOString(),
        },
        features: [
            {
                type: 'Feature',
                geometry: {
                    type: 'MultiLineString',
                    coordinates: morelosLines.length ? morelosLines : [fallbackMorelos],
                },
                properties: {
                    id: 'sector-morelos-camelinas',
                    nombre: 'Sector 01 · Avenida Morelos Norte-Morelos Sur',
                    color: '#0284c7',
                    tipo: 'linea_sector',
                    source: morelosLines.length ? 'OpenStreetMap Overpass API' : 'trazo_manual_inicial',
                },
            },
            {
                type: 'Feature',
                geometry: {
                    type: 'MultiLineString',
                    coordinates: maderoLines,
                },
                properties: {
                    id: 'sector-francisco-i-madero',
                    nombre: 'Sector 02 · Avenida Francisco I. Madero-Acueducto',
                    color: '#dc2626',
                    tipo: 'linea_sector',
                    source: 'OpenStreetMap Overpass API',
                },
            },
        ],
    };
}

function buildLibramientoGeojson() {
    const roadData = fs.existsSync(libramientoInputPath)
        ? JSON.parse(fs.readFileSync(libramientoInputPath, 'utf8'))
        : { elements: [] };

    const nombresCircuito = new Set([
        'AVENIDA CAMELINAS',
        'CIRCUITO PERIFERICO PASEO DE LA REPUBLICA',
        'PERIFERICO INDEPENDENCIA',
        'PERIFERICO ORIENTE',
        'PERIFERICO PASE DE LA REPUBLICA',
        'PERIFERICO REVOLUCION',
    ]);
    const jerarquiasViales = new Set(['trunk', 'secondary', 'tertiary']);
    const lines = [];

    for (const element of roadData.elements || []) {
        const name = normalizeName(element.tags?.name || '');
        const highway = String(element.tags?.highway || '').toLowerCase();
        const coordinates = roadCoordinates(element);

        if (!nombresCircuito.has(name) || !jerarquiasViales.has(highway) || !coordinates) {
            continue;
        }

        lines.push(coordinates);
    }

    return {
        type: 'FeatureCollection',
        name: 'Libramiento urbano de Morelia',
        metadata: {
            source: 'OpenStreetMap Overpass API',
            generated_at: new Date().toISOString(),
            circuito_cerrado: true,
        },
        features: [
            {
                type: 'Feature',
                geometry: {
                    type: 'MultiLineString',
                    coordinates: lines,
                },
                properties: {
                    id: 'libramiento-morelia',
                    nombre: 'Libramiento de Morelia',
                    color: '#24272b',
                    tipo: 'circuito_vial_cerrado',
                    source: 'OpenStreetMap Overpass API',
                },
            },
        ],
    };
}

function writeJson(filePath, data) {
    fs.mkdirSync(path.dirname(filePath), { recursive: true });
    fs.writeFileSync(filePath, JSON.stringify(data), 'utf8');
}

const coloniasGeojson = buildColoniasGeojson();
const sectorLinesGeojson = buildSectorLinesGeojson();
const libramientoGeojson = buildLibramientoGeojson();
const libramientoOnly = process.argv.includes('--libramiento-only');

if (!libramientoOnly) {
    writeJson(coloniasOutputPath, coloniasGeojson);
    writeJson(sectorOutputPath, sectorLinesGeojson);
}

writeJson(libramientoOutputPath, libramientoGeojson);

const counts = coloniasGeojson.features.reduce((acc, feature) => {
    acc[feature.geometry.type] = (acc[feature.geometry.type] || 0) + 1;
    return acc;
}, {});

console.log(JSON.stringify({
    colonias_output: coloniasOutputPath,
    sector_lines_output: sectorOutputPath,
    libramiento_output: libramientoOutputPath,
    colonias_total: coloniasGeojson.features.length,
    colonias_counts: counts,
    sector_lines: sectorLinesGeojson.features.map(feature => ({
        id: feature.properties.id,
        geometry: feature.geometry.type,
        segments: feature.geometry.type === 'MultiLineString' ? feature.geometry.coordinates.length : 1,
    })),
    libramiento_segments: libramientoGeojson.features[0].geometry.coordinates.length,
}, null, 2));
