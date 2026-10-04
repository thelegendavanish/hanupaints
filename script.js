// Hanu Enterprises website interactions
document.addEventListener("DOMContentLoaded", () => {
  const menuToggle = document.getElementById("menuToggle");
  const mainNav = document.getElementById("mainNav");
  const queryForm = document.getElementById("queryForm");
  const formStatus = document.getElementById("formStatus");

  // Keep the footer year current.
  document.getElementById("year").textContent = new Date().getFullYear();

  // Accessible mobile navigation.
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

  // Prefill the query form when a customer clicks a product enquiry link.
  document.querySelectorAll("[data-product]").forEach((link) => {
    link.addEventListener("click", () => {
      const select = document.getElementById("queryType");
      const requested = link.dataset.product;
      const matchingOption = Array.from(select.options).find((option) => option.text === requested);
      if (matchingOption) select.value = matchingOption.value;
      document.getElementById("message").value = `I would like to know more about ${requested.toLowerCase()}.`;
    });
  });

  // Static-site form: compose an email instead of pretending a server sent it.
  queryForm.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!queryForm.reportValidity()) return;

    const formData = new FormData(queryForm);
    const name = String(formData.get("name") || "").trim();
    const phone = String(formData.get("phone") || "").trim();
    const email = String(formData.get("email") || "").trim();
    const interest = String(formData.get("interest") || "").trim();
    const message = String(formData.get("message") || "").trim();

    const subject = `Website enquiry: ${interest} — ${name}`;
    const body = [
      "Hello Hanu Enterprises,",
      "",
      "I would like to make the following enquiry:",
      `Name: ${name}`,
      `Phone: ${phone}`,
      `Email: ${email || "Not provided"}`,
      `Interested in: ${interest}`,
      "",
      "Message:",
      message,
      "",
      "Sent from the Hanu Enterprises website."
    ].join("\n");

    const mailto = `mailto:hanuenterprises@gmail.com?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    formStatus.textContent = "Opening your email app with your enquiry. Please send the prepared email to complete the request.";
    window.location.href = mailto;
  });
});
