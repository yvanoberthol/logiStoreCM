function getDataTable(elem,searchHighlight=false, pageLength = 10,dom='frtip'){
    return elem.DataTable({
        language: {
            searchPlaceholder: "Rechercher",
            sLengthMenu: "_MENU_",
            sEmptyTable: "Aucune donnée trouvée dans la table",
            sInfo: "_START_ à _END_ de _TOTAL_ enregistrements",
            sInfoFiltered: "(Filtré de _MAX_ entrées total)",
            sInfoPostFix: "",
            sInfoThousands: " ",
            sLoadingRecords: "Chargement...",
            sSearch: "Recherche:",
            sZeroRecords: "Aucun enregistrement correspondant",
            oPaginate: {
                sFirst: "Premier",
                sLast: "Dernier",
                sNext: "Suivant",
                sPrevious: "Précédent"
            },
            oAria: {
                sSortAscending: "Tri du bas vers le haut",
                sSortDescending: "Tri du haut vers le bas"
            }
        },
        pageLength: pageLength,
        dom: dom,
        searchHighlight: searchHighlight,
        buttons: [
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

        //ordering: false,
        responsive: true
    });
}
