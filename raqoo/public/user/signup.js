document.addEventListener("DOMContentLoaded", () => {
  const departmentSelect = document.querySelector("#department");
  const majorSelect = document.querySelector("#major_id");

  if (!departmentSelect || !majorSelect) return;

  departmentSelect.addEventListener("change", () => {
    const departmentId = departmentSelect.value;

    if (!departmentId) {
      majorSelect.innerHTML = '<option value="">学科を選択してください</option>';
      majorSelect.disabled = true;
      return;
    }

    majorSelect.disabled = true;
    majorSelect.innerHTML = '<option value="">読み込み中...</option>';

    fetch(`/majors?department_id=${encodeURIComponent(departmentId)}`, { cache: "no-store" })
      .then((res) => res.json())
      .then((majors) => {
        majorSelect.innerHTML = "";

        const placeholder = document.createElement("option");
        placeholder.value = "";
        placeholder.textContent = "選択してください";
        majorSelect.appendChild(placeholder);

        majors.forEach((major) => {
          const option = document.createElement("option");
          option.value = major.id;
          option.textContent = major.name;
          majorSelect.appendChild(option);
        });

        majorSelect.disabled = false;
      })
      .catch((error) => {
        console.error("専攻の取得に失敗しました:", error);
        majorSelect.innerHTML = '<option value="">取得に失敗しました</option>';
      });
  });
});
