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

// Get Flight ID from URL Query Parameters
function getFlightIdFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('flight_id');
}

// Fetch and display flight details
function fetchFlightDetails() {
    const flight_id = getFlightIdFromURL();
    if (!flight_id) {
        showError('No flight ID specified.');
        return;
    }

    fetch(`../php/controllers/get_flight_details.php?flight_id=${flight_id}`, {
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.flight) {
            document.getElementById('flight-id').textContent = data.flight.flight_id;
            document.getElementById('flight-name').textContent = data.flight.name;
            document.getElementById('flight-itinerary').textContent = data.flight.itinerary;
            document.getElementById('flight-time').textContent = data.flight.flight_time;

            // Populate Pending Passengers
            const pendingList = document.getElementById('pending-passengers');
            pendingList.innerHTML = '';
            if (data.pending_passengers && data.pending_passengers.length > 0) {
                data.pending_passengers.forEach(passenger => {
                    const li = document.createElement('li');
                    li.textContent = passenger.name;
                    pendingList.appendChild(li);
                });
            } else {
                pendingList.innerHTML = '<li>No pending passengers.</li>';
            }

            // Populate Registered Passengers
            const registeredList = document.getElementById('registered-passengers');
            registeredList.innerHTML = '';
            if (data.registered_passengers && data.registered_passengers.length > 0) {
                data.registered_passengers.forEach(passenger => {
                    const li = document.createElement('li');
                    li.textContent = passenger.name;
                    registeredList.appendChild(li);
                });
            } else {
                registeredList.innerHTML = '<li>No registered passengers.</li>';
            }
        } else if (data.error) {
            showError(data.error);
        }
    })
    .catch(error => {
        console.error('Error fetching flight details:', error);
        showError('Error fetching flight details.');
    });
}

// Confirm Flight Cancellation
function confirmCancelFlight() {
    const confirmation = confirm('Are you sure you want to cancel this flight and refund all passengers?');
    if (confirmation) {
        cancelFlight();
    }
}

// Cancel Flight and Refund Passengers via AJAX
function cancelFlight() {
    const flight_id = getFlightIdFromURL();
    if (!flight_id) {
        showError('No flight ID specified.');
        return;
    }

    // Prepare form data
    const formData = new FormData();
    formData.append('flight_id', flight_id);

    fetch('../php/controllers/cancel_flight.php', {
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
            // Optionally, redirect to the Company Home page after cancellation
            setTimeout(() => {
                window.location.href = 'company_home.html';
            }, 3000);
        } else if (data.error) {
            showError(data.error);
        }
    })
    .catch(error => {
        console.error('Error cancelling flight:', error);
        showError('Error cancelling flight.');
    });
}

// Initialize Flight Details Page
function initializeFlightDetails() {
    fetchFlightDetails();
}

// Call initialization on page load
window.onload = initializeFlightDetails;