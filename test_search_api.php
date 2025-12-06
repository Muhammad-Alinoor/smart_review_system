<?php
/**
 * Test Search API Response
 * Shows raw API response for debugging
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Search API</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; }
        input { padding: 10px; width: 300px; font-size: 16px; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
        .result-item { border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .is-post { background: #e3f2fd; }
        .is-item { background: #e8f5e9; }
    </style>
</head>
<body>
    <h1>🔍 Search API Test</h1>
    
    <div class="section">
        <h2>Test Search</h2>
        <input type="text" id="searchQuery" placeholder="Enter search term (e.g., Samsung)" value="Samsung">
        <button onclick="testSearch()">Search</button>
    </div>

    <div id="resultsSection" class="section" style="display: none;">
        <h2>API Response</h2>
        <div id="rawResponse"></div>
    </div>

    <div id="parsedSection" class="section" style="display: none;">
        <h2>Parsed Results</h2>
        <div id="parsedResults"></div>
    </div>

    <script>
        async function testSearch() {
            const query = document.getElementById('searchQuery').value;
            const url = `api/search.php?q=${encodeURIComponent(query)}`;
            
            console.log('Fetching:', url);
            
            try {
                const response = await fetch(url);
                const text = await response.text();
                
                document.getElementById('resultsSection').style.display = 'block';
                document.getElementById('rawResponse').innerHTML = '<pre>' + text + '</pre>';
                
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    if (data.success && data.results) {
                        document.getElementById('parsedSection').style.display = 'block';
                        displayParsedResults(data.results);
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    document.getElementById('parsedResults').innerHTML = '<p style="color: red;">Response is not valid JSON</p>';
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('Failed to fetch: ' + error.message);
            }
        }

        function displayParsedResults(results) {
            let html = `<p><strong>Total results: ${results.length}</strong></p>`;
            
            results.forEach((item, index) => {
                const isPost = item.result_type === 'post';
                const itemId = isPost ? item.post_id : item.item_id;
                
                html += `
                    <div class="result-item ${isPost ? 'is-post' : 'is-item'}">
                        <h3>#${index + 1}: ${item.title}</h3>
                        <p><strong>Type:</strong> ${item.result_type || 'MISSING'}</p>
                        <p><strong>Is Post?</strong> ${isPost ? 'YES' : 'NO'}</p>
                        <p><strong>Item ID:</strong> ${itemId || 'MISSING'}</p>
                        <p><strong>post_id:</strong> ${item.post_id || 'N/A'}</p>
                        <p><strong>item_id:</strong> ${item.item_id || 'N/A'}</p>
                        <p><strong>Rating:</strong> ${item.avg_rating}</p>
                        <p><strong>Score:</strong> ${item.score}</p>
                        ${item.tags ? `<p><strong>Tags:</strong> ${item.tags}</p>` : ''}
                        
                        <div style="margin-top: 10px; padding: 10px; background: ${isPost ? '#1976d2' : '#388e3c'}; color: white; border-radius: 3px;">
                            ${isPost ? '👤 USER REVIEW - Checkbox should appear' : '🏢 OFFICIAL ITEM - Checkbox should appear'}
                        </div>
                        
                        <details style="margin-top: 10px;">
                            <summary>Raw JSON</summary>
                            <pre>${JSON.stringify(item, null, 2)}</pre>
                        </details>
                    </div>
                `;
            });
            
            document.getElementById('parsedResults').innerHTML = html;
        }

        // Auto-run on page load
        window.onload = function() {
            testSearch();
        };
    </script>
</body>
</html>