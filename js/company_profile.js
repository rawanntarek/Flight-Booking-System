
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
        // Handle Edit Image Form Submission

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
                    document.getElementById('company-bio').textContent = data.company.bio;
                    document.getElementById('company-address').textContent = data.company.address;
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
                    document.getElementById('edit-bio').value = data.company.bio;
                    document.getElementById('edit-address').value = data.company.address;


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


// Handle Edit Logo Form Submission
document.getElementById('editLogoForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent default form submission

    const fileInput = document.getElementById('edit-logo');
    if (!fileInput.files || fileInput.files.length === 0) {
        showError('Please select an image file.');
        return;
    }

    // Prepare form data
    const formData = new FormData();
    formData.append('logo', fileInput.files[0]); // The actual file object

    // IMPORTANT: double-check the path below!
    fetch('../php/controllers/company/update_company_logo.php', {
        method: 'POST',
        body: formData,
        credentials: 'include',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // If the server returns a 404 or other error, handle it:
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json(); 
    })
    .then(data => {
        if (data.success) {
            showFeedback(data.success);
            // Refresh the displayed logo
            fetchCompanyLogo();
        } else if (data.error) {
            showError(data.error);
        }
    })
    .catch(error => {
        console.error('Error updating logo:', error);
        showError('Error updating logo.');
    });
});




 // Handle Edit Email Form Submission
        document.getElementById('editBioForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const bio = document.getElementById('edit-bio').value.trim();

            if (bio === '') {
                showError('Bio cannot be empty.');
                return;
            }

            // Prepare form data
            const formData = new FormData();
            formData.append('bio', bio);

            // Send the updated bio via AJAX
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
                console.error('Error updating bio:', error);
                showError('Error updating bio.');
            });
        });
         // Handle Edit Email Form Submission
         document.getElementById('editAddressForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const address = document.getElementById('edit-address').value.trim();

            if (address === '') {
                showError('Address cannot be empty.');
                return;
            }

            // Prepare form data
            const formData = new FormData();
            formData.append('address', address);

            // Send the updated bio via AJAX
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
                console.error('Error updating address:', error);
                showError('Error updating adress.');
            });
        });

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
        // Initialize Company Profile Page
        function initializeProfilePage() {
            fetchProfileDetails();
            fetchCompanyLogo();
            fetchFlights();
        }

        // Call initialization on page load
        window.onload = initializeProfilePage;
