jQuery(document).ready(function ($) {
    // Toggle row details on mobile
    $('.toggle-row').on('click', function (e) {
        e.preventDefault();
        var $row = $(this).closest('tr');        
        $row.toggleClass('active');
        $row.find('td:not(.check-column, .column-primary, .exclude)').toggle();
        $(this).toggleClass('active');
    });

    // Hide all non-primary cells on mobile initially
    function setupMobileView() {
        if ($(window).width() <= 782) {
            $('.wp-list-table tbody tr').each(function () {
                $(this).find('td:not(.check-column, .column-primary, .exclude)').hide();
            });
        } else {
            $('.wp-list-table tbody tr').each(function () {
                $(this).find('td').show();
            });
        }
    }

    setupMobileView();
    $(window).resize(setupMobileView);

    // Enhance filters with Select2
    const selectConfigs = {
        '#filter-by-exam': 'Select Exam',
        '#filter-by-question_type': 'All Questions',
        '#filter-by-category': 'All Categories',
        '#filter-by-user': 'Attempted By'
    };

    $.each(selectConfigs, function (selector, placeholder) {
        if ($(selector).length) {
            $(selector).select2({
                width: '200px',
                placeholder: placeholder,
                allowClear: false,
                minimumResultsForSearch: 8
            });
        }
    });
});
