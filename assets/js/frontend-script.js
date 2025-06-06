// assets/js/frontend-script.js
jQuery(document).ready(function ($) {
    // Start Exam Button
    $(document).on('click', '#lwa-start-exam', function (e) {
        e.preventDefault();

        const examId = $(this).data('exam-id');

        $.ajax({
            url: lwa_exams.ajax_url,
            type: 'POST',
            data: {
                action: 'lwa_start_exam',
                exam_id: examId,
                nonce: lwa_exams.nonce
            },
            beforeSend: function () {
                $('#lwa-start-exam').prop('disabled', true).text('Starting...');
            },
            success: function (response) {
                if (response.success) {
                    // Construct the new URL on the frontend
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('action', 'questions');
                    newUrl.searchParams.set('attempt_id', response.data.attempt_id);
                    window.location.href = newUrl.toString();
                } else {
                    // Handle not logged in case gracefully
                    if (response.data.includes('logged in')) {
                        window.location.href = wp_login_url(window.location.href);
                    } else {
                        alert(response.data);
                        $('#lwa-start-exam').prop('disabled', false).text('Start Exam');
                    }
                }
            },
            error: function (xhr) {
                if (xhr.status === 403) {
                    window.location.href = wp_login_url(window.location.href);
                } else {
                    alert('An error occurred. Please try again.');
                }
                $('#lwa-start-exam').prop('disabled', false).text('Start Exam');
            }
        });
    });

    // Exam Timer
    // Replace the timer code with this
    if ($('#lwa-exam-time').length) {
        const timerElement = $('#lwa-exam-time');
        let totalSeconds = parseInt(timerElement.data('remaining'));
        const timeLimit = parseInt(timerElement.data('time-limit'));
        const startTime = timerElement.data('start-time');

        // Function to update timer display
        function updateTimerDisplay(seconds) {
            const displayMinutes = Math.floor(seconds / 60);
            const displaySeconds = seconds % 60;
            timerElement.text(
                displayMinutes.toString().padStart(2, '0') + ':' +
                displaySeconds.toString().padStart(2, '0')
            );

            // Visual feedback based on remaining time
            if (seconds <= 300) { // 5 minutes
                timerElement.css('color', '#d63638');
                timerElement.addClass('pulse');

                if (seconds === 300) {
                    showTimeWarning('5 minutes remaining!');
                } else if (seconds === 120) {
                    showTimeWarning('2 minutes remaining!');
                } else if (seconds === 60) {
                    showTimeWarning('1 minute remaining!');
                }
            } else {
                timerElement.css('color', '');
                timerElement.removeClass('pulse');
            }
        }

        function showTimeWarning(message) {
            const $warning = $(`<div class="lwa-time-warning">${message}</div>`);
            $('body').append($warning);
            setTimeout(() => $warning.addClass('show'), 10);
            setTimeout(() => {
                $warning.removeClass('show');
                setTimeout(() => $warning.remove(), 500);
            }, 3000);
        }

        // Function to sync with server time
        function syncServerTime() {
            $.ajax({
                url: lwa_exams.ajax_url,
                type: 'POST',
                data: {
                    action: 'lwa_get_remaining_time',
                    attempt_id: $('input[name="attempt_id"]').val(),
                    nonce: lwa_exams.nonce
                },
                success: function (response) {
                    if (response.success) {
                        totalSeconds = response.data.remaining_time;
                        updateTimerDisplay(totalSeconds);
                    }
                }
            });
        }

        // Initial sync
        syncServerTime();

        // Sync every 30 seconds to prevent client-side drift
        setInterval(syncServerTime, 30000);

        const timer = setInterval(function () {
            totalSeconds--;
            updateTimerDisplay(totalSeconds);

            if (totalSeconds <= 0) {
                clearInterval(timer);
                // alert('Time is up! Your exam will be submitted automatically.');
                window.onbeforeunload = null;
                submitExam();
                return;
            }
        }, 1000);
    }

    // Submit Exam Form
    $(document).on('submit', '#lwa-exam-form', function (e) {
        e.preventDefault();

        if (confirm('Are you sure you want to submit your exam? You cannot change your answers after submission.')) {
            submitExam();
        }
    });

    // In the submitExam() function, update the answer processing logic:
    function submitExam() {
        if (window.lwa_exam_submitted) return; // Prevent double submission
        window.lwa_exam_submitted = true;
        window.onbeforeunload = null; // Clear warning first

        const form = $('#lwa-exam-form');
        const formData = form.serializeArray();
        const answers = {};

        // Process form data
        $.each(formData, function (i, field) {
            if (field.name.startsWith('answers')) {
                // Extract question ID from name like "answers[123][]"
                const matches = field.name.match(/\[(\d+)\](\[\])?/);
                if (matches && matches[1]) {
                    const questionId = matches[1];
                    if (!answers[questionId]) {
                        answers[questionId] = [];
                    }

                    // For Fill in the Blank, we need to maintain the array structure
                    if (matches[2] === '[]') {
                        answers[questionId].push(field.value);
                    } else {
                        // For other question types (radio/checkbox)
                        if ($.isArray(answers[questionId])) {
                            answers[questionId].push(field.value);
                        } else {
                            answers[questionId] = field.value;
                        }
                    }
                }
            }
        });

        $.ajax({
            url: lwa_exams.ajax_url,
            type: 'POST',
            data: {
                action: 'lwa_submit_exam',
                attempt_id: $('input[name="attempt_id"]').val(),
                answers: answers,
                nonce: lwa_exams.nonce
            },
            beforeSend: function () {
                $('.lwa-submit-exam').prop('disabled', true).text('Submitting...');
            },
            success: function (response) {
                if (response.success) {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('action', 'results');
                    currentUrl.searchParams.set('attempt_id', response.data.attempt_id);
                    window.location.href = currentUrl.toString();
                } else {
                    alert(response.data);
                    $('.lwa-submit-exam').prop('disabled', false).text('Submit Exam');
                }
            },
            error: function (xhr, status, error) {
                console.error('Submission error:', xhr.responseText);
                alert('An error occurred. Please try again.');
                $('.lwa-submit-exam').prop('disabled', false).text('Submit Exam');
            }
        });
    }


    // Prevent accidental navigation away from exam
    if ($('#lwa-exam-form').length) {
        window.onbeforeunload = function () {

            return 'Are you sure you want to leave? Your exam progress will be lost.';
        };

        // Remove the warning when form is submitted
        $(document).on('submit', '#lwa-exam-form', function () {
            window.onbeforeunload = null;
        });
    }

    // Single question navigation
    if ($('.lwa-question').length) {
        const totalQuestions = $('.lwa-question').length;
        let currentQuestion = 1;
        const attemptedQuestions = new Set();

        // Track when an answer is selected
        $(document).on('change', 'input[type="radio"], input[type="checkbox"], input[type="text"]', function () {
            const questionId = $(this).closest('.lwa-question').data('question-id');
            attemptedQuestions.add(questionId);
            if ($(this).is(':checkbox') && !$(this).is(':checked')) {
                const questionId = $(this).closest('.lwa-question').data('question-id');
                const otherInputs = $(this).closest('.lwa-question').find('input:checked');
                if (otherInputs.length === 0) attemptedQuestions.delete(questionId);
                updateProgress();
            }

        });



        // Navigation handlers
        $('.lwa-next-question').on('click', function () {
            if (currentQuestion < totalQuestions) {
                navigateToQuestion(currentQuestion + 1);
            }
        });

        $('.lwa-prev-question').on('click', function () {
            if (currentQuestion > 1) {
                navigateToQuestion(currentQuestion - 1);
            }
        });

        function navigateToQuestion(index) {
            $('.lwa-question.active').removeClass('active');
            currentQuestion = index;
            $(`.lwa-question[data-question-index="${currentQuestion}"]`).addClass('active');
            updateNavigation();
        }

        // Add keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' && !prevBtn.disabled) prevBtn.click();
            if (e.key === 'ArrowRight' && !nextBtn.disabled) nextBtn.click();
        });

        function updateProgress() {
            const progressPercent = (attemptedQuestions.size / totalQuestions) * 100;
            $('.lwa-progress-completed').css('width', progressPercent + '%');

            // Update progress text to show attempted count
            $('.lwa-progress-text').html(`
                    <span class="lwa-attempted-count">${attemptedQuestions.size}</span>/${totalQuestions} questions answered
                    <span class="lwa-current-position">(Viewing question ${currentQuestion})</span>
                `);

            // Add to updateProgress()
            $('.lwa-progress-completed')
                .toggleClass('halfway', progressPercent >= 50)
                .toggleClass('almost-done', progressPercent >= 75);
        }

        function updateNavigation() {
            // Update button states
            $('.lwa-prev-question').prop('disabled', currentQuestion === 1);

            if (currentQuestion === totalQuestions) {
                $('.lwa-next-question').hide();
                $('.lwa-submit-exam').addClass('visible');
            } else {
                $('.lwa-next-question').show();
                $('.lwa-submit-exam').removeClass('visible');
            }

            updateProgress();
        }

        // Initialize
        updateNavigation();
    }



});



