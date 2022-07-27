function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "Ara",
            sLengthMenu: "_MENU_",
            sEmptyTable: "Tabloda herhangi bir veri mevcut değil",
            sInfo: "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
            sInfoFiltered: "(_MAX_ kayıt içerisinden bulunan)",
            sInfoPostFix: "",
            sInfoThousands: ".",
            sLoadingRecords: "Yükleniyor...",
            sSearch: "",
            sZeroRecords: "Eşleşen kayıt bulunamadı",
            oPaginate: {
                sFirst: "İlk",
                sLast: "Son",
                sNext: "Sonraki",
                sPrevious: "Önceki"
            },
            oAria: {
                sSortAscending: "artan sütun sıralamasını aktifleştir",
                sSortDescending: "azalan sütun sıralamasını aktifleştir"
            }
        },
        pageLength: pageLength,
        searchHighlight: searchHighlight,
        dom: dom,
        buttons: [],
        columnDefs: [
            {
                targets: 'searchable',
                searchable: true,
                visible: false,

            },{
                targets: 'not-searchable',
                searchable: false,
            },
            {
                targets: 'not-sort',
                sortable: false,

            },
            {
                targets: -1,
                className: 'dt-body-center'
            },

        ],
        responsive: true
    });
}
