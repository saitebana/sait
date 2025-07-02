// Фильтрация костюмов
document.addEventListener('DOMContentLoaded', function() {
    const categoryFilter = document.getElementById('category');
    const priceFilter = document.getElementById('price');
    const resetBtn = document.getElementById('resetFilters');
    const costumesContainer = document.getElementById('costumesContainer');
    const costumeCards = costumesContainer.querySelectorAll('.costume-card');

    // Функция фильтрации
    function filterCostumes() {
        const selectedCategory = categoryFilter.value;
        const selectedPrice = priceFilter.value;

        costumeCards.forEach(card => {
            const cardCategory = card.dataset.category;
            const cardPrice = parseInt(card.dataset.price);
            
            let categoryMatch = selectedCategory === 'all' || cardCategory === selectedCategory;
            let priceMatch = true;

            if (selectedPrice !== 'all') {
                if (selectedPrice === '1000-3000') {
                    priceMatch = cardPrice >= 1000 && cardPrice <= 3000;
                } else if (selectedPrice === '3000-5000') {
                    priceMatch = cardPrice >= 3000 && cardPrice <= 5000;
                } else if (selectedPrice === '5000+') {
                    priceMatch = cardPrice >= 5000;
                }
            }

            if (categoryMatch && priceMatch) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // События для фильтров
    categoryFilter.addEventListener('change', filterCostumes);
    priceFilter.addEventListener('change', filterCostumes);

    // Сброс фильтров
    resetBtn.addEventListener('click', function(e) {
        e.preventDefault();
        categoryFilter.value = 'all';
        priceFilter.value = 'all';
        filterCostumes();
    });

    // Инициализация модального окна для заказа
    const orderButtons = document.querySelectorAll('[data-modal="order"]');
    orderButtons.forEach(button => {
        button.addEventListener('click', function() {
            const costumeCard = this.closest('.costume-card');
            const costumeName = costumeCard.querySelector('.costume-card__title').textContent;
            const costumePrice = costumeCard.querySelector('.costume-card__price').textContent;
            
            document.getElementById('costumeName').value = `${costumeName} (${costumePrice})`;
            
            // Показываем модальное окно (код для модального окна должен быть в script.js)
            openModal('orderModal');
        });
    });

    // Анимация при загрузке
    setTimeout(() => {
        costumesContainer.style.opacity = '1';
        costumesContainer.style.transform = 'translateY(0)';
    }, 100);
});

// Пагинация (можно добавить при необходимости)
function setupPagination() {
    const itemsPerPage = 6;
    const paginationLinks = document.querySelectorAll('.pagination__link:not(.pagination__next)');
    
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Удаляем активный класс у всех ссылок
            paginationLinks.forEach(l => l.classList.remove('active'));
            
            // Добавляем активный класс текущей ссылке
            this.classList.add('active');
            
            // Здесь можно добавить логику загрузки данных для страницы
            // Например, через AJAX или просто показать/скрыть элементы
        });
    });
}