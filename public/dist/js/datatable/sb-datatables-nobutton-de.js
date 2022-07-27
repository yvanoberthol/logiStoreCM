function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "Suche",
            sLengthMenu: "_MENU_",
            sEmptyTable: "Keine Daten in der Tabelle vorhanden",
            sInfo: "_START_ bis _END_ von _TOTAL_ Einträgen",
            sInfoFiltered: "(gefiltert von _MAX_ Einträgen)",
            sInfoPostFix: "",
            sInfoThousands: ".",
            sLoadingRecords: "Wird geladen ..",
            sSearch: "",
            sZeroRecords: "Keine passenden Einträge gefunden",
            oPaginate: {
                sFirst: "Erste",
                sLast: "Letzte",
                sNext: "Nächste",
                sPrevious: "Zurück"
            },
            oAria: {
                sSortAscending: ": aktivieren, um Spalte aufsteigend zu sortieren",
                sSortDescending: ": aktivieren, um Spalte absteigend zu sortieren"
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
