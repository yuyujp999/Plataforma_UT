document.querySelectorAll(".btn-elegir").forEach((btn) => {
  btn.addEventListener("click", (e) => {
    document.getElementById("rolInput").value = e.target.dataset.rol;
    document.getElementById("rolForm").submit();
  });
});
