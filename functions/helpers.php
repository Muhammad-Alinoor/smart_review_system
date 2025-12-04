<?php
/**
 * Helper Functions
 * Quick access functions for common operations
 */

/**
 * Get cached score for an item
 * Simple wrapper without recomputation logic
 */
function get_item_score(PDO $pdo, int $item_id): float {
    try {
        $stmt = $pdo->prepare("SELECT score_value FROM scores WHERE item_id = ?");
        $stmt->execute([$item_id]);
        $result = $stmt->fetch();
        return $result ? (float)$result['score_value'] : 0.0;
    } catch (PDOException $e) {
        error_log("Get Item Score Error: " . $e->getMessage());
        return 0.0;
    }
}

/**
 * Get average rating for an item
 */
function get_average_rating(PDO $pdo, int $item_id): float {
    try {
        $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE item_id = ?");
        $stmt->execute([$item_id]);
        $result = $stmt->fetch();
        return $result ? (float)$result['avg_rating'] : 0.0;
    } catch (PDOException $e) {
        error_log("Get Average Rating Error: " . $e->getMessage());
        return 0.0;
    }
}

/**
 * Get review count for an item
 */
function get_review_count(PDO $pdo, int $item_id): int {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE item_id = ?");
        $stmt->execute([$item_id]);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    } catch (PDOException $e) {
        error_log("Get Review Count Error: " . $e->getMessage());
        return 0;
    }
}
?>