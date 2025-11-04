<?php
    // START: This block was moved from the <body>
    session_start();
    require '../db.php'; // Ensure this path is correct

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

    if (empty($user_id)) {
        // Redirect to login if not logged in
        // header('Location: /login.php');
        // exit();
    }

    // Fetch service options from the database
    $service_options = "";
    $sql_services = "SELECT service_id, service_name FROM services ORDER BY service_name ASC";
    $result_services = $conn->query($sql_services);
    if ($result_services && $result_services->num_rows > 0) {
        while ($row = $result_services->fetch_assoc()) {
            $service_options .= "<option value='" . htmlspecialchars($row['service_id']) . "'>" 
                              . htmlspecialchars($row['service_name']) . "</option>";
        }
    }
    // END: This block was moved from the <body>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Provider Registration</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <style>
        /* --- 🎨 Custom Properties for Theming --- */
        :root {
            --primary-color: #4f46e5;      /* Indigo */
            --secondary-color: #7c3aed;   /* Violet */
            --light-gray: #f3f4f6;
            --medium-gray: #d1d5db;
            --dark-gray: #374151;
            --text-gray: #6b7280;
            --success-color: #10b981;
            --error-color: #ef4444;
            --border-color: #e5e7eb;
        }

        /* --- 🖌️ Base Body Styles --- */
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-gray);
            background-image: radial-gradient(circle at 100% 0%, rgba(199, 210, 254, 0.6) 0%, transparent 40%),
                              radial-gradient(circle at 0% 100%, rgba(221, 214, 254, 0.6) 0%, transparent 40%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        /* --- 📄 Main Card Styling --- */
        .card {
            background-color: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        }

        /* --- ✨ Form Header Gradient Text --- */
        .form-header {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- 📊 Progress Bar --- */
        .progressbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start; /* Align to top for labels */
            position: relative;
            margin-bottom: 3.5rem; /* Increased margin for labels */
        }
        .progressbar::before {
            content: '';
            position: absolute;
            top: 20px; /* Vertically center with the circle */
            left: 0;
            transform: translateY(-50%);
            height: 4px;
            width: 100%;
            background-color: var(--border-color);
            z-index: 1;
        }
        .progress-line {
            position: absolute;
            top: 20px;
            left: 0;
            transform: translateY(-50%);
            height: 4px;
            background-color: var(--primary-color);
            z-index: 2;
            width: 0%;
            transition: width 0.4s ease;
        }
        .progress-step {
            width: 40px;
            height: 40px;
            background-color: #fff;
            border: 3px solid var(--border-color);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 3;
            transition: all 0.4s ease;
            font-weight: 600;
            color: var(--text-gray);
            position: relative;
        }
        .progress-step.active {
            border-color: var(--primary-color);
            background-color: var(--primary-color);
            color: #fff;
            transform: scale(1.1);
        }
        /* Step Label Below the Circle */
        .progress-step::after {
            content: attr(data-title);
            position: absolute;
            top: 120%;
            font-size: 0.8rem;
            color: var(--text-gray);
            width: 100px;
            text-align: center;
        }
        .progress-step.active::after {
            color: var(--dark-gray);
            font-weight: 600;
        }
        
        /* --- 🎬 Form Step Animation --- */
        .form-step {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }
        .form-step.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- 📝 Input Field Styling --- */
        .input-group {
            position: relative;
        }
        .input-group .form-input, .input-group .form-select, .input-group .form-textarea {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem; /* 14px padding */
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f9fafb;
            color: var(--dark-gray);
        }
        .input-group .form-input[type="date"], .input-group .form-input[type="time"] {
            padding-right: 1rem; /* Adjust padding for date/time inputs */
        }
        /* File input custom styles */
        .input-group input[type="file"].form-input {
            padding: 0;
            line-height: 1.5;
        }
        .input-group input[type="file"]::file-selector-button {
            background-color: var(--light-gray);
            border: none;
            border-right: 2px solid var(--border-color);
            padding: 0.875rem 1rem;
            margin-right: 1rem;
            font-weight: 500;
            color: var(--dark-gray);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .input-group input[type="file"]::file-selector-button:hover {
            background-color: #e5e7eb;
        }
        .input-group .form-input:focus, .input-group .form-select:focus, .input-group .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            background-color: #fff;
        }
        .input-group .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--dark-gray);
            margin-bottom: 0.5rem;
            display: block;
        }
        .input-group .form-select {
            appearance: none; /* Remove default browser arrow */
            padding-right: 3rem; /* Space for custom arrow */
        }
        .input-group .icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--medium-gray);
            transition: color 0.3s ease;
            pointer-events: none; /* Allow clicks to pass through */
        }
        /* Adjust icon for file input */
        .input-group input[type="file"] ~ .icon {
            top: 1.15rem;
            transform: none;
        }
        .input-group .form-textarea ~ .icon {
            top: 1.15rem; /* Adjust icon for textarea */
            transform: none;
        }
        .input-group .form-input:focus ~ .icon, .input-group .form-select:focus ~ .icon, .input-group .form-textarea:focus ~ .icon {
            color: var(--primary-color);
        }
        .select-arrow {
              position: absolute;
              right: 1rem;
              top: 50%;
              transform: translateY(-50%);
              color: var(--medium-gray);
              pointer-events: none;
        }
        
        /* --- 📁 File Preview Styling --- */
        #file-preview-list {
            margin-top: 1.5rem;
            max-height: 200px;
            overflow-y: auto;
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            background-color: #f9fafb;
        }
        .file-preview-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.75rem;
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        .file-preview-item:last-child {
            margin-bottom: 0;
        }

        /* --- 🚀 Button Styling --- */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            gap: 0.5rem; /* Space between text and icon */
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            background-color: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -5px rgba(79, 70, 229, 0.4);
        }
        .btn-secondary {
            background-color: #e5e7eb;
            color: var(--dark-gray);
        }
        .btn-secondary:hover {
            background-color: var(--medium-gray);
        }
    </style>
</head>
<body>
    <div class="card w-full max-w-3xl p-8 md:p-12">
        <div class="text-center mb-10">
            <h1 class="text-3xl md:text-4xl font-bold form-header mb-2">Become a Service Provider</h1>
            <p class="text-gray-600">Complete the steps below to join our network</p>
        </div>

        <div class="progressbar">
            <div class="progress-line"></div>
            <div class="progress-step active" data-title="Company Info"><span>1</span></div>
            <div class="progress-step" data-title="Service Details"><span>2</span></div>
            <div class="progress-step" data-title="Documents"><span>3</span></div>
            <div class="progress-step" data-title="Confirmation"><span><i class="fas fa-check"></i></span></div>
        </div>

        <form id="service-provider-form" action="SubmitaddNewservice.php" method="POST" novalidate enctype="multipart/form-data">
            <input type="hidden" name="service_userID" value="<?php echo htmlspecialchars($user_id); ?>">
            <input type="hidden" id="service_name" name="service_name">

            <div class="form-step active">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-semibold text-gray-800">Company Information</h2>
                    <p class="text-gray-500">Let's start with the basics.</p>
                </div>
                <div class="space-y-6">
                    <div class="input-group">
                        <input type="text" id="companyname" name="companyname" class="form-input" placeholder="Company Name" required>
                        <i class="fas fa-building icon"></i>
                    </div>
                    <div class="input-group">
                        <textarea id="companyaddress" name="companyaddress" class="form-textarea" placeholder="Company Address" rows="3" required></textarea>
                        <i class="fas fa-map-marked-alt icon"></i>
                    </div>
                    <div class="input-group">
                        <input type="email" id="companyemail" name="companyemail" class="form-input" placeholder="Company Email" required>
                        <i class="fas fa-envelope icon"></i>
                    </div>
                    <div class="input-group">
                        <input type="tel" id="contactnumber" name="contactnumber" class="form-input" placeholder="Contact Number (e.g., +639171234567)" required>
                        <i class="fas fa-phone icon"></i>
                    </div>
                </div>
            </div>

            <div class="form-step">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-semibold text-gray-800">Service Details</h2>
                    <p class="text-gray-500">Tell us what service you are offering.</p>
                </div>
                <div class="space-y-6">
                    <div class="input-group">
                        <select id="service_id" name="service_id" class="form-select" required>
                            <option value="" disabled selected>-- Select Your Service --</option>
                            <?php echo $service_options; ?>
                        </select>
                        <i class="fas fa-briefcase icon"></i>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                    <div class="input-group">
                        <textarea id="description" name="description" class="form-textarea bg-gray-50" placeholder="Service description will appear here..." rows="4" required></textarea>
                        <i class="fas fa-file-alt icon"></i>
                    </div>
                    <div>
                        <label class="form-label">Available Date Range</label>
                        <div class="flex items-center space-x-4">
                            <div class="input-group flex-1">
                                <input type="date" id="available_date_from" name="available_date_from" class="form-input" required>
                                <i class="fas fa-calendar-alt icon"></i>
                            </div>
                            <span class="text-gray-500">to</span>
                            <div class="input-group flex-1">
                                <input type="date" id="available_date_to" name="available_date_to" class="form-input" required>
                                <i class="fas fa-calendar-alt icon"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Available Time Range</label>
                        <div class="flex items-center space-x-4">
                            <div class="input-group flex-1">
                                <input type="time" id="available_time_from" name="available_time_from" class="form-input" required>
                                <i class="fas fa-clock icon"></i>
                            </div>
                            <span class="text-gray-500">to</span>
                            <div class="input-group flex-1">
                                <input type="time" id="available_time_to" name="available_time_to" class="form-input" required>
                                <i class="fas fa-clock icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="price" class="form-label">Service Price / Rate (PHP)</label>
                        <input type="number" id="price" name="price" class="form-input" placeholder="Enter Amount" required min="0" step="0.01">
                        <i class="icon font-bold" style="font-style: normal;">₱</i>
                    </div>
                </div>
            </div>
            
            <div class="form-step">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-semibold text-gray-800">Legal Documents</h2>
                    <p class="text-gray-500">Upload any supporting documents (e.g., DTI, permits). Up to 10 files. (Optional)</p>
                </div>
                <div class="space-y-6">
                    <div class="input-group">
                        <label for="legal_documents" class="form-label">Upload Documents</label>
                        <input type="file" id="legal_documents" name="legal_documents[]" class="form-input" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <i class="fas fa-file-upload icon"></i>
                    </div>
                    
                    <div id="file-preview-list" class="hidden">
                        <span class="text-sm font-medium text-gray-700">Selected files (max 10):</span>
                        <div id="file-list-items" class="mt-2 space-y-2"></div>
                    </div>
                </div>
            </div>

            <div class="form-step">
                <div class="text-center">
                    <h2 class="text-2xl font-semibold text-gray-800 mt-6 mb-2">Ready to Launch?</h2>
                    <p class="text-gray-500 max-w-md mx-auto">You're all set! Please review your information, then click the submit button to complete your registration.</p>
                </div>
            </div>

            <div class="flex justify-between mt-10">
                <button type="button" class="btn btn-secondary" id="prev-btn" style="display: none;">
                    <i class="fas fa-arrow-left"></i>
                    <span>Previous</span>
                </button>
                <button type="button" class="btn btn-primary ml-auto" id="next-btn">
                    <span>Next</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
                <button type="submit" class="btn btn-primary" id="submit-btn" style="display: none;">
                    <i class="fas fa-paper-plane"></i>
                    <span>Submit Registration</span>
                </button>
            </div>
        </form>
    </div>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const submitBtn = document.getElementById('submit-btn');
        const form = document.getElementById('service-provider-form');
        const formSteps = [...document.querySelectorAll('.form-step')];
        const progressSteps = [...document.querySelectorAll('.progress-step')];
        const progressLine = document.querySelector('.progress-line');
        const fileInput = document.getElementById('legal_documents');
        const filePreviewList = document.getElementById('file-preview-list');
        const fileListItems = document.getElementById('file-list-items');

        let currentStep = 0;

        nextBtn.addEventListener('click', () => {
            if (validateCurrentStep()) {
                if(currentStep < formSteps.length - 1) {
                    currentStep++;
                    updateFormUI();
                }
            }
        });

        prevBtn.addEventListener('click', () => {
            if (currentStep > 0) {
                currentStep--;
                updateFormUI();
            }
        });
        
        form.addEventListener('submit', (e) => {
            if (!validateCurrentStep()) {
                e.preventDefault(); 
                return;
            }
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Processing...</span>';
            submitBtn.disabled = true;
        });

        function updateFormUI() {
            // Update form steps visibility
            formSteps.forEach((step, index) => {
                step.classList.toggle('active', index === currentStep);
            });
            
            // Update progress bar
            progressSteps.forEach((step, index) => {
                step.classList.toggle('active', index <= currentStep);
            });
            const activeSteps = document.querySelectorAll('.progress-step.active');
            const progressWidth = ((activeSteps.length - 1) / (progressSteps.length - 1)) * 100;
            progressLine.style.width = `${progressWidth}%`;
            
            // Update button visibility
            prevBtn.style.display = currentStep > 0 ? 'inline-flex' : 'none';
            nextBtn.style.display = currentStep < formSteps.length - 1 ? 'inline-flex' : 'none';
            submitBtn.style.display = currentStep === formSteps.length - 1 ? 'inline-flex' : 'none';

            // Ensure previous button isn't pushed to the right
            if (currentStep === 0) {
                nextBtn.classList.add('ml-auto');
            } else {
                nextBtn.classList.remove('ml-auto');
            }
        }

        function validateCurrentStep() {
            const activeStep = formSteps[currentStep];
            const requiredFields = activeStep.querySelectorAll('input:required, textarea:required, select:required');
            let isValid = true;

            requiredFields.forEach(field => {
                field.parentElement.classList.remove('border-red-500'); // Reset style
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--error-color)';
                    isValid = false;
                } else {
                    field.style.borderColor = 'var(--border-color)';
                }
            });

            // --- NEW: Validate file step ---
            if (currentStep === 2) { // Step 2 is the document upload step (0, 1, 2)
                const files = fileInput.files;
                if (files.length > 10) {
                    showToast('You can upload a maximum of 10 files.', 'error');
                    fileInput.style.borderColor = 'var(--error-color)';
                    isValid = false;
                } else {
                    fileInput.style.borderColor = 'var(--border-color)';
                }
            }
            // --- End new validation ---

            if (!isValid && currentStep !== 2) { // Don't show for file count
                showToast('Please fill out all required fields.', 'error');
            }
            return isValid;
        }

        function showToast(message, type = 'info') {
            const backgroundColor = {
                success: 'var(--success-color)',
                error: 'var(--error-color)',
                info: 'var(--dark-gray)'
            }[type];

            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                style: {
                    background: backgroundColor,
                    borderRadius: "12px",
                    fontFamily: "'Inter', sans-serif",
                    boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)"
                },
                stopOnFocus: true,
            }).showToast();
        }
        
        // --- Event Listener for Service Dropdown ---
        document.getElementById('service_id').addEventListener('change', function () {
            const serviceId = this.value;
            const descriptionTextarea = document.getElementById('description');
            
            // Set the service name in the hidden input field
            const selectedOption = this.options[this.selectedIndex];
            const serviceName = selectedOption ? selectedOption.text : '';
            document.getElementById('service_name').value = serviceName;

            if (serviceId) {
                fetch(`get_service_description.php?id=${serviceId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.text();
                    })
                    .then(data => {
                        if (data.includes("not found") || data.includes("No description")) {
                            descriptionTextarea.value = "";
                        } else {
                            descriptionTextarea.value = data;
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        descriptionTextarea.value = ""; // Clear on error
                        showToast('Error fetching service details.', 'error');
                    });
            } else {
                descriptionTextarea.value = "";
            }
        });
        
        // --- Handle Toast Messages on Page Load (from redirects) ---
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'success') {
            showToast('Service added successfully! Please wait for the admin to confirm. Welcome aboard!', 'success');
        } else if (urlParams.get('status') === 'error') {
            const errorMessage = urlParams.get('message') || 'An unknown error occurred.';
            showToast(decodeURIComponent(errorMessage), 'error');
        }

        // --- NEW: Event Listener for File Input ---
        fileInput.addEventListener('change', () => {
            const files = fileInput.files;
            if (files.length > 0) {
                filePreviewList.classList.remove('hidden');
                fileListItems.innerHTML = ''; // Clear previous list

                if (files.length > 10) {
                    showToast('Maximum 10 files allowed. Only the first 10 will be shown.', 'error');
                }

                // Show up to 10 files
                for (let i = 0; i < Math.min(files.length, 10); i++) {
                    const file = files[i];
                    const item = document.createElement('div');
                    item.className = 'file-preview-item';
                    item.innerHTML = `
                        <span class="flex items-center gap-2">
                            <i class="fas fa-file-alt text-gray-500"></i>
                            <span class="text-sm text-gray-800">${file.name}</span>
                        </span>
                        <span class="text-xs text-gray-500">${(file.size / 1024).toFixed(1)} KB</span>
                    `;
                    fileListItems.appendChild(item);
                }
            } else {
                filePreviewList.classList.add('hidden');
            }
        });

        // Initial UI setup
        updateFormUI();
    });
    </script>
</body>
</html>