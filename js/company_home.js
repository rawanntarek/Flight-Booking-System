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

// Fetch and display company name
function fetchCompanyName() {
    fetch('../php/controllers/company/get_company_details.php', {
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.company) {
            document.getElementById('company-name').textContent = data.company.name;
        } else if (data.error) {
            showError(data.error);
        }
    })
    .catch(error => {
        console.error('Error fetching company details:', error);
        showError('Error fetching company details.');
    });
}

// Fetch and display all available flights
function fetchFlights() {
    fetch('../php/controllers/get_company_flights.php', {
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        const flightList = document.getElementById('flight-list');
        flightList.innerHTML = ''; // Clear existing list
        if (data.flights && data.flights.length > 0) {
            data.flights.forEach(flight => {
                const li = document.createElement('li');
                li.textContent = `${flight.name} (ID: ${flight.flight_id})`;
                li.onclick = () => openFlightDetails(flight.flight_id);
                flightList.appendChild(li);
            });
        } else {
            flightList.innerHTML = '<li>No available flights.</li>';
        }
    })
    .catch(error => {
        console.error('Error fetching flights:', error);
        const flightList = document.getElementById('flight-list');
        flightList.innerHTML = '<li>Error fetching flights.</li>';
    });
}

// Fetch and display messages from passengers
function fetchMessages() {
    fetch('../php/controllers/get_company_messages.php', {
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        const messageList = document.getElementById('message-list');
        messageList.innerHTML = ''; // Clear existing list
        if (data.messages && data.messages.length > 0) {
            data.messages.forEach(message => {
                const li = document.createElement('li');
                li.textContent = `From: ${message.passenger_name} - ${message.message_content}`;
                li.onclick = () => openReplyModal(message.message_id, message.passenger_id);
                messageList.appendChild(li);
            });
        } else {
            messageList.innerHTML = '<li>No messages received.</li>';
        }
    })
    .catch(error => {
        console.error('Error fetching messages:', error);
        const messageList = document.getElementById('message-list');
        messageList.innerHTML = '<li>Error fetching messages.</li>';
    });
}

// Open flight details page
function openFlightDetails(flight_id) {
    window.location.href = `flight_details.html?flight_id=${flight_id}`;
}

// Variables to store current message and passenger IDs for reply
let currentMessageId = null;
let currentPassengerId = null;

// Open reply modal
function openReplyModal(message_id, passenger_id) {
    currentMessageId = message_id;
    currentPassengerId = passenger_id;
    document.getElementById('replyMessage').value = '';
    document.getElementById('replyModal').style.display = 'block';
}

// Close reply modal
function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
    currentMessageId = null;
    currentPassengerId = null;
}

// Send reply to passenger
function sendReply() {
    const replyContent = document.getElementById('replyMessage').value.trim();
    if (replyContent === '') {
        showError('Reply message cannot be empty.');
        return;
    }

    // Prepare form data
    const formData = new FormData();
    formData.append('message_id', currentMessageId);
    formData.append('passenger_id', currentPassengerId);
    formData.append('reply_content', replyContent);

    fetch('../php/controllers/send_company_reply.php', {
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
            closeReplyModal();
            fetchMessages(); // Refresh messages list
        } else if (data.error) {
            showError(data.error);
        }
    })
    .catch(error => {
        console.error('Error sending reply:', error);
        showError('Error sending reply.');
    });
}

// Initialize Company Home Page
function initializeCompanyHome() {
    fetchCompanyName();
    fetchFlights();
    fetchMessages();
}

// Call initialization on page load
window.onload = initializeCompanyHome;
