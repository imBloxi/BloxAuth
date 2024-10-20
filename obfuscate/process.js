document.getElementById('startButton').addEventListener('click', function() {
    document.getElementById('startButton').style.display = 'none';
    document.getElementById('uploadForm').style.display = 'block';
});

document.getElementById('uploadForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData();
    const fileInput = document.getElementById('script');
    formData.append('script', fileInput.files[0]);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'process.php', true);

    xhr.upload.onprogress = function(event) {
        if (event.lengthComputable) {
            const percentComplete = (event.loaded / event.total) * 100;
            document.getElementById('progress').style.display = 'block';
            document.getElementById('progressBar').style.width = percentComplete + '%';
            document.getElementById('progressText').textContent = `Uploading... ${Math.round(percentComplete)}%`;
        }
    };

    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('progress').style.display = 'none';
            document.getElementById('result').innerHTML = `<h2>Obfuscated Script:</h2><pre>${xhr.responseText}</pre>`;
        } else {
            document.getElementById('result').textContent = 'Failed to obfuscate the script. Please try again.';
        }
    };

    xhr.send(formData);
});
