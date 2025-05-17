<div class="lwa-attempts-container">
    <div class="lwa-attempts-header">
        <h2>Your Exam Attempts</h2>
        <?php if (!empty($attempts)): ?>
            <div class="lwa-attempts-controls">
                <div class="lwa-search-box">
                    <input type="text" id="lwa-attempts-search" placeholder="Search exams..." aria-label="Search exams">
                    <span class="lwa-search-icon">🔍</span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($attempts)): ?>
        <div class="lwa-no-attempts">
            <p>You haven't taken any exams yet.</p>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('exams'))); ?>" class="lwa-button">
                Browse Available Exams
            </a>
        </div>
    <?php else: ?>
        <div class="lwa-attempts-table-container">
            <table class="lwa-attempts-table">
                <thead>
                    <tr>
                        <th data-sort="exam">Exam</th>
                        <th data-sort="date">Date</th>
                        <th data-sort="score">Score</th>
                        <th data-sort="result">Result</th>
                        <th data-sort="time">Time Taken</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $attempt): ?>
                        <tr class="<?php echo $attempt->passed ? 'passed' : 'failed'; ?>">
                            <td data-label="Exam"><?php echo esc_html($attempt->title); ?></td>
                            <td data-label="Date" data-sort-value="<?php echo strtotime($attempt->start_time); ?>">
                                <?php echo date_i18n('M j, Y g:i a', strtotime($attempt->start_time)); ?>
                            </td>
                            <td data-label="Score" data-sort-value="<?php echo intval($attempt->percentage); ?>">
                                <div class="lwa-score-progress">
                                    <div class="lwa-attempts-progress-bar">
                                        <?php
                                        $percentage = intval($attempt->percentage);
                                        $progressClass = 'progress-green';
                                        if ($percentage <= 50) {
                                            $progressClass = 'progress-red';
                                        } elseif ($percentage <= 75) {
                                            $progressClass = 'progress-orange';
                                        }
                                        ?>
                                        <div class="lwa-progress-fill <?php echo $progressClass; ?>" style="width: <?php echo $percentage; ?>%"></div>

                                    </div>
                                    <span><?php echo intval($attempt->percentage); ?>%</span>
                                </div>
                            </td>
                            <td data-label="Result">
                                <span class="lwa-attempt-status">
                                    <?php echo $attempt->passed ? 'Passed' : 'Failed'; ?>
                                </span>
                            </td>
                            <td data-label="Time Taken" data-sort-value="<?php echo intval($attempt->time_taken_seconds); ?>">
                                <?php echo gmdate("i:s", intval($attempt->time_taken_seconds)); ?>
                            </td>
                            <td data-label="Actions">
                                <a href="<?php echo esc_url(add_query_arg([
                                                'exam_id'   => $attempt->exam_id,
                                                'action'    => 'results',
                                                'attempt_id' => $attempt->id,
                                            ], get_permalink(get_page_by_path('take-exam')))); ?>" class="lwa-button small lwa-view-details">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($attempts) > 10): ?>
            <div class="lwa-pagination">
                <button class="lwa-pagination-prev" disabled>← Previous</button>
                <div class="lwa-pagination-numbers"></div>
                <button class="lwa-pagination-next">Next →</button>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        // Pagination
        const rowsPerPage = 10;
        const $table = $('.lwa-attempts-table');
        const $rows = $table.find('tbody tr');
        const totalPages = Math.ceil($rows.length / rowsPerPage);

        function updatePagination(currentPage) {
            $rows.hide();
            $rows.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage).show();

            $('.lwa-pagination-numbers').empty();

            const sideCount = 2; // 2 before and after current
            let startPage = Math.max(1, currentPage - sideCount);
            let endPage = Math.min(totalPages, currentPage + sideCount);

            // Always show first page
            if (startPage > 1) {
                $('.lwa-pagination-numbers').append(`<button class="lwa-page-btn">1</button>`);
                if (startPage > 2) {
                    $('.lwa-pagination-numbers').append(`<span class="lwa-pagination-ellipsis">..</span>`);
                }
            }

            // Middle range
            for (let i = startPage; i <= endPage; i++) {
                $('.lwa-pagination-numbers').append(
                    `<button class="lwa-page-btn ${i === currentPage ? 'active' : ''}">${i}</button>`
                );
            }

            // Always show last page
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    $('.lwa-pagination-numbers').append(`<span class="lwa-pagination-ellipsis">..</span>`);
                }
                $('.lwa-pagination-numbers').append(`<button class="lwa-page-btn">${totalPages}</button>`);
            }

            $('.lwa-pagination-prev').prop('disabled', currentPage === 1);
            $('.lwa-pagination-next').prop('disabled', currentPage === totalPages);
        }


        $('.lwa-pagination').on('click', '.lwa-page-btn', function() {
            updatePagination(parseInt($(this).text()));
        });

        $('.lwa-pagination-prev').click(function() {
            const current = $('.lwa-page-btn.active').text();
            if (current > 1) updatePagination(parseInt(current) - 1);
        });

        $('.lwa-pagination-next').click(function() {
            const current = $('.lwa-page-btn.active').text();
            if (current < totalPages) updatePagination(parseInt(current) + 1);
        });

        // Initialize
        if (totalPages > 1) {
            updatePagination(1);
        } else {
            $rows.show(); // Show all if pagination is not needed
        }


        // Search functionality
        $('#lwa-attempts-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $rows.each(function() {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.includes(searchTerm));
            });
        });



        // Sorting with toggle and indicator
        let sortState = {}; // Store the current sort direction per column

        $('th[data-sort]').click(function() {
            const $header = $(this);
            const column = $header.data('sort');
            const columnIndex = $header.index();
            const $rowsArray = $table.find('tbody tr').get();

            // Toggle direction
            const currentDirection = sortState[column] === 'asc' ? 'desc' : 'asc';
            sortState = {
                [column]: currentDirection
            }; // Reset others

            // Remove sort indicators from other headers
            $('th[data-sort]').not($header).removeClass('sorted-asc sorted-desc');

            // Add visual indicator
            $header.removeClass('sorted-asc sorted-desc')
                .addClass(currentDirection === 'asc' ? 'sorted-asc' : 'sorted-desc');

            $rowsArray.sort(function(a, b) {
                const aCell = $(a).find('td').eq(columnIndex);
                const bCell = $(b).find('td').eq(columnIndex);

                const aVal = aCell.data('sort-value') !== undefined ? aCell.data('sort-value') : aCell.text().toLowerCase();
                const bVal = bCell.data('sort-value') !== undefined ? bCell.data('sort-value') : bCell.text().toLowerCase();

                if (typeof aVal === 'string') {
                    return currentDirection === 'asc' ?
                        aVal.localeCompare(bVal) :
                        bVal.localeCompare(aVal);
                } else {
                    return currentDirection === 'asc' ?
                        aVal - bVal :
                        bVal - aVal;
                }
            });

            $table.find('tbody').empty().append($rowsArray);
            updatePagination(1);
        });

    });
</script>