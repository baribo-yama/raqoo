document.addEventListener("DOMContentLoaded", function () {
    /*
        reservation-form.js

        役割：
        - 利用目的の文字数カウント
        - 利用メンバー入力の管理
        - メンバー入力内容を hidden にまとめて送信
        - 連続予約の選択可否制御
        - 選択中時限の仮ロック / 解除API通信

        注意：
        - このJSは予約申請フォーム画面専用
        - #selectingMeta が存在する前提の処理がある
        - サイドバー開閉処理は共通JSの home.js に統一する
    */

    /* =========================================
       01. 利用目的の文字数カウント
       - textarea 入力時に現在の文字数を表示
    ========================================= */
    const purposeTextarea = document.getElementById("purpose");
    const purposeCount = document.getElementById("purposeCount");

    if (purposeTextarea && purposeCount) {
        purposeTextarea.addEventListener("input", function () {
            purposeCount.textContent = this.value.length;
        });

        purposeCount.textContent = purposeTextarea.value.length;
    }

    /* =========================================
       02. 利用メンバー入力欄の取得
       - membersProxy は「メンバー入力があるかどうか」を判定するための代理hidden
       - membersHidden は送信時に実際のメンバー一覧を入れるhidden
    ========================================= */
    const membersHidden = document.getElementById("membersHidden");
    const membersProxy = document.getElementById("members");
    const memberInputs = document.querySelectorAll(".member-name-input");

    /* =========================================
       03. バリデーションエラー後の再表示対応
       - old() で復元されたメンバーがある場合、membersProxy に値を入れる
       - 連続予約制御では「メンバー入力あり」として扱う
    ========================================= */
    const hasOldMembers = Array.from(memberInputs).some((input) => {
        return input.value.trim() !== "";
    });

    if (hasOldMembers && membersProxy) {
        membersProxy.value = "dummy";
    }

    /* =========================================
       04. メンバー入力状態の監視
       - 1人でも入力があれば membersProxy に dummy を入れる
       - 全員空欄なら空にする
       - membersProxy の input イベントを発火して連続予約制御を更新する
    ========================================= */
    memberInputs.forEach((input) => {
        input.addEventListener("input", function () {
            const hasAnyMember = Array.from(memberInputs).some((memberInput) => {
                return memberInput.value.trim() !== "";
            });

            if (membersProxy) {
                membersProxy.value = hasAnyMember ? "dummy" : "";
                membersProxy.dispatchEvent(new Event("input"));
            }
        });
    });

    /* =========================================
       05. フォーム送信時にメンバー一覧を hidden へ集約
       - 空欄は除外
       - 改行区切りで membersHidden に入れる
    ========================================= */
    const reservationForm = document.getElementById("reservationForm");

    if (reservationForm && membersHidden) {
        reservationForm.addEventListener("submit", function () {
            const filledMembers = Array.from(memberInputs)
                .map((input) => input.value.trim())
                .filter((value) => value !== "");

            membersHidden.value = filledMembers.join("\n");
        });
    }

    /* =========================================
       06. 仮ロックAPI用メタ情報の取得
       - Blade側の #selectingMeta の data-* 属性から取得
       - 必要な要素が無い場合はAPI通信を行わない
    ========================================= */
    const selectingMeta = document.getElementById("selectingMeta");
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');

    const roomId = selectingMeta?.dataset.roomId || "";
    const date = selectingMeta?.dataset.date || "";
    const toggleUrl = selectingMeta?.dataset.toggleUrl || "";
    const csrf = csrfMeta?.content || "";

    const canUseSelectingApi = Boolean(roomId && date && toggleUrl && csrf);

    /* =========================================
       07. 時限の仮ロック / 解除API
       - action: lock / unlock
       - 他ユーザーとの同時選択衝突を防ぐために使用
    ========================================= */
    async function toggleSelectingApi(period, action) {
        if (!canUseSelectingApi) {
            console.warn("selecting API に必要な情報が不足しています。");
            return false;
        }

        try {
            const response = await fetch(toggleUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify({
                    room_id: roomId,
                    date: date,
                    period: period,
                    action: action,
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                return false;
            }

            return true;
        } catch (error) {
            console.error("selecting toggle error:", error);
            return false;
        }
    }

    /* =========================================
       08. 時限チェックボックスの取得
       - data-original="1" の時限を基準時限として扱う
    ========================================= */
    const checkboxes = document.querySelectorAll(".period-checkbox");
    const instructionText = document.getElementById("instructionText");

    let mainPeriod = 0;

    checkboxes.forEach((checkbox) => {
        if (checkbox.dataset.original === "1") {
            mainPeriod = parseInt(checkbox.value, 10);
        }
    });

    /* =========================================
       09. チェック解除 + 仮ロック解除
       - 連続予約条件を満たさなくなった時に使用
    ========================================= */
    async function uncheckWithUnlock(checkbox, label) {
        if (!checkbox.checked) {
            return;
        }

        const period = parseInt(checkbox.value, 10);

        checkbox.checked = false;
        label.classList.remove("bg-blue-50", "border-blue-400");
        label.classList.add("border-gray-200");

        await toggleSelectingApi(period, "unlock");
    }

    /* =========================================
       10. フォーム状態の更新
       - メンバー入力の有無に応じて連続予約を許可 / 不許可にする
       - 連続していない時限は選択不可
       - 追加で選べる時限は最大1つまで
    ========================================= */
    async function updateFormState() {
        const hasMembers = membersProxy && membersProxy.value.trim().length > 0;

        if (instructionText) {
            if (hasMembers) {
                instructionText.textContent = "※最大2コマまで選択可能です。";
                instructionText.classList.remove("text-orange-600");
                instructionText.classList.add("text-blue-600");
            } else {
                instructionText.textContent = "※1名利用（メンバー未入力）の場合は連続予約できません。";
                instructionText.classList.add("text-orange-600");
                instructionText.classList.remove("text-blue-600");
            }
        }

        let extraCheckedCount = 0;

        checkboxes.forEach((checkbox) => {
            if (checkbox.dataset.original === "0" && checkbox.checked) {
                extraCheckedCount++;
            }
        });

        for (const checkbox of checkboxes) {
            const isOriginal = checkbox.dataset.original === "1";
            const isReserved = checkbox.dataset.reserved === "1";
            const label = document.getElementById("label-" + checkbox.value);
            const currentPeriod = parseInt(checkbox.value, 10);

            if (!label) {
                continue;
            }

            if (isOriginal) {
                continue;
            }

            if (isReserved) {
                checkbox.disabled = true;
                label.classList.add("bg-gray-100", "opacity-50", "cursor-not-allowed");
                continue;
            }

            if (!hasMembers) {
                checkbox.disabled = true;
                await uncheckWithUnlock(checkbox, label);
                label.classList.add("border-gray-200");
                continue;
            }

            const isConsecutive = currentPeriod === mainPeriod - 1 || currentPeriod === mainPeriod + 1;

            if (!isConsecutive) {
                checkbox.disabled = true;
                await uncheckWithUnlock(checkbox, label);
                label.classList.add("opacity-50", "cursor-not-allowed");
            } else if (extraCheckedCount >= 1 && !checkbox.checked) {
                checkbox.disabled = true;
                label.classList.add("opacity-50", "cursor-not-allowed");
            } else {
                checkbox.disabled = false;
                label.classList.remove("opacity-50", "cursor-not-allowed");
            }
        }
    }

    /* =========================================
       11. メンバー入力状態が変わったらフォーム状態を更新
    ========================================= */
    if (membersProxy) {
        membersProxy.addEventListener("input", updateFormState);
    }

    /* =========================================
       12. 時限チェックボックス変更時の処理
       - チェック時：仮ロックAPIを実行
       - 解除時：仮ロック解除APIを実行
    ========================================= */
    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", async function () {
            const label = document.getElementById("label-" + this.value);
            const period = parseInt(this.value, 10);
            const isOriginal = this.dataset.original === "1";

            if (!label) {
                return;
            }

            if (isOriginal) {
                return;
            }

            if (this.checked) {
                const success = await toggleSelectingApi(period, "lock");

                if (!success) {
                    this.checked = false;
                    alert("その時間帯はすでに他の人が選択中です。");
                    updateFormState();
                    return;
                }

                label.classList.add("bg-blue-50", "border-blue-400");
                label.classList.remove("border-gray-200");
            } else {
                await toggleSelectingApi(period, "unlock");

                label.classList.remove("bg-blue-50", "border-blue-400");
                label.classList.add("border-gray-200");
            }

            updateFormState();
        });
    });

    /* =========================================
       13. 初期状態の反映
    ========================================= */
    updateFormState();
});