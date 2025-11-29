<?php
/**
 * Compare Page
 * Side-by-side comparison of two items
 */

require_once '../config/db.php';
$item_a = intval($_GET['a'] ?? 0);
$item_b = intval($_GET['b'] ?? 0);
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
            const a = <?php echo $item_a; ?>;
            const b = <?php echo $item_b; ?>;

            if (!a || !b) {
                showMessage('Invalid comparison parameters', 'error');
                return;
            }

            try {
                const response = await fetch(`../api/compare.php?a=${a}&b=${b}`);
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
            const metaHTML = item.metadata ? Object.entries(item.metadata).map(([key, value]) => 
                `<div class="meta-row"><span class="meta-key">${key}:</span> <span class="meta-value">${escapeHtml(value)}</span></div>`
            ).join('') : '';

            return `
                <h2>${escapeHtml(item.title)}</h2>
                <p class="item-description-full">${escapeHtml(item.description)}</p>
                
                <div class="compare-stats">
                    <div class="stat-box">
                        <div class="stat-label">Overall Score</div>
                        <div class="stat-value large">${item.score}/100</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Average Rating</div>
                        <div class="stat-value">⭐ ${item.avg_rating}/5</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Sentiment</div>
                        <div class="stat-value">${item.avg_sentiment}</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Reviews</div>
                        <div class="stat-value">${item.review_count}</div>
                    </div>
                </div>

                <div class="compare-section">
                    <h3>Specifications</h3>
                    ${metaHTML}
                </div>

                <div class="compare-section">
                    <h3>👍 Top Pros</h3>
                    ${item.pros.length > 0 ? 
                        item.pros.map(pro => `<p class="pro">✓ ${escapeHtml(pro)}</p>`).join('') :
                        '<p>No positive reviews yet</p>'
                    }
                </div>

                <div class="compare-section">
                    <h3>👎 Top Cons</h3>
                    ${item.cons.length > 0 ?
                        item.cons.map(con => `<p class="con">✗ ${escapeHtml(con)}</p>`).join('') :
                        '<p>No negative reviews yet</p>'
                    }
                </div>

                <a href="item.php?id=${item.item_id}" class="btn btn-primary btn-block">View Full Details</a>
            `;
        }

        loadComparison();
    </script>
</body>
</html>