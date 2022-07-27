function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "搜索",
            sLengthMenu: "_MENU_",
            sEmptyTable: "表中数据为空",
            sInfo: "显示第 _START_ 至 _END_ 项结果，共 _TOTAL_ 项",
            sInfoFiltered: "(由 _MAX_ 项结果过滤)",
            sInfoPostFix: "",
            sInfoThousands: ",",
            sLoadingRecords: "数据加载提示信息，例如：数据加载中...",
            sSearch: "",
            sZeroRecords: "没有匹配结果",
            oPaginate: {
                sFirst: "首页",
                sLast: "末页",
                sNext: "下页",
                sPrevious: "上页"
            },
            oAria: {
                sSortAscending: ": 以升序排列此列ً",
                sSortDescending: ": 以降序排列此列"
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
