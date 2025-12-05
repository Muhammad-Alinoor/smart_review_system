<?php
/**
 * Search Results Page
 * Displays search results with compare functionality
 */

require_once '../config/db.php';
$query = htmlspecialchars($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search: <?php echo $query; ?> - Smart Review System</title>
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
                    <a href="../auth/signup.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="search-header">
            <div class="search-box">
                <input type="text" id="searchInput" value="<?php echo $query; ?>" 
                       placeholder="Search for products..."
                       onkeypress="if(event.key==='Enter') performSearch()">
                <button onclick="performSearch()" class="btn btn-primary">Search</button>
            </div>
        </div>

        <div class="results-header">
            <h2>Search Results for "<span id="queryText"><?php echo $query; ?></span>"</h2>
            <div id="compareSection" class="compare-section" style="display: none;">
                <button onclick="compareSelected()" class="btn btn-success">Compare Selected</button>
            </div>
        </div>

        <div id="loadingMessage" class="loading">Searching...</div>
        <div id="resultsContainer" class="items-grid"></div>
        <div id="noResults" class="no-results" style="display: none;">
            <p>No results found. Try a different search term.</p>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        const selectedItems = new Set();

        async function loadSearchResults() {
            const query = '<?php echo addslashes($query); ?>';
            if (!query) {
                window.location.href = 'index.php';
                return;
            }

            try {
                const response = await fetch(`../api/search.php?q=${encodeURIComponent(query)}`);
                const data = await response.json();

                document.getElementById('loadingMessage').style.display = 'none';

                if (data.success && data.results.length > 0) {
                    displayResults(data.results);
                } else {
                    document.getElementById('noResults').style.display = 'block';
                }
            } catch (error) {
                console.error('Search error:', error);
                showMessage('Search failed. Please try again.', 'error');
            }
        }

        function displayResults(results) {
            const container = document.getElementById('resultsContainer');
            container.innerHTML = '';

            results.forEach(item => {
                const card = document.createElement('div');
                card.className = 'item-card';
                
                // Check if it's a user post or original item
                const isPost = item.result_type === 'post';
                const itemUrl = isPost ? `post_view.php?id=${item.post_id}` : `item.php?id=${item.item_id}`;
                
                let metaBadges = '';
                if (item.metadata) {
                    if (isPost) {
                        // User post metadata
                        metaBadges = `
                            <span class="badge">by ${escapeHtml(item.metadata.author)}</span>
                            ${item.metadata.verified === 'Yes' ? '<span class="badge">✅ Verified</span>' : ''}
                        `;
                    } else {
                        // Original item metadata
                        metaBadges = `
                            <span class="badge">${escapeHtml(item.metadata.brand || '')}</span>
                            <span class="badge">${escapeHtml(item.metadata.price || '')}</span>
                        `;
                    }
                }

                // Display tags if available
                let tagsHtml = '';
                if (item.tags) {
                    const tags = item.tags.split(' ').slice(0, 3); // Show first 3 tags
                    tagsHtml = '<div class="item-tags">' + 
                        tags.map(tag => `<span class="post-tag">${escapeHtml(tag)}</span>`).join('') + 
                        '</div>';
                }

                card.innerHTML = `
                    ${isPost ? '<div class="result-type-badge">User Review</div>' : '<div class="result-type-badge official">Official Item</div>'}
                    <div class="compare-checkbox" style="display: ${isPost ? 'none' : 'block'}">
                        <input type="checkbox" id="compare_${item.item_id || item.post_id}" 
                               onchange="toggleCompare(${item.item_id || item.post_id})"
                               ${isPost ? 'disabled' : ''}>
                        <label for="compare_${item.item_id || item.post_id}">Compare</label>
                    </div>
                    <h3>${escapeHtml(item.title)}</h3>
                    <p class="item-description">${escapeHtml(item.description.substring(0, 100))}...</p>
                    <div class="item-meta">${metaBadges}</div>
                    ${tagsHtml}
                    <div class="item-stats">
                        <div class="rating">⭐ ${item.avg_rating}/5</div>
                        <div class="score">Score: <strong>${parseFloat(item.score).toFixed(1)}</strong></div>
                        <div class="reviews">${item.review_count} ${isPost ? 'comments' : 'reviews'}</div>
                    </div>
                    <a href="${itemUrl}" class="btn btn-primary btn-block">View Details</a>
                `;
                container.appendChild(card);
            });
        }

        function toggleCompare(itemId) {
            if (selectedItems.has(itemId)) {
                selectedItems.delete(itemId);
            } else {
                if (selectedItems.size >= 2) {
                    showMessage('You can only compare 2 items at a time', 'error');
                    document.getElementById(`compare_${itemId}`).checked = false;
                    return;
                }
                selectedItems.add(itemId);
            }

            document.getElementById('compareSection').style.display = 
                selectedItems.size === 2 ? 'block' : 'none';
        }

        function compareSelected() {
            const items = Array.from(selectedItems);
            if (items.length === 2) {
                window.location.href = `compare.php?a=${items[0]}&b=${items[1]}`;
            }
        }

        function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                window.location.href = 'search_results.php?q=' + encodeURIComponent(query);
            }
        }

        // Load results on page load
        loadSearchResults();
    </script>
</body>
</html>