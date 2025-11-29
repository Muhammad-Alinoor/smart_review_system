# Smart Review System - Presentation Slides

## Slide 1: Problem Statement

### The Challenge
- **Information Overload**: Consumers face thousands of reviews for products
- **Biased Reviews**: Difficult to distinguish genuine reviews from fake ones
- **No Standardization**: Different platforms use different rating systems
- **Time-Consuming**: Comparing products manually takes hours

### Our Solution
A smart review system that:
- Automatically analyzes sentiment in reviews
- Ranks products using intelligent algorithms
- Provides easy product comparison
- Multilingual support (English + Bangla)

---

## Slide 2: System Architecture

### Three-Tier Architecture

```
┌─────────────────────────────────────┐
│     Presentation Layer              │
│  HTML5, CSS3, JavaScript (AJAX)    │
└─────────────────────────────────────┘
              ↕
┌─────────────────────────────────────┐
│      Application Layer              │
│   PHP (RESTful APIs, Business Logic)│
│   - Authentication                  │
│   - Review Management               │
│   - Sentiment Analysis              │
│   - Score Computation               │
└─────────────────────────────────────┘
              ↕
┌─────────────────────────────────────┐
│         Data Layer                  │
│   MySQL Database (Relational)      │
│   PDO with Prepared Statements     │
└─────────────────────────────────────┘
```

### Key Technologies
- **Backend**: PHP 7.4+ (No frameworks, pure PHP)
- **Database**: MySQL 5.7+ with PDO
- **Frontend**: Vanilla JavaScript (No libraries)
- **Server**: Apache (XAMPP/WAMP)

---

## Slide 3: Database Schema

### Core Tables

```sql
users
├─ user_id (PK)
├─ name, email, password_hash
└─ created_at

items (Products)
├─ item_id (PK)
├─ title, description
├─ metadata_json (specs)
└─ created_at

reviews
├─ review_id (PK)
├─ item_id (FK), user_id (FK)
├─ rating (1-5), review_text
├─ sentiment_score (-1 to 1)
└─ created_at

comments
├─ comment_id (PK)
├─ review_id (FK), user_id (FK)
├─ comment_text
└─ created_at

likes
├─ like_id (PK)
├─ review_id (FK), user_id (FK)
├─ like_type (1=like, -1=dislike)
└─ UNIQUE(user_id, review_id)

scores (Cached)
├─ score_id (PK)
├─ item_id (FK, UNIQUE)
├─ score_value (0-100)
└─ last_updated
```

### Indexes
- Primary keys on all tables
- Foreign keys with CASCADE delete
- Index on item_id, user_id, created_at
- FULLTEXT index on items(title, description)

---

## Slide 4: Ranking Formula

### Multi-Factor Scoring Algorithm

```
Final Score = 0.5×Rating + 0.25×Sentiment + 0.15×Engagement + 0.10×Recency
```

### Component Breakdown

**1. Rating (50% weight)**
```
rating_norm = (avg_rating - 1) / 4
```
- Normalizes 1-5 star ratings to 0-1 scale

**2. Sentiment (25% weight)**
```
sentiment_norm = (avg_sentiment + 1) / 2
```
- Sentiment: -1 (negative) to +1 (positive)
- Normalized to 0-1 scale

**3. Engagement (15% weight)**
```
engagement_value = log(1 + likes - dislikes + comments)
engagement_norm = min(engagement_value / 4.6, 1.0)
```
- Logarithmic scale prevents outlier dominance
- Max normalized at ~100 interactions

**4. Recency (10% weight)**
```
recency_norm = 1 / (1 + days_since_latest_review / 30)
```
- Decays over 30-day period
- Promotes fresh content

### Caching Strategy
- Scores cached for 1 hour
- Recomputed on new reviews
- Stored in `scores` table

---

## Slide 5: Demo & Key Features

### Live Demo Flow

**1. Home Page**
```
http://localhost/smart-review-system/public/
```
- View trending products (sorted by score)
- Search functionality

**2. Search Products**
```
Search: "Samsung" → Returns ranked results
```
- Full-text search on title/description
- Results ordered by computed score

**3. View Product Details**
```
Click any product → item.php?id=2
```
- Overall score, rating, sentiment
- Specifications
- All reviews with comments
- Post new review (requires login)

**4. Compare Products**
```
Select 2 items → compare.php?a=1&b=2
```
- Side-by-side comparison
- Pros and cons analysis
- Metadata comparison

**5. Interactive Features**
- Like/dislike reviews (AJAX)
- Post comments (AJAX)
- Real-time updates

### Security Features
- Password hashing (password_hash/verify)
- PDO prepared statements (SQL injection prevention)
- XSS prevention (htmlspecialchars)
- Session-based authentication

---

## Slide 6: Sentiment Analysis

### Rule-Based Approach

**Algorithm Steps:**
1. Load positive and negative word lists
2. Tokenize review text (lowercase, remove punctuation)
3. Count matches in both lists
4. Calculate score: `(positive - negative) / total_matches`

**Example:**
```php
Review: "Amazing phone! Great camera, excellent battery life."

Tokens: [amazing, phone, great, camera, excellent, battery, life]
Positive matches: amazing, great, excellent (3)
Negative matches: 0

Score = (3 - 0) / 3 = +1.0 (Very Positive)
```

**Multilingual Support:**
- 50+ English sentiment words
- 20+ Bangla sentiment words
- Expandable wordlists (data/positive.txt, negative.txt)

**Sentiment Categories:**
- Score > 0.3: **Positive** 😊
- Score -0.3 to 0.3: **Neutral** 😐
- Score < -0.3: **Negative** 😞

---

## Slide 7: Limitations & Future Work

### Current Limitations

1. **Sentiment Analysis**
   - Simple rule-based approach
   - No context understanding
   - Limited to wordlist coverage

2. **Scalability**
   - No caching layer (Redis)
   - No CDN for static assets
   - Single server deployment

3. **Features**
   - No image uploads for reviews
   - No email verification
   - No admin panel

4. **Search**
   - Basic LIKE-based search
   - No relevance ranking beyond score

### Future Enhancements

**Phase 1: Improved AI**
- Machine learning-based sentiment (BERT, LSTM)
- Context-aware analysis
- Sarcasm detection

**Phase 2: Advanced Features**
- Image/video review uploads
- Review helpfulness voting
- Verified purchase badges
- User reputation system

**Phase 3: Scalability**
- Redis caching layer
- Elasticsearch for advanced search
- Load balancing
- Microservices architecture

**Phase 4: Analytics**
- Admin dashboard
- Fake review detection
- Trend analysis
- User behavior insights

**Phase 5: Mobile**
- Progressive Web App (PWA)
- Native mobile apps (React Native)
- Push notifications

---

## Questions & Answers

### Technical Questions Preparation

**Q: Why not use a framework like Laravel?**
A: Educational purpose - demonstrates core PHP, database interaction, and architectural patterns without framework abstractions.

**Q: How do you prevent SQL injection?**
A: PDO prepared statements with parameterized queries throughout the application.

**Q: What happens if two users like the same review simultaneously?**
A: UNIQUE constraint on (user_id, review_id) in likes table prevents duplicates. Application handles with toggle logic.

**Q: How accurate is sentiment analysis?**
A: ~70-75% accuracy with rule-based approach. Can improve to 85-90% with machine learning models.

**Q: Can the system scale to millions of reviews?**
A: Current design handles thousands. Would need caching (Redis), read replicas, and partitioning for millions.

---

## Demo Commands

### Setup (5 minutes)
```bash
# 1. Start XAMPP
# 2. Import database
http://localhost/phpmyadmin
# Import: database/database.sql into smart_review_system

# 3. Access application
http://localhost/smart-review-system/public/
```

### Test Credentials
```
Email: john@example.com
Password: password123
```

### Demo Flow (10 minutes)
1. Browse trending products (homepage)
2. Search "iPhone" - show ranked results
3. Click iPhone 15 Pro - show details
4. Login and post review with rating
5. Show sentiment score calculation
6. Like a review, post comment
7. Select 2 phones for comparison
8. Show side-by-side comparison

**End of Presentation**