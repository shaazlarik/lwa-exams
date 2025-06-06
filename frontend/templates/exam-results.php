<div class="results-container">
    <h2>Exam Results: <?php echo esc_html($attempt->title); ?></h2>

    <div class="score-display <?= $attempt->passed ? 'passed' : 'failed'; ?>">
        <h2 class="title-status <?= $attempt->passed ? 'passed' : 'failed'; ?>">Exam Result: <?= $attempt->passed ? 'Passed' : 'Failed'; ?></h2>
        <div class="score-details">
            <span class="score"><?= intval($attempt->score); ?>/<?= intval($attempt->total_questions); ?></span>
            <span class="percentage"><?= number_format(intval($attempt->percentage), 2) ?>%</span>
        </div>
    </div>

    <div class="stats-container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?= gmdate("i:s", intval($attempt->time_taken_seconds)); ?></div>
                <div class="stat-label">Time Taken</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-value"><?= intval($attempt->passing_score); ?>%</div>
                <div class="stat-label">Passing Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value">
                    <?= !empty($attempt->end_time) ? date('M j, Y g:i a', strtotime($attempt->end_time)) : date('M j, Y g:i a', strtotime($attempt->start_time)); ?>
                </div>
                <div class="stat-label"><?= !empty($attempt->end_time) ? 'Completed' : 'Abandoned' ?>
                    <?php if (empty($attempt->end_time)): ?>
                        <span class="tooltip-icon" title="This exam was started but not completed.">ⓘ</span>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="progress-container">
            <div class="progress-info">
                <span>Your Performance:</span>
                <span><?= number_format(intval($attempt->percentage), 2) ?>%</span>
            </div>
            <div class="progress-bar-wrapper">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="results">Summary</button>
            <button class="tab-btn" data-tab="answers">Detailed Answers</button>
        </div>

        <div class="tab-content active" id="resultsTab">
            <h3>Performance Summary</h3>
            <div class="performance-summary">
                <p>You answered <strong><?= intval($attempt->score); ?></strong> out of <strong><?= intval($attempt->total_questions); ?></strong> questions correctly.</p>
                <p>Your score of <strong><?= number_format(intval($attempt->percentage), 2) ?>%</strong> <?= intval($attempt->percentage) >= intval($attempt->passing_score) ? 'exceeds' : 'is below' ?> the passing score of <?= intval($attempt->passing_score) ?>%.</p>
                <p>Time taken: <strong><?= gmdate("i:s", intval($attempt->time_taken_seconds)); ?></strong></p>
                <?php
                $user = get_user_by('id', $attempt->user_id);
                if ($user && !empty($user->display_name)) {
                    echo '<p>By: ' . '<strong>' . esc_html($user->display_name) . '</strong></p>';
                } else {
                    echo '<p>By user ID: ' . '<strong>' . esc_html($attempt->user_id) . '</strong></p>';
                }
                ?>
            </div>

            <div class="lwa-results-actions">
                <a href="<?php echo esc_url(add_query_arg('exam_id', $attempt->exam_id, get_permalink())); ?>" class="lwa-button">
                    Retake Exam
                </a>
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('exams'))); ?>" class="lwa-button secondary">
                    Back to Exams
                </a>
            </div>
        </div>

        <div class="tab-content" id="answersTab">

            <h3>Question Details</h3>

            <div class="questions-container">
                <?php foreach ($answers as $index => $answer): ?>
                    <div class="answer-item <?php echo $answer->is_correct ? 'correct' : 'incorrect'; ?>">
                        <div class="question-header">
                            <h3>Question: <?= $index + 1 ?></h3>


                            <div class="question-text">
                                <?php
                                // Preserve line breaks from the original question
                                $formatted_question = esc_html($answer->question_text);
                                $formatted_question = str_replace(["\r\n", "\n", "\r"], '<br>', $formatted_question);
                                echo $formatted_question;
                                ?>
                            </div>


                            <?php if ($answer->has_image && $answer->question_image): ?>
                                <div class="question-image">
                                    <img src="<?php echo esc_url($answer->question_image); ?>" alt="Question Image">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="question-options">
                            <?php if ($answer->type === 'Multiple Choice' || $answer->type === 'Multiple Select'): ?>
                                <?php
                                $has_selected_answer = !empty(trim($answer->selected_answer));
                                $selected_answers = array_filter(array_map('trim', explode(',', $answer->selected_answer)));
                                $correct_answers = array_filter(array_map('trim', explode(',', $answer->correct_answer)));

                                $wrong_selections = array_diff($selected_answers, $correct_answers);
                                $missed_correct = array_diff($correct_answers, $selected_answers);

                                if (!$has_selected_answer): ?>
                                    <div class="no-answer-message">
                                        <em>You did not attempt this question.</em>
                                    </div>
                                <?php elseif (empty($wrong_selections) && !empty($missed_correct)): ?>
                                    <div class="partial-answer-message">
                                        <em>You selected some correct options only.</em>
                                    </div>
                                <?php endif; ?>

                                <ul class="option-list">
                                    <?php foreach (['A', 'B', 'C', 'D'] as $option):
                                        $option_key = 'option_' . strtolower($option);
                                        if (!empty($answer->$option_key)): ?>
                                            <li class="option-item">
                                                <?php
                                                $is_selected = false;
                                                $is_correct_option = false;

                                                if ($answer->type === 'Multiple Select') {
                                                    $selected_answers = explode(',', $answer->selected_answer);
                                                    $correct_answers = explode(',', $answer->correct_answer);
                                                    $is_selected = in_array($option, $selected_answers);
                                                    $is_correct_option = in_array($option, $correct_answers);
                                                } else {
                                                    $is_selected = ($answer->selected_answer === $option);
                                                    $is_correct_option = ($answer->correct_answer === $option);
                                                }
                                                ?>

                                                <span class="option-letter <?php echo ($is_correct_option) ? 'correct-option' : ($is_selected ? 'user-selected' : ''); ?>">

                                                    <?php echo esc_html($option); ?>
                                                </span>

                                                <span class="option-text">
                                                    <?php echo esc_html($answer->$option_key); ?>
                                                    <?php if ($answer->{$option_key . '_image'}): ?>
                                                        <img src="<?php echo esc_url($answer->{$option_key . '_image'}); ?>" class="option-image">
                                                    <?php endif; ?>
                                                </span>

                                                <?php if ($is_selected && !$is_correct_option): ?>
                                                    <span class="user-answer-marker">X Your Answer</span>
                                                <?php endif; ?>
                                                <?php if ($is_correct_option): ?>
                                                    <span class="correct-answer-marker">✓ Correct Answer</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>

                            <?php elseif ($answer->type === 'True/False'): ?>
                                <?php if (empty(trim($answer->selected_answer))) : ?>
                                    <div class="no-answer-message">
                                        <em>You did not attempt this question.</em>
                                    </div>
                                <?php endif; ?>
                                <div class="true-false-options">
                                    <?php
                                    $has_user_answer = !empty(trim($answer->selected_answer));
                                    $user_answer = $answer->selected_answer === 'T' ? 'True' : ($answer->selected_answer === 'F' ? 'False' : null);
                                    $correct_answer = $answer->correct_answer === 'True' ? 'True' : 'False';
                                    ?>

                                    <div class="tf-option <?php echo $correct_answer === 'True' ? 'correct-option' : ''; ?> <?php echo $user_answer === 'True' ? 'user-selected' : ''; ?>">
                                        True
                                        <?php if ($has_user_answer && $user_answer === 'True' && !$answer->is_correct): ?>
                                            <span class="user-answer-marker">X Your Answer</span>
                                        <?php endif; ?>
                                        <?php if ($correct_answer === 'True'): ?>
                                            <span class="correct-answer-marker">✓ Correct Answer</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="tf-option <?php echo $correct_answer === 'False' ? 'correct-option' : ''; ?> <?php echo $user_answer === 'False' ? 'user-selected' : ''; ?>">
                                        False
                                        <?php if ($has_user_answer && $user_answer === 'False' && !$answer->is_correct): ?>
                                            <span class="user-answer-marker">X Your Answer</span>
                                        <?php endif; ?>
                                        <?php if ($correct_answer === 'False'): ?>
                                            <span class="correct-answer-marker">✓ Correct Answer</span>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            <?php elseif ($answer->type === 'Fill in the Blank'): ?>
                                <?php
                                // Process answers - ensure we have arrays even if data is null/empty
                                $selected_answers = !empty($answer->selected_answer) ?
                                    array_map('trim', explode(',', $answer->selected_answer)) :
                                    [];

                                $correct_answers = !empty($answer->correct_answer) ?
                                    array_map('trim', explode(',', $answer->correct_answer)) :
                                    [];

                                // Count how many blanks exist (based on correct answers)
                                $blank_count = count($correct_answers);

                                // Check if user attempted any of the blanks
                                $has_attempt = false;
                                if (!empty($selected_answers)) {
                                    foreach ($selected_answers as $user_answer) {
                                        if (!empty(trim($user_answer))) {
                                            $has_attempt = true;
                                            break;
                                        }
                                    }
                                }

                                // Check correctness of each answer (case-insensitive)
                                $answer_status = [];
                                $any_correct = false;
                                $all_correct = true;

                                for ($i = 0; $i < $blank_count; $i++) {
                                    $user_answer = $selected_answers[$i] ?? '';
                                    $correct_answer = $correct_answers[$i] ?? '';
                                    $is_correct = !empty($user_answer) &&
                                        !empty($correct_answer) &&
                                        (strtolower(trim($user_answer)) === strtolower(trim($correct_answer)));

                                    $answer_status[$i] = $is_correct;
                                    if ($is_correct) $any_correct = true;
                                    if (!$is_correct) $all_correct = false;
                                }
                                ?>

                                <?php if (!$has_attempt): ?>
                                    <div class="no-answer-message">
                                        <em>You did not attempt this question.</em>
                                    </div>
                                <?php elseif ($any_correct && !$all_correct): ?>
                                    <div class="partial-answer-message">
                                        <em>You provided some correct answers only.</em>
                                    </div>
                                <?php endif; ?>

                                <div class="fill-blank-answer">
                                    <?php if ($has_attempt): ?>
                                        <div class="user-answers-container">
                                            <div><strong>Your Answers:</strong></div>
                                            <?php for ($i = 0; $i < $blank_count; $i++): ?>
                                                <?php
                                                $user_answer = $selected_answers[$i] ?? '';
                                                $is_correct = $answer_status[$i] ?? false;
                                                $has_answer = !empty(trim($user_answer));
                                                ?>
                                                <div class="user-answer <?= $has_answer ? ($is_correct ? 'correct' : 'incorrect') : 'empty'; ?>">
                                                    <span class="answer-number">Blank <?= $i + 1; ?>:</span>
                                                    <?= $has_answer ? esc_html($user_answer) : '[Not answered]'; ?>
                                                    <?php if ($has_answer): ?>
                                                        <?php if ($is_correct): ?>
                                                            <span class="correct-answer-marker">✓ Correct</span>
                                                        <?php else: ?>
                                                            <span class="user-answer-marker">X Your Answer</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="correct-answers-container">
                                        <div><strong>Correct Answers:</strong></div>
                                        <?php foreach ($correct_answers as $index => $correct_answer): ?>
                                            <div class="correct-answer">
                                                <span class="answer-number">Blank <?= $index + 1; ?>:</span>
                                                <?= esc_html($correct_answer); ?>
                                                <span class="correct-answer-marker">✓</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($answer->explanation)): ?>
                            <div class="explanation">
                                <strong>Explanation:</strong> <?php echo esc_html($answer->explanation); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>




    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Tab functionality
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.dataset.tab;

                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(`${tabId}Tab`).classList.add('active');
                });
            });

            // Progress bar animation
            const progressBar = document.querySelector('.progress-bar');
            const percentage = <?= intval($attempt->percentage) ?>;
            let width = 0;
            const interval = setInterval(() => {
                if (width >= percentage) {
                    clearInterval(interval);
                } else {
                    width++;
                    progressBar.style.width = width + '%';
                    progressBar.textContent = width + '%';
                }
            }, 10);
        });
    </script>

</div>