document.addEventListener("DOMContentLoaded", function () {
	const button = document.getElementById("bpmg_send_mpesa_request");
	const phoneInput = document.getElementById("bpmg_mpesa_phone");
	const errorDiv = document.getElementById("bpmg_error_message");
	const submitBtn = document.querySelector('#signup-form input[type="submit"]');

	// Guard: required elements must exist before proceeding
	if (!button || !phoneInput || !errorDiv || !submitBtn) return;

	if (bpmg_get_cookie("payment") === "paid") {
		bpmg_mark_payment_success(button, phoneInput);
		return; // Do NOT allow STK push again
	}

	// Block form submission until payment is confirmed
	const submitClickHandler = function (e) {
		// Re-read cookie at click time rather than relying on stale closure value
		if (bpmg_get_cookie("payment") !== "paid") {
			e.preventDefault();
			e.stopImmediatePropagation();
			bpmg_show_error(
				errorDiv,
				"Please complete M-Pesa payment before continuing.",
			);
			return false;
		}
	};

	submitBtn.addEventListener("click", submitClickHandler, true);
	window.bpmg_submitClickHandler = submitClickHandler;
	window.bpmg_submitBtn = submitBtn;

	button.addEventListener("click", function (e) {
		const rawPhone = phoneInput.value.trim();

		bpmg_clear_error(errorDiv);

		if (!rawPhone) {
			errorDiv.textContent = "M-Pesa phone number is required.";
			errorDiv.style.display = "block";
			return;
		}
		console.log(rawPhone);

		// Use shared cleanPhoneNumber utility instead of duplicating logic
		const cleanPhone = cleanPhoneNumber(rawPhone);

		if (!cleanPhone) {
			bpmg_show_error(
				errorDiv,
				"Please enter a valid Kenya M-Pesa phone number (e.g., 254712345678 or 0712345678).",
			);
			return;
		}

		button.disabled = true;
		button.textContent = "Sending Request...";

		bpmg_send_mpesa_request(button, cleanPhone, errorDiv);
	});
});

/**
 * Send the STK push request to the server.
 */
function bpmg_send_mpesa_request(button, phoneNumber, errorDiv) {
	errorDiv = errorDiv || document.getElementById("bpmg_error_message");

	fetch(bpmpesa_ajax.process_payment_url, {
		method: "POST",
		credentials: "same-origin",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
			"X-WP-Nonce": bpmpesa_ajax.nonce, // WordPress checks this automatically
		},
		body: new URLSearchParams({ phone_number: phoneNumber }),
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				console.log("M-Pesa request initiated successfully:", data);
				bpmg_start_mpesa_polling(
					data.data.checkout_id,
					button,
				);
			} else {
				bpmg_reset_button(button);
				bpmg_show_error(
					errorDiv,
					data.data?.message || "Failed to initiate M-Pesa request.",
				);
			}
		})
		.catch((error) => {
			console.error("M-Pesa request error:", error);
			bpmg_reset_button(button);
			bpmg_show_error(errorDiv, "A network error occurred. Please try again.");
		});
}

/**
 * Poll the server for payment status until confirmed, failed, or timed out.
 * Uses async/await with exponential backoff and consecutive error tracking.
 *
 * @param {string} checkoutId     - The M-Pesa CheckoutRequestID to poll for
 * @param {HTMLElement} button    - The STK push button element
 * @param {string} phoneNumber    - The phone number used for payment (unused but kept for API consistency)
 * @param {number} maxAttempts    - Maximum number of poll attempts (default: 20)
 * @param {number} pollInterval   - Initial interval in ms between polls (default: 6000)
 */
async function bpmg_start_mpesa_polling(
	checkoutId,
	button,
	maxAttempts = 20,
	pollInterval = 6000,
) {
	const errorDiv = document.getElementById("bpmg_error_message");

	if (!checkoutId) {
		bpmg_reset_button(button);
		bpmg_show_error(errorDiv, "Invalid checkout session. Please try again.");
		return;
	}

	let pollCount = 0;
	let continuePolling = true;
	let consecutiveErrors = 0;
	const maxConsecutiveErrors = 3;

	button.textContent = "Waiting for payment confirmation...";

	while (pollCount < maxAttempts && continuePolling) {
		pollCount++;

		try {
			const response = await fetch(bpmpesa_ajax.confirm_payment_url, {
				method: "POST",
				credentials: "same-origin",
				headers: {
					"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
					"X-WP-Nonce": bpmpesa_ajax.nonce,
				},
				body: new URLSearchParams({ checkout_id: checkoutId }),
			});

			// 1. Check if the server actually responded with a 200-level status
			if (!response.ok) throw new Error(`Server error: ${response.status}`);
			consecutiveErrors = 0; // Reset on any successful HTTP response

			// 2. Parse JSON
			const data = await response.json();

			if (data.status === "success") {
				document.cookie = "payment=paid; path=/; SameSite=Lax; Secure";
				bpmg_mark_payment_success(
					button,
					document.getElementById("bpmg_mpesa_phone"),
				);

				// Unblock form submission
				if (window.bpmg_submitBtn && window.bpmg_submitClickHandler) {
					window.bpmg_submitBtn.removeEventListener(
						"click",
						window.bpmg_submitClickHandler,
						true,
					);
				}

				continuePolling = false;
				return;
			}

			if (data.status === "failed") {
				bpmg_reset_button(button);
				button.textContent = "Payment failed. Try again.";
				bpmg_show_error(
					errorDiv,
					data.message || "Payment was not completed. Please try again.",
				);
				continuePolling = false;
				return;
			}

			// Any other status (e.g. "pending") — fall through to wait and retry

		} catch (error) {
			consecutiveErrors++;
			console.error(`Polling error (${consecutiveErrors}/${maxConsecutiveErrors}):`, error);

			if (consecutiveErrors >= maxConsecutiveErrors) {
				bpmg_reset_button(button);
				button.textContent = "Connection lost. Please check your payment and try again.";
				bpmg_show_error(
					errorDiv,
					"A network error occurred while confirming payment. Please try again.",
				);
				continuePolling = false;
				return;
			}
		}

		// Wait before next attempt (only if we're going to poll again)
		if (pollCount < maxAttempts && continuePolling) {
			await new Promise((resolve) => setTimeout(resolve, pollInterval));
			pollInterval = Math.min(pollInterval * 1.2, 10000); // Backoff, cap at 10s
		}
	}

	// Only reached if max attempts exceeded without success or failure
	if (continuePolling) {
		bpmg_reset_button(button);
		button.textContent = "Payment timeout. Please try again.";
		button.style.backgroundColor = "#ff9800";
	}
}

/**
 * Normalize and validate a Kenyan phone number.
 * Accepts: 07xxxxxxxx, 254xxxxxxxxx, +254xxxxxxxxx
 * Returns the normalized 254xxxxxxxxx string, or false if invalid.
 */
function cleanPhoneNumber(phone) {
	// Input validation
	if (!phone || typeof phone !== "string") return false;

	let cleaned = phone.trim().replace(/[\s\-\+]/g, "");

	// Length constraints
	if (cleaned.length < 10 || cleaned.length > 20) return false;

	// Handle different input formats
	if (cleaned.startsWith("+254")) {
		cleaned = cleaned.substring(1); // Remove +
	} else if (cleaned.startsWith("07")) {
		cleaned = "254" + cleaned.substring(1); // Convert 07 to 254
	}

	// Validate Kenyan number format
	const phonePattern =
		/^254(7(?:[0129][0-9]|4[0-3568]|5[7-9]|6[89])|11[0-5])\d{6}$/; // Get pattern from localized script

	if (!phonePattern.test(cleaned)) {
		return false;
	}
	return cleaned; // Return normalized number
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function bpmg_mark_payment_success(button, phoneInput) {
	button.disabled = true;
	button.textContent = "Payment successful. Continue registration.";
	button.style.backgroundColor = "#4CAF50";
	button.style.borderColor = "#4CAF50";
	if (phoneInput) phoneInput.disabled = true;
}

function bpmg_reset_button(button) {
	button.disabled = false;
	button.textContent = "Send M-Pesa Payment Request";
	button.style.backgroundColor = "";
	button.style.borderColor = "";
}

function bpmg_show_error(errorDiv, message) {
	errorDiv.textContent = message;
	errorDiv.style.display = "block";
}

function bpmg_clear_error(errorDiv) {
	errorDiv.textContent = "";
	errorDiv.style.display = "none";
}

/** Read a cookie value by name. Returns null if not found. */
function bpmg_get_cookie(name) {
	const value = `; ${document.cookie}`;
	const parts = value.split(`; ${name}=`);
	if (parts.length === 2) return parts.pop().split(";").shift();
	return null;
}
