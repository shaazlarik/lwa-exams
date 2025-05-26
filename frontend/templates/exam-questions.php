<div class="lwa-exam-questions-container">
    <div class="lwa-exam-header">
        <h2><?php echo esc_html($exam->title); ?></h2>
        <div class="lwa-exam-timer">
            Time Remaining: <span id="lwa-exam-time"
                data-remaining="<?php echo esc_attr($timer_data['remaining_time']); ?>"
                data-time-limit="<?php echo esc_attr($timer_data['time_limit']); ?>"
                data-start-time="<?php echo esc_attr($timer_data['start_time']); ?>">
                <?php echo gmdate("i:s", $timer_data['remaining_time']); ?>
            </span>
        </div>
    </div>

    <!-- Add progress indicator -->
    <div class="lwa-exam-progress">
        <div class="lwa-progress-bar">
            <div class="lwa-progress-completed" style="width: <?php echo (1 / count($questions)) * 100; ?>%"></div>
        </div>
        <div class="lwa-progress-text">
            Question <span class="lwa-current-question">1</span> of <?php echo count($questions); ?>
        </div>
    </div>

    <form id="lwa-exam-form" method="post" autocomplete="off">
        <input type="hidden" name="attempt_id" value="<?php echo intval($attempt->id); ?>">

        <div class="lwa-questions-wrapper">
            <?php foreach ($questions as $index => $question): ?>
                <div class="lwa-question <?php echo $index === 0 ? 'active' : ''; ?>"
                    data-question-id="<?php echo intval($question->id); ?>"
                    data-question-index="<?php echo $index + 1; ?>">
                    <div class="lwa-question-header">
                        <!-- <h3>Question <?php //echo intval($question->question_number); ?></h3> -->
                        <h3>Question: <?= $index + 1 ?></h3> <!-- Always shows 1, 2, 3... -->
                    </div>

                    <div class="lwa-question-text">
                        <?php echo wp_kses_post(wpautop($question->question_text)); ?>

                        <?php if ($question->has_image && !empty($question->question_image)): ?>
                            <div class="lwa-question-image">
                                <img src="<?php echo esc_url($question->question_image); ?>" alt="Question Image">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="lwa-question-options">
                        <?php switch ($question->type):
                            case 'Multiple Choice':
                            case 'Multiple Select': ?>
                                <?php foreach (['a', 'b', 'c', 'd'] as $option):
                                    if (!empty($question->{'option_' . $option}) || !empty($question->{'option_' . $option . '_image'})): ?>
                                        <div class="lwa-option">
                                            <label>
                                                <input type="<?php echo $question->type === 'Multiple Select' ? 'checkbox' : 'radio'; ?>"
                                                    name="answers[<?php echo intval($question->id); ?>]<?php echo $question->type === 'Multiple Select' ? '[]' : ''; ?>"
                                                    value="<?php echo strtoupper($option); ?>">

                                                <span class="lwa-option-letter"><?php echo strtoupper($option); ?>.</span>

                                                <?php if (!empty($question->{'option_' . $option})): ?>
                                                    <span class="lwa-option-text"><?php echo esc_html($question->{'option_' . $option}); ?></span>
                                                <?php endif; ?>

                                                <?php if (!empty($question->{'option_' . $option . '_image'})): ?>
                                                    <div class="lwa-option-image">
                                                        <img src="<?php echo esc_url($question->{'option_' . $option . '_image'}); ?>" alt="Option Image">
                                                    </div>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php break;

                            case 'True/False': ?>
                                <div class="lwa-option">
                                    <label>
                                        <input type="radio" name="answers[<?php echo intval($question->id); ?>]" value="True">
                                        <span class="lwa-option-text">True</span>
                                    </label>
                                </div>
                                <div class="lwa-option">
                                    <label>
                                        <input type="radio" name="answers[<?php echo intval($question->id); ?>]" value="False">
                                        <span class="lwa-option-text">False</span>
                                    </label>
                                </div>
                                <?php break;

                            case 'Fill in the Blank':
                                $correct_answers = explode(',', $question->correct_answer);
                                foreach ($correct_answers as $index => $answer): ?>
                                    <div class="lwa-fib-answer">
                                        <label>
                                            <span class="lwa-fib-label">Answer <?php echo $index + 1; ?>:</span>
                                            <input type="text"
                                                name="answers[<?php echo intval($question->id); ?>][]"
                                                placeholder="Type your answer"
                                                autocomplete="off"
                                                readonly
                                                onfocus="this.removeAttribute('readonly');">
                                        </label>
                                    </div>
                        <?php endforeach;
                                break;
                        endswitch; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="lwa-exam-navigation">
            <button type="button" id="prevBtn" class="lwa-button lwa-prev-question" disabled>Previous</button>
            <button type="button" id="nextBtn" class="lwa-button lwa-next-question">Next</button>
            <button type="submit" class="lwa-button lwa-submit-exam" style="display: none;">Submit Exam</button>
        </div>
    </form>



</div>