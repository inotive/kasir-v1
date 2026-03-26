import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const parseMarkers = (el) => {
    try {
        const raw = el?.dataset?.markers;
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
};

const formatCurrency = (value) => {
    const num = Number(value) || 0;
    return `Rp${num.toLocaleString('id-ID', { maximumFractionDigits: 0 })}`;
};

const radiusFor = (memberCount) => {
    const n = Number(memberCount) || 0;
    if (n <= 0) return 6;
    return Math.min(26, 6 + Math.sqrt(n) * 2.2);
};

const normalizeGeojson = (geojson) => {
    if (!geojson) return null;
    if (typeof geojson === 'string') {
        try {
            return JSON.parse(geojson);
        } catch {
            return null;
        }
    }
    if (typeof geojson === 'object') return geojson;
    return null;
};

const createPopupHtml = (m) => {
    const memberCount = Number(m.member_count) || 0;
    const revenue = Number(m.revenue) || 0;
    const profit = Number(m.profit) || 0;
    const margin = Number(m.margin_percent) || 0;
    const txCount = Number(m.tx_count) || 0;
    const district = m.district ? `${m.district}, ` : '';
    const title = `${district}${m.regency ?? '-'}, ${m.province ?? '-'}`;

    return `
        <div style="min-width:220px">
            <div style="font-weight:600;margin-bottom:4px">${title}</div>
            <div style="color:#64748b;font-size:12px;margin-bottom:8px">${m.status_label ?? ''}</div>
            <div style="display:flex;justify-content:space-between;gap:12px"><span>Member</span><span style="font-weight:600">${memberCount}</span></div>
            <div style="display:flex;justify-content:space-between;gap:12px"><span>Transaksi</span><span style="font-weight:600">${txCount}</span></div>
            <div style="display:flex;justify-content:space-between;gap:12px"><span>Revenue</span><span style="font-weight:600">${formatCurrency(revenue)}</span></div>
            <div style="display:flex;justify-content:space-between;gap:12px"><span>Profit</span><span style="font-weight:600">${formatCurrency(profit)}</span></div>
            <div style="display:flex;justify-content:space-between;gap:12px"><span>Margin</span><span style="font-weight:600">${margin.toFixed(1)}%</span></div>
        </div>
    `;
};

const applyMarkers = (el, markers) => {
    const map = el.__leafletMap;
    const layer = el.__leafletLayer;
    if (!map || !layer) return;

    layer.clearLayers();

    const bounds = [];
    let hasPolygon = false;

    markers.forEach((m) => {
        const lat = Number(m.lat);
        const lng = Number(m.lng);
        const district = m.district ? `${m.district}, ` : '';
        const title = `${district}${m.regency ?? '-'}, ${m.province ?? '-'}`;
        const popup = createPopupHtml(m);

        const geojson = normalizeGeojson(m.geojson);
        if (geojson) {
            const geo = L.geoJSON(geojson, {
                style: () => ({
                    color: m.status_color || '#3b82f6',
                    weight: 2,
                    fillColor: m.status_color || '#3b82f6',
                    fillOpacity: 0.25,
                }),
            });

            geo.eachLayer((l) => {
                l.bindPopup(popup);
                l.bindTooltip(title, { direction: 'top', opacity: 0.9 });
            });

            geo.addTo(layer);
            const b = geo.getBounds?.();
            if (b && b.isValid()) {
                bounds.push([b.getSouthWest().lat, b.getSouthWest().lng]);
                bounds.push([b.getNorthEast().lat, b.getNorthEast().lng]);
            }
            hasPolygon = true;
            return;
        }

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

        const memberCount = Number(m.member_count) || 0;
        const circle = L.circleMarker([lat, lng], {
            radius: radiusFor(memberCount),
            color: m.status_color || '#94a3b8',
            fillColor: m.status_color || '#94a3b8',
            fillOpacity: 0.45,
            weight: 2,
        });

        circle.bindPopup(popup);
        circle.bindTooltip(title, { direction: 'top', opacity: 0.9 });
        circle.addTo(layer);
        bounds.push([lat, lng]);
    });

    if (bounds.length) {
        map.fitBounds(bounds, { padding: [24, 24] });
    } else {
        map.setView([-2.5, 118], 5);
    }

    if (hasPolygon) {
        map.options.scrollWheelZoom = true;
    }
};

export const initMemberMap = () => {
    const el = document.querySelector('#memberMap');
    if (!el) return;

    const markers = parseMarkers(el);

    const map = L.map(el, {
        zoomControl: true,
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const layer = L.layerGroup().addTo(map);

    el.__leafletMap = map;
    el.__leafletLayer = layer;

    applyMarkers(el, markers);

    return map;
};

export const updateMemberMap = (markers) => {
    const el = document.querySelector('#memberMap');
    if (!el) return;

    if (!el.__leafletMap || !el.__leafletLayer) {
        el.dataset.markers = JSON.stringify(markers ?? []);
        return initMemberMap();
    }

    const safe = Array.isArray(markers) ? markers : [];
    applyMarkers(el, safe);
    return el.__leafletMap;
};

export default initMemberMap;
