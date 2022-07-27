function getDataTable(elem,searchHighlight=false,
                      pageLength = 10, dom='frtip') {
    return elem.DataTable({
        language: {
            searchPlaceholder: "অনুসন্ধান",
            sLengthMenu: "_MENU_",
            sEmptyTable: "কোন এন্ট্রি খুঁজে পাওয়া যায় নাই",
            sInfo: "_TOTAL_ টা এন্ট্রির মধ্যে _START_ থেকে _END_ পর্যন্ত দেখানো হচ্ছে",
            sInfoFiltered: "(মোট _MAX_ টা এন্ট্রির মধ্যে থেকে বাছাইকৃত)",
            sInfoPostFix: "",
            sInfoThousands: ".",
            sLoadingRecords: "প্রসেসিং হচ্ছে...",
            sSearch: "",
            sZeroRecords: "আপনি যা অনুসন্ধান করেছেন তার সাথে মিলে যাওয়া কোন রেকর্ড খুঁজে পাওয়া যায় নাই",
            oPaginate: {
                sFirst: "প্রথমটা",
                sLast: "শেষেরটা",
                sNext: "পরবর্তীটা",
                sPrevious: "আগেরটা"
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
