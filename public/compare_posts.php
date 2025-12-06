<?php
/**
 * Compare User Posts
 * Side-by-side comparison of two user reviews
 */

require_once '../config/db.php';
$post_a = intval($_GET['a'] ?? 0);
$post_b = intval($_GET['b'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Reviews - Smart Review System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1 class="logo"><a href="index.php">📱 Smart Review System</a></h1>
            <div class="nav-links">
                <a href="feed.php">Feed</a>
                <a href="index.php" class="btn btn-secondary">Home</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Review Comparison</h1>
        <div id="loadingMessage" class="loading">Loading comparison...</div>
        <div id="comparisonContainer" class="comparison-grid"></div>
    </div>

    <script src="js/main.js"></script>
    <script>
        async function loadComparison() {
            const a = <?php echo $post_a; ?>;
            const b = <?php echo $post_b; ?>;

            if (!a || !b) {
                showMessage('Invalid comparison parameters', 'error');
                return;
            }

            try {
                const response = await fetch(`../api/compare_posts.php?a=${a}&b=${b}`);
                const data = await response.json();

                document.getElementById('loadingMessage').style.display = 'none';

                if (data.success) {
                    displayComparison(data.comparison);
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                console.error('Comparison error:', error);
                showMessage('Failed to load comparison', 'error');
            }
        }

        function displayComparison(posts) {
            const container = document.getElementById('comparisonContainer');
            
            container.innerHTML = `
                <div class="compare-item">
                    ${generatePostHTML(posts[0])}
                </div>
                <div class="compare-vs">VS</div>
                <div class="compare-item">
                    ${generatePostHTML(posts[1])}
                </div>
            `;
        }

        function generatePostHTML(post) {
            const rating = parseFloat(post.rating);
            const stars = '⭐'.repeat(Math.floor(rating)) + (rating % 1 >= 0.5 ? '½' : '');
            
            const tags = post.tags ? post.tags.split(' ').map(tag => 
                `<span class="post-tag">${escapeHtml(tag)}</span>`
            ).join('') : '';

            return `
                <h2>📱 ${escapeHtml(post.product_name)}</h2>
                <div class="post-rating-large">${stars} (${rating.toFixed(1)})</div>
                
                <div class="post-meta" style="margin: 1rem 0;">
                    by <strong>${escapeHtml(post.user_name)}</strong> • 
                    ${formatDate(post.created_at)}
                    ${post.is_verified ? '<span class="verified-badge">✅ Verified</span>' : ''}
                </div>
                
                <div class="compare-stats">
                    <div class="stat-box">
                        <div class="stat-label">Rating</div>
                        <div class="stat-value">${rating.toFixed(1)}/5</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Sentiment</div>
                        <div class="stat-value ${getSentimentClass(post.sentiment_score)}">
                            ${post.sentiment_score > 0.3 ? 'Positive' : post.sentiment_score < -0.3 ? 'Negative' : 'Neutral'}
                        </div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Engagement</div>
                        <div class="stat-value">👍 ${post.likes} 👎 ${post.dislikes}</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Comments</div>
                        <div class="stat-value">${post.comment_count}</div>
                    </div>
                </div>

                <div class="compare-section">
                    <h3>📝 Review</h3>
                    <p>${escapeHtml(post.post_text)}</p>
                </div>

                ${tags ? `
                <div class="compare-section">
                    <h3>🏷️ Tags</h3>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        ${tags}
                    </div>
                </div>
                ` : ''}

                ${post.category_name ? `
                <div class="compare-section">
                    <h3>📂 Category</h3>
                    <p>${escapeHtml(post.category_name)}</p>
                </div>
                ` : ''}

                <a href="post_view.php?id=${post.post_id}" class="btn btn-primary btn-block">View Full Review</a>
            `;
        }

        function getSentimentClass(score) {
            if (score > 0.3) return 'sentiment-positive';
            if (score < -0.3) return 'sentiment-negative';
            return 'sentiment-neutral';
        }

        loadComparison();
    </script>
</body>
</html>