function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "Search",
            sLengthMenu: "_MENU_",
            sEmptyTable: "No data found in the table",
            sInfo: "",
            sInfoFiltered: "",
            sInfoPostFix: "",
            sInfoThousands: " ",
            sLoadingRecords: "Loading...",
            sSearch: "Search:",
            sZeroRecords: "No matching record",
            oPaginate: {
                sFirst: "First",
                sLast: "Last",
                sNext: "Next",
                sPrevious: "Previous"
            },
            oAria: {
                sSortAscending: "Sorting from bottom to top",
                sSortDescending: "Sorting from top to bottom"
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
