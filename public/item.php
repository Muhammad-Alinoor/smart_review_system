<?php
/**
 * Item Details Page
 * Shows item info, reviews, and allows posting reviews/comments
 */

require_once '../config/db.php';
require_once '../functions/compute_score.php';

$item_id = intval($_GET['id'] ?? 0);

if ($item_id <= 0) {
    header('Location: index.php');
    exit;
}

// Get item details
$stmt = $pdo->prepare("SELECT * FROM items WHERE item_id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: index.php');
    exit;
}

// Get item score and stats
$score = get_or_compute_score($pdo, $item_id);
$stmt = $pdo->prepare("
    SELECT COUNT(*) as review_count, AVG(rating) as avg_rating, AVG(sentiment_score) as avg_sentiment
    FROM reviews WHERE item_id = ?
");
$stmt->execute([$item_id]);
$stats = $stmt->fetch();

$metadata = json_decode($item['metadata_json'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title']); ?> - Smart Review System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1 class="logo"><a href="index.php">📱 Smart Review System</a></h1>
            <div class="nav-links">
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
        <div class="item-detail">
            <div class="item-header">
                <h1><?php echo htmlspecialchars($item['title']); ?></h1>
                <p class="item-description-full"><?php echo htmlspecialchars($item['description']); ?></p>
            </div>

            <div class="item-info-grid">
                <div class="item-stats-detail">
                    <div class="stat-box">
                        <div class="stat-label">Overall Score</div>
                        <div class="stat-value"><?php echo number_format($score, 1); ?>/100</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Average Rating</div>
                        <div class="stat-value">⭐ <?php echo number_format($stats['avg_rating'], 1); ?>/5</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Sentiment</div>
                        <div class="stat-value"><?php echo number_format($stats['avg_sentiment'], 2); ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Total Reviews</div>
                        <div class="stat-value"><?php echo $stats['review_count']; ?></div>
                    </div>
                </div>

                <?php if ($metadata): ?>
                <div class="item-metadata">
                    <h3>Specifications</h3>
                    <?php foreach ($metadata as $key => $value): ?>
                        <div class="meta-row">
                            <span class="meta-key"><?php echo ucfirst(htmlspecialchars($key)); ?>:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($value); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="review-form-container">
                <h3>Write a Review</h3>
                <form id="reviewForm">
                    <div class="form-group">
                        <label>Rating (required)</label>
                        <div class="rating-input-select">
                            <select name="rating" id="ratingSelect" required>
                                <option value="">Select rating...</option>
                                <option value="5">⭐⭐⭐⭐⭐ (5 stars)</option>
                                <option value="4">⭐⭐⭐⭐ (4 stars)</option>
                                <option value="3">⭐⭐⭐ (3 stars)</option>
                                <option value="2">⭐⭐ (2 stars)</option>
                                <option value="1">⭐ (1 star)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reviewText">Your Review</label>
                        <textarea id="reviewText" rows="4" required minlength="10" 
                                  placeholder="Share your experience with this product... (minimum 10 characters)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            </div>
            <?php else: ?>
            <div class="login-prompt">
                <p><a href="../auth/login.php">Login</a> to write a review</p>
            </div>
            <?php endif; ?>

            <div class="reviews-section">
                <h2>Reviews</h2>
                <div id="reviewsList"></div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        const itemId = <?php echo $item_id; ?>;
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        async function loadReviews() {
            try {
                const response = await fetch(`../api/get_reviews.php?item_id=${itemId}`);
                const data = await response.json();

                if (data.success) {
                    displayReviews(data.reviews);
                }
            } catch (error) {
                console.error('Load reviews error:', error);
            }
        }

        function displayReviews(reviews) {
            const container = document.getElementById('reviewsList');
            if (reviews.length === 0) {
                container.innerHTML = '<p class="no-reviews">No reviews yet. Be the first to review!</p>';
                return;
            }

            container.innerHTML = reviews.map(review => `
                <div class="review-card" id="review_${review.review_id}">
                    <div class="review-header">
                        <div>
                            <strong>${escapeHtml(review.user_name)}</strong>
                            <span class="review-rating">${'⭐'.repeat(review.rating)}</span>
                        </div>
                        <span class="review-date">${formatDate(review.created_at)}</span>
                    </div>
                    <p class="review-text">${escapeHtml(review.review_text)}</p>
                    <div class="review-sentiment">
                        Sentiment: <span class="${getSentimentClass(review.sentiment_score)}">
                            ${review.sentiment_score > 0.3 ? 'Positive' : review.sentiment_score < -0.3 ? 'Negative' : 'Neutral'}
                            (${review.sentiment_score.toFixed(2)})
                        </span>
                    </div>
                    <div class="review-actions">
                        <button onclick="toggleLike(${review.review_id}, 1)" 
                                class="btn-like ${review.user_like === 1 ? 'active' : ''}" 
                                ${!isLoggedIn ? 'disabled' : ''}>
                            👍 <span id="likes_${review.review_id}">${review.likes}</span>
                        </button>
                        <button onclick="toggleLike(${review.review_id}, -1)" 
                                class="btn-dislike ${review.user_like === -1 ? 'active' : ''}"
                                ${!isLoggedIn ? 'disabled' : ''}>
                            👎 <span id="dislikes_${review.review_id}">${review.dislikes}</span>
                        </button>
                        <button onclick="toggleCommentForm(${review.review_id})" 
                                class="btn-comment" ${!isLoggedIn ? 'disabled' : ''}>
                            💬 Comment
                        </button>
                    </div>
                    ${isLoggedIn ? `
                    <div id="commentForm_${review.review_id}" class="comment-form" style="display:none;">
                        <textarea id="commentText_${review.review_id}" placeholder="Add a comment..." rows="2"></textarea>
                        <button onclick="postComment(${review.review_id})" class="btn btn-sm btn-primary">Post</button>
                    </div>
                    ` : ''}
                    <div class="comments-list" id="comments_${review.review_id}">
                        ${review.comments.map(c => `
                            <div class="comment">
                                <strong>${escapeHtml(c.user_name)}</strong>: ${escapeHtml(c.comment_text)}
                                <span class="comment-date">${formatDate(c.created_at)}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('');
        }

        // Handle review form submission
        document.getElementById('reviewForm').addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const rating = document.getElementById('ratingSelect').value;
            const reviewText = document.getElementById('reviewText').value.trim();

            // Validation
            if (!rating) {
                showMessage('Please select a rating', 'error');
                return;
            }

            if (reviewText.length < 10) {
                showMessage('Review must be at least 10 characters', 'error');
                return;
            }

            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            try {
                const response = await fetch('../api/post_review.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        item_id: itemId, 
                        rating: parseInt(rating), 
                        review_text: reviewText
                    })
                });
                const data = await response.json();

                if (data.success) {
                    showMessage('Review posted successfully!', 'success');
                    document.getElementById('reviewForm').reset();
                    setTimeout(() => {
                        location.reload(); // Reload to update stats
                    }, 1000);
                } else {
                    showMessage(data.message || 'Failed to post review', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Review';
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Failed to post review. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Review';
            }
        });

        async function toggleLike(reviewId, likeType) {
            try {
                const response = await fetch('../api/like_review.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({review_id: reviewId, like_type: likeType})
                });
                const data = await response.json();

                if (data.success) {
                    document.getElementById(`likes_${reviewId}`).textContent = data.likes;
                    document.getElementById(`dislikes_${reviewId}`).textContent = data.dislikes;
                    loadReviews(); // Reload to update button states
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Action failed', 'error');
            }
        }

        function toggleCommentForm(reviewId) {
            const form = document.getElementById(`commentForm_${reviewId}`);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        async function postComment(reviewId) {
            const text = document.getElementById(`commentText_${reviewId}`).value.trim();
            if (!text) return;

            try {
                const response = await fetch('../api/post_comment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({review_id: reviewId, comment_text: text})
                });
                const data = await response.json();

                if (data.success) {
                    showMessage('Comment posted!', 'success');
                    loadReviews();
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                showMessage('Failed to post comment', 'error');
            }
        }

        function getSentimentClass(score) {
            if (score > 0.3) return 'sentiment-positive';
            if (score < -0.3) return 'sentiment-negative';
            return 'sentiment-neutral';
        }

        loadReviews();
    </script>
</body>
</html>