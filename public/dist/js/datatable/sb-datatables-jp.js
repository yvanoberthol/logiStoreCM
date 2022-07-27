function getDataTable(elem,searchHighlight=false, pageLength = 10, dom = 'lBfrtip',lengthMenu = [10,25,50,100]) {
    return elem.DataTable({
        language: {
            searchPlaceholder: "検索",
            sLengthMenu: "_MENU_",
            sEmptyTable: "テーブルにデータがありません",
            sInfo: "_TOTAL_ 件中 _START_ から _END_ まで表示",
            sInfoFiltered: "（全 _MAX_ 件より抽出）",
            sInfoPostFix: "",
            sInfoThousands: ",",
            sLoadingRecords: "読み込み中...",
            sSearch: "",
            sZeroRecords: "一致するレコードがありません",
            oPaginate: {
                sFirst: "先頭",
                sLast: "最終",
                sNext: "次",
                sPrevious: "前"
            },
            oAria: {
                sSortAscending: "列を昇順に並べ替えるにはアクティブにする",
                sSortDescending: "列を降順に並べ替えるにはアクティブにする"
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
                text: 'Copier',
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
                text: 'Print',
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
