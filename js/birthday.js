document.addEventListener('DOMContentLoaded', function(){
    // adding animation for today borthday
    const todayCards = document.querySelectorAll('.birthday-card[data-days="0"]');
    todayCards.forEach(card => {
        card.classList.add('today-birthday');
    });
    // sort card by days until birthday
    const container = document.querySelector('.birthday-cards');
    const cards = Array.from(container.children);

    cards.sort((a,b) => {
        const daysA = parseInt(a.dataset.days);
        const daysB = parseInt(b.dataset.days);

        return daysA - daysB;
    });

    cards.forEach(card => container.appendChild(card));


} )