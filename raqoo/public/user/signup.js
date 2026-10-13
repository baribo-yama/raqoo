document.addEventListener("DOMContentLoaded", () => {
  const departmentSelect = document.querySelector("#department");
  const majorSelect = document.querySelector("#major_id");

  if (!departmentSelect || !majorSelect) return;
  let oldMajorId = majorSelect.dataset.old;

  departmentSelect.addEventListener("change", () => {
    const departmentId = departmentSelect.value;

    if (!departmentId) {
      majorSelect.innerHTML = '<option value="">学科を選択してください</option>';
      majorSelect.disabled = true;
      return;
    }

    majorSelect.disabled = true;
    majorSelect.innerHTML = '<option value="">読み込み中...</option>';

    fetch(`/majors?department_id=${encodeURIComponent(departmentId)}`, {
      cache: "no-store",
      headers: { Accept: "application/json" },
    })
      .then((res) => {
        if(!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
      })
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

        if(oldMajorId){
          majorSelect.value = oldMajorId;
          oldMajorId = "";
        }

        majorSelect.disabled = false;
      })
      .catch((error) => {
        console.error("専攻の取得に失敗しました:", error);
        majorSelect.innerHTML = '<option value="">取得に失敗しました</option>';
      });
  });
  if (departmentSelect.value) {
    departmentSelect.dispatchEvent(new Event("change"));
  }

});
