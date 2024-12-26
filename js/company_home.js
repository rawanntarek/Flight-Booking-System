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
// Function to fetch and display the company logo
// Function to fetch and display the company logo

function fetchCompanyLogo() {
    fetch('../php/controllers/company/get_company_logo.php', {
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.logo_url) {
            document.getElementById('company-logo').src = data.logo_url;
            
        } else if (data.error) {
            showError(data.error);
        }
    })
    .catch(error => {
        console.error('Error fetching company logo:', error);
        showError('Error fetching company logo.');
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
                // Display flight name and ID
                li.textContent = `${flight.name} (ID: ${flight.flight_id})`;

                // Make <li> clickable
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

// This function is called when a user clicks on a flight <li>
function openFlightDetails(flight_id) {
    // Redirect to a flight details page, e.g. flight_details.html
    // Optionally pass flight_id via URL query parameter
    window.location.href = `flight_details.html?flight_id=${flight_id}`;
}

// Fetch and display messages from passengers
// ======================================
// 1) Fetch only UNREPLIED messages
function fetchUnrepliedMessages() {
    fetch('../php/controllers/get_company_unreplied_messages.php', {
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {
        const messageList = document.getElementById('message-list');
        messageList.innerHTML = '';

        if (data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <strong>From:</strong> ${msg.passenger_name}<br>
                    <strong>Message:</strong> ${msg.message_content}<br>
                    <strong>Timestamp:</strong> ${msg.timestamp}<br>
                `;
                // On click, open reply modal
                li.onclick = () => openReplyModal(msg.message_id, msg.passenger_id);
                messageList.appendChild(li);
            });
        } else {
            messageList.innerHTML = '<li>No unreplied messages.</li>';
        }
    })
    .catch(error => {
        console.error('Error fetching unreplied messages:', error);
        document.getElementById('message-list').innerHTML = '<li>Error fetching messages.</li>';
    });
}

// 2) Handle opening and closing the reply modal
let currentMessageId = null;
let currentPassengerId = null;

function openReplyModal(message_id, passenger_id) {
    currentMessageId = message_id;
    currentPassengerId = passenger_id;
    document.getElementById('replyMessage').value = '';
    document.getElementById('replyModal').style.display = 'block';
}

function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
    currentMessageId = null;
    currentPassengerId = null;
}

// 3) Send the reply
function sendReply() {
    const replyContent = document.getElementById('replyMessage').value.trim();
    if (!replyContent) {
        showError('Reply message cannot be empty.');
        return;
    }

    const formData = new FormData();
    formData.append('message_id', currentMessageId);
    formData.append('passenger_id', currentPassengerId);
    formData.append('reply_content', replyContent);

    fetch('../php/controllers/send_company_reply.php', {
        method: 'POST',
        body: formData,
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showFeedback(data.success);
            closeReplyModal();
            // Refresh the unreplied messages list:
            fetchUnrepliedMessages();
        } else if (data.error) {
            showError(data.error);
        }
    })
    .catch(error => {
        console.error('Error sending reply:', error);
        showError('Error sending reply.');
    });
}

// ======================================
// 4) Chat Implementation

// Open the chat section
function openChatSection() {
    document.getElementById('chatSection').style.display = 'block';
    fetchChatUsers();
}

// Close the chat section
function closeChatSection() {
    document.getElementById('chatSection').style.display = 'none';
}

// Fetch all unique users that have ever messaged the company
function fetchChatUsers() {
    fetch('../php/controllers/get_company_chat_users.php', { credentials: 'include' })
    .then(res => res.json())
    .then(data => {
        const usersList = document.getElementById('chatUsersList');
        usersList.innerHTML = '';

        if (data.users && data.users.length > 0) {
            data.users.forEach(user => {
                // user.user_id, user.name
                const li = document.createElement('li');
                li.style.cursor = 'pointer';
                li.innerHTML = user.name;
                li.onclick = () => openConversation(user.user_id, user.name);
                usersList.appendChild(li);
            });
        } else {
            usersList.innerHTML = '<li>No users found.</li>';
        }
    })
    .catch(error => {
        console.error('Error fetching chat users:', error);
        document.getElementById('chatUsersList').innerHTML = '<li>Error fetching users.</li>';
    });
}

let currentChatPassengerId = null;

// Open conversation with a specific passenger
function openConversation(passenger_id, passenger_name) {
    currentChatPassengerId = passenger_id;
    document.getElementById('chatWithHeader').textContent = `Chat with ${passenger_name}`;
    document.getElementById('conversation').innerHTML = 'Loading messages...';

    // Fetch all messages between company and this passenger
    fetch(`../php/controllers/get_conversation.php?passenger_id=${passenger_id}`, {
        credentials: 'include'
    })
    .then(res => res.json())
    .then(data => {
        const convoDiv = document.getElementById('conversation');
        convoDiv.innerHTML = '';

        if (data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => {
                const p = document.createElement('p');
                // Check who is sender:
                if (msg.sender_id == passenger_id) {
                    p.style.textAlign = 'left';
                    p.innerHTML = `<strong>${msg.sender_name}:</strong> ${msg.message_content}`;
                } else {
                    p.style.textAlign = 'right';
                    p.innerHTML = `<strong>Me:</strong> ${msg.message_content}`;
                }
                convoDiv.appendChild(p);
            });
            // Scroll to bottom
            convoDiv.scrollTop = convoDiv.scrollHeight;
        } else {
            convoDiv.innerHTML = 'No messages yet.';
        }
    })
    .catch(error => {
        console.error('Error fetching conversation:', error);
        document.getElementById('conversation').innerHTML = 'Error loading messages.';
    });
}

// Send a new chat message
function sendChatMessage() {
    const chatReply = document.getElementById('chatReply');
    const messageContent = chatReply.value.trim();
    if (!messageContent) {
        showError('Message cannot be empty.');
        return;
    }

    const formData = new FormData();
    formData.append('passenger_id', currentChatPassengerId);
    formData.append('message_content', messageContent);

    fetch('../php/controllers/send_company_chat_message.php', {
        method: 'POST',
        body: formData,
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            chatReply.value = '';
            // Refresh conversation
            openConversation(currentChatPassengerId, document.getElementById('chatWithHeader').textContent.replace('Chat with ', ''));
        } else if (data.error) {
            showError(data.error);
        }
    })
    .catch(error => {
        console.error('Error sending chat message:', error);
        showError('Error sending chat message.');
    });
}

// Initialize
function initializeCompanyHome() {
    fetchCompanyName();
    fetchCompanyLogo();
    fetchFlights();
    fetchUnrepliedMessages(); // <--- changed to fetch only unreplied
}

window.onload = initializeCompanyHome;