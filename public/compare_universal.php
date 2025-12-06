<?php
/**
 * Universal Comparison Page
 * Compares ANY combination: Items, Posts, or Mixed
 */

require_once '../config/db.php';
$item_a = $_GET['a'] ?? '';
$item_b = $_GET['b'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Products - Smart Review System</title>
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
        <h1>Product Comparison</h1>
        <div id="loadingMessage" class="loading">Loading comparison...</div>
        <div id="comparisonContainer" class="comparison-grid"></div>
    </div>

    <script src="js/main.js"></script>
    <script>
        async function loadComparison() {
            const a = '<?php echo addslashes($item_a); ?>';
            const b = '<?php echo addslashes($item_b); ?>';

            console.log('Loading comparison:', {a, b}); // Debug

            if (!a || !b) {
                showMessage('Invalid comparison parameters', 'error');
                return;
            }

            try {
                const url = `../api/compare_universal.php?a=${encodeURIComponent(a)}&b=${encodeURIComponent(b)}`;
                console.log('Fetching:', url); // Debug
                
                const response = await fetch(url);
                const text = await response.text();
                console.log('Response text:', text); // Debug
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', text);
                    showMessage('Invalid server response', 'error');
                    document.getElementById('loadingMessage').innerHTML = 
                        '<pre style="text-align: left; background: #f8f9fa; padding: 10px;">' + 
                        escapeHtml(text) + '</pre>';
                    return;
                }

                console.log('Parsed data:', data); // Debug

                document.getElementById('loadingMessage').style.display = 'none';

                if (data.success) {
                    displayComparison(data.comparison);
                } else {
                    showMessage(data.message || 'Comparison failed', 'error');
                    document.getElementById('loadingMessage').innerHTML = 
                        `<p style="color: #e74c3c;">${escapeHtml(data.message)}</p>`;
                }
            } catch (error) {
                console.error('Comparison error:', error);
                showMessage('Failed to load comparison: ' + error.message, 'error');
                document.getElementById('loadingMessage').innerHTML = 
                    `<p style="color: #e74c3c;">Error: ${escapeHtml(error.message)}</p>`;
            }
        }

        function displayComparison(items) {
            const container = document.getElementById('comparisonContainer');
            
            container.innerHTML = `
                <div class="compare-item">
                    ${generateItemHTML(items[0])}
                </div>
                <div class="compare-vs">VS</div>
                <div class="compare-item">
                    ${generateItemHTML(items[1])}
                </div>
            `;
        }

        function generateItemHTML(item) {
            const isPost = item.type === 'post';
            const rating = parseFloat(item.rating) || 0;
            const stars = '⭐'.repeat(Math.floor(rating)) + (rating % 1 >= 0.5 ? '½' : '');

            let html = `
                <div class="compare-type-badge ${isPost ? 'user-review' : 'official-item'}">
                    ${isPost ? '👤 User Review' : '🏢 Official Item'}
                </div>
                <h2>📱 ${escapeHtml(item.title)}</h2>
                <div class="post-rating-large">${stars} (${rating.toFixed(1)})</div>
            `;

            // Common stats
            html += `
                <div class="compare-stats">
                    <div class="stat-box">
                        <div class="stat-label">Rating</div>
                        <div class="stat-value large">${rating.toFixed(1)}/5</div>
                    </div>
            `;

            if (item.sentiment !== undefined) {
                const sentClass = item.sentiment > 0.3 ? 'sentiment-positive' : 
                                 item.sentiment < -0.3 ? 'sentiment-negative' : 'sentiment-neutral';
                const sentLabel = item.sentiment > 0.3 ? 'Positive' : 
                                 item.sentiment < -0.3 ? 'Negative' : 'Neutral';
                html += `
                    <div class="stat-box">
                        <div class="stat-label">Sentiment</div>
                        <div class="stat-value ${sentClass}">${sentLabel}</div>
                    </div>
                `;
            }

            if (item.score !== undefined) {
                html += `
                    <div class="stat-box">
                        <div class="stat-label">Overall Score</div>
                        <div class="stat-value">${item.score}/100</div>
                    </div>
                `;
            }

            html += `
                    <div class="stat-box">
                        <div class="stat-label">${isPost ? 'Comments' : 'Reviews'}</div>
                        <div class="stat-value">${item.review_count || 0}</div>
                    </div>
                </div>
            `;

            // Description/Review
            html += `
                <div class="compare-section">
                    <h3>${isPost ? '📝 Review' : '📄 Description'}</h3>
                    <p>${escapeHtml(item.description)}</p>
                </div>
            `;

            // Metadata/Specs
            if (item.metadata && Object.keys(item.metadata).length > 0) {
                html += `<div class="compare-section"><h3>📊 ${isPost ? 'Details' : 'Specifications'}</h3>`;
                
                for (const [key, value] of Object.entries(item.metadata)) {
                    if (value && key !== 'author' && key !== 'tags') {
                        html += `
                            <div class="meta-row">
                                <span class="meta-key">${escapeHtml(key)}:</span>
                                <span class="meta-value">${escapeHtml(String(value))}</span>
                            </div>
                        `;
                    }
                }
                html += `</div>`;
            }

            // Tags (for posts)
            if (item.tags) {
                const tags = item.tags.split(' ').map(tag => 
                    `<span class="post-tag">${escapeHtml(tag)}</span>`
                ).join('');
                html += `
                    <div class="compare-section">
                        <h3>🏷️ Tags</h3>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            ${tags}
                        </div>
                    </div>
                `;
            }

            // Engagement (for posts)
            if (item.likes !== undefined) {
                html += `
                    <div class="compare-section">
                        <h3>📊 Engagement</h3>
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <span>👍 ${item.likes} Likes</span>
                            <span>👎 ${item.dislikes} Dislikes</span>
                        </div>
                    </div>
                `;
            }

            // Pros/Cons (for official items)
            if (item.pros && item.pros.length > 0) {
                html += `
                    <div class="compare-section">
                        <h3>👍 Top Pros</h3>
                        ${item.pros.map(pro => `<p class="pro">✓ ${escapeHtml(pro)}</p>`).join('')}
                    </div>
                `;
            }

            if (item.cons && item.cons.length > 0) {
                html += `
                    <div class="compare-section">
                        <h3>👎 Top Cons</h3>
                        ${item.cons.map(con => `<p class="con">✗ ${escapeHtml(con)}</p>`).join('')}
                    </div>
                `;
            }

            // Author info (for posts)
            if (item.user_name) {
                html += `
                    <div class="compare-section">
                        <h3>✍️ Author</h3>
                        <p><strong>${escapeHtml(item.user_name)}</strong></p>
                        <p><small>${formatDate(item.created_at)}</small></p>
                        ${item.is_verified ? '<span class="verified-badge">✅ Verified Purchase</span>' : ''}
                    </div>
                `;
            }

            // View button
            const viewUrl = isPost ? `post_view.php?id=${item.id}` : `item.php?id=${item.id}`;
            html += `<a href="${viewUrl}" class="btn btn-primary btn-block">View Full Details</a>`;

            return html;
        }

        loadComparison();
    </script>
</body>
</html>