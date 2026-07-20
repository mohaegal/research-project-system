<?php
// survey_owner.php
// Interview Guide for Small Business Owners (Appendix III)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Owner Interview - Youth Employment Study</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .survey-container {
            max-width: 680px;
            margin: 40px auto;
        }
        .form-section-title {
            border-bottom: 1px solid var(--border-glass);
            padding-bottom: 12px;
            margin-bottom: 20px;
            font-size: 1.15rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--accent-secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .survey-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            border-top: 1px solid var(--border-glass);
            padding-top: 20px;
        }
        .conditional-group {
            display: none;
            margin-top: 12px;
            padding-left: 20px;
            border-left: 3px solid var(--accent-secondary);
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="navbar-brand">
                <i class="fa-solid fa-graduation-cap"></i> ResearchPortal
            </a>
            <ul class="nav-links">
                <li class="nav-item"><a href="index.php">Home</a></li>
                <li class="nav-item"><a href="survey_employee.php">Employee Survey</a></li>
                <li class="nav-item active"><a href="survey_owner.php">Owner Interview</a></li>
                <li>
                    <button class="theme-toggle-btn" id="theme-toggle" title="Toggle Theme">
                        <i class="fa-solid fa-moon" id="theme-icon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Content -->
    <div class="container">
        <div class="survey-container glass-card">
            <!-- Header -->
            <div class="text-center mb-4">
                <h2>Interview Guide for Small Business Owners</h2>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 6px;">
                    Investigating employment metrics, challenges, and support strategies. All responses are voluntary and treated with strict confidentiality.
                </p>
            </div>

            <!-- Progress Bar -->
            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="progress-bar" style="background: linear-gradient(90deg, var(--accent-secondary), var(--accent-primary));"></div>
            </div>

            <div id="alert-container"></div>

            <!-- Form -->
            <form id="interview-form" onsubmit="submitInterview(event)">
                <!-- STEP 1: Business Profile -->
                <div class="survey-step active" id="step-1">
                    <h3 class="form-section-title"><i class="fa-solid fa-store"></i> Step 1: Business Profile</h3>
                    
                    <!-- Owner Name -->
                    <div class="form-group">
                        <label for="owner_name">1. Owner Name <span style="color: var(--accent-danger);">*</span></label>
                        <input type="text" id="owner_name" name="owner_name" class="form-control" placeholder="Enter your full name" required>
                    </div>

                    <!-- Business Name -->
                    <div class="form-group">
                        <label for="business_name">2. Business Name <span style="color: var(--accent-danger);">*</span></label>
                        <input type="text" id="business_name" name="business_name" class="form-control" placeholder="Enter your business name" required>
                    </div>

                    <!-- Business Type -->
                    <div class="form-group">
                        <label for="business_type">3. What type of business do you operate? <span style="color: var(--accent-danger);">*</span></label>
                        <select id="business_type" name="business_type" class="form-control" onchange="toggleOtherBusiness()" required>
                            <option value="" disabled selected>-- Select business type --</option>
                            <option value="Retail shop">Retail shop (Boutique, Grocery, etc.)</option>
                            <option value="Salon/Barbershop">Salon / Barbershop</option>
                            <option value="Restaurant/Food kiosk">Restaurant / Food kiosk</option>
                            <option value="Repair shop">Repair shop (Electronics, Mechanics, etc.)</option>
                            <option value="Transport">Transport services (Boda Boda, Taxi, etc.)</option>
                            <option value="Other">Other (Please specify)</option>
                        </select>
                        <div id="other-business-container" class="conditional-group">
                            <label for="other_business_type">Specify other business:</label>
                            <input type="text" id="other_business_type" class="form-control" placeholder="Specify business type">
                        </div>
                    </div>

                    <!-- Operation Duration -->
                    <div class="form-group">
                        <label>4. How long has your business been in operation? <span style="color: var(--accent-danger);">*</span></label>
                        <div class="options-grid">
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="operation_duration" value="Less than 6 months" required>
                                <span>Less than 6 months</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="operation_duration" value="6 months-1 year">
                                <span>6 months - 1 year</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="operation_duration" value="1-2 years">
                                <span>1 - 2 years</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="operation_duration" value="Over 2 years">
                                <span>Over 2 years</span>
                            </label>
                        </div>
                    </div>

                    <!-- Employees Counts -->
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="total_employees">5. Total Employees currently <span style="color: var(--accent-danger);">*</span></label>
                            <input type="number" id="total_employees" class="form-control" min="1" placeholder="e.g. 5" required>
                        </div>
                        <div class="form-group">
                            <label for="youth_employees">6. Youth Employees (below 35 years) <span style="color: var(--accent-danger);">*</span></label>
                            <input type="number" id="youth_employees" class="form-control" min="0" placeholder="e.g. 3" required>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Motivation & Roles -->
                <div class="survey-step" id="step-2">
                    <h3 class="form-section-title"><i class="fa-solid fa-heart"></i> Step 2: Motivation & Roles</h3>

                    <div class="form-group">
                        <label for="motivation">5. What motivated you to employ young people in your business? <span style="color: var(--accent-danger);">*</span></label>
                        <textarea id="motivation" class="form-control" rows="3" placeholder="Explain what makes young people suitable hires for your business..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="roles">6. What roles, duties, or positions do young employees hold in your business? <span style="color: var(--accent-danger);">*</span></label>
                        <textarea id="roles" class="form-control" rows="3" placeholder="Describe their tasks (e.g. customer service, cash handling, technical work)..." required></textarea>
                    </div>
                </div>

                <!-- STEP 3: Challenges, Support & Advice -->
                <div class="survey-step" id="step-3">
                    <h3 class="form-section-title"><i class="fa-solid fa-triangle-exclamation"></i> Step 3: Challenges & Advice</h3>

                    <div class="form-group">
                        <label for="challenges">7. What are the main challenges you face in employing young people? <span style="color: var(--accent-danger);">*</span></label>
                        <textarea id="challenges" class="form-control" rows="3" placeholder="Describe difficulties (e.g. high turnover, lack of training, indiscipline)..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="support_needed">8. What kind of support (financial, government, or training) would help you hire more young people? <span style="color: var(--accent-danger);">*</span></label>
                        <textarea id="support_needed" class="form-control" rows="3" placeholder="Describe help needed (e.g. tax breaks, cheap loans, subsidized courses)..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="training_programs">9. Do you have any training programs/mentorship for your young employees? If yes, describe them. <span style="color: var(--accent-danger);">*</span></label>
                        <textarea id="training_programs" class="form-control" rows="3" placeholder="Describe on-the-job training or guidance provided..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="advice">10. What advice would you give to young people seeking employment in small businesses? <span style="color: var(--accent-danger);">*</span></label>
                        <textarea id="advice" class="form-control" rows="3" placeholder="Share tips on discipline, work ethic, and skills acquisition..." required></textarea>
                    </div>
                </div>

                <!-- Navigation buttons -->
                <div class="survey-navigation">
                    <button type="button" class="btn btn-secondary" id="prev-btn" onclick="changeStep(-1)" style="visibility: hidden;">
                        <i class="fa-solid fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="next-btn" onclick="changeStep(1)" style="background: linear-gradient(135deg, var(--accent-secondary), var(--accent-primary));">
                        Next <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>

            <!-- Success Screen -->
            <div id="success-screen" style="display: none; text-align: center; padding: 40px 10px;">
                <div style="width: 80px; height: 80px; background: rgba(168, 85, 247, 0.1); color: var(--accent-secondary); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 24px;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h2 class="mb-2">Interview Data Submitted!</h2>
                <p style="color: var(--text-secondary); margin-bottom: 30px;">
                    Thank you, Business Owner, for sharing your experience and feedback. Your responses will help design strategies to support small businesses.
                </p>
                <a href="index.php" class="btn btn-primary">
                    Return to Homepage <i class="fa-solid fa-home"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Scripting -->
    <script>
        let currentStep = 1;
        const totalSteps = 3;

        function updateProgressBar() {
            const pct = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progress-bar').style.width = pct + '%';
        }

        function highlightSelection(label) {
            const input = label.querySelector('input');
            const parent = label.closest('.form-group');
            parent.querySelectorAll('.option-box').forEach(box => {
                box.classList.remove('selected');
            });
            if (input.checked) {
                label.classList.add('selected');
            }
        }

        document.querySelectorAll('.option-box input').forEach(inp => {
            inp.addEventListener('change', (e) => {
                const label = e.target.closest('.option-box');
                highlightSelection(label);
            });
        });

        function toggleOtherBusiness() {
            const selector = document.getElementById('business_type');
            const container = document.getElementById('other-business-container');
            const otherInput = document.getElementById('other_business_type');
            
            if (selector.value === 'Other') {
                container.style.display = 'block';
                otherInput.required = true;
            } else {
                container.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }
        }

        function validateStep(step) {
            const stepElement = document.getElementById('step-' + step);
            const requiredFields = stepElement.querySelectorAll('[required]');
            let valid = true;

            requiredFields.forEach(field => {
                if (field.type === 'radio') {
                    const name = field.name;
                    const checked = stepElement.querySelector(`input[name="${name}"]:checked`);
                    if (!checked) {
                        valid = false;
                        field.closest('.form-group').style.borderLeft = '3px solid var(--accent-danger)';
                    } else {
                        field.closest('.form-group').style.borderLeft = 'none';
                    }
                } else if (field.tagName === 'SELECT' || field.type === 'text' || field.type === 'number' || field.tagName === 'TEXTAREA') {
                    if (!field.value.trim()) {
                        valid = false;
                        field.style.borderColor = 'var(--accent-danger)';
                    } else {
                        field.style.borderColor = 'var(--border-glass)';
                    }
                }
            });

            // Specific validation: Youth employees <= total employees
            if (step === 1) {
                const total = parseInt(document.getElementById('total_employees').value || 0);
                const youth = parseInt(document.getElementById('youth_employees').value || 0);
                if (total > 0 && youth > total) {
                    valid = false;
                    document.getElementById('youth_employees').style.borderColor = 'var(--accent-danger)';
                    document.getElementById('alert-container').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-triangle-exclamation"></i> Youth employee count cannot exceed total employee count.
                        </div>
                    `;
                    window.scrollTo(0, 0);
                    return false;
                }
            }

            if (!valid) {
                document.getElementById('alert-container').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-xmark"></i> Please answer all required fields before proceeding.
                    </div>
                `;
                window.scrollTo(0, 0);
            } else {
                document.getElementById('alert-container').innerHTML = '';
            }

            return valid;
        }

        function changeStep(dir) {
            if (dir === 1 && !validateStep(currentStep)) return;

            document.getElementById('step-' + currentStep).classList.remove('active');
            currentStep += dir;
            document.getElementById('step-' + currentStep).classList.add('active');

            // Manage nav buttons
            document.getElementById('prev-btn').style.visibility = currentStep === 1 ? 'hidden' : 'visible';
            
            const nextBtn = document.getElementById('next-btn');
            if (currentStep === totalSteps) {
                nextBtn.innerHTML = 'Submit Interview <i class="fa-solid fa-paper-plane"></i>';
                nextBtn.type = 'submit';
                nextBtn.onclick = null;
            } else {
                nextBtn.innerHTML = 'Next <i class="fa-solid fa-arrow-right"></i>';
                nextBtn.type = 'button';
                nextBtn.onclick = () => changeStep(1);
            }

            updateProgressBar();
            window.scrollTo(0, 0);
        }

        async function submitInterview(e) {
            e.preventDefault();
            
            if (!validateStep(currentStep)) return;

            const submitBtn = document.getElementById('next-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

            // Gather inputs
            const payload = {
                owner_name: document.getElementById('owner_name').value.trim(),
                business_name: document.getElementById('business_name').value.trim(),
                business_type: document.getElementById('business_type').value === 'Other' 
                    ? document.getElementById('other_business_type').value 
                    : document.getElementById('business_type').value,
                operation_duration: document.querySelector('input[name="operation_duration"]:checked')?.value || '',
                total_employees: parseInt(document.getElementById('total_employees').value),
                youth_employees: parseInt(document.getElementById('youth_employees').value),
                motivation: document.getElementById('motivation').value,
                roles: document.getElementById('roles').value,
                challenges: document.getElementById('challenges').value,
                support_needed: document.getElementById('support_needed').value,
                training_programs: document.getElementById('training_programs').value,
                advice: document.getElementById('advice').value
            };

            try {
                const response = await fetch('api.php?action=submit_owner', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    document.getElementById('interview-form').style.display = 'none';
                    document.querySelector('.progress-bar-container').style.display = 'none';
                    document.getElementById('success-screen').style.display = 'block';
                } else {
                    document.getElementById('alert-container').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-circle-xmark"></i> ${result.message}
                        </div>
                    `;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit Interview <i class="fa-solid fa-paper-plane"></i>';
                }
            } catch (err) {
                document.getElementById('alert-container').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-xmark"></i> Connection error. Please try again.
                    </div>
                `;
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Interview <i class="fa-solid fa-paper-plane"></i>';
            }
        }

        updateProgressBar();

        // Theme Switcher
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-theme');
            themeIcon.className = 'fa-solid fa-sun';
        }

        themeToggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('light-theme');
            if (document.body.classList.contains('light-theme')) {
                themeIcon.className = 'fa-solid fa-sun';
                localStorage.setItem('theme', 'light');
            } else {
                themeIcon.className = 'fa-solid fa-moon';
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>
