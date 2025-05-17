jQuery(document).ready(function($) {
    // Toggle row details on mobile
    $('.toggle-row').on('click', function(e) {
        e.preventDefault();
        var $row = $(this).closest('tr');
        $row.toggleClass('active');
        
        // Toggle all cells except the first one (checkbox) and the primary column
        $row.find('td:not(.check-column, .column-primary)').toggle();
        
        // Update the toggle button state
        $(this).toggleClass('active');
    });
    
    // Hide all non-primary cells on mobile initially
    function setupMobileView() {
        if ($(window).width() <= 782) {
            $('.wp-list-table tbody tr').each(function() {
                $(this).find('td:not(.check-column, .column-primary)').hide();
            });
        } else {
            $('.wp-list-table tbody tr').each(function() {
                $(this).find('td').show();
            });
        }
    }
    
    // Run on load and resize
    setupMobileView();
    $(window).resize(setupMobileView);
});


jQuery(document).ready(function($) {
    // Enhance the exam filter select with Select2
    if ($('#filter-by-exam').length) {
        $('#filter-by-exam').select2({
            width: '250px',
            placeholder: 'Select Exam',
            allowClear: false,
            minimumResultsForSearch: 8
        });
    }
});
jQuery(document).ready(function($) {
    // Enhance the exam filter select with Select2
    if ($('#filter-by-question_type').length) {
        $('#filter-by-question_type').select2({
            width: '200px',
            placeholder: 'All Questions',
            allowClear: false,
            minimumResultsForSearch: 8
        });
    }
});

jQuery(document).ready(function($) {
    // Enhance the exam filter select with Select2
    if ($('#filter-by-question_type').length) {
        $('#filter-by-question_type').select2({
            width: '200px',
            placeholder: 'All Questions',
            allowClear: false,
            minimumResultsForSearch: 8
        });
    }
});

jQuery(document).ready(function($) {
    // Enhance the exam filter select with Select2
    if ($('#filter-by-category').length) {
        $('#filter-by-category').select2({
            width: '200px',
            placeholder: 'All Categories',
            allowClear: false,
            minimumResultsForSearch: 8
        });
    }
});

jQuery(document).ready(function($) {
    // Enhance the exam filter select with Select2
    if ($('#filter-by-user').length) {
        $('#filter-by-user').select2({
            width: '200px',
            placeholder: 'Attempted By',
            allowClear: false,
            minimumResultsForSearch: 8
        });
    }
});