  // Function to display feedback messages
  function showFeedback(message) {
    const feedbackDiv = document.getElementById('feedback');
    feedbackDiv.textContent = message;
    feedbackDiv.style.display = 'block';
    setTimeout(() => {
        feedbackDiv.style.display = 'none';
    }, 5000);
}

// Function to display error messages
function showError(message) {
    const errorDiv = document.getElementById('error');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}

// Handle Add Flight Form Submission via AJAX
document.getElementById('addFlightForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent default form submission

    // Gather form data
    const formData = new FormData(this);

    // Send the form data via AJAX to add_flight.php
    fetch('../php/controllers/company/add_flight.php', {
        method: 'POST',
        body: formData,
        credentials: 'include',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFeedback(data.success);
            document.getElementById('addFlightForm').reset();
        } else if (data.error) {
            showError(data.error);
        }
    })
    .catch(error => {
        console.error('Error adding flight:', error);
        showError('Error adding flight.');
    });
});