function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "Buscar",
            sLengthMenu: "_MENU_",
            sEmptyTable: "Ningún dato disponible en esta tabla",
            sInfo: "_START_ a _END_ de _TOTAL_ registros",
            sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
            sInfoPostFix: "",
            sInfoThousands: ",",
            sLoadingRecords: "Cargando...",
            sSearch: "",
            sZeroRecords: "No se encontraron resultados",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: "Siguiente",
                sPrevious: "Anterior"
            },
            oAria: {
                sSortAscending: "Activar para ordenar la columna de manera ascendente",
                sSortDescending: "Activar para ordenar la columna de manera descendente"
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
