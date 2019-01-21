$(document).ready(function() {
    $('body').on('click', '.cl-btn-change', function (e) {
        e.preventDefault();
        var parent = $(e.target).closest('form');
        $(this).hide();
        parent.find('.cl-text').hide();
        parent.find('.cl-new').show();
        return false;
    }).on('click', '.cl-btn-cancel', function (e) {
        e.preventDefault();
        var parent = $(e.target).closest('form');
        parent.find('.cl-btn-change').show();
        parent.find('.cl-text').show();
        parent.find('.cl-new').hide();
        return false;
    });
});
