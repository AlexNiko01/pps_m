// ================= Node tree =================

var nodeTreeContainer = $('#node-tree-container');

$(document).keyup(function (e) {
    if (e.keyCode == 27) {
        nodeTreeContainer.hide();
    }
});

var treeContainer = $('#node-tree');
var treeBreadCrumbs = $('.fancytree-breadcrumbs');


// Clicking node breadcrumbs
treeBreadCrumbs.on('click', function () {
    var _t = $(this);

    var nodeActiveId = treeBreadCrumbs.last('li').data('node-id');

    if (nodeTreeContainer.is(':visible')) {
        nodeTreeContainer.hide();
    }
    else if (nodeActiveId) {
        nodeTreeContainer.show();

        treeContainer.fancytree("getTree").activateKey('id' + nodeActiveId);
        $('#tree-search').focus();
    }

});


// Initialize fancytree
treeContainer.fancytree({
    extensions: ["filter"],
    quicksearch: true,
    filter: {
        mode: "hide"
    },
    click: function (event, data) {
        if (data.targetType == 'title') {
            var node = data.node;
            if (node.data.href) {
                location.href = node.data.href;
            }
        }
    }
});

var tree = treeContainer.fancytree("getTree");

// Fancytree search
$("#tree-search").keyup(function (e) {
    var match = $(this).val();

    if (e && e.which === $.ui.keyCode.ESCAPE || $.trim(match) === "") {
        tree.clearFilter();
        return;
    }

    tree.filterNodes(match, false);

    treeContainer.fancytree("getRootNode").visit(function (node) {
        node.setExpanded(true);
    });
}).focus();

var Configure = new function () {
    this.showPass = function (fieldID) {
        var field = document.getElementById(fieldID);

        if (field.getAttribute('type') !== 'text') {
            field.setAttribute('type', 'text');

            setTimeout(function () {
                field.setAttribute('type', 'password');
            }, 5000);
        }
    };

    this.checkAll = function (currency) {
        var inputs = this._findInputs(currency);

        for (var i = 0; i < inputs.length; i++) {
            inputs[i].setAttribute('checked', true);
        }
    };

    this.uncheckAll = function (currency) {
        var inputs = this._findInputs(currency);

        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i].hasAttribute('checked')) {
                inputs[i].removeAttribute('checked')
            }
        }
    };

    this._findInputs = function (currency) {
        if (currency === undefined) {
            return document.querySelectorAll('#userpaymentsystem-currencies input');
        } else {
            return document.querySelectorAll('input[value^="' + currency + '"]');
        }
    }
};

