# Smart Review System

A comprehensive review and ranking platform for products (mobile phones) with intelligent scoring, sentiment analysis, and comparison features.

## Features

- **User Authentication**: Secure signup/login with password hashing
- **Review System**: Post reviews with ratings (1-5 stars) and text
- **Sentiment Analysis**: Automatic sentiment detection (supports English and Bangla)
- **Smart Ranking**: Advanced scoring algorithm based on:
  - Average rating (50%)
  - Sentiment score (25%)
  - Engagement (likes, dislikes, comments) (15%)
  - Recency (10%)
- **Search Engine**: Find products with ranked results
- **Product Comparison**: Side-by-side comparison with pros/cons
- **Interactive Features**: Like/dislike reviews, post comments (AJAX)

## Tech Stack

- **Backend**: PHP 7.4+ (no frameworks)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Server**: Apache (XAMPP/WAMP)

## Project Structure

```
smart-review-system/
├── database/
│   └── database.sql          # Database schema and seed data
├── config/
│   └── db.php               # Database connection
├── auth/
│   ├── signup.php           # User registration
│   ├── login.php            # User login
│   └── logout.php           # Logout handler
├── api/
│   ├── post_review.php      # Post new review
│   ├── get_reviews.php      # Fetch reviews for item
│   ├── post_comment.php     # Post comment on review
│   ├── like_review.php      # Like/dislike review
│   ├── search.php           # Search products
│   └── compare.php          # Compare two products
├── functions/
│   ├── compute_score.php    # Score calculation algorithm
│   └── analyze_sentiment.php # Sentiment analysis
├── public/
│   ├── index.php            # Home page
│   ├── search_results.php   # Search results page
│   ├── item.php             # Product details page
│   ├── compare.php          # Comparison page
│   ├── css/style.css        # Styles
│   └── js/main.js           # JavaScript functions
├── data/
│   ├── positive.txt         # Positive sentiment words
│   └── negative.txt         # Negative sentiment words
├── README.md
└── presentation_slides.md
```

## Setup Instructions

### 1. Prerequisites
- XAMPP/WAMP installed (Apache + MySQL)
- PHP 7.4 or higher
- Modern web browser

### 2. Installation Steps

1. **Start XAMPP/WAMP**
   - Start Apache and MySQL services

2. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database named `smart_review_system`
   - Click on the database, go to "Import" tab
   - Select `database/database.sql` file and click "Go"

3. **Copy Project Files**
   - Copy the `smart-review-system` folder to:
     - XAMPP: `C:/xampp/htdocs/`
     - WAMP: `C:/wamp64/www/`

4. **Configure Database Connection**
   - Open `config/db.php`
   - Update database credentials if needed (default works for XAMPP/WAMP):
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'smart_review_system');
     define('DB_USER', 'root');
     define('DB_PASS', ''); // Empty for XAMPP/WAMP
     ```

5. **Access Application**
   - Open browser and go to: `http://localhost/smart-review-system/public/`

### 3. Demo Credentials

Use these credentials to login:
- **Email**: john@example.com
- **Password**: password123

Other test users:
- jane@example.com / password123
- ahmed@example.com / password123
- fatima@example.com / password123
- michael@example.com / password123

## API Endpoints

All API endpoints return JSON responses.

### POST /api/post_review.php
Post a new review
```json
{
  "item_id": 1,
  "rating": 5,
  "review_text": "Great phone!"
}
```

### GET /api/get_reviews.php?item_id=1
Get all reviews for an item

### POST /api/post_comment.php
Add comment to a review
```json
{
  "review_id": 1,
  "comment_text": "I agree!"
}
```

### POST /api/like_review.php
Like or dislike a review
```json
{
  "review_id": 1,
  "like_type": 1  // 1 = like, -1 = dislike
}
```

### GET /api/search.php?q=iphone
Search products by query

### GET /api/compare.php?a=1&b=2
Compare two products

## Score Calculation Formula

```
score = 0.5 * rating_norm + 0.25 * sentiment_norm + 0.15 * engagement_norm + 0.10 * recency_norm

Where:
- rating_norm: (avg_rating - 1) / 4  [0-1]
- sentiment_norm: (avg_sentiment + 1) / 2  [0-1]
- engagement_norm: log(1 + likes - dislikes + comments) / 4.6  [0-1]
- recency_norm: 1 / (1 + days_since_last_review/30)  [0-1]

Final score: scaled to 0-100
```

## Database Schema

- **users**: User accounts
- **items**: Products to review
- **reviews**: User reviews with ratings and sentiment
- **comments**: Comments on reviews
- **likes**: Like/dislike actions
- **scores**: Cached score values
- **search_logs**: Search query tracking

## Features in Detail

### Sentiment Analysis
- Rule-based approach using positive and negative word lists
- Supports English and Bangla languages
- Score range: -1 (very negative) to +1 (very positive)
- Automatic categorization: Positive, Neutral, Negative

### Smart Ranking
- Multi-factor scoring system
- Cached scores with 1-hour refresh
- Automatically recomputed on new reviews
- Considers rating quality, user sentiment, community engagement, and content freshness

### Search
- Full-text search on product title and description
- Results ranked by score
- Shows top 10 matching products
- Search logging for analytics

### Comparison
- Side-by-side view of two products
- Displays specifications, scores, ratings
- Shows top 3 pros and cons from reviews
- Direct links to detailed pages

## Troubleshooting

**Database connection error**:
- Verify MySQL is running in XAMPP/WAMP
- Check database name and credentials in `config/db.php`

**Blank page**:
- Enable PHP error display in php.ini
- Check Apache error logs

**AJAX not working**:
- Clear browser cache
- Check browser console for errors
- Verify API endpoint paths

## License

Educational project - Free to use

## Author

Smart Review System Team