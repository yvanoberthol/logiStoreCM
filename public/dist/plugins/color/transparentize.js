const lim = (v, l, h) => Math.max(Math.min(v, h), l);
const map = {0: 0, 1: 1, 2: 2, 3: 3, 4: 4, 5: 5, 6: 6, 7: 7, 8: 8, 9: 9, A: 10, B: 11, C: 12, D: 13, E: 14, F: 15, a: 10, b: 11, c: 12, d: 13, e: 14, f: 15};
function transparentize(value, opacity) {

    let alpha = opacity === undefined ? 0.4 : 1 - opacity;
    let color = hexParse(value);
    color.a = lim(round(alpha * 255), 0, 255);


    return rgbString(color);
}

function round(v) {
    return v + 0.5 | 0;
}

function rgbString(v) {
    return v && (
        v.a < 255
            ? `rgba(${v.r}, ${v.g}, ${v.b}, ${b2n(v.a)})`
            : `rgb(${v.r}, ${v.g}, ${v.b})`
    );
}

function hexParse(str) {
    const len = str.length;
    let ret;
    if (str[0] === '#') {
        if (len === 4 || len === 5) {
            ret = {
                r: 255 & map[str[1]] * 17,
                g: 255 & map[str[2]] * 17,
                b: 255 & map[str[3]] * 17,
                a: len === 5 ? map[str[4]] * 17 : 255
            };
        } else if (len === 7 || len === 9) {
            ret = {
                r: map[str[1]] << 4 | map[str[2]],
                g: map[str[3]] << 4 | map[str[4]],
                b: map[str[5]] << 4 | map[str[6]],
                a: len === 9 ? (map[str[7]] << 4 | map[str[8]]) : 255
            };
        }
    }
    return ret;
}

function b2n(v) {
    return lim(round(v / 2.55) / 100, 0, 1);
}
