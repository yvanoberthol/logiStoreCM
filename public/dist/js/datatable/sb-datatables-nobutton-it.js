function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "Cerca",
            sLengthMenu: "_MENU_",
            sEmptyTable: "Nessun dato disponibile nella tabella",
            sInfo: "_START_ a _END_ di _TOTAL_ elementi",
            sInfoFiltered: "(filtrati da _MAX_ elementi totali)",
            sInfoPostFix: "",
            sInfoThousands: ".",
            sLoadingRecords: "Caricamento...",
            sSearch: "",
            sZeroRecords: "Nessun elemento corrispondente trovato",
            oPaginate: {
                sFirst: "Inizio",
                sLast: "Fine",
                sNext: "Successivo",
                sPrevious: "Precedente"
            },
            oAria: {
                sSortAscending: "attiva per ordinare la colonna in ordine crescente",
                sSortDescending: "attiva per ordinare la colonna in ordine decrescente"
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
