function formatNumber(number,separator = ",") {
    return String(number).replace(/(.)(?=(\d{3})+$)/g,'$1'+separator);
}