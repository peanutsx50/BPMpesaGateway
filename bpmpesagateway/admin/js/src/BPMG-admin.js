document.addEventListener("DOMContentLoaded", function () {
  const inputfield = document.querySelectorAll("noCopyPaste");

  inputfield.forEach((field) => {
    field.addEventListener("copy", (e) => {
      e.preventDefault();
    });

    field.addEventListener("cut", (e) => {
      e.preventDefault();
    });
  });
});
