function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "खोजें",
            sLengthMenu: "_MENU_",
            sEmptyTable: "तालिका में आंकड़े उपलब्ध नहीं है",
            sInfo: "_START_ to _END_ of _TOTAL_ प्रविष्टियां दिखा रहे हैं",
            sInfoFiltered: "(_MAX_ कुल प्रविष्टियों में से छठा हुआ)",
            sInfoPostFix: "",
            sInfoThousands: ".",
            sLoadingRecords: "्रगति पे हैं ...",
            sSearch: "",
            sZeroRecords: "रिकॉर्ड्स का मेल नहीं मिला",
            oPaginate: {
                sFirst: "प्रथम",
                sLast: "अंतिम",
                sNext: "अगला",
                sPrevious: "पिछला"
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
