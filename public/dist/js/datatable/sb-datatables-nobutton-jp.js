function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
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
