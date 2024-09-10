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
    .then(response => response.text()) // Menggunakan .text() untuk mendapatkan respons mentah
    .then(text => {
        if (text.trim() === "") {
            throw new Error("Empty response from server");
        }
        try {
            const data = JSON.parse(text); // Parsing JSON
            if (data.status === 'error') {
                resultsDiv.innerHTML = data.message;
            } else {
                // Tampilkan daftar vulnerabilities dengan elemen HTML <ul> dan <li>
                resultsDiv.innerHTML = `<p>${data.message}</p>`;
            }
        } catch (error) {
            resultsDiv.innerHTML = "Error: Invalid response from server.";
            console.error('Parsing error:', error);
            console.error('Server response:', text); // Tampilkan respons mentah dari server
        }
    })    
    .catch(error => {
        console.error('Fetch error:', error);
        resultsDiv.innerHTML = `Error: ${error}`;
    });
    
});
