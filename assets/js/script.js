// HANU PAINTS AND DECOR website interactions
document.addEventListener("DOMContentLoaded", () => {
  const menuToggle = document.getElementById("menuToggle");
  const mainNav    = document.getElementById("mainNav");
  const queryForm  = document.getElementById("queryForm");
  const formStatus = document.getElementById("formStatus");
  const submitBtn  = queryForm.querySelector(".submit-btn");

  // Keep the footer year current.
  document.getElementById("year").textContent = new Date().getFullYear();

  // ── Mobile navigation ────────────────────────────────────────────────────
  menuToggle.addEventListener("click", () => {
    const isOpen = mainNav.classList.toggle("open");
    menuToggle.setAttribute("aria-expanded", String(isOpen));
    menuToggle.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
  });

  mainNav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      mainNav.classList.remove("open");
      menuToggle.setAttribute("aria-expanded", "false");
      menuToggle.setAttribute("aria-label", "Open navigation");
      mainNav.querySelectorAll("a").forEach((item) => item.classList.remove("active"));
      link.classList.add("active");
    });
  });

  // ── Pre-fill form when a product "Ask about this" link is clicked ────────
  document.querySelectorAll("[data-product]").forEach((link) => {
    link.addEventListener("click", () => {
      const select    = document.getElementById("queryType");
      const requested = link.dataset.product;
      const matchingOption = Array.from(select.options).find(
        (option) => option.text === requested
      );
      if (matchingOption) select.value = matchingOption.value;
      document.getElementById("message").value =
        `I would like to know more about ${requested.toLowerCase()}.`;
    });
  });

  // ── Helper: show a status message ────────────────────────────────────────
  function showStatus(text, isError) {
    formStatus.textContent = text;
    formStatus.className   = "form-status " + (isError ? "form-status--error" : "form-status--ok");
  }

  function clearStatus() {
    formStatus.textContent = "";
    formStatus.className   = "form-status";
  }

  // ── Simple client-side validation (mirrors PHP rules) ───────────────────
  function validateForm(data) {
    const name     = (data.get("name")     || "").trim();
    const phone    = (data.get("phone")    || "").trim();
    const email    = (data.get("email")    || "").trim();
    const interest = (data.get("interest") || "").trim();
    const message  = (data.get("message")  || "").trim();
    const consent  = data.get("consent");

    if (name.length < 2)                 return "Please enter your full name.";
    if (!/^[0-9+() \-]{8,18}$/.test(phone)) return "Please enter a valid phone number.";
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
                                         return "Please enter a valid email address.";
    if (!interest)                       return "Please select what you are looking for.";
    if (message.length < 5)             return "Your message is too short — please tell us a bit more.";
    if (!consent)                        return "Please confirm that you agree to be contacted.";
    return null; // all good
  }

  // ── Contact form: AJAX submission to contact.php ─────────────────────────
  queryForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearStatus();

    const formData = new FormData(queryForm);

    // Client-side check first (gives instant feedback without a round-trip).
    const clientError = validateForm(formData);
    if (clientError) {
      showStatus(clientError, true);
      return;
    }

    // Disable the button while the request is in flight.
    submitBtn.disabled = true;
    submitBtn.textContent = "Sending…";

    try {
      const response = await fetch("api/contact.php", {
        method:  "POST",
        body:    formData,
      });

      // Parse the JSON response from contact.php.
      const result = await response.json();

      if (result.success) {
        showStatus(result.message, false);
        queryForm.reset();
      } else {
        showStatus(result.message || "Something went wrong. Please try again.", true);
      }
    } catch {
      showStatus(
        "Could not reach the server. Please check your connection or call us directly.",
        true
      );
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = "Send Query <span>↗</span>";
    }
  });
});
