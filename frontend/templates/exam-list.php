<div class="lwa-exams-container">
    <div class="lwa-exams-header">
        <h2>Available Exams</h2>
        <div class="lwa-exams-filter">
            <label for="lwa-category-filter">Filter by Category:</label>
            <select id="lwa-category-filter">
                <option value="">All Categories</option>
                <?php
                // Get all unique categories from exams
                global $wpdb;
                $categories = $wpdb->get_results("
                    SELECT DISTINCT c.id, c.name 
                    FROM {$wpdb->prefix}exam_categories ec
                    JOIN {$wpdb->prefix}categories c ON ec.category_id = c.id
                    ORDER BY c.name
                ");
                foreach ($categories as $category) {
                    echo '<option value="' . esc_attr($category->id) . '">' . esc_html($category->name) . '</option>';
                }
                ?>
            </select>
        </div>
    </div>

    <div class="lwa-exams-grid">
        <?php foreach ($exams as $exam):
            // Get categories for this exam
            $exam_categories = $wpdb->get_results($wpdb->prepare("
                SELECT c.id, c.name 
                FROM {$wpdb->prefix}exam_categories ec
                JOIN {$wpdb->prefix}categories c ON ec.category_id = c.id
                WHERE ec.exam_id = %d
                ORDER BY c.name
            ", $exam->id));
        ?>
            <div class="lwa-exam-card" data-categories="<?php echo esc_attr(implode(',', wp_list_pluck($exam_categories, 'id'))); ?>">
                <div class="lwa-exam-categories">
                    <?php foreach ($exam_categories as $category): ?>
                        <span class="lwa-exam-category"><?php echo esc_html($category->name); ?></span>
                    <?php endforeach; ?>
                </div>

                <h3><?php echo esc_html($exam->title); ?></h3>
                <?php if (!empty($exam->description)): ?>
                    <div class="lwa-exam-description">
                        <?php echo wp_kses_post(wp_trim_words($exam->description, 40, '...')); ?>
                    </div>
                <?php endif; ?>

                <div class="lwa-exam-meta">
                    <div class="lwa-meta-item">
                        <span class="lwa-meta-icon">📝</span>
                        <span><?php echo intval($exam->question_count); ?> Questions</span>
                    </div>
                    <div class="lwa-meta-item">
                        <span class="lwa-meta-icon">⏱️</span>
                        <span><?php echo intval($exam->time_limit_minutes); ?> mins</span>
                    </div>
                    <div class="lwa-meta-item">
                        <span class="lwa-meta-icon">🎯</span>
                        <span><?php echo intval($exam->passing_score); ?>% to pass</span>
                    </div>
                </div>


                <div class="lwa-exam-footer">
                    <?php if (is_user_logged_in()): ?>
                        <span class="lwa-attempts-count">
                            <?php echo intval($exam->attempts_count); ?> attempt<?php echo $exam->attempts_count != 1 ? 's' : ''; ?>
                        </span>
                        <a href="<?php echo esc_url(add_query_arg('exam_id', $exam->id, get_permalink(get_page_by_path('take-exam')))); ?>" class="lwa-button">
                            <?php echo $exam->attempts_count > 0 ? 'Retake Exam' : 'Start Exam'; ?>
                        </a>
                    <?php else: ?>
                        <span class="lwa-login-prompt">Login to take this exam</span>
                        <a href="<?php echo esc_url(wp_login_url(add_query_arg('exam_id', $exam->id, get_permalink(get_page_by_path('take-exam'))))); ?>" class="lwa-button">
                            Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($exams)): ?>
        <div class="lwa-no-exams">
            <p>No exams available at this time.</p>
        </div>
    <?php endif; ?>

    <!-- Pagination Controls -->
    <?php if (count($exams) > 6): ?>
        <div class="lwa-pagination">
            <button class="lwa-pagination-prev" disabled>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Previous
            </button>
            <div class="lwa-pagination-numbers"></div>
            <button class="lwa-pagination-next">
                Next
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        // Pagination variables
        const examsPerPage = 6;
        let currentPage = 1;
        let filteredExams = $('.lwa-exam-card').toArray();

        // Initialize pagination
        function initPagination() {
            filteredExams = $('.lwa-exam-card:not(.hidden)').toArray();
            const totalPages = Math.ceil(filteredExams.length / examsPerPage);

            // Hide all exams
            $('.lwa-exam-card').hide();

            // Show exams for current page
            const startIndex = (currentPage - 1) * examsPerPage;
            const endIndex = startIndex + examsPerPage;
            filteredExams.slice(startIndex, endIndex).forEach(exam => $(exam).show());

            // Update pagination controls
            updatePaginationControls(totalPages);

            // Show no exams message if needed
            if (filteredExams.length === 0) {
                if ($('#lwa-no-category-exams').length === 0) {
                    $('.lwa-exams-grid').after('<div id="lwa-no-category-exams" class="lwa-no-exams"><p>No exams available in this category at the moment.</p></div>');
                }
            } else {
                $('#lwa-no-category-exams').remove();
            }
        }

        // Update pagination controls
        function updatePaginationControls(totalPages) {
            const $paginationNumbers = $('.lwa-pagination-numbers');
            $paginationNumbers.empty();

            // Previous button
            $('.lwa-pagination-prev').prop('disabled', currentPage === 1);

            // Page numbers
            const maxVisiblePages = 5;
            let startPage, endPage;

            if (totalPages <= maxVisiblePages) {
                startPage = 1;
                endPage = totalPages;
            } else {
                const maxPagesBeforeCurrent = Math.floor(maxVisiblePages / 2);
                const maxPagesAfterCurrent = Math.ceil(maxVisiblePages / 2) - 1;

                if (currentPage <= maxPagesBeforeCurrent) {
                    startPage = 1;
                    endPage = maxVisiblePages;
                } else if (currentPage + maxPagesAfterCurrent >= totalPages) {
                    startPage = totalPages - maxVisiblePages + 1;
                    endPage = totalPages;
                } else {
                    startPage = currentPage - maxPagesBeforeCurrent;
                    endPage = currentPage + maxPagesAfterCurrent;
                }
            }

            // First page and ellipsis
            if (startPage > 1) {
                $paginationNumbers.append(createPageNumber(1));
                if (startPage > 2) {
                    $paginationNumbers.append('<span class="lwa-pagination-ellipsis">..</span>');
                }
            }

            // Middle pages
            for (let i = startPage; i <= endPage; i++) {
                $paginationNumbers.append(createPageNumber(i));
            }

            // Last page and ellipsis
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    $paginationNumbers.append('<span class="lwa-pagination-ellipsis">..</span>');
                }
                $paginationNumbers.append(createPageNumber(totalPages));
            }

            // Next button
            $('.lwa-pagination-next').prop('disabled', currentPage === totalPages || totalPages === 0);
        }

        // Create page number element
        function createPageNumber(pageNumber) {
            const $page = $('<button class="lwa-pagination-number"></button>')
                .text(pageNumber)
                .toggleClass('active', pageNumber === currentPage);

            $page.on('click', function() {
                currentPage = pageNumber;
                initPagination();
                scrollToTop();
            });

            return $page;
        }

        // Scroll to top of exams grid
        function scrollToTop() {
            $('html, body').animate({
                scrollTop: $('.lwa-exams-grid').offset().top - 20
            }, 300);
        }

        // Previous button click
        $('.lwa-pagination-prev').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                initPagination();
                scrollToTop();
            }
        });

        // Next button click
        $('.lwa-pagination-next').on('click', function() {
            const totalPages = Math.ceil(filteredExams.length / examsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                initPagination();
                scrollToTop();
            }
        });

        // Category filtering
        $('#lwa-category-filter').on('change', function() {
            const selectedCategory = $(this).val();

            $('.lwa-exam-card').each(function() {
                let cardCategories = $(this).data('categories');

                if (typeof cardCategories === 'string') {
                    cardCategories = cardCategories.split(',');
                } else if (typeof cardCategories === 'number') {
                    cardCategories = [String(cardCategories)];
                } else if (!Array.isArray(cardCategories)) {
                    cardCategories = [];
                }

                if (selectedCategory === '' || cardCategories.includes(selectedCategory)) {
                    $(this).removeClass('hidden');
                } else {
                    $(this).addClass('hidden');
                }
            });

            // Reset to first page when filtering
            currentPage = 1;
            initPagination();
        });

        // Initialize pagination on load
        initPagination();
    });
</script>