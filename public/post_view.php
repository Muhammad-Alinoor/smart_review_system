<?php
/**
 * Single Post View
 * Facebook-style detailed view with comments
 */

require_once '../config/db.php';

$post_id = intval($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header('Location: feed.php');
    exit;
}

// Get post details
$stmt = $pdo->prepare("
    SELECT 
        up.*,
        u.name as user_name,
        c.name as category_name,
        CONCAT(pc.name, ' > ', c.name) as category_path,
        COALESCE(SUM(CASE WHEN pl.like_type = 1 THEN 1 ELSE 0 END), 0) as likes,
        COALESCE(SUM(CASE WHEN pl.like_type = -1 THEN 1 ELSE 0 END), 0) as dislikes
    FROM user_posts up
    JOIN users u ON up.user_id = u.user_id
    LEFT JOIN categories c ON up.category_id = c.category_id
    LEFT JOIN categories pc ON c.parent_id = pc.category_id
    LEFT JOIN post_likes pl ON up.post_id = pl.post_id
    WHERE up.post_id = ?
    GROUP BY up.post_id
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: feed.php');
    exit;
}

// Get community insights
$stmt = $pdo->prepare("SELECT * FROM community_insights WHERE product_name = ?");
$stmt->execute([$post['product_name']]);
$insights = $stmt->fetch();

// Get comments
$stmt = $pdo->prepare("
    SELECT 
        pc.*,
        u.name as user_name,
        (SELECT COUNT(*) FROM comment_likes WHERE comment_id = pc.comment_id) as likes
    FROM post_comments pc
    JOIN users u ON pc.user_id = u.user_id
    WHERE pc.post_id = ? AND pc.parent_comment_id IS NULL
    ORDER BY pc.created_at DESC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

// Get similar reviews
$stmt = $pdo->prepare("
    SELECT up.*, u.name as user_name,
           (SELECT COUNT(*) FROM reviews WHERE item_id IN (SELECT item_id FROM items WHERE title LIKE ?)) as review_count
    FROM user_posts up
    JOIN users u ON up.user_id = u.user_id
    WHERE up.product_name LIKE ? AND up.post_id != ?
    LIMIT 3
");
$search_term = '%' . $post['product_name'] . '%';
$stmt->execute([$search_term, $search_term, $post_id]);
$similar = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['product_name']); ?> Review - Smart Review System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1 class="logo"><a href="index.php">📱 Smart Review System</a></h1>
            <div class="nav-links">
                <a href="feed.php">Feed</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
                    <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-secondary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="post-detail-container">
            <!-- Post Header -->
            <div class="post-detail-header">
                <div class="post-breadcrumb">
                    <a href="feed.php">← Back</a>
                    <span class="post-category">[<?php echo htmlspecialchars($post['category_path'] ?? 'General'); ?>]</span>
                </div>
            </div>

            <!-- Post Content -->
            <div class="post-detail-card">
                <div class="post-product-header">
                    <h1>📱 <?php echo htmlspecialchars($post['product_name']); ?></h1>
                    <div class="post-rating-large">
                        <?php 
                        $stars = str_repeat('⭐', floor($post['rating'])) . ($post['rating'] - floor($post['rating']) >= 0.5 ? '½' : '');
                        echo $stars; 
                        ?> (<?php echo number_format($post['rating'], 1); ?>)
                    </div>
                </div>

                <div class="post-meta">
                    by <strong><?php echo htmlspecialchars($post['user_name']); ?></strong> • 
                    <?php echo date('M d, Y', strtotime($post['created_at'])); ?> •
                    <?php if ($post['is_verified']): ?>
                        <span class="verified-badge">✅ Verified Purchase</span>
                    <?php endif; ?>
                </div>

                <div class="post-text-full">
                    <?php echo nl2br(htmlspecialchars($post['post_text'])); ?>
                </div>

                <?php if (!empty($post['tags'])): ?>
                <div class="post-tags">
                    <?php foreach (explode(' ', $post['tags']) as $tag): ?>
                        <span class="post-tag"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="post-actions-large">
                    <button class="post-action-btn" onclick="togglePostLike(<?php echo $post_id; ?>, 1)" 
                            <?php echo !isset($_SESSION['user_id']) ? 'disabled' : ''; ?>>
                        👍 <span id="likes_count"><?php echo $post['likes']; ?></span>
                    </button>
                    <button class="post-action-btn" onclick="togglePostLike(<?php echo $post_id; ?>, -1)"
                            <?php echo !isset($_SESSION['user_id']) ? 'disabled' : ''; ?>>
                        👎 <span id="dislikes_count"><?php echo $post['dislikes']; ?></span>
                    </button>
                    <button class="post-action-btn">
                        💬 <?php echo count($comments); ?>
                    </button>
                    <button class="post-action-btn" onclick="sharePost()">
                        🔗 Share
                    </button>
                </div>
            </div>

            <!-- Community Insights -->
            <?php if ($insights): ?>
            <div class="community-insight-detail">
                <h3>🧠 Community Insight:</h3>
                <?php if (!empty($insights['common_praise'])): ?>
                    <p class="insight-praise">
                        <strong>Most users mention:</strong> <?php echo htmlspecialchars($insights['common_praise']); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($insights['common_complaints'])): ?>
                    <p class="insight-complaint">
                        <strong>Common complaint:</strong> <?php echo htmlspecialchars($insights['common_complaints']); ?>
                    </p>
                <?php endif; ?>
                <?php 
                $positive = json_decode($insights['positive_keywords'], true);
                $negative = json_decode($insights['negative_keywords'], true);
                ?>
                <?php if (!empty($positive)): ?>
                    <div class="keyword-cloud">
                        <strong>👍 Keywords:</strong>
                        <?php foreach ($positive as $word): ?>
                            <span class="keyword positive"><?php echo htmlspecialchars($word); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($negative)): ?>
                    <div class="keyword-cloud">
                        <strong>👎 Keywords:</strong>
                        <?php foreach ($negative as $word): ?>
                            <span class="keyword negative"><?php echo htmlspecialchars($word); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Comments Section -->
            <div class="comments-section-detail">
                <h3>💬 Comments (<?php echo count($comments); ?>)</h3>

                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="comment-form-detail">
                    <textarea id="commentText" placeholder="Add your comment..." rows="3"></textarea>
                    <div class="comment-form-actions">
                        <label>
                            Your rating (optional):
                            <select id="commentRating">
                                <option value="">No rating</option>
                                <option value="5">⭐⭐⭐⭐⭐</option>
                                <option value="4">⭐⭐⭐⭐</option>
                                <option value="3">⭐⭐⭐</option>
                                <option value="2">⭐⭐</option>
                                <option value="1">⭐</option>
                            </select>
                        </label>
                        <button onclick="postComment()" class="btn btn-primary">Post Comment</button>
                    </div>
                </div>
                <?php else: ?>
                <p class="login-prompt-inline">
                    <a href="../auth/login.php">Login</a> to post a comment
                </p>
                <?php endif; ?>

                <div id="commentsList">
                    <?php foreach ($comments as $comment): ?>
                    <div class="comment-detail">
                        <div class="comment-header">
                            <span class="comment-user">🧍 <?php echo htmlspecialchars($comment['user_name']); ?></span>
                            <?php if ($comment['rating']): ?>
                                <span class="comment-rating">
                                    <?php echo str_repeat('⭐', floor($comment['rating'])); ?>
                                </span>
                            <?php endif; ?>
                            <span class="comment-date"><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></span>
                        </div>
                        <div class="comment-text">
                            <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                        </div>
                        <div class="comment-actions">
                            <button class="comment-like-btn" 
                                    onclick="likeComment(<?php echo $comment['comment_id']; ?>)"
                                    <?php echo !isset($_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                👍 <span id="comment_likes_<?php echo $comment['comment_id']; ?>"><?php echo $comment['likes']; ?></span>
                            </button>
                            <button class="comment-reply-btn">💬 Reply</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Similar Reviews -->
            <?php if (count($similar) > 0): ?>
            <div class="similar-reviews-detail">
                <h3>Similar Reviews</h3>
                <div class="similar-grid">
                    <?php foreach ($similar as $sim): ?>
                    <a href="post_view.php?id=<?php echo $sim['post_id']; ?>" class="similar-card">
                        <div class="similar-product"><?php echo htmlspecialchars($sim['product_name']); ?></div>
                        <div class="similar-rating">
                            <?php echo number_format($sim['rating'], 1); ?>⭐
                        </div>
                        <div class="similar-user">by <?php echo htmlspecialchars($sim['user_name']); ?></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        const postId = <?php echo $post_id; ?>;
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        async function togglePostLike(postId, likeType) {
            if (!isLoggedIn) {
                showMessage('Please login to like', 'error');
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
                    document.getElementById('likes_count').textContent = data.likes;
                    document.getElementById('dislikes_count').textContent = data.dislikes;
                }
            } catch (error) {
                showMessage('Failed to process like', 'error');
            }
        }

        async function postComment() {
            const text = document.getElementById('commentText').value.trim();
            const rating = document.getElementById('commentRating').value;

            if (!text) {
                showMessage('Please enter a comment', 'error');
                return;
            }

            try {
                const response = await fetch('../api/post_post_comment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        post_id: postId,
                        comment_text: text,
                        rating: rating ? parseFloat(rating) : null
                    })
                });
                const data = await response.json();

                if (data.success) {
                    showMessage('Comment posted!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Failed to post comment', 'error');
            }
        }

        async function likeComment(commentId) {
            try {
                const response = await fetch('../api/like_comment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({comment_id: commentId})
                });
                const data = await response.json();

                if (data.success) {
                    document.getElementById('comment_likes_' + commentId).textContent = data.likes;
                }
            } catch (error) {
                showMessage('Failed to like comment', 'error');
            }
        }

        function sharePost() {
            const url = window.location.href;
            if (navigator.share) {
                navigator.share({title: '<?php echo addslashes($post['product_name']); ?> Review', url: url});
            } else {
                navigator.clipboard.writeText(url);
                showMessage('Link copied!', 'success');
            }
        }
    </script>
</body>
</html>