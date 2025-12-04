<?php
/**
 * Home Page
 * Shows search bar and trending items
 */

require_once '../config/db.php';

// Get top trending items
$stmt = $pdo->prepare("
    SELECT 
        i.item_id,
        i.title,
        i.description,
        i.metadata_json,
        s.score_value as score,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.review_id) as review_count
    FROM items i
    LEFT JOIN reviews r ON i.item_id = r.item_id
    LEFT JOIN scores s ON i.item_id = s.item_id
    GROUP BY i.item_id
    ORDER BY s.score_value DESC
    LIMIT 8
");
$stmt->execute();
$trending_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Review System - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1 class="logo">📱 Smart Review System</h1>
            <div class="nav-links">
                <a href="feed.php">Review Feed</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="create_post.php" class="btn btn-primary">+ Post Review</a>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-secondary">Login</a>
                    <a href="../auth/signup.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="hero">
        <div class="container">
            <h2>Find the Best Products with Smart Reviews</h2>
            <p>Search, compare, and make informed decisions</p>
            
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search for mobile phones..." 
                       onkeypress="if(event.key==='Enter') performSearch()">
                <button onclick="performSearch()" class="btn btn-primary">Search</button>
            </div>
        </div>
    </div>

    <div class="container">
        <section class="trending">
            <h2>🔥 Trending Products</h2>
            <div class="items-grid">
                <?php foreach ($trending_items as $item): 
                    $metadata = json_decode($item['metadata_json'], true);
                ?>
                <div class="item-card">
                    <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                    <p class="item-description"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</p>
                    
                    <div class="item-meta">
                        <?php if ($metadata): ?>
                            <span class="badge"><?php echo htmlspecialchars($metadata['brand']); ?></span>
                            <span class="badge">$<?php echo htmlspecialchars($metadata['price']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="item-stats">
                        <div class="rating">
                            ⭐ <?php echo number_format($item['avg_rating'], 1); ?>/5
                        </div>
                        <div class="score">
                            Score: <strong><?php echo number_format($item['score'], 1); ?></strong>
                        </div>
                        <div class="reviews">
                            <?php echo $item['review_count']; ?> reviews
                        </div>
                    </div>
                    
                    <a href="item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-primary btn-block">
                        View Details
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="features">
            <h2>Why Choose Our Platform?</h2>
            <div class="features-grid">
                <div class="feature">
                    <div class="feature-icon">🎯</div>
                    <h3>Smart Ranking</h3>
                    <p>Advanced algorithm considers rating, sentiment, engagement, and recency</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🔍</div>
                    <h3>Intelligent Search</h3>
                    <p>Find exactly what you need with our powerful search engine</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">⚖️</div>
                    <h3>Compare Products</h3>
                    <p>Side-by-side comparison with pros and cons analysis</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">💬</div>
                    <h3>Sentiment Analysis</h3>
                    <p>Automatic sentiment detection in English and Bangla</p>
                </div>
            </div>
        </section>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2024 Smart Review System. Built with PHP, MySQL, and JavaScript.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script>
        function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                window.location.href = 'search_results.php?q=' + encodeURIComponent(query);
            }
        }
    </script>
</body>
</html>