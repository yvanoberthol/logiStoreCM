function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "Cari",
            sLengthMenu: "_MENU_",
            sEmptyTable: "Tidak ada data yang tersedia pada tabel ini",
            sInfo: "_START_ sampai _END_ dari _TOTAL_ entri",
            sInfoFiltered: "(disaring dari _MAX_ entri keseluruhan)",
            sInfoPostFix: "",
            sInfoThousands: ".",
            sLoadingRecords: "Sedang memuat...",
            sSearch: "",
            sZeroRecords: "Tidak ditemukan data yang sesuai",
            oPaginate: {
                sFirst: "Pertama",
                sLast: "Terakhir",
                sNext: "Selanjutnya",
                sPrevious: "Sebelumnya"
            },
            oAria: {
                sSortAscending: ": aktifkan untuk mengurutkan kolom ke atas",
                sSortDescending: ": aktifkan untuk mengurutkan kolom menurun"
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
