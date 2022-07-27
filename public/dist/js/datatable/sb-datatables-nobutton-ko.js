function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "검색",
            sLengthMenu: "_MENU_",
            sEmptyTable: "데이터가 없습니다",
            sInfo: "_START_ - _END_ \\/ _TOTAL_",
            sInfoFiltered: "(총 _MAX_ 개)",
            sInfoPostFix: "",
            sInfoThousands: ",",
            sLoadingRecords: "읽는중...",
            sSearch: "",
            sZeroRecords: "검색 결과가 없습니다",
            oPaginate: {
                sFirst: "처음",
                sLast: "마지막",
                sNext: "다음",
                sPrevious: "이전"
            },
            oAria: {
                sSortAscending: ": 오름차순 정렬",
                sSortDescending: ": 내림차순 정렬"
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
