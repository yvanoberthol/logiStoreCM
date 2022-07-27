function getDataTable(elem,searchHighlight=false, pageLength = 10, dom = 'lBfrtip',lengthMenu = [10,25,50,100]) {
    return elem.DataTable({
        language: {
            searchPlaceholder: "Procurar",
            sLengthMenu: "_MENU_",
            sEmptyTable: "Não foi encontrado nenhum registo",
            sInfo: "_START_ a _END_ num total de _TOTAL_ registos",
            sInfoFiltered: "(filtrado num total de _MAX_ registos)",
            sInfoPostFix: "",
            sInfoThousands: ".",
            sLoadingRecords: "A carregar...",
            sSearch: "",
            sZeroRecords: "Não foram encontrados resultados",
            oPaginate: {
                sFirst: "Primeiro",
                sLast: "Último",
                sNext: "Seguinte",
                sPrevious: "Anterior"
            },
            oAria: {
                sSortAscending: "Ordenar colunas de forma ascendente",
                sSortDescending: "Ordenar colunas de forma descendente"
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
                text: 'Copiar',
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
                text: 'Imprimir',
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
