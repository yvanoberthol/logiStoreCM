function getDataTable(elem,searchHighlight=false, pageLength = 10, dom = 'lBfrtip',lengthMenu = [10,25,50,100]) {
    return elem.DataTable({
        language: {
            searchPlaceholder: "Tìm kiếm",
            sLengthMenu: "_MENU_",
            sEmptyTable: "Không có dữ liệu",
            sInfo: "Hiển thị _START_ tới _END_ của _TOTAL_ dữ liệu",
            sInfoFiltered: "(được lọc từ _MAX_ mục)",
            sInfoPostFix: "",
            sInfoThousands: "`",
            sLoadingRecords: "Đang tải...",
            sSearch: "",
            sZeroRecords: "Không tìm thấy kết quả",
            oPaginate: {
                sFirst: "Đầu tiên",
                sLast: "Cuối cùng",
                sNext: "Sau",
                sPrevious: "Trước"
            },
            oAria: {
                sSortAscending: ": Sắp xếp thứ tự tăng dần",
                sSortDescending: ": Sắp xếp thứ tự giảm dần"
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
                text: 'Sao chép"',
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
                text: 'In ấn',
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
