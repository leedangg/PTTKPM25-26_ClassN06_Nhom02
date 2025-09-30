function formatDate(date) {
  if (!date) return "";
  const [year, month, day] = date.split("-");
  return `${day}/${month}/${year}`;
}

function parseDateForInput(dateStr) {
  if (!dateStr) return "";
  const [day, month, year] = dateStr.split("/");
  return `${year}-${month}-${day}`;
}

document.addEventListener("DOMContentLoaded", function () {
  const dateInputs = document.querySelectorAll('input[type="date"]');

  dateInputs.forEach((input) => {
    // Xử lý khi người dùng chọn ngày
    input.addEventListener("change", function (e) {
      const date = this.value;
      if (date) {
        const formattedDate = formatDate(date);
        this.setAttribute("data-date", formattedDate);
      }
    });

    // Format ngày hiện tại nếu có
    if (input.value) {
      const formattedDate = formatDate(input.value);
      input.setAttribute("data-date", formattedDate);
    }
  });
});
