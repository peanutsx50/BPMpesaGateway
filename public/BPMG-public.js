document.addEventListener("DOMContentLoaded", function () {
  const button = document.getElementById("bpmg_send_mpesa_request");
  const phoneInput = document.getElementById("bpmg_mpesa_phone");
  const errorDiv = document.getElementById("bpmg_error_message");

  if (button) {
    button.addEventListener("click", function (e) {
      const phoneNumber = phoneInput.value.trim();

      // Validate Kenya phone number format
      // Accepts: 254xxxxxxxxx only
      const phonePattern = /^254(?:7[0-9]|1[01])[0-9]{7}$/;
      const cleanPhone = phoneNumber.replace(/\s/g, "").replace(/^\+/, "");

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
          "Please enter a valid Kenya M-Pesa phone number (e.g., 254712345678).";
        errorDiv.style.display = "block";
        return false;
      }

      // Phone is valid, proceed with M-Pesa request
      try {
        button.disabled = true; // Prevent multiple clicks
        button.textContent = "Sending Request..."; // loading state
        bpmg_send_mpesa_request(button);
      } catch (error) {
        e.preventDefault();
        console.error("Error:", error);
        button.disabled = false;
        button.textContent = "Send M-Pesa Payment Request";
        errorDiv.textContent =
          "An error occurred while sending the M-Pesa request. Please try again.";
        errorDiv.style.display = "block";
        return false;
      }
    });
  }
});

//ajax function to send mpesa request
function bpmg_send_mpesa_request(button) {
  fetch(bpmpesa_ajax.ajax_url, {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: new URLSearchParams({
      action: "bpmg_send_mpesa_request", //match action hook in PHP
      phone: document.getElementById("bpmg_mpesa_phone").value.trim(), // data for function
      bpmg_nonce: bpmpesa_ajax.nonce, // pass the security nonce
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      // Payment request sent successfully
      if (data.success) {
        button.textContent = data.data?.message;
        // check if user accepted or failed to complete payment
        bpmg_start_mpesa_polling(
          data.data?.response?.CheckoutRequestID,
          button
        );
      } else {
        button.disabled = false;
        button.textContent = "Send M-Pesa Payment Request";
        const errorDiv = document.getElementById("bpmg_error_message");
        // Display the error message from server
        errorDiv.textContent = data.data?.message;
        errorDiv.style.display = "block";
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      // Network or other error
      button.disabled = false;
      button.textContent = "Send M-Pesa Payment Request";
      const errorDiv = document.getElementById("bpmg_error_message");
      errorDiv.textContent =
        "An error occurred while sending the M-Pesa request. Please try again.";
      errorDiv.style.display = "block";
    });
}

function bpmg_start_mpesa_polling(checkoutId, button) {

  let pollCount = 0;
  const maxPolls = 20; // Stop after 2 minutes (20 * 3 seconds)

  const interval = setInterval(() => {
    pollCount++;

    if (pollCount > maxPolls) {
      clearInterval(interval);
      button.disabled = false;
      button.textContent = "Payment timeout. Please try again.";
      button.style.backgroundColor = "#ff9800";
      return;
    }

    fetch(`${bpmpesa_ajax.callback_url}?checkout_id=${checkoutId}`, {
      method: "GET",
      credentials: "same-origin",
    })
      .then((res) => res.json())
      .then((data) => {
        console.log("data", data);
        const status = data.status;
        if (status === "success") {
          clearInterval(interval);
          button.textContent = "Payment successful. Continue registration.";
          button.style.backgroundColor = "#4CAF50";
          button.style.borderColor = "#4CAF50";
        }

        if (status === "failed") {
          clearInterval(interval);
          button.disabled = false;
          button.textContent = "Payment failed. Try again.";
        }
      })
      .catch((err) => {
        console.error("Polling error:", err);
      });
  }, 3000); // poll every 3 sec
}
