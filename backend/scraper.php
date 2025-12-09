<?php

class PlayStoreScraper {
    private $baseUrl = 'https://play.google.com/store/apps/details';
    
    /**
     * Get app information
     */
    public function getAppInfo($appId) {
        $url = $this->baseUrl . '?id=' . urlencode($appId);
        
        $html = $this->fetchUrl($url);
        
        if (!$html) {
            throw new Exception('Failed to fetch app information');
        }
        
        $appInfo = [
            'name' => $this->extractAppName($html),
            'developer' => $this->extractDeveloper($html),
            'rating' => $this->extractRating($html),
            'ratingCount' => $this->extractRatingCount($html)
        ];
        
        return $appInfo;
    }
    
    /**
     * Get reviews for an app
     */
    public function getReviews($appId, $count = 50) {
        $reviews = [];
        $page = 0;
        $perPage = 40; // Google Play typically shows 40 reviews per page
        
        while (count($reviews) < $count && $page < 5) { // Limit to 5 pages max
            $url = $this->baseUrl . '?id=' . urlencode($appId) . '&showAllReviews=true';
            
            if ($page > 0) {
                $url .= '&reviewSortOrder=0&reviewType=0&pageNum=' . $page;
            }
            
            $html = $this->fetchUrl($url);
            
            if (!$html) {
                break;
            }
            
            $pageReviews = $this->parseReviews($html);
            
            if (empty($pageReviews)) {
                break;
            }
            
            $reviews = array_merge($reviews, $pageReviews);
            $page++;
            
            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }
        
        // Limit to requested count
        return array_slice($reviews, 0, $count);
    }
    
    /**
     * Fetch URL content using cURL
     */
    private function fetchUrl($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$html) {
            return false;
        }
        
        return $html;
    }
    
    /**
     * Extract app name from HTML
     */
    private function extractAppName($html) {
        // Try multiple patterns
        if (preg_match('/<h1[^>]class="[^"]*Fd93Bb[^"]"[^>]*>([^<]+)<\/h1>/', $html, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/', $html, $matches)) {
            return trim($matches[1]);
        }
        return 'Unknown App';
    }
    
    /**
     * Extract developer name from HTML
     */
    private function extractDeveloper($html) {
        if (preg_match('/<div[^>]class="[^"]*Vbfug[^"]"[^>]*>([^<]+)<\/div>/', $html, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/<a[^>]class="[^"]*hrTbp[^"]"[^>]*>([^<]+)<\/a>/', $html, $matches)) {
            return trim($matches[1]);
        }
        return 'Unknown Developer';
    }
    
    /**
     * Extract rating from HTML
     */
    private function extractRating($html) {
        if (preg_match('/<div[^>]class="[^"]*TT9eCd[^"]"[^>]*>([0-9.]+)<\/div>/', $html, $matches)) {
            return floatval($matches[1]);
        }
        if (preg_match('/"ratingValue":\s*([0-9.]+)/', $html, $matches)) {
            return floatval($matches[1]);
        }
        return null;
    }
    
    /**
     * Extract rating count from HTML
     */
    private function extractRatingCount($html) {
        if (preg_match('/<div[^>]class="[^"]*EHUI5b[^"]"[^>]*>([^<]+)<\/div>/', $html, $matches)) {
            $text = $matches[1];
            // Extract numbers from text like "1,234,567 reviews"
            if (preg_match('/([\d,]+)/', $text, $numMatches)) {
                return str_replace(',', '', $numMatches[1]);
            }
        }
        if (preg_match('/"ratingCount":\s*(\d+)/', $html, $matches)) {
            return intval($matches[1]);
        }
        return null;
    }
    
    /**
     * Parse reviews from HTML
     */
    private function parseReviews($html) {
        $reviews = [];
        
        // Google Play Store uses specific class names and structure
        // Pattern 1: Modern structure with specific classes
        if (preg_match_all('/<div[^>]class="[^"]*RHo1pe[^"]"[^>]>(.?)<\/div><\/div><\/div>/s', $html, $reviewBlocks, PREG_SET_ORDER)) {
            foreach ($reviewBlocks as $block) {
                $review = $this->parseReviewBlock($block[1]);
                if ($review) {
                    $reviews[] = $review;
                }
            }
        }
        
        // Pattern 2: Alternative structure
        if (empty($reviews)) {
            if (preg_match_all('/<div[^>]jscontroller="[^"]"[^>]data-review-id="[^"]"[^>]>(.?)<\/div><\/div><\/div>/s', $html, $reviewBlocks, PREG_SET_ORDER)) {
                foreach ($reviewBlocks as $block) {
                    $review = $this->parseReviewBlock($block[1]);
                    if ($review) {
                        $reviews[] = $review;
                    }
                }
            }
        }
        
        // Pattern 3: JSON-LD structured data
        if (empty($reviews)) {
            if (preg_match('/"review":\s*\[(.*?)\]/s', $html, $matches)) {
                $reviewsJson = json_decode('[' . $matches[1] . ']', true);
                if ($reviewsJson) {
                    foreach ($reviewsJson as $reviewData) {
                        if (isset($reviewData['author'], $reviewData['reviewBody'], $reviewData['reviewRating']['ratingValue'])) {
                            $reviews[] = [
                                'author' => $reviewData['author']['name'] ?? 'Anonymous',
                                'text' => $reviewData['reviewBody'] ?? '',
                                'rating' => intval($reviewData['reviewRating']['ratingValue'] ?? 5),
                                'date' => $reviewData['datePublished'] ?? date('Y-m-d')
                            ];
                        }
                    }
                }
            }
        }
        
        // Fallback: Simple text extraction
        if (empty($reviews)) {
            // Extract from common review patterns
            if (preg_match_all('/<span[^>]*>([^<]{20,500})<\/span>/', $html, $textMatches)) {
                foreach ($textMatches[1] as $index => $text) {
                    if (strlen(trim($text)) > 20 && !preg_match('/^(Download|Install|Get|Free|Paid)/i', trim($text))) {
                        $reviews[] = [
                            'author' => 'User ' . ($index + 1),
                            'text' => trim($text),
                            'rating' => 3,
                            'date' => date('Y-m-d')
                        ];
                    }
                }
            }
        }
        
        return $reviews;
    }
    
    /**
     * Parse individual review block
     */
    private function parseReviewBlock($blockHtml) {
        $review = [
            'author' => 'Anonymous',
            'text' => '',
            'rating' => 5,
            'date' => date('Y-m-d')
        ];
        
        // Extract author name
        if (preg_match('/<span[^>]class="[^"]*X43Kjb[^"]"[^>]*>([^<]+)<\/span>/', $blockHtml, $matches)) {
            $review['author'] = trim($matches[1]);
        } elseif (preg_match('/<span[^>]*>([A-Z][a-z]+ [A-Z][a-z]+)<\/span>/', $blockHtml, $matches)) {
            $review['author'] = trim($matches[1]);
        }
        
        // Extract review text
        if (preg_match('/<span[^>]jscontroller="[^"]"[^>]*>([^<]{20,})<\/span>/', $blockHtml, $matches)) {
            $review['text'] = trim(strip_tags($matches[1]));
        } elseif (preg_match('/<div[^>]class="[^"]*UD7Dzf[^"]"[^>]*>([^<]{20,})<\/div>/', $blockHtml, $matches)) {
            $review['text'] = trim(strip_tags($matches[1]));
        }
        
        // Extract rating
        if (preg_match('/aria-label="([0-5]) star/i', $blockHtml, $matches)) {
            $review['rating'] = intval($matches[1]);
        } elseif (preg_match('/"ratingValue":\s*([0-5])/', $blockHtml, $matches)) {
            $review['rating'] = intval($matches[1]);
        }
        
        // Extract date
        if (preg_match('/(\d{1,2})\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{4})/', $blockHtml, $matches)) {
            $review['date'] = $matches[3] . '-' . $this->monthToNumber($matches[2]) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }
        
        // Only return if we have at least some text
        if (!empty($review['text']) && strlen($review['text']) > 10) {
            return $review;
        }
        
        return null;
    }
    
    /**
     * Convert month name to number
     */
    private function monthToNumber($month) {
        $months = [
            'Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04',
            'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
            'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'
        ];
        return $months[$month] ?? '01';
    }
}

?>