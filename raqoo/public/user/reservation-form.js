document.addEventListener('DOMContentLoaded', function() {
    const purposeTextarea = document.getElementById('purpose');
    const purposeCount = document.getElementById('purposeCount');
    if (purposeTextarea) {
        purposeTextarea.addEventListener('input', function() {
            purposeCount.textContent = this.value.length;
        });
        purposeCount.textContent = purposeTextarea.value.length;
    }

    const membersHidden = document.getElementById('membersHidden');
    const membersProxy = document.getElementById('members');
    const memberInputs = document.querySelectorAll('.member-name-input');
    const memberRows = document.querySelectorAll('.member-row');
    const addMemberRowsButton = document.getElementById('addMemberRowsButton');
    const memberRowCount = document.getElementById('memberRowCount');
    const maxVisibleMemberCount = 30;
    const addMemberCount = 5;

    function updateMemberRowCount() {
        const visibleMemberCount = Array.from(memberRows)
            .filter(row => !row.classList.contains('hidden'))
            .length;

        if (memberRowCount) {
            memberRowCount.textContent = `${visibleMemberCount}/${maxVisibleMemberCount}名分表示中`;
        }

        if (addMemberRowsButton && visibleMemberCount >= maxVisibleMemberCount) {
            addMemberRowsButton.classList.add('hidden');
        }
    }

    if (addMemberRowsButton) {
        addMemberRowsButton.addEventListener('click', function() {
            const hiddenRows = Array.from(memberRows)
                .filter(row => row.classList.contains('hidden'))
                .slice(0, addMemberCount);

            hiddenRows.forEach(row => {
                row.classList.remove('hidden');
            });

            updateMemberRowCount();
        });
    }

    updateMemberRowCount();
    const hasOldMembers = Array.from(memberInputs).some(i => i.value.trim() !== '');
    if (hasOldMembers) {
        membersProxy.value = 'dummy';
    }

    memberInputs.forEach(input => {
        input.addEventListener('input', function() {
            const hasAny = Array.from(memberInputs).some(i => i.value.trim() !== '');
            membersProxy.value = hasAny ? 'dummy' : '';
            membersProxy.dispatchEvent(new Event('input'));
        });
    });

    document.getElementById('reservationForm').addEventListener('submit', function() {
        const filled = Array.from(memberInputs)
            .map(input => input.value.trim())
            .filter(val => val !== '');
        membersHidden.value = filled.join('\n');
    });

    const checkboxes = document.querySelectorAll('.period-checkbox');
    const instructionText = document.getElementById('instructionText');

    let mainPeriod = 0;
    checkboxes.forEach(cb => {
        if (cb.dataset.original === "1") {
            mainPeriod = parseInt(cb.value, 10);
        }
    });

    function updateFormState() {
        const hasMembers = membersProxy.value.trim().length > 0;

        if (hasMembers) {
            instructionText.textContent = "※最大2コマまで選択可能です。";
            instructionText.classList.remove('text-orange-600');
            instructionText.classList.add('text-blue-600');
        } else {
            instructionText.textContent = "※1名利用（メンバー未入力）の場合は連続予約できません。";
            instructionText.classList.add('text-orange-600');
            instructionText.classList.remove('text-blue-600');
        }

        let extraCheckedCount = 0;
        checkboxes.forEach(cb => {
            if (cb.dataset.original === "0" && cb.checked) {
                extraCheckedCount++;
            }
        });

        for (const cb of checkboxes) {
            const isOriginal = cb.dataset.original === "1";
            const isReserved = cb.dataset.reserved === "1";
            const label = document.getElementById('label-' + cb.value);
            const currentPeriod = parseInt(cb.value, 10);

            if (isOriginal) continue;

            if (isReserved) {
                cb.disabled = true;
                label.classList.add('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                continue;
            }

            if (!hasMembers) {
                if (cb.checked) {
                    cb.checked = false;
                    label.classList.remove('bg-blue-50', 'border-blue-400');
                }
                cb.disabled = true;
                label.classList.add('border-gray-200');
                continue;
            }

            const isConsecutive = (currentPeriod === mainPeriod - 1) || (currentPeriod === mainPeriod + 1);

            if (!isConsecutive) {
                if (cb.checked) {
                    cb.checked = false;
                    label.classList.remove('bg-blue-50', 'border-blue-400');
                }
                cb.disabled = true;
                label.classList.add('opacity-50', 'cursor-not-allowed');
            } else if (extraCheckedCount >= 1 && !cb.checked) {
                cb.disabled = true;
                label.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                cb.disabled = false;
                label.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    membersProxy.addEventListener('input', updateFormState);

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const label = document.getElementById('label-' + this.value);
            const isOriginal = this.dataset.original === "1";

            if (isOriginal) return;

            if (this.checked) {
                label.classList.add('bg-blue-50', 'border-blue-400');
                label.classList.remove('border-gray-200');
            } else {
                label.classList.remove('bg-blue-50', 'border-blue-400');
                label.classList.add('border-gray-200');
            }

            updateFormState();
        });
    });

    let list = document.querySelectorAll('.list');
    function activeLink() {
        list.forEach((item) => item.classList.remove('active'));
        this.classList.add('active');
    }
    list.forEach((item) => item.addEventListener('click', activeLink));

    let toggle = document.querySelector('.nav-toggle');
    let navigation = document.querySelector('.navigation');
    if (toggle) {
        toggle.onclick = function() {
            navigation.classList.toggle('active');
        }
    }

    updateFormState();
});
