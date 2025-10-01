document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    
    // Auto-complete simples para medicamentos comuns
    const medicamentosComuns = [
        'Paracetamol', 'Ibuprofeno', 'Amoxicilina', 'Dipirona', 'Omeprazol',
        'Losartana', 'Metformina', 'Sinvastatina', 'Clonazepam', 'Sertralina'
    ];
    
    if (searchInput) {
        // Focar no input de busca
        searchInput.focus();
        
        // Sugestões de auto-complete
        searchInput.addEventListener('input', function() {
            const value = this.value.toLowerCase();
            if (value.length > 2) {
                // Em uma implementação real, aqui viria uma chamada AJAX
                console.log('Buscando sugestões para:', value);
            }
        });
        
        // Prevenir envio de formulário vazio
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                if (searchInput.value.trim() === '') {
                    e.preventDefault();
                    searchInput.focus();
                    alert('Por favor, digite o nome de um medicamento');
                }
            });
        }
    }
    
    // Busca em tempo real nos resultados (se existir)
    const searchResultsInput = document.getElementById('search-medicine');
    const searchResultsBtn = document.getElementById('search-medicine-btn');
    
    if (searchResultsInput && searchResultsBtn) {
        function filterResults() {
            const searchTerm = searchResultsInput.value.toLowerCase();
            const pharmacyCards = document.querySelectorAll('.pharmacy-card');
            
            pharmacyCards.forEach(card => {
                const medicineName = card.querySelector('.medicine-name')?.textContent.toLowerCase() || '';
                const pharmacyName = card.querySelector('.pharmacy-name')?.textContent.toLowerCase() || '';
                
                if (medicineName.includes(searchTerm) || pharmacyName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        searchResultsBtn.addEventListener('click', filterResults);
        searchResultsInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterResults();
            }
        });
    }
    
    // Melhorar experiência em mobile
    if (window.innerWidth < 768) {
        document.querySelector('.search-box')?.classList.add('mobile-optimized');
    }
    
    // Adicionar estilo para mobile
    const style = document.createElement('style');
    style.textContent = `
        @media (max-width: 768px) {
            .mobile-optimized {
                flex-direction: column;
            }
            .mobile-optimized input,
            .mobile-optimized button {
                width: 100%;
                margin: 5px 0;
            }
        }
    `;
    document.head.appendChild(style);
});