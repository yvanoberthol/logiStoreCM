function getDataTable(elem,searchHighlight=false, pageLength = 10, dom = 'lBfrtip',lengthMenu = [10,25,50,100]) {
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
        lengthMenu: lengthMenu,
        //dom: dom,
        dom: '<"top"<"left-col"l><"center-col"B><"right-col"f>>rtp',
        searchHighlight: searchHighlight,
        buttons: [
            {
                extend: 'copy',
                text: 'Копировать',
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
                text: 'Печать',
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
