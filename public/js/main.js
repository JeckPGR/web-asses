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
    .then(response => response.text()) // Use .text() to get raw response
    .then(text => {
        try {
            const data = JSON.parse(text); // Attempt to parse JSON
            if (data.status === 'error') {
                console.error(data.message);
                resultsDiv.innerHTML = data.message;
            } else {
                console.log(data.message);
                resultsDiv.innerHTML = `<p>${data.message}</p>`;
            }
        } catch (error) {
            console.error('Parsing error:', error);
            console.error('Server response:', text); // Log the raw response for debugging
            resultsDiv.innerHTML = `Error: Invalid response from server.`;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        resultsDiv.innerHTML = `Error: ${error}`;
    });
    
    
});
