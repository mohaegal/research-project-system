<?php
// survey_employee.php
// Questionnaire for Young Employees (Appendix II)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Questionnaire - Youth Employment Study</title>
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
            color: var(--accent-primary);
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
            border-left: 3px solid var(--accent-primary);
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
                <li class="nav-item active"><a href="survey_employee.php">Employee Survey</a></li>
                <li class="nav-item"><a href="survey_owner.php">Owner Interview</a></li>
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
                <h2>Questionnaire for Young Employees</h2>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 6px;">
                    Investigating the role of small businesses in youth employment. Your responses are treated with strict confidentiality.
                </p>
            </div>

            <!-- Progress Bar -->
            <div class="progress-bar-container">
                <div class="progress-bar-fill" id="progress-bar"></div>
            </div>

            <div id="alert-container"></div>

            <!-- Form -->
            <form id="survey-form" onsubmit="submitSurvey(event)">
                <!-- STEP 1: Section A - Personal Info -->
                <div class="survey-step active" id="step-1">
                    <h3 class="form-section-title"><i class="fa-solid fa-circle-user"></i> Section A: Personal Information</h3>
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label for="fullname">1. Full Name <span style="color: var(--accent-danger);">*</span></label>
                        <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Enter your full name" required>
                    </div>

                    <!-- Job Title -->
                    <div class="form-group">
                        <label for="job_title">2. Job Title / Occupation <span style="color: var(--accent-danger);">*</span></label>
                        <input type="text" id="job_title" name="job_title" class="form-control" placeholder="e.g. Sales Assistant, Hairstylist, Rider" required>
                    </div>

                    <!-- Gender -->
                    <div class="form-group">
                        <label>3. Gender <span style="color: var(--accent-danger);">*</span></label>
                        <div class="options-grid">
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="gender" value="Male" required>
                                <span>Male</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="gender" value="Female">
                                <span>Female</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="gender" value="Prefer not to say">
                                <span>Prefer not to say</span>
                            </label>
                        </div>
                    </div>

                    <!-- Age -->
                    <div class="form-group">
                        <label>4. Age bracket <span style="color: var(--accent-danger);">*</span></label>
                        <div class="options-grid">
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="age" value="18-20" required>
                                <span>18-20 years</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="age" value="21-24">
                                <span>21-24 years</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="age" value="25-29">
                                <span>25-29 years</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="age" value="30-35">
                                <span>30-35 years</span>
                            </label>
                        </div>
                    </div>

                    <!-- Education -->
                    <div class="form-group">
                        <label>5. Highest Level of Education <span style="color: var(--accent-danger);">*</span></label>
                        <div class="options-grid">
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="education" value="Primary" required>
                                <span>Primary</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="education" value="Secondary">
                                <span>Secondary</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="education" value="Certificate">
                                <span>Certificate</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="education" value="Diploma">
                                <span>Diploma</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="education" value="Degree">
                                <span>Degree</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Section B - Business Info -->
                <div class="survey-step" id="step-2">
                    <h3 class="form-section-title"><i class="fa-solid fa-shop"></i> Section B: Employment Context</h3>

                    <!-- Type of business -->
                    <div class="form-group">
                        <label for="business_type">4. What type of small business are you employed in? <span style="color: var(--accent-danger);">*</span></label>
                        <select id="business_type" name="business_type" class="form-control" onchange="toggleOtherBusinessInput()" required>
                            <option value="" disabled selected>-- Select business type --</option>
                            <option value="Retail shop">Retail shop (Boutique, Grocery, etc.)</option>
                            <option value="Salon/Barbershop">Salon / Barbershop</option>
                            <option value="Restaurant/Food kiosk">Restaurant / Food kiosk</option>
                            <option value="Repair shop">Repair shop (Electronics, Mechanics, etc.)</option>
                            <option value="Transport">Transport services (Boda Boda, Taxi, etc.)</option>
                            <option value="Other">Other (Please specify)</option>
                        </select>
                        <div id="other-business-container" class="conditional-group">
                            <label for="other_business_type">Specify other business type:</label>
                            <input type="text" id="other_business_type" class="form-control" placeholder="Enter business type">
                        </div>
                    </div>

                    <!-- Work Duration -->
                    <div class="form-group">
                        <label>5. How long have you been employed in this business? <span style="color: var(--accent-danger);">*</span></label>
                        <div class="options-grid">
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="duration" value="Less than 6 months" required>
                                <span>Less than 6 months</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="duration" value="6 months-1 year">
                                <span>6 months - 1 year</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="duration" value="1-2 years">
                                <span>1 - 2 years</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="duration" value="Over 2 years">
                                <span>Over 2 years</span>
                            </label>
                        </div>
                    </div>

                    <!-- Monthly Income -->
                    <div class="form-group">
                        <label>6. What is your average monthly income from this job? <span style="color: var(--accent-danger);">*</span></label>
                        <div class="options-grid">
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="income" value="Below KES 5,000" required>
                                <span>Below KES 5,000</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="income" value="KES 5,000-10,000">
                                <span>KES 5,000 - 10,000</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="income" value="KES 10,001-20,000">
                                <span>KES 10,001 - 20,000</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="income" value="Above KES 20,000">
                                <span>Above KES 20,000</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Section C - Qualitative Feedback -->
                <div class="survey-step" id="step-3">
                    <h3 class="form-section-title"><i class="fa-solid fa-briefcase"></i> Section C: Feedback & Experience</h3>

                    <!-- Skills Improved -->
                    <div class="form-group">
                        <label>7. Has working in this small business helped improve your work skills? <span style="color: var(--accent-danger);">*</span></label>
                        <div class="options-grid" style="grid-template-columns: repeat(2, 1fr);">
                            <label class="option-box" onclick="toggleSkillsDetails(true)">
                                <input type="radio" name="skills_improved" value="Yes" required>
                                <span>Yes</span>
                            </label>
                            <label class="option-box" onclick="toggleSkillsDetails(false)">
                                <input type="radio" name="skills_improved" value="No">
                                <span>No</span>
                            </label>
                        </div>
                        <div id="skills-details-container" class="conditional-group">
                            <label for="skills_details">What skills did you develop/improve?</label>
                            <input type="text" id="skills_details" class="form-control" placeholder="e.g. communication, accounting, technical repairs">
                        </div>
                    </div>

                    <!-- Challenges Faced -->
                    <div class="form-group">
                        <label>8. Do you face any challenges in your current employment? <span style="color: var(--accent-danger);">*</span></label>
                        <div class="options-grid" style="grid-template-columns: repeat(2, 1fr);">
                            <label class="option-box" onclick="toggleChallengesDetails(true)">
                                <input type="radio" name="challenges" value="Yes" required>
                                <span>Yes</span>
                            </label>
                            <label class="option-box" onclick="toggleChallengesDetails(false)">
                                <input type="radio" name="challenges" value="No">
                                <span>No</span>
                            </label>
                        </div>
                        <div id="challenges-details-container" class="conditional-group">
                            <label for="challenges_details">Briefly explain the challenges you face:</label>
                            <input type="text" id="challenges_details" class="form-control" placeholder="e.g. low pay, long hours, instability">
                        </div>
                    </div>

                    <!-- Recommendation -->
                    <div class="form-group">
                        <label>9. Would you recommend other youth to seek jobs in small businesses? <span style="color: var(--accent-danger);">*</span></label>
                        <div class="options-grid" style="grid-template-columns: repeat(2, 1fr);">
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="recommend" value="Yes" required>
                                <span>Yes</span>
                            </label>
                            <label class="option-box" onclick="highlightSelection(this)">
                                <input type="radio" name="recommend" value="No">
                                <span>No</span>
                            </label>
                        </div>
                        <div style="margin-top: 12px;">
                            <label for="recommend_reason">State your main reason:</label>
                            <input type="text" id="recommend_reason" class="form-control" placeholder="Explain your reason" required>
                        </div>
                    </div>

                    <!-- Improvements Suggestions -->
                    <div class="form-group">
                        <label for="improvements">10. What improvements would you suggest to make small businesses better employers?</label>
                        <textarea id="improvements" class="form-control" rows="3" placeholder="Enter your suggestions..."></textarea>
                    </div>
                </div>

                <!-- Step navigation buttons -->
                <div class="survey-navigation">
                    <button type="button" class="btn btn-secondary" id="prev-btn" onclick="changeStep(-1)" style="visibility: hidden;">
                        <i class="fa-solid fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="next-btn" onclick="changeStep(1)">
                        Next <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>
            
            <!-- Success Screen -->
            <div id="success-screen" style="display: none; text-align: center; padding: 40px 10px;">
                <div style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.1); color: var(--accent-success); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 24px;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h2 class="mb-2">Survey Submitted Successfully!</h2>
                <p style="color: var(--text-secondary); margin-bottom: 30px;">
                    Thank you for taking time to participate in this academic research. Your input is extremely valuable to this study.
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
            // Uncheck other elements in matching radio group
            const input = label.querySelector('input');
            const name = input.name;
            const parent = label.closest('.form-group');
            parent.querySelectorAll('.option-box').forEach(box => {
                box.classList.remove('selected');
            });
            if (input.checked) {
                label.classList.add('selected');
            }
        }

        // Initialize click highlights on label load
        document.querySelectorAll('.option-box input').forEach(inp => {
            inp.addEventListener('change', (e) => {
                const label = e.target.closest('.option-box');
                highlightSelection(label);
            });
        });

        function toggleOtherBusinessInput() {
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

        function toggleSkillsDetails(show) {
            const container = document.getElementById('skills-details-container');
            const detailInput = document.getElementById('skills_details');
            const labelYes = document.querySelector('input[name="skills_improved"][value="Yes"]').closest('.option-box');
            const labelNo = document.querySelector('input[name="skills_improved"][value="No"]').closest('.option-box');
            
            labelYes.classList.toggle('selected', show);
            labelNo.classList.toggle('selected', !show);

            if (show) {
                container.style.display = 'block';
                detailInput.required = true;
            } else {
                container.style.display = 'none';
                detailInput.required = false;
                detailInput.value = '';
            }
        }

        function toggleChallengesDetails(show) {
            const container = document.getElementById('challenges-details-container');
            const detailInput = document.getElementById('challenges_details');
            const labelYes = document.querySelector('input[name="challenges"][value="Yes"]').closest('.option-box');
            const labelNo = document.querySelector('input[name="challenges"][value="No"]').closest('.option-box');
            
            labelYes.classList.toggle('selected', show);
            labelNo.classList.toggle('selected', !show);

            if (show) {
                container.style.display = 'block';
                detailInput.required = true;
            } else {
                container.style.display = 'none';
                detailInput.required = false;
                detailInput.value = '';
            }
        }

        function validateStep(step) {
            const stepElement = document.getElementById('step-' + step);
            const requiredFields = stepElement.querySelectorAll('[required]');
            let valid = true;

            requiredFields.forEach(field => {
                if (field.type === 'radio') {
                    // Check if group is checked
                    const name = field.name;
                    const checked = stepElement.querySelector(`input[name="${name}"]:checked`);
                    if (!checked) {
                        valid = false;
                        // Add error outline
                        field.closest('.form-group').style.borderLeft = '3px solid var(--accent-danger)';
                    } else {
                        field.closest('.form-group').style.borderLeft = 'none';
                    }
                } else if (field.tagName === 'SELECT' || field.type === 'text') {
                    if (!field.value.trim()) {
                        valid = false;
                        field.classList.add('error');
                        field.style.borderColor = 'var(--accent-danger)';
                    } else {
                        field.classList.remove('error');
                        field.style.borderColor = 'var(--border-glass)';
                    }
                }
            });

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
                nextBtn.innerHTML = 'Submit Questionnaire <i class="fa-solid fa-paper-plane"></i>';
                nextBtn.type = 'submit';
                nextBtn.onclick = null; // Let standard form submission handle it
            } else {
                nextBtn.innerHTML = 'Next <i class="fa-solid fa-arrow-right"></i>';
                nextBtn.type = 'button';
                nextBtn.onclick = () => changeStep(1);
            }

            updateProgressBar();
            window.scrollTo(0, 0);
        }

        async function submitSurvey(e) {
            e.preventDefault();
            
            if (!validateStep(currentStep)) return;

            const submitBtn = document.getElementById('next-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

            // Gather inputs
            const payload = {
                fullname: document.getElementById('fullname').value.trim(),
                job_title: document.getElementById('job_title').value.trim(),
                gender: document.querySelector('input[name="gender"]:checked')?.value || '',
                age: document.querySelector('input[name="age"]:checked')?.value || '',
                education: document.querySelector('input[name="education"]:checked')?.value || '',
                business_type: document.getElementById('business_type').value === 'Other' 
                    ? document.getElementById('other_business_type').value 
                    : document.getElementById('business_type').value,
                duration: document.querySelector('input[name="duration"]:checked')?.value || '',
                income: document.querySelector('input[name="income"]:checked')?.value || '',
                skills_improved: document.querySelector('input[name="skills_improved"]:checked')?.value || '',
                skills_details: document.getElementById('skills_details').value,
                challenges: document.querySelector('input[name="challenges"]:checked')?.value || '',
                challenges_details: document.getElementById('challenges_details').value,
                recommend: document.querySelector('input[name="recommend"]:checked')?.value || '',
                recommend_reason: document.getElementById('recommend_reason').value,
                improvements: document.getElementById('improvements').value
            };

            try {
                const response = await fetch('api.php?action=submit_employee', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    // Show success screen
                    document.getElementById('survey-form').style.display = 'none';
                    document.querySelector('.progress-bar-container').style.display = 'none';
                    document.getElementById('success-screen').style.display = 'block';
                } else {
                    document.getElementById('alert-container').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-circle-xmark"></i> ${result.message}
                        </div>
                    `;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit Questionnaire <i class="fa-solid fa-paper-plane"></i>';
                }
            } catch (err) {
                document.getElementById('alert-container').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-xmark"></i> Connection error. Please try again.
                    </div>
                `;
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Questionnaire <i class="fa-solid fa-paper-plane"></i>';
            }
        }

        // Initialize progress bar
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
