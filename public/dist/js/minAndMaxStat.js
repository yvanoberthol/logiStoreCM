function argMax(array) {
    return array.map((x, i) => [x, i])
        .reduce((r, a) => (a[0] > r[0]? a: r))[1];
}
function argMin(array) {
    return array.map((x, i) => [x, i])
        .reduce((r, a) => (a[0] < r[0]? a: r))[1];
}
