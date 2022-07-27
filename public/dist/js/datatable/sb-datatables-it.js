function getDataTable(elem,searchHighlight=false, pageLength = 10, dom = 'lBfrtip',lengthMenu = [10,25,50,100]) {
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
        lengthMenu: lengthMenu,
        //dom: dom,
        dom: '<"top"<"left-col"l><"center-col"B><"right-col"f>>rtp',
        searchHighlight: searchHighlight,
        buttons: [
            {
                extend: 'copy',
                text: 'Copia',
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
                text: 'Stampa',
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
