document.getElementById('scanForm').addEventListener('submit', function(e) {
    e.preventDefault();

    let url = document.getElementById('url').value.trim();
    const resultsDiv = document.getElementById('results');

    // Jika tidak ada skema, tambahkan "https://"
    if (!url.match(/^https?:\/\//)) {
        url = 'https://' + url;
    }

    // Regular expression untuk validasi URL yang lebih fleksibel
    const urlPattern = /^(https?:\/\/)?([a-zA-Z0-9.-]+)\.([a-zA-Z]{2,6})([\/\w .-]*)*\/?$/;

    if (!urlPattern.test(url)) {
        resultsDiv.innerHTML = "Invalid URL! Please ensure your URL is in the correct format, e.g., example.com or https://example.com.";
        return;
    }

    resultsDiv.innerHTML = "Scanning...";

    fetch('../src/api/scan.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `url=${encodeURIComponent(url)}`,
    })
    .then(response => response.text()) // Use text() instead of json() to handle non-JSON responses
    .then(text => {
        try {
            const data = JSON.parse(text); // Attempt to parse the JSON
            if (data.status === 'error') {
                console.error(data.message);
                resultsDiv.innerHTML = data.message;
            } else {
                console.log(data.message);
                resultsDiv.innerHTML = `<p>${data.message}</p>`;
            }
        } catch (error) {
            // Handle JSON parsing errors
            console.error('Parsing error:', error);
            resultsDiv.innerHTML = `Error: Invalid response from server.`;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        resultsDiv.innerHTML = `Error: ${error}`;
    });
    
});
