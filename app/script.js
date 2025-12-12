// ---------------------------
// Play Store Review Analyzer
// ---------------------------

// UI elements
const form = document.getElementById("reviewForm");
const packageInput = document.getElementById("packageName");
const resultsDiv = document.getElementById("results");
const loader = document.getElementById("loader");

// Sentiment analyzer (simple version)
function analyzeSentiment(text) {
    const positiveWords = ["good", "great", "amazing", "excellent", "love", "fast"];
    const negativeWords = ["bad", "slow", "hate", "worst", "bug", "issue"];

    let score = 0;

    text = text.toLowerCase();

    positiveWords.forEach(word => {
        if (text.includes(word)) score++;
    });

    negativeWords.forEach(word => {
        if (text.includes(word)) score--;
    });

    if (score > 0) return "Positive";
    if (score < 0) return "Negative";
    return "Neutral";
}

// Fetch reviews from PHP backend
async function getReviews(pkg) {
    loader.style.display = "block";

    try {
        const response = await fetch("backend.php?package=" + pkg);

        if (!response.ok) {
            throw new Error("Failed to fetch reviews");
        }

        const data = await response.json();

        loader.style.display = "none";

        displayReviews(data.reviews);

    } catch (error) {
        loader.style.display = "none";
        resultsDiv.innerHTML = `<p class="error">${error.message}</p>`;
    }
}

// Display reviews + sentiment result
function displayReviews(reviews) {
    if (!reviews || reviews.length === 0) {
        resultsDiv.innerHTML = "<p>No reviews found.</p>";
        return;
    }

    let html = "<h2>Review Results</h2>";

    reviews.forEach((review, i) => {
        const sentiment = analyzeSentiment(review);

        html += `
        <div class="review-card">
            <p><strong>Review ${i + 1}:</strong> ${review}</p>
            <p class="sentiment">Sentiment: <strong>${sentiment}</strong></p>
        </div>
        `;
    });

    resultsDiv.innerHTML = html;
}

// Form submit
form.addEventListener("submit", function (e) {
    e.preventDefault();
    const pkg = packageInput.value.trim();

    if (pkg === "") {
        alert("Please enter a package name!");
        return;
    }

    getReviews(pkg);
});
