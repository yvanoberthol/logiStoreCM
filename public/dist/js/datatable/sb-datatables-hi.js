function getDataTable(elem,searchHighlight=false, pageLength = 10, dom = 'lBfrtip',lengthMenu = [10,25,50,100]) {
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
        lengthMenu: lengthMenu,
        //dom: dom,
        dom: '<"top"<"left-col"l><"center-col"B><"right-col"f>>rtp',
        searchHighlight: searchHighlight,
        buttons: [
            {
                extend: 'copy',
                text: 'نسخ',
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
                text: 'طباعة',
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
