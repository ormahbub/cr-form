document.addEventListener("DOMContentLoaded", function () {
  let currentStep = 1;
  const totalSteps = 4;
  const nextBtn = document.getElementById("nextBtn");
  const prevBtn = document.getElementById("prevBtn");

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
    nextBtn.innerText = currentStep === totalSteps ? "RESISTER" : "NEXT";
  }

  // 3. Navigation and Validation
  nextBtn.addEventListener("click", () => {
    const currentSection = document.getElementById(`section-${currentStep}`);
    const inputs = currentSection.querySelectorAll("input[required]");
    let valid = true;

    // Basic HTML5 validation
    inputs.forEach((input) => {
      if (!input.checkValidity()) {
        input.reportValidity();
        valid = false;
      }
    });

    // Custom Password Match Check (Only on Step 1)
    if (currentStep === 1 && valid) {
      const pwd = document.getElementById("main_pwd").value;
      const confirm = document.getElementById("confirm_pwd").value;
      const errorMsg = document.getElementById("pass-error");

      if (pwd !== confirm) {
        errorMsg.style.display = "block";
        valid = false;
      } else {
        errorMsg.style.display = "none";
      }
    }

    if (valid) {
      if (currentStep < totalSteps) {
        currentStep++;
        updateUI();
      } else {
        // Collect all data from the form
        const formData = new FormData();
        formData.append("action", "register_user_action");
        formData.append("security", document.getElementById("security").value);
        formData.append(
          "email",
          document.querySelector('input[type="email"]').value
        );
        formData.append(
          "first_name",
          document.querySelector('input[name="first_name"]').value
        );
        formData.append(
          "last_name",
          document.querySelector('input[name="last_name"]').value
        );
        formData.append("password", document.getElementById("main_pwd").value);
        formData.append(
          "phone",
          document.querySelector('input[type="tel"]').value
        );
        formData.append(
          "sponsor",
          document.querySelector('input[name="sponsor"]').value
        );

        // Send to WordPress
        nextBtn.innerText = "PROCESSING...";
        nextBtn.disabled = true;

        fetch("http://localhost/wp_06/wp-admin/admin-ajax.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              document.getElementById(
                "multi-step-form"
              ).innerHTML = `<div class="success-msg">${data.data}</div>`;
            } else {
              alert("Error: " + data.data);
              nextBtn.innerText = "SUBMIT";
              nextBtn.disabled = false;
            }
          })
          .catch((error) => {
            console.error("Error:", error);
            nextBtn.innerText = "SUBMIT";
            nextBtn.disabled = false;
          });
      }
    }
  });

  prevBtn.addEventListener("click", () => {
    if (currentStep > 1) {
      currentStep--;
      updateUI();
    }
  });
});
