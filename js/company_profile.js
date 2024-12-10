
        function go_home(){
            window.location.href = 'company_home.html';
        }

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

        // Fetch and display company profile details
        function fetchProfileDetails() {
            fetch('../php/controllers/company/get_company_details.php', {
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                console.log(data); // For debugging
                if (data.company) {
                    document.getElementById('company-name').textContent = data.company.name;
                    document.getElementById('company-email').textContent = data.company.email;

                    // Ensure account_balance is a number before using toFixed
                    const accountBalance = parseFloat(data.company.account_balance);
                    if (!isNaN(accountBalance)) {
                        document.getElementById('account-balance').textContent = accountBalance.toFixed(2);
                    } else {
                        document.getElementById('account-balance').textContent = '0.00';
                    }

                    // Populate edit form fields
                    document.getElementById('edit-name').value = data.company.name;
                    document.getElementById('edit-email').value = data.company.email;
                } else if (data.error) {
                    showError(data.error);
                }
            })
            .catch(error => {
                console.error('Error fetching profile details:', error);
                showError('Error fetching profile details.');
            });
        }

        // Handle Edit Name Form Submission
        document.getElementById('editNameForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const name = document.getElementById('edit-name').value.trim();

            if (name === '') {
                showError('Name cannot be empty.');
                return;
            }

            // Prepare form data
            const formData = new FormData();
            formData.append('name', name);

            // Send the updated name via AJAX
            fetch('../php/controllers/company/update_company_profile.php', {
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
                    fetchProfileDetails(); // Refresh profile details
                } else if (data.error) {
                    showError(data.error);
                }
            })
            .catch(error => {
                console.error('Error updating name:', error);
                showError('Error updating name.');
            });
        });

        // Handle Edit Email Form Submission
        document.getElementById('editEmailForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const email = document.getElementById('edit-email').value.trim();

            if (email === '') {
                showError('Email cannot be empty.');
                return;
            }

            // Prepare form data
            const formData = new FormData();
            formData.append('email', email);

            // Send the updated email via AJAX
            fetch('../php/controllers/company/update_company_profile.php', {
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
                    fetchProfileDetails(); // Refresh profile details
                } else if (data.error) {
                    showError(data.error);
                }
            })
            .catch(error => {
                console.error('Error updating email:', error);
                showError('Error updating email.');
            });
        });

        // Initialize Company Profile Page
        function initializeProfilePage() {
            fetchProfileDetails();
        }

        // Call initialization on page load
        window.onload = initializeProfilePage;
