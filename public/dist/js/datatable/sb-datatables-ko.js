function getDataTable(elem,searchHighlight=false, pageLength = 10, dom = 'lBfrtip',lengthMenu = [10,25,50,100]) {
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
        lengthMenu: lengthMenu,
        //dom: dom,
        dom: '<"top"<"left-col"l><"center-col"B><"right-col"f>>rtp',
        searchHighlight: searchHighlight,
        buttons: [
            {
                extend: 'copy',
                text: '복사',
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
                text: '인쇄',
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
