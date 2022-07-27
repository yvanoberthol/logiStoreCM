function getDataTable(elem,searchHighlight=false, pageLength = 10, dom = 'lBfrtip',lengthMenu = [10,25,50,100]) {
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
        lengthMenu: lengthMenu,
        //dom: dom,
        dom: '<"top"<"left-col"l><"center-col"B><"right-col"f>>rtp',
        searchHighlight: searchHighlight,
        buttons: [
            {
                extend: 'copy',
                text: 'Kopieren',
                className: 'copyButton',
                exportOptions: {
                    columns: ':not(.not-export)'
                }
            },
            {
                extend: 'csv',
                text: 'CSV',
                className: 'csvButton',
                exportOptions: {
                    columns: ':not(.not-export)'
                }
            },
            {
                extend: 'excel',
                text: 'Excel',
                className: 'excelButton',
                exportOptions: {
                    columns: ':not(.not-export)'
                }
            },
            {
                extend: 'print',
                text: 'Drucken',
                className: 'pdfButton',
                exportOptions: {
                    columns: ':not(.not-export)'
                }
            }
        ],
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

        //ordering: false
        responsive: true
    });
}
