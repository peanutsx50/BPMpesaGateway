document.addEventListener("DOMContentLoaded", function () {
  const button = document.getElementById("bpmg_send_mpesa_request");
  const phoneInput = document.getElementById("bpmg_mpesa_phone");
  const errorDiv = document.getElementById("bpmg_error_message");

  if (button) {
    button.addEventListener("click", function (e) {
      const phoneNumber = phoneInput.value.trim();

      // Validate Kenya phone number format
      // Accepts: +254xxxxxxxxx only
      const phonePattern = /^(?:\+254)(?:7[0-9]|1[01])[0-9]{7}$/;
      const cleanPhone = phoneNumber.replace(/\s/g, "");

      // Clear previous error
      errorDiv.style.display = "none";
      errorDiv.textContent = "";

      if (!phoneNumber) {
        e.preventDefault();
        errorDiv.textContent = "M-Pesa phone number is required.";
        errorDiv.style.display = "block";
        return false;
      }

      if (!phonePattern.test(cleanPhone)) {
        e.preventDefault();
        errorDiv.textContent =
          "Please enter a valid Kenya M-Pesa phone number (e.g., +254712345678).";
        errorDiv.style.display = "block";
        return false;
      }

      // Phone is valid, proceed with M-Pesa request
      console.log("Valid phone number:", cleanPhone);
    });
  }
});
