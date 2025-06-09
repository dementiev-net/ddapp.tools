BX.ready(function () {
    // Объект для хранения интервалов
    const progressIntervals = {};

    // Функция для показа ошибки в стиле Битрикса
    function showError(barId, errorText) {
        const errorContainer = document.getElementById("error-message-" + barId);

        errorContainer.innerHTML = `
                    <div class="adm-info-message-wrap adm-info-message-red">
                       <div class="adm-info-message">
                           <div class="adm-info-message-title">Ошибка выполнения операции</div>
                           <div class="adm-info-message-text">${errorText}</div>
                           <div class="adm-info-message-icon"></div>
                       </div>
                    </div>`
        errorContainer.style.display = "block";
    }

    // Функция для скрытия ошибки
    function hideError(barId) {
        const errorContainer = document.getElementById("error-message-" + barId);
        errorContainer.style.display = "none";
        errorContainer.innerHTML = "";
    }

    function updateProgress(barId, percent, maxGb) {
        const progressBar = document.getElementById("progress-bar-" + barId);
        const progressText = document.getElementById("progress-text-" + barId);
        const percentText = document.getElementById("percent-text-" + barId);
        const usedSpace = document.getElementById("used-space-" + barId);

        const width = 500 * (percent / 100);
        progressBar.style.width = width + "px";
        progressText.innerText = percent + "%";
        percentText.innerText = percent + "%";

        const usedGb = (maxGb * percent / 100).toFixed(2);
        usedSpace.innerText = usedGb + " ГБ";
    }

    function stopProgress(barId) {
        if (progressIntervals[barId]) {
            clearInterval(progressIntervals[barId]);
            delete progressIntervals[barId];
        }

        // Показываем кнопку "Запустить" и скрываем "Остановить"
        document.querySelector(`[data-bar-id="${barId}"].progress-start-btn`).style.display = "inline-block";
        document.querySelector(`[data-bar-id="${barId}"].progress-stop-btn`).style.display = "none";
    }

    function getCurrentPercent(barId) {
        const progressText = document.getElementById("progress-text-" + barId);
        return parseInt(progressText.innerText) || 0;
    }

    // Обработчики для кнопок "Запустить"
    const startButtons = document.querySelectorAll(".progress-start-btn");
    startButtons.forEach(button => {
        button.addEventListener("click", function () {
            const barId = this.dataset.barId;
            const maxGb = parseFloat(this.dataset.maxGb);

            // Скрываем предыдущие ошибки
            hideError(barId);

            // Показываем блок с информацией и прогресс-баром
            document.getElementById("progress-info-" + barId).style.display = "block";

            // Показываем кнопки управления
            document.querySelector(`[data-bar-id="${barId}"].progress-stop-btn`).style.display = "inline-block";
            document.querySelector(`[data-bar-id="${barId}"].progress-reset-btn`).style.display = "inline-block";

            // Скрываем кнопку "Запустить"
            this.style.display = "none";

            // Получаем текущий процент (может быть 100% после завершения)
            let percent = getCurrentPercent(barId);

            // Если прогресс уже 100%, сбрасываем его на 0
            if (percent >= 100) {
                percent = 0;
                updateProgress(barId, percent, maxGb);
            }

            progressIntervals[barId] = setInterval(() => {
                BX.ajax({
                    url: "/bitrix/admin/hmarketing.php",
                    method: "POST",
                    dataType: "json",
                    data: {
                        ajax: "Y",
                        sessid: BX.bitrix_sessid(),
                        percent: percent,
                        bar_id: barId
                    },
                    onsuccess: function (result) {
                        if (result.bar_id === barId) {
                            if (result.success) {
                                percent = result.percent;
                                updateProgress(barId, percent, maxGb);

                                if (percent >= 100) {
                                    stopProgress(barId);
                                }
                            } else {
                                // Показываем ошибку и останавливаем прогресс
                                stopProgress(barId);
                                showError(barId, result.error || "Неизвестная ошибка сервера");
                            }
                        }
                    },
                    onfailure: function () {
                        stopProgress(barId);
                        showError(barId, "Ошибка соединения с сервером. Проверьте подключение к интернету.");
                    }
                });
            }, 500);
        });
    });

    // Обработчики для кнопок "Остановить"
    const stopButtons = document.querySelectorAll(".progress-stop-btn");
    stopButtons.forEach(button => {
        button.addEventListener("click", function () {
            const barId = this.dataset.barId;
            stopProgress(barId);
        });
    });

    // Обработчики для кнопок "Сбросить"
    const resetButtons = document.querySelectorAll(".progress-reset-btn");
    resetButtons.forEach(button => {
        button.addEventListener("click", function () {
            const barId = this.dataset.barId;
            const maxGb = parseFloat(this.dataset.maxGb);

            stopProgress(barId);
            updateProgress(barId, 0, maxGb);
            hideError(barId);

            // Скрываем весь блок с информацией
            document.getElementById("progress-info-" + barId).style.display = "none";

            // Показываем только кнопку "Запустить"
            document.querySelector(`[data-bar-id="${barId}"].progress-start-btn`).style.display = "inline-block";
            document.querySelector(`[data-bar-id="${barId}"].progress-stop-btn`).style.display = "none";
            document.querySelector(`[data-bar-id="${barId}"].progress-reset-btn`).style.display = "none";
        });
    });
});