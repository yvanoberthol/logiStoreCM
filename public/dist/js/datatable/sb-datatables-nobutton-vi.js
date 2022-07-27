function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
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
