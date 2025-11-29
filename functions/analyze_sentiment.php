<?php
/**
 * Sentiment Analysis Function
 * Rule-based sentiment analysis using positive and negative word lists
 * Supports both English and Bangla words
 * 
 * @param string $text The text to analyze
 * @return float Sentiment score from -1.0 (negative) to +1.0 (positive)
 * 
 * Example usage:
 * $score = analyze_sentiment("This phone is amazing and excellent!");
 * // Returns: 0.8 (positive)
 * 
 * $score = analyze_sentiment("Terrible battery life, very disappointed");
 * // Returns: -0.7 (negative)
 */

// Cache for word lists to avoid repeated file reads
$SENTIMENT_CACHE = null;

function analyze_sentiment(string $text): float {
    global $SENTIMENT_CACHE;
    
    // Load word lists on first call
    if ($SENTIMENT_CACHE === null) {
        $SENTIMENT_CACHE = load_sentiment_wordlists();
    }
    
    // Tokenize text: lowercase, remove punctuation, split by whitespace
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text); // Keep letters, numbers, spaces
    $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    if (empty($tokens)) {
        return 0.0;
    }
    
    // Count positive and negative matches
    $positive_count = 0;
    $negative_count = 0;
    
    foreach ($tokens as $token) {
        if (in_array($token, $SENTIMENT_CACHE['positive'])) {
            $positive_count++;
        }
        if (in_array($token, $SENTIMENT_CACHE['negative'])) {
            $negative_count++;
        }
    }
    
    $total_matches = $positive_count + $negative_count;
    
    // If no sentiment words found, return neutral
    if ($total_matches == 0) {
        return 0.0;
    }
    
    // Calculate sentiment score
    // score = (positive - negative) / total_matches
    // Range: -1.0 to +1.0
    $score = ($positive_count - $negative_count) / $total_matches;
    
    // Clamp to [-1, 1] range
    return max(-1.0, min(1.0, $score));
}

/**
 * Load positive and negative word lists from files
 */
function load_sentiment_wordlists(): array {
    $base_dir = __DIR__ . '/../data/';
    
    // Load positive words
    $positive_file = $base_dir . 'positive.txt';
    $positive_words = [];
    if (file_exists($positive_file)) {
        $content = file_get_contents($positive_file);
        $positive_words = array_filter(array_map('trim', explode("\n", $content)));
        // Convert to lowercase for matching
        $positive_words = array_map(function($word) {
            return mb_strtolower($word, 'UTF-8');
        }, $positive_words);
    }
    
    // Load negative words
    $negative_file = $base_dir . 'negative.txt';
    $negative_words = [];
    if (file_exists($negative_file)) {
        $content = file_get_contents($negative_file);
        $negative_words = array_filter(array_map('trim', explode("\n", $content)));
        // Convert to lowercase for matching
        $negative_words = array_map(function($word) {
            return mb_strtolower($word, 'UTF-8');
        }, $negative_words);
    }
    
    return [
        'positive' => $positive_words,
        'negative' => $negative_words
    ];
}

/**
 * Get sentiment label from score
 */
function get_sentiment_label(float $score): string {
    if ($score > 0.3) return 'Positive';
    if ($score < -0.3) return 'Negative';
    return 'Neutral';
}
?>