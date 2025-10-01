class GeolocalizacaoFarmApp {
    constructor() {
        this.userLocation = null;
        this.farmacias = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadAllFarmacias(); // Carregar todas as farmácias inicialmente
    }

    bindEvents() {
        // Botão de localização automática
        document.getElementById('get-location-btn').addEventListener('click', () => {
            this.getUserLocation();
        });

        // Botão de localização manual
        document.getElementById('manual-location-btn').addEventListener('click', () => {
            this.showManualLocationModal();
        });

        // Aplicar filtros
        document.getElementById('apply-filters-btn').addEventListener('click', () => {
            this.applyFilters();
        });

        // Ordenação
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setActiveSort(e.target);
                this.sortFarmacias(e.target.dataset.sort);
            });
        });

        // Enter no filtro de medicamento
        document.getElementById('medicamento-filter').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.applyFilters();
            }
        });
    }

    getUserLocation() {
        this.showStatus('Buscando sua localização...', 'loading');

        if (!navigator.geolocation) {
            this.showStatus('Geolocalização não suportada neste navegador.', 'error');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.userLocation = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                };
                
                this.showStatus('Localização encontrada! Buscando farmácias próximas...', 'success');
                this.loadFarmaciasProximas();
            },
            (error) => {
                let message = 'Erro ao obter localização: ';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        message += 'Permissão de localização negada.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message += 'Localização indisponível.';
                        break;
                    case error.TIMEOUT:
                        message += 'Tempo limite excedido.';
                        break;
                    default:
                        message += 'Erro desconhecido.';
                }
                this.showStatus(message, 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    }

    async loadFarmaciasProximas() {
        if (!this.userLocation) return;

        const raio = document.getElementById('raio-filter').value;
        const medicamento = document.getElementById('medicamento-filter').value;

        try {
            const response = await fetch(`../api/geolocalizacao.php?lat=${this.userLocation.latitude}&lng=${this.userLocation.longitude}&raio=${raio}&medicamento=${encodeURIComponent(medicamento)}`);
            const data = await response.json();

            if (data.success) {
                this.farmacias = data.farmacias;
                this.displayFarmacias();
            } else {
                this.showStatus('Erro ao carregar farmácias.', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            this.showStatus('Erro de conexão.', 'error');
        }
    }

    async loadAllFarmacias() {
        try {
            const response = await fetch('../api/geolocalizacao.php');
            const data = await response.json();

            if (data.success) {
                this.farmacias = data.farmacias;
                this.displayFarmacias();
            }
        } catch (error) {
            console.error('Erro ao carregar farmácias:', error);
        }
    }

    displayFarmacias() {
        const container = document.getElementById('results-container');
        
        if (this.farmacias.length === 0) {
            container.innerHTML = `
                <div class="no-results">
                    <h3>😔 Nenhuma farmácia encontrada</h3>
                    <p>Tente ajustar os filtros ou aumentar o raio de busca.</p>
                </div>
            `;
            return;
        }

        let html = '<div class="results-grid">';
        
        this.farmacias.forEach(farmacia => {
            const hasDistance = farmacia.distancia_texto;
            const is24h = farmacia.aberta_24h;
            const totalMedicamentos = farmacia.total_medicamentos || 1;
            
            html += `
                <div class="pharmacy-card">
                    <div class="pharmacy-header">
                        <div class="pharmacy-name">${farmacia.nome}</div>
                        <div class="pharmacy-address">📍 ${farmacia.endereco}</div>
                        ${hasDistance ? `
                            <div class="pharmacy-marker">
                                <span class="distance-badge">${farmacia.distancia_texto}</span>
                                <span style="color: #666;">de distância</span>
                            </div>
                        ` : ''}
                        ${is24h ? 
                            '<div class="pharmacy-status" style="color: #27ae60; margin-top: 5px;">🕒 Aberta 24 horas</div>' : 
                            `<div class="pharmacy-status" style="margin-top: 5px;">🕒 ${farmacia.horario_funcionamento || 'Horário não informado'}</div>`
                        }
                    </div>
                    
                    <div class="pharmacy-body">
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 36px; color: #4a6ee0; margin-bottom: 10px;">${totalMedicamentos}</div>
                            <div style="color: #666;">medicamentos disponíveis</div>
                            ${farmacia.medicamento_nome ? `
                                <div style="margin-top: 15px; padding: 10px; background: #e8f4ff; border-radius: 5px;">
                                    <strong>${farmacia.medicamento_nome}</strong>
                                    ${farmacia.principio_ativo ? `<br><small>${farmacia.principio_ativo}</small>` : ''}
                                    <div style="margin-top: 5px;">
                                        <span style="color: #27ae60; font-weight: bold;">${farmacia.quantidade} unidades</span> • 
                                        <span style="color: #e74c3c; font-weight: bold;">${parseFloat(farmacia.preco).toFixed(2)} MZN</span>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="pharmacy-footer">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>📞 ${farmacia.telefone}</div>
                            <a href="buscar-medicamentos.php?medicamento=${farmacia.medicamento_nome || ''}" class="btn-small" style="background: #4a6ee0; color: white;">
                                Ver Medicamentos
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }

    applyFilters() {
        if (this.userLocation) {
            this.loadFarmaciasProximas();
        } else {
            this.showStatus('Use primeiro a localização para aplicar filtros.', 'error');
        }
    }

    sortFarmacias(criteria) {
        switch (criteria) {
            case 'distance':
                this.farmacias.sort((a, b) => (a.distancia_km || 999) - (b.distancia_km || 999));
                break;
            case 'name':
                this.farmacias.sort((a, b) => a.nome.localeCompare(b.nome));
                break;
            case 'medicines':
                this.farmacias.sort((a, b) => (b.total_medicamentos || 0) - (a.total_medicamentos || 0));
                break;
        }
        this.displayFarmacias();
    }

    setActiveSort(activeBtn) {
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        activeBtn.classList.add('active');
    }

    showManualLocationModal() {
        const cidade = prompt('Digite sua cidade (ex: Maputo):');
        if (cidade) {
            // Simulação - em produção, usar API de geocoding
            this.showStatus(`Localização definida para: ${cidade}. Buscando farmácias...`, 'success');
            
            // Coordenadas aproximadas para Maputo
            this.userLocation = {
                latitude: -25.969248,
                longitude: 32.573174
            };
            
            this.loadFarmaciasProximas();
        }
    }

    showStatus(message, type) {
        const statusEl = document.getElementById('location-status');
        statusEl.textContent = message;
        statusEl.className = `location-status location-${type}`;
        statusEl.style.display = 'block';

        if (type !== 'loading') {
            setTimeout(() => {
                statusEl.style.display = 'none';
            }, 5000);
        }
    }
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', () => {
    new GeolocalizacaoFarmApp();
});