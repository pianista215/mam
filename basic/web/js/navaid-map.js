// Shared navaid map symbols and style factory.
// Requires OpenLayers (ol) to be loaded first.

const NAV_SVG = {
    // VOR: flat hexagon outline + center dot
    'VOR': [
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-12 -12 24 24' width='24' height='24'>` +
        `<polygon points='11,0 5.5,9.5 -5.5,9.5 -11,0 -5.5,-9.5 5.5,-9.5' fill='none' stroke='#2255ff' stroke-width='2'/>` +
        `<circle cx='0' cy='0' r='2.5' fill='#2255ff'/></svg>`, 24, 24],
    // NDB: two concentric circles + center dot
    'NDB': [
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-11 -11 22 22' width='22' height='22'>` +
        `<circle cx='0' cy='0' r='10' fill='none' stroke='#ff8800' stroke-width='2'/>` +
        `<circle cx='0' cy='0' r='5.5' fill='none' stroke='#ff8800' stroke-width='1.5'/>` +
        `<circle cx='0' cy='0' r='2.5' fill='#ff8800'/></svg>`, 22, 22],
    // DME: square outline + center dot
    'DME': [
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-10 -10 20 20' width='20' height='20'>` +
        `<rect x='-9' y='-9' width='18' height='18' fill='none' stroke='#00aacc' stroke-width='2'/>` +
        `<circle cx='0' cy='0' r='2.5' fill='#00aacc'/></svg>`, 20, 20],
    // ILS-LOC / LOC: diamond outline + center dot
    'ILS-LOC': [
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-11 -11 22 22' width='22' height='22'>` +
        `<polygon points='0,-10 10,0 0,10 -10,0' fill='none' stroke='#00cc44' stroke-width='2'/>` +
        `<circle cx='0' cy='0' r='2.5' fill='#00cc44'/></svg>`, 22, 22],
    'LOC': [
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-11 -11 22 22' width='22' height='22'>` +
        `<polygon points='0,-10 10,0 0,10 -10,0' fill='none' stroke='#00cc44' stroke-width='2'/>` +
        `<circle cx='0' cy='0' r='2.5' fill='#00cc44'/></svg>`, 22, 22],
};
// FIX and unknown types: open upward triangle
const NAV_SVG_DEFAULT = [
    `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-10 -10 20 20' width='20' height='20'>` +
    `<polygon points='0,-9 8,7 -8,7' fill='none' stroke='#666666' stroke-width='1.8'/></svg>`, 20, 20];

/**
 * Returns an array of ol.style.Style for a nav point object.
 * np must have: point_type, identifier, frequency (string or null).
 */
function makeNavStyle(np) {
    const [svgStr, , iconH] = NAV_SVG[np.point_type] || NAV_SVG_DEFAULT;
    const src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgStr);
    const baseOffsetY = Math.round(iconH / 2) + 9;
    const styles = [
        new ol.style.Style({
            image: new ol.style.Icon({
                src: src,
                anchor: [0.5, 0.5],
                anchorXUnits: 'fraction',
                anchorYUnits: 'fraction',
            })
        }),
        new ol.style.Style({
            text: new ol.style.Text({
                text: np.identifier,
                font: 'bold 10px sans-serif',
                offsetY: baseOffsetY,
                textAlign: 'center',
                fill: new ol.style.Fill({ color: '#222' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
            })
        })
    ];
    const freqStr = Array.isArray(np.navaids)
        ? np.navaids.map(n => n.frequency).join(' / ')
        : (np.frequency || '');
    if (freqStr) {
        styles.push(new ol.style.Style({
            text: new ol.style.Text({
                text: freqStr,
                font: 'bold 11px sans-serif',
                offsetY: baseOffsetY + 12,
                textAlign: 'center',
                fill: new ol.style.Fill({ color: '#555' }),
                stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
            })
        }));
    }
    return styles;
}
