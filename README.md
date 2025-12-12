# Play Store Review Analyzer

A web application that scrapes and analyzes Google Play Store reviews using sentiment analysis. using with HTML, CSS, JavaScript, and PHP.

## Features

- 🔍 *Scrape Reviews*: Extract reviews from any Google Play Store app
- 📊 *Sentiment Analysis*: Automatically classify reviews as positive, negative, or neutral
- 📈 *Statistics Dashboard*: View sentiment distribution and summary statistics
- 🎨 *Modern UI*: Beautiful, responsive design with smooth animations
- 🔄 *Real-time Filtering*: Filter reviews by sentiment type

## Requirements

- PHP 7.4 or higher
- cURL extension enabled in PHP
- Web server (Apache, Nginx, or PHP built-in server)
- Internet connection (for scraping Play Store)

## Installation

1. Clone or download this repository
2. Make sure PHP is installed and cURL extension is enabled
3. Place the files in your web server directory

### Using PHP Built-in Server (Development)

bash
# Navigate to the project directory
cd PlayStoreReviewApp

# Start PHP server
php -S localhost:8000

# Open browser and go to:
# http://localhost:8000/app/index.html


### Using Apache/Nginx

1. Copy the project to your web server directory (e.g., htdocs, www, or /var/www/html)
2. Ensure PHP is configured properly
3. Access via: http://localhost/PlayStoreReviewApp/app/index.html

## Usage

1. Open app/index.html in your browser
2. Enter a Google Play Store app package name or URL
   - Example package name: com.whatsapp
   - Example URL: https://play.google.com/store/apps/details?id=com.whatsapp
3. Select the number of reviews to analyze (10-500)
4. Click "Analyze Reviews"
5. View the results with sentiment analysis

## Project Structure


PlayStoreReviewApp/
├── app/
│   ├── index.html          # Home page with input form
│   ├── result.html         # Results display page
│   ├── style.css           # Stylesheet
│   └── script.js           # Frontend JavaScript
├── backend/
│   ├── api.php             # Main API endpoint
│   ├── scraper.php         # Play Store scraper class
│   └── sentiment.php       # Sentiment analysis class
└── README.md               # This file


## How It Works

1. *Frontend (HTML/CSS/JS)*:
   - User enters app ID/URL and review count
   - Sends AJAX request to PHP backend
   - Displays results with filtering capabilities

2. *Backend (PHP)*:
   - api.php: Main API endpoint that handles requests
   - scraper.php: Scrapes Google Play Store for app info and reviews
   - sentiment.php: Analyzes review text to determine sentiment

3. *Sentiment Analysis*:
   - Uses keyword-based analysis
   - Checks for positive/negative words
   - Considers negations and intensifiers
   - Returns: positive, negative, or neutral

## Limitations

- *Scraping*: Google Play Store may change their HTML structure, which could break the scraper
- *Rate Limiting*: Too many requests may result in temporary blocks
- *Sentiment Analysis*: Uses simple keyword matching. For better accuracy, consider using ML-based solutions
- *CORS*: Make sure CORS headers are properly configured if accessing from different domains

## Troubleshooting

### Reviews Not Loading
- Check if the app ID is correct
- Verify internet connection
- Check PHP error logs
- Ensure cURL is enabled: php -m | grep curl

### CORS Errors
- If accessing from file:// protocol, use a web server instead
- Check CORS headers in api.php

### PHP Errors
- Enable error reporting in PHP
- Check PHP version (7.4+ required)
- Verify all PHP files are in the correct directory

## Future Improvements

- [ ] Add caching to reduce API calls
- [ ] Implement more advanced sentiment analysis (ML-based)
- [ ] Add export functionality (CSV, PDF)
- [ ] Add charts and visualizations
- [ ] Support for multiple languages
- [ ] Add review search functionality
- [ ] Implement user authentication
- [ ] Add database storage for historical data

## License

This project is open source and available for educational purposes.

## Disclaimer

This tool is for educational purposes only. Make sure to comply with Google Play Store's Terms of Service when scraping data. Use responsibly and respect rate limits.

## Support

For issues or questions, please check:
- PHP error logs
- Browser console for JavaScript errors
- Network tab for API request/response issues 