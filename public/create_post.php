<?php
/**
 * Create Post Page
 * Facebook-style product review posting
 */

require_once '../config/db.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Check if user_posts table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_posts'");
    if ($stmt->rowCount() == 0) {
        die('
            <html>
            <head><title>Feature Not Installed</title></head>
            <body style="font-family: Arial; padding: 40px; text-align: center;">
                <h1>⚠️ User Posts Feature Not Installed</h1>
                <p>The user posts feature needs to be set up first.</p>
                <p><a href="../setup_user_posts.php" style="padding: 15px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px;">
                    Click Here to Setup Now
                </a></p>
                <p><a href="index.php">← Back to Homepage</a></p>
            </body>
            </html>
        ');
    }
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}

// Get categories
$stmt = $pdo->query("
    SELECT c.*, p.name as parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.category_id 
    ORDER BY c.parent_id, c.name
");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Smart Review System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1 class="logo"><a href="index.php">📱 Smart Review System</a></h1>
            <div class="nav-links">
                <a href="feed.php">Feed</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
                <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="create-post-container">
            <div class="breadcrumb">
                <a href="feed.php">← Back to Feed</a>
            </div>

            <h1>📝 Create a Review Post</h1>
            <p class="subtitle">Share your experience with any product</p>

            <form id="createPostForm" class="post-form">
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category_id" required>
                        <option value="">Select category...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>">
                                <?php echo $cat['parent_name'] ? $cat['parent_name'] . ' > ' : ''; ?>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" name="product_name" required 
                           placeholder="e.g., Samsung Galaxy A55 5G">
                </div>

                <div class="form-group">
                    <label for="rating">Your Rating *</label>
                    <div class="star-rating-input">
                        <input type="radio" name="rating" value="5" id="star5" required>
                        <label for="star5">⭐</label>
                        <input type="radio" name="rating" value="4" id="star4">
                        <label for="star4">⭐</label>
                        <input type="radio" name="rating" value="3" id="star3">
                        <label for="star3">⭐</label>
                        <input type="radio" name="rating" value="2" id="star2">
                        <label for="star2">⭐</label>
                        <input type="radio" name="rating" value="1" id="star1">
                        <label for="star1">⭐</label>
                    </div>
                    <div id="ratingDisplay" class="rating-display"></div>
                </div>

                <div class="form-group">
                    <label for="postText">Your Review *</label>
                    <textarea id="postText" name="post_text" rows="6" required
                              placeholder="Share your detailed experience with this product...&#10;&#10;What did you like?&#10;What could be better?&#10;Would you recommend it?"></textarea>
                    <small>Minimum 20 characters</small>
                </div>

                <div class="form-group">
                    <label for="tags">Tags (optional)</label>
                    <input type="text" id="tags" name="tags" 
                           placeholder="#Camera #Battery #Value (separate with spaces)">
                    <small>Add hashtags to help others find your review</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_verified" id="isVerified">
                        I own this product (Verified Purchase)
                    </label>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="window.history.back()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post Review</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Star rating visual feedback
        const starInputs = document.querySelectorAll('.star-rating-input input');
        const ratingDisplay = document.getElementById('ratingDisplay');
        
        starInputs.forEach(input => {
            input.addEventListener('change', function() {
                const value = this.value;
                ratingDisplay.textContent = value + '.0 ⭐'.repeat(parseInt(value));
            });
        });

        // Form submission
        document.getElementById('createPostForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                category_id: document.getElementById('category').value,
                product_name: document.getElementById('productName').value.trim(),
                rating: parseFloat(document.querySelector('input[name="rating"]:checked').value),
                post_text: document.getElementById('postText').value.trim(),
                tags: document.getElementById('tags').value.trim(),
                is_verified: document.getElementById('isVerified').checked
            };

            // Validation
            if (formData.post_text.length < 20) {
                showMessage('Review must be at least 20 characters long', 'error');
                return;
            }

            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Posting...';

            try {
                const response = await fetch('../api/create_post.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('Post created successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = 'post_view.php?id=' + data.post_id;
                    }, 1000);
                } else {
                    showMessage(data.message || 'Failed to create post', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Post Review';
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Failed to create post. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Post Review';
            }
        });
    </script>
</body>
</html>