document.addEventListener('DOMContentLoaded', function() {
    // Elementos do modal
    const addMedicineModal = document.getElementById('add-medicine-modal');
    const editMedicineModal = document.getElementById('edit-medicine-modal');
    const addMedicineBtn = document.getElementById('add-medicine-btn');
    const addFirstMedicineBtn = document.getElementById('add-first-medicine');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const editMedicineBtns = document.querySelectorAll('.editar-medicamento');
    
    // Abrir modal de adicionar
    if (addMedicineBtn) {
        addMedicineBtn.addEventListener('click', function() {
            addMedicineModal.style.display = 'flex';
        });
    }
    
    if (addFirstMedicineBtn) {
        addFirstMedicineBtn.addEventListener('click', function() {
            addMedicineModal.style.display = 'flex';
        });
    }
    
    // Abrir modal de editar
    editMedicineBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const estoqueId = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            const quantidade = this.getAttribute('data-quantidade');
            const preco = this.getAttribute('data-preco');
            
            document.getElementById('edit-estoque-id').value = estoqueId;
            document.getElementById('edit-medicine-name').value = nome;
            document.getElementById('edit-quantidade').value = quantidade;
            document.getElementById('edit-preco').value = preco;
            
            editMedicineModal.style.display = 'flex';
        });
    });
    
    // Fechar modais
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            addMedicineModal.style.display = 'none';
            editMedicineModal.style.display = 'none';
        });
    });
    
    // Fechar modal ao clicar fora
    window.addEventListener('click', function(event) {
        if (event.target === addMedicineModal) {
            addMedicineModal.style.display = 'none';
        }
        if (event.target === editMedicineModal) {
            editMedicineModal.style.display = 'none';
        }
    });
    
    // Busca em tempo real
    const searchInput = document.getElementById('search-medicine');
    const searchBtn = document.getElementById('search-medicine-btn');
    
    if (searchInput && searchBtn) {
        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#stock-table-body tr');
            
            rows.forEach(row => {
                const medicineName = row.cells[0].textContent.toLowerCase();
                if (medicineName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        searchBtn.addEventListener('click', performSearch);
        searchInput.addEventListener('input', performSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
    
    // Navegação entre seções
    const navLinks = document.querySelectorAll('.nav-link');
    const contentSections = document.querySelectorAll('.content-section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover classe active de todos os links e seções
            navLinks.forEach(l => l.classList.remove('active'));
            contentSections.forEach(s => s.classList.remove('active'));
            
            // Adicionar classe active ao link clicado
            this.classList.add('active');
            
            // Mostrar seção correspondente
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).classList.add('active');
        });
    });
});