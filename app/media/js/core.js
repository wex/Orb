$(function() {
    $('nav ol > li').hover(function() {
        $(this).find('ul').show();
    }, function() {
        $(this).find('ul').hide();
    });
})