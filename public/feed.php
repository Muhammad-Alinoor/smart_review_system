<?php
/**
 * Feed Page
 * Shows all user posts in Facebook-style feed
 */

require_once '../config/db.php';

// Check if user_posts table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_posts'");
    if ($stmt->rowCount() == 0) {
        die('
            <html>
            <head><title>Feature Not Installed</title>
            <style>
                body { font-family: Arial; padding: 40px; text-align: center; background: #f5f5f5; }
                .setup-box { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .btn { padding: 15px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px; }
            </style>
            </head>
            <body>
                <div class="setup-box">
                    <h1>⚠️ User Posts Feature Not Installed</h1>
                    <p>The review feed feature needs to be set up first.</p>
                    <p>This is a one-time setup that takes just a few seconds.</p>
                    <a href="../setup_user_posts.php" class="btn">🚀 Setup Now (Click Here)</a>
                    <p><a href="index.php">← Back to Homepage</a></p>
                </div>
            </body>
            </html>
        ');
    }
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Feed - Smart Review System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1 class="logo"><a href="index.php">📱 Smart Review System</a></h1>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="feed.php" class="active">Feed</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="create_post.php" class="btn btn-primary">+ Create Post</a>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-secondary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container feed-container">
        <div class="feed-header">
            <h1>📱 Product Reviews Feed</h1>
            <div class="feed-search-box">
                <input type="text" id="feedSearchInput" placeholder="Search posts by product, tags, or content..." 
                       onkeypress="if(event.key==='Enter') searchFeed()">
                <button onclick="searchFeed()" class="btn btn-primary">🔍</button>
            </div>
            <div class="feed-filters">
                <select id="categoryFilter" onchange="loadFeed()">
                    <option value="">All Categories</option>
                    <option value="2">Smartphones</option>
                    <option value="3">Laptops</option>
                    <option value="4">Tablets</option>
                    <option value="5">Smartwatches</option>
                </select>
                <select id="sortFilter" onchange="loadFeed()">
                    <option value="recent">Most Recent</option>
                    <option value="popular">Most Popular</option>
                    <option value="rating">Highest Rated</option>
                </select>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="quick-post-card">
            <div class="quick-post-prompt">
                <span class="user-avatar">👤</span>
                <a href="create_post.php" class="quick-post-link">
                    Share your product review...
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div id="feedContent" class="feed-posts"></div>
        
        <div id="loadingFeed" class="loading">Loading posts...</div>
        <div id="noPostsMessage" class="no-results" style="display: none;">
            <p>No posts yet. Be the first to share a review!</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="create_post.php" class="btn btn-primary">Create First Post</a>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const currentUserId = <?php echo $_SESSION['user_id'] ?? 'null'; ?>;

        async function loadFeed() {
            const category = document.getElementById('categoryFilter').value;
            const sort = document.getElementById('sortFilter').value;
            
            document.getElementById('loadingFeed').style.display = 'block';
            document.getElementById('feedContent').innerHTML = '';
            document.getElementById('noPostsMessage').style.display = 'none';

            try {
                const response = await fetch(`../api/get_feed.php?category=${category}&sort=${sort}`);
                const text = await response.text();
                
                // Try to parse JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    document.getElementById('loadingFeed').style.display = 'none';
                    showMessage('Server returned invalid response. Check console for details.', 'error');
                    return;
                }

                document.getElementById('loadingFeed').style.display = 'none';

                if (data.success && data.posts && data.posts.length > 0) {
                    displayPosts(data.posts);
                    document.getElementById('noPostsMessage').style.display = 'none';
                } else if (!data.success) {
                    document.getElementById('noPostsMessage').innerHTML = `
                        <p>${escapeHtml(data.message || 'Failed to load feed')}</p>
                        ${data.error ? `<pre style="text-align: left; background: #f8f9fa; padding: 10px; border-radius: 5px;">${escapeHtml(data.error)}</pre>` : ''}
                    `;
                    document.getElementById('noPostsMessage').style.display = 'block';
                } else {
                    document.getElementById('noPostsMessage').style.display = 'block';
                }
            } catch (error) {
                console.error('Load feed error:', error);
                document.getElementById('loadingFeed').style.display = 'none';
                document.getElementById('noPostsMessage').innerHTML = `
                    <p>Failed to load feed</p>
                    <p style="color: #e74c3c;">${escapeHtml(error.message)}</p>
                    <p><a href="../setup_user_posts.php" class="btn btn-primary">Run Setup</a></p>
                `;
                document.getElementById('noPostsMessage').style.display = 'block';
            }
        }

        function displayPosts(posts) {
            const container = document.getElementById('feedContent');
            
            posts.forEach(post => {
                const postCard = createPostCard(post);
                container.appendChild(postCard);
            });
        }

        function createPostCard(post) {
            const card = document.createElement('div');
            card.className = 'post-card';
            
            // Convert rating to number and handle half stars
            const rating = parseFloat(post.rating) || 0;
            const fullStars = Math.floor(rating);
            const hasHalfStar = (rating % 1) >= 0.5;
            const stars = '⭐'.repeat(fullStars) + (hasHalfStar ? '½' : '');
            
            const timeAgo = formatDate(post.created_at);
            const tags = post.tags ? post.tags.split(' ').map(tag => 
                `<span class="post-tag">${escapeHtml(tag)}</span>`
            ).join('') : '';

            card.innerHTML = `
                <div class="post-header">
                    <div class="post-breadcrumb">
                        <a href="feed.php">← Back</a>
                        <span class="post-category">[${escapeHtml(post.category_path || 'General')}]</span>
                        <button class="post-menu">⋮</button>
                    </div>
                </div>
                
                <div class="post-content">
                    <div class="post-product-header">
                        <h2 class="post-product-name">📱 ${escapeHtml(post.product_name)}</h2>
                        <div class="post-rating">
                            ${stars} (${rating.toFixed(1)})
                        </div>
                    </div>
                    
                    <div class="post-meta">
                        by <strong>${escapeHtml(post.user_name)}</strong> • ${timeAgo}
                        ${post.is_verified ? '<span class="verified-badge">✅ Verified</span>' : ''}
                    </div>
                    
                    <div class="post-text">
                        ${escapeHtml(post.post_text)}
                    </div>
                    
                    ${tags ? `<div class="post-tags">${tags}</div>` : ''}
                    
                    <div class="post-actions">
                        <button class="post-action-btn ${post.user_like === 1 ? 'active' : ''}" 
                                onclick="togglePostLike(${post.post_id}, 1)" ${!isLoggedIn ? 'disabled' : ''}>
                            👍 <span id="likes_${post.post_id}">${post.likes || 0}</span>
                        </button>
                        <button class="post-action-btn ${post.user_like === -1 ? 'active' : ''}" 
                                onclick="togglePostLike(${post.post_id}, -1)" ${!isLoggedIn ? 'disabled' : ''}>
                            👎 <span id="dislikes_${post.post_id}">${post.dislikes || 0}</span>
                        </button>
                        <a href="post_view.php?id=${post.post_id}" class="post-action-btn">
                            💬 ${post.comment_count || 0}
                        </a>
                        <button class="post-action-btn" onclick="sharePost(${post.post_id})">
                            🔗 Share
                        </button>
                    </div>
                    
                    ${post.has_insights ? `
                    <div class="community-insight">
                        <div class="insight-header">🧠 Community Insight:</div>
                        <div class="insight-content">
                            <p>${escapeHtml(post.insight_praise || 'Loading insights...')}</p>
                            ${post.insight_complaint ? `<p class="insight-complaint">${escapeHtml(post.insight_complaint)}</p>` : ''}
                        </div>
                        <a href="post_view.php?id=${post.post_id}" class="insight-expand">[View full review]</a>
                    </div>
                    ` : ''}
                    
                    ${post.similar_count > 0 ? `
                    <div class="similar-reviews">
                        <div class="similar-header">Similar Reviews:</div>
                        <a href="search_results.php?q=${encodeURIComponent(post.product_name)}">
                            View ${post.similar_count} more reviews
                        </a>
                    </div>
                    ` : ''}
                </div>
            `;
            
            return card;
        }

        async function togglePostLike(postId, likeType) {
            if (!isLoggedIn) {
                showMessage('Please login to like posts', 'error');
                return;
            }

            try {
                const response = await fetch('../api/like_post.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({post_id: postId, like_type: likeType})
                });
                const data = await response.json();

                if (data.success) {
                    document.getElementById(`likes_${postId}`).textContent = data.likes;
                    document.getElementById(`dislikes_${postId}`).textContent = data.dislikes;
                    loadFeed(); // Reload to update button states
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Failed to process like', 'error');
            }
        }

        function sharePost(postId) {
            const url = window.location.origin + '/smart-review-system/public/post_view.php?id=' + postId;
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this review',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url);
                showMessage('Link copied to clipboard!', 'success');
            }
        }

        function searchFeed() {
            const query = document.getElementById('feedSearchInput').value.trim();
            if (query) {
                // Clear filters when searching
                document.getElementById('categoryFilter').value = '';
                document.getElementById('sortFilter').value = 'recent';
                
                // Load search results
                loadSearchResults(query);
            } else {
                loadFeed();
            }
        }

        async function loadSearchResults(query) {
            document.getElementById('loadingFeed').style.display = 'block';
            document.getElementById('feedContent').innerHTML = '';

            try {
                const response = await fetch(`../api/search_posts.php?q=${encodeURIComponent(query)}`);
                const data = await response.json();

                document.getElementById('loadingFeed').style.display = 'none';

                if (data.success && data.results && data.results.length > 0) {
                    displayPosts(data.results);
                    document.getElementById('noPostsMessage').style.display = 'none';
                } else {
                    document.getElementById('noPostsMessage').innerHTML = 
                        `<p>No posts found for "${escapeHtml(query)}"</p>
                         <button onclick="document.getElementById('feedSearchInput').value=''; loadFeed();" class="btn btn-primary">
                            Clear Search
                         </button>`;
                    document.getElementById('noPostsMessage').style.display = 'block';
                }
            } catch (error) {
                console.error('Search error:', error);
                showMessage('Search failed', 'error');
            }
        }

        // Load feed on page load
        loadFeed();
    </script>
</body>
</html>