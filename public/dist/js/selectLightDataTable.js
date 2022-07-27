function selectRowLight(elem){
    elem.on( 'click', 'tr', function () {
        $(this).toggleClass('selected bg-light');
    } );
}

