document.addEventListener("DOMContentLoaded", function () {
  let currentStep = 1;
  const totalSteps = 4;
  const nextBtn = document.getElementById("nextBtn");
  const prevBtn = document.getElementById("prevBtn");

  // Get AJAX URL from localized script
  const ajaxUrl = crf_ajax.ajax_url;
  const ajaxNonce = crf_ajax.nonce;

  // 1. Password Visibility Toggle
  document.querySelectorAll(".toggle-password").forEach((icon) => {
    icon.addEventListener("click", function () {
      const input = this.parentElement.querySelector(".password-field");
      const isPassword = input.type === "password";
      input.type = isPassword ? "text" : "password";
      this.classList.toggle("fa-eye-slash", !isPassword);
      this.classList.toggle("fa-eye", isPassword);
    });
  });

  // 2. UI Update Function
  function updateUI() {
    document
      .querySelectorAll(".form-section")
      .forEach((s) => s.classList.remove("active"));
    document.getElementById(`section-${currentStep}`).classList.add("active");

    document.querySelectorAll(".step").forEach((s, idx) => {
      s.classList.toggle("active", idx + 1 <= currentStep);
    });

    prevBtn.style.display = currentStep === 1 ? "none" : "inline-block";
    nextBtn.innerText = currentStep === totalSteps ? "REGISTER" : "NEXT";

    // Show plan selection on step 1
    const planSection = document.getElementById("plan-selection");
    if (planSection) {
      planSection.style.display = currentStep === 1 ? "block" : "none";
    }
  }

  // 3. Validate Step 1
  function validateStep1() {
    let valid = true;

    // Check required fields
    const requiredFields = document.querySelectorAll(
      "#section-1 input[required]"
    );
    requiredFields.forEach((field) => {
      if (!field.value.trim()) {
        field.reportValidity();
        valid = false;
      }
    });

    if (!valid) return false;

    // Password match validation
    const pwd = document.getElementById("main_pwd").value;
    const confirm = document.getElementById("confirm_pwd").value;
    const errorMsg = document.getElementById("pass-error");

    if (pwd !== confirm) {
      errorMsg.style.display = "block";
      return false;
    } else {
      errorMsg.style.display = "none";
    }

    // Phone validation for Bangladesh
    const phoneInput = document.getElementById("user_phone");
    const phoneError = document.getElementById("phone-error");
    const phoneRegex = /^(01)[3-9]\d{8}$/;

    if (!phoneRegex.test(phoneInput.value)) {
      phoneError.style.display = "block";
      phoneInput.style.borderColor = "#ff4d4d";
      return false;
    } else {
      phoneError.style.display = "none";
      phoneInput.style.borderColor = "";
    }

    // Email validation
    const emailInput = document.getElementById("user_email");
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailInput.value)) {
      alert("Please enter a valid email address");
      return false;
    }

    return true;
  }

  // 4. Validate other steps
  function validateStep(step) {
    if (step === 1) return validateStep1();

    const currentSection = document.getElementById(`section-${step}`);
    const inputs = currentSection.querySelectorAll("input[required]");

    for (let input of inputs) {
      if (!input.checkValidity()) {
        input.reportValidity();
        return false;
      }
    }

    return true;
  }

  // 5. Navigation Handler
  nextBtn.addEventListener("click", () => {
    if (!validateStep(currentStep)) {
      return;
    }

    if (currentStep < totalSteps) {
      currentStep++;
      updateUI();
    } else {
      // Submit registration
      submitRegistration();
    }
  });

  prevBtn.addEventListener("click", () => {
    if (currentStep > 1) {
      currentStep--;
      updateUI();
    }
  });

  // 6. Phone input formatting
  const phoneInput = document.getElementById("user_phone");
  phoneInput.addEventListener("input", function (e) {
    this.value = this.value.replace(/[^\d]/g, "").substring(0, 11);
  });

  // 7. Registration Submission
  function submitRegistration() {
    // Collect all form data
    const formData = new FormData();
    formData.append("action", "register_user_action");
    formData.append(
      "security",
      document.querySelector('input[name="security"]').value
    );
    formData.append("email", document.getElementById("user_email").value);
    formData.append(
      "first_name",
      document.querySelector('input[name="first_name"]').value
    );
    formData.append(
      "last_name",
      document.querySelector('input[name="last_name"]').value
    );
    formData.append("password", document.getElementById("main_pwd").value);
    formData.append("phone", document.getElementById("user_phone").value);
    formData.append(
      "sponsor",
      document.querySelector('input[name="sponsor"]').value
    );
    formData.append(
      "membership_plan",
      document.getElementById("membership_plan").value
    );
    formData.append(
      "terms_agree",
      document.getElementById("terms_agree").checked ? "1" : "0"
    );
    formData.append("email_code", document.getElementById("email_code").value);
    formData.append("sms_code", document.getElementById("sms_code").value);

    // Disable button and show processing
    nextBtn.innerText = "PROCESSING...";
    nextBtn.disabled = true;

    // Send AJAX request
    fetch(ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Show success message
          const form = document.getElementById("multi-step-form");
          form.innerHTML = `
            <div class="success-msg">
              <i class="fas fa-check-circle" style="font-size: 48px; color: #4CAF50; margin-bottom: 20px;"></i>
              <h3>Registration Successful!</h3>
              <p>${data.data.message}</p>
              <p>User ID: ${data.data.user_id}</p>
              <p>URMembership Integration: ${data.data.urm_integration}</p>
              <p>You can now <a href="${window.location.origin}/wp-login.php">log in</a> to your account.</p>
            </div>
          `;
        } else {
          alert("Error: " + data.data);
          nextBtn.innerText = "REGISTER";
          nextBtn.disabled = false;
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
        nextBtn.innerText = "REGISTER";
        nextBtn.disabled = false;
      });
  }

  // Initialize UI
  updateUI();
});
