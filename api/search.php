<?php
/**
 * API: Search Items
 * Full-text search on items, returns top results sorted by score
 */

header('Content-Type: application/json');
require_once '../config/db.php';

$query = trim($_GET['q'] ?? '');

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Search query required.']);
    exit;
}

try {
    // Log search query
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO search_logs (query, user_id) VALUES (?, ?)");
        $stmt->execute([$query, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO search_logs (query) VALUES (?)");
        $stmt->execute([$query]);
    }
    
    $search_term = '%' . $query . '%';
    $results = [];
    
    // Search in original items table
    $stmt = $pdo->prepare("
        SELECT 
            i.item_id,
            i.title,
            i.description,
            i.metadata_json,
            COALESCE(s.score_value, 0) as score,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.review_id) as review_count,
            'item' as result_type
        FROM items i
        LEFT JOIN reviews r ON i.item_id = r.item_id
        LEFT JOIN scores s ON i.item_id = s.item_id
        WHERE i.title LIKE ? OR i.description LIKE ?
        GROUP BY i.item_id
        ORDER BY score DESC, avg_rating DESC
        LIMIT 5
    ");
    $stmt->execute([$search_term, $search_term]);
    $items = $stmt->fetchAll();
    
    foreach ($items as $item) {
        $item['avg_rating'] = round((float)$item['avg_rating'], 1);
        $item['score'] = round((float)$item['score'], 2);
        
        if (!empty($item['metadata_json'])) {
            $item['metadata'] = json_decode($item['metadata_json'], true);
            unset($item['metadata_json']);
        }
        
        $results[] = $item;
    }
    
    // Check if user_posts table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_posts'");
    if ($stmt->rowCount() > 0) {
        // Search in user posts (product name, post text, and tags)
        $stmt = $pdo->prepare("
            SELECT 
                up.post_id,
                up.product_name as title,
                up.post_text as description,
                up.rating as avg_rating,
                up.tags,
                up.is_verified,
                up.created_at,
                u.name as user_name,
                'post' as result_type,
                (SELECT COUNT(*) FROM post_comments WHERE post_id = up.post_id) as review_count,
                (
                    CASE 
                        WHEN up.product_name LIKE ? THEN 100
                        WHEN up.tags LIKE ? THEN 80
                        WHEN up.post_text LIKE ? THEN 60
                        ELSE 50
                    END
                ) as score
            FROM user_posts up
            JOIN users u ON up.user_id = u.user_id
            WHERE up.product_name LIKE ? 
               OR up.post_text LIKE ? 
               OR up.tags LIKE ?
            ORDER BY score DESC, up.rating DESC, up.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([
            $search_term, $search_term, $search_term,
            $search_term, $search_term, $search_term
        ]);
        $posts = $stmt->fetchAll();
        
        foreach ($posts as $post) {
            $post['avg_rating'] = round((float)$post['avg_rating'], 1);
            $post['score'] = (float)$post['score'];
            $post['review_count'] = (int)$post['review_count'];
            
            // Truncate description for search results
            if (strlen($post['description']) > 150) {
                $post['description'] = substr($post['description'], 0, 150) . '...';
            }
            
            // Create metadata from post info
            $post['metadata'] = [
                'author' => $post['user_name'],
                'verified' => $post['is_verified'] ? 'Yes' : 'No',
                'tags' => $post['tags']
            ];
            
            $results[] = $post;
        }
    }
    
    // Sort combined results by score
    usort($results, function($a, $b) {
        if ($b['score'] == $a['score']) {
            return $b['avg_rating'] <=> $a['avg_rating'];
        }
        return $b['score'] <=> $a['score'];
    });
    
    // Limit to top 10
    $results = array_slice($results, 0, 10);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'count' => count($results),
        'results' => $results
    ]);
    
} catch (PDOException $e) {
    error_log("Search Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Search failed.']);
}
?>