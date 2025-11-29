<?php
/**
 * Score Computation Function
 * Calculates item score based on ratings, sentiment, engagement, and recency
 * 
 * Formula: score = 0.5*rating + 0.25*sentiment + 0.15*engagement + 0.10*recency
 * Returns: score value 0-100
 */

function compute_score(PDO $pdo, int $item_id): float {
    try {
        // Get review statistics in a single query
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as review_count,
                AVG(rating) as avg_rating,
                AVG(sentiment_score) as avg_sentiment,
                MAX(created_at) as latest_review
            FROM reviews 
            WHERE item_id = ?
        ");
        $stmt->execute([$item_id]);
        $stats = $stmt->fetch();
        
        // If no reviews, return 0
        if ($stats['review_count'] == 0) {
            update_scores_table($pdo, $item_id, 0);
            return 0.0;
        }
        
        // Get engagement metrics (likes, dislikes, comments)
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN l.like_type = 1 THEN 1 ELSE 0 END), 0) as likes,
                COALESCE(SUM(CASE WHEN l.like_type = -1 THEN 1 ELSE 0 END), 0) as dislikes,
                (SELECT COUNT(*) FROM comments c 
                 JOIN reviews r ON c.review_id = r.review_id 
                 WHERE r.item_id = ?) as comments
            FROM reviews r
            LEFT JOIN likes l ON r.review_id = l.review_id
            WHERE r.item_id = ?
        ");
        $stmt->execute([$item_id, $item_id]);
        $engagement = $stmt->fetch();
        
        // 1. Rating component (normalized to 0-1, then 0-100)
        // Average rating is 1-5, normalize to 0-1
        $rating_norm = ($stats['avg_rating'] - 1) / 4; // (rating - min) / (max - min)
        
        // 2. Sentiment component (normalized to 0-1)
        // Sentiment is -1 to 1, map to 0-1
        $sentiment_norm = ($stats['avg_sentiment'] + 1) / 2;
        
        // 3. Engagement component (logarithmic scale, normalized)
        // engagement = log(1 + likes - dislikes + comments)
        $engagement_value = log(1 + $engagement['likes'] - $engagement['dislikes'] + $engagement['comments']);
        // Normalize: assume max engagement of ~100 interactions gives log(101) ≈ 4.6
        $engagement_norm = min($engagement_value / 4.6, 1.0);
        
        // 4. Recency component
        // Calculate days since latest review
        $now = new DateTime();
        $latest = new DateTime($stats['latest_review']);
        $days_since = $now->diff($latest)->days;
        // recency = 1 / (1 + days/30) - decays over month
        $recency_norm = 1 / (1 + ($days_since / 30));
        
        // Combined weighted score (0-1 scale)
        $score_01 = (
            0.5 * $rating_norm +      // 50% weight on rating
            0.25 * $sentiment_norm +  // 25% weight on sentiment
            0.15 * $engagement_norm + // 15% weight on engagement
            0.10 * $recency_norm      // 10% weight on recency
        );
        
        // Scale to 0-100
        $score = $score_01 * 100;
        
        // Update scores table with computed value
        update_scores_table($pdo, $item_id, $score);
        
        return round($score, 2);
        
    } catch (PDOException $e) {
        error_log("Compute Score Error for item $item_id: " . $e->getMessage());
        return 0.0;
    }
}

/**
 * Update or insert score in scores table
 */
function update_scores_table(PDO $pdo, int $item_id, float $score): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO scores (item_id, score_value, last_updated) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                score_value = VALUES(score_value),
                last_updated = NOW()
        ");
        $stmt->execute([$item_id, $score]);
    } catch (PDOException $e) {
        error_log("Update Scores Table Error: " . $e->getMessage());
    }
}

/**
 * Get cached score or compute if stale (older than 1 hour)
 */
function get_or_compute_score(PDO $pdo, int $item_id): float {
    try {
        $stmt = $pdo->prepare("
            SELECT score_value, last_updated 
            FROM scores 
            WHERE item_id = ?
        ");
        $stmt->execute([$item_id]);
        $cached = $stmt->fetch();
        
        // If no cache or older than 1 hour, recompute
        if (!$cached || strtotime($cached['last_updated']) < strtotime('-1 hour')) {
            return compute_score($pdo, $item_id);
        }
        
        return (float)$cached['score_value'];
    } catch (PDOException $e) {
        error_log("Get Score Error: " . $e->getMessage());
        return compute_score($pdo, $item_id);
    }
}
?>