function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
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
