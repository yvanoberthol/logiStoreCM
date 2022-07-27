function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "Поиск",
            sLengthMenu: "_MENU_",
            sEmptyTable: "В таблице отсутствуют данные",
            sInfo: "_START_ до _END_ из _TOTAL_ записей",
            sInfoFiltered: "(отфильтровано из _MAX_ записей)",
            sInfoPostFix: "",
            sInfoThousands: ",",
            sLoadingRecords: "Загрузка записей...",
            sSearch: "",
            sZeroRecords: "Записи отсутствуют.",
            oPaginate: {
                sFirst: "Первая",
                sLast: "Последняя",
                sNext: "Следующая",
                sPrevious: "Предыдущая"
            },
            oAria: {
                sSortAscending: "активировать для сортировки столбца по возрастанию",
                sSortDescending: "активировать для сортировки столбца по убыванию"
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
