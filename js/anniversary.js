document.addEventListener('DOMContentLoaded', function() {
    // Highlight today's cards on hover
    document.querySelectorAll('.anniversary-card.today').forEach(card => {
        card.addEventListener('mouseenter', () => card.style.boxShadow = '0 4px 16px #ffe082');
        card.addEventListener('mouseleave', () => card.style.boxShadow = '');
    });
});