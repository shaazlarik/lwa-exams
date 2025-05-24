<div class="lwa-exam-start-container">
    <div class="lwa-exam-start-header">
        <h2><?php echo esc_html($exam->title); ?></h2>
        <div class="lwa-exam-categories">
            <?php 
            global $wpdb;
            $exam_categories = $wpdb->get_results($wpdb->prepare("
                SELECT c.name 
                FROM {$wpdb->prefix}exam_categories ec
                JOIN {$wpdb->prefix}categories c ON ec.category_id = c.id
                WHERE ec.exam_id = %d
                ORDER BY c.name
            ", $exam->id));
            
            foreach ($exam_categories as $category): ?>
                <span class="lwa-exam-category"><?php echo esc_html($category->name); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="lwa-exam-instructions">
        <div class="lwa-instructions-card">
            <h3>📋 Exam Summary</h3>
            <ul class="lwa-exam-summary">
                <li>
                    <span class="lwa-summary-icon">⏱️</span>
                    <span><strong>Time Limit:</strong> <?php echo intval($exam->time_limit_minutes); ?> minutes</span>
                </li>
                <li>
                    <span class="lwa-summary-icon">🎯</span>
                    <span><strong>Passing Score:</strong> <?php echo intval($exam->passing_score); ?>%</span>
                </li>
                <li>
                    <span class="lwa-summary-icon">📝</span>
                    <span><strong>Question Count:</strong> <?php echo intval($exam->question_count); ?></span>

                </li>
            </ul>
        </div>
        
        <div class="lwa-instructions-card">
            <h3>📌 Instructions</h3>
            <ul class="lwa-instructions-list">
                <li>The exam must be completed in one session.</li>
                <li>You can't pause and return later.</li>
                <li>There's no penalty for wrong answers.</li>
                <li>Read each question carefully before answering.</li>
            </ul>
        </div>
    </div>
    
    <div class="lwa-exam-start-actions">
        <button id="lwa-start-exam" class="lwa-button" data-exam-id="<?php echo intval($exam->id); ?>">
            Start Exam Now
        </button>
    </div>
</div>