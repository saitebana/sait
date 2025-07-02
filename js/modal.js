document.addEventListener('DOMContentLoaded', function() {
    const modalForm = document.getElementById('modalForm');
    
    modalForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = modalForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Показываем индикатор загрузки
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Отправка...';
        
        try {
            // Собираем данные формы
            const formData = new FormData(modalForm);
            const data = Object.fromEntries(formData.entries());
            
            // Добавляем дополнительные данные
            data.source = window.location.href;
            data.referrer = document.referrer;
            
            const response = await fetch('mail.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            // Проверяем статус ответа
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(errorText || 'HTTP error ' + response.status);
            }
            
            // Парсим JSON
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Неизвестная ошибка сервера');
            }
            
            // Успешная отправка
            alert(result.message || 'Спасибо! Ваша заявка принята.');
            modalForm.reset();
            closeModal();
            
            // Аналитика
            if (typeof ym !== 'undefined') {
                ym(XXXXXX, 'reachGoal', 'ORDER_SENT');
            }
            
        } catch (error) {
            console.error('Error:', error);
            
            try {
                // Пробуем распарсить JSON ошибки
                const errorData = JSON.parse(error.message);
                alert(errorData.message || 'Ошибка при отправке формы');
            } catch (e) {
                // Если не JSON, показываем как есть
                alert(error.message || 'Произошла ошибка. Пожалуйста, попробуйте позже.');
            }
            
        } finally {
            // Восстанавливаем кнопку
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
});