function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "ابحث",
            sLengthMenu: "_MENU_",
            sEmptyTable: "ليست هناك بيانات متاحة في الجدول",
            sInfo: "إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل",
            sInfoFiltered: "(منتقاة من مجموع _MAX_ مُدخل)",
            sInfoPostFix: "",
            sInfoThousands: ".",
            sLoadingRecords: "جارٍ التحميل...",
            sSearch: "",
            sZeroRecords: "لم يعثر على أية سجلات",
            oPaginate: {
                sFirst: "الأول",
                sLast: "الأخير",
                sNext: "التالي",
                sPrevious: "السابق"
            },
            oAria: {
                sSortAscending: ": تفعيل لترتيب العمود تصاعدياً",
                sSortDescending: ": تفعيل لترتيب العمود تنازلياً"
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
