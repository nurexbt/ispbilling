$(document).ready(function() {
    $("#menu-toggle").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");
    });

    // Fix for Bootstrap 5 dropdowns in responsive tables
    // Allows the dropdown to overflow the table container
    $('.table-responsive').on('show.bs.dropdown', function () {
        $(this).css("overflow", "inherit");
    });

    $('.table-responsive').on('hide.bs.dropdown', function () {
        $(this).css("overflow", "auto");
    });
});
