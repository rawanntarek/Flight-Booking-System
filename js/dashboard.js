// Function to open the chat form
function openForm() {
    document.getElementById("myForm").style.display = "block";
    fetchCompanies();
}

// Function to close the chat form
function closeForm() {
    document.getElementById("myForm").style.display = "none";
}


function viewMessage() {
    window.location.href = "../php/controllers/view_messages.php";
}

// Function to fetch companies via AJAX
function fetchCompanies() {
    fetch('../php/controllers/get_companies.php', {
        credentials: 'include' // Include cookies for session
    })
        .then(response => response.json())
        .then(data => {
            if (data.companies) {
                const companySelect = document.getElementById('company');
                // Clear existing options except the first
                companySelect.innerHTML = '<option value="" disabled selected>Select a company</option>';
                data.companies.forEach(company => {
                    const option = document.createElement('option');
                    option.value = company.user_id;
                    option.textContent = company.name;
                    companySelect.appendChild(option);
                });
            } else if (data.error) {
                displayFeedback('errors', data.error);
            }
        })
        .catch(error => {
            console.error('Error fetching companies:', error);
            displayFeedback('errors', 'Error fetching companies.');
        });
}

// Function to display feedback messages
function displayFeedback(type, message) {
    const feedbackDiv = document.getElementById('feedback');
    feedbackDiv.innerHTML = `<div class="${type}">${message}</div>`;
    // Optionally, remove the message after some time
    setTimeout(() => {
        feedbackDiv.innerHTML = '';
    }, 5000);
}

// Handle chat form submission via AJAX
document.getElementById('chatForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent default form submission

    const company_id = document.getElementById('company').value;
    const message_content = document.getElementById('msg').value;

    // Basic validation
    if (!company_id || !message_content.trim()) {
        displayFeedback('errors', 'Please select a company and enter a message.');
        return;
    }

    // Prepare form data
    const formData = new FormData();
    formData.append('company_id', company_id);
    formData.append('message_content', message_content);

    // Send the message via AJAX
    fetch('../php/controllers/send_message.php', {
        method: 'POST',
        body: formData,
        credentials: 'include', // Include cookies for session
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // To indicate AJAX request
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayFeedback('success', data.success);
            // Clear the form
            document.getElementById('chatForm').reset();
            // Optionally close the chat popup
            closeForm();
        } else if (data.error) {
            displayFeedback('errors', data.error);
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        displayFeedback('errors', 'Error sending message.');
    });
});

// Optional: Close the chat form when clicking outside of it
window.onclick = function(event) {
    var chatForm = document.getElementById("myForm");
    if (event.target == chatForm) {
        chatForm.style.display = "none";
    }
}
