/**
 * Module de graphiques pour le dashboard
 * Utilise Chart.js
 */

// Configuration globale Chart.js
if (window.Chart) {
    window.Chart.defaults.font.family = "'Inter', sans-serif";
    window.Chart.defaults.color = '#64748b';
    window.Chart.defaults.borderColor = '#e2e8f0';
}

/**
 * Créer un graphique en ligne
 */
export function createLineChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultOptions = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    };
    
    return new window.Chart(ctx, { ...defaultOptions, ...options });
}

/**
 * Créer un graphique en barres
 */
export function createBarChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultOptions = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    };
    
    return new window.Chart(ctx, { ...defaultOptions, ...options });
}

/**
 * Créer un graphique circulaire
 */
export function createDoughnutChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultOptions = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        }
    };
    
    return new window.Chart(ctx, { ...defaultOptions, ...options });
}

/**
 * Composant Alpine.js pour les graphiques du dashboard
 */
export function dashboardChartsComponent() {
    return {
        charts: {},
        loading: true,
        
        async init() {
            await this.loadChartData();
            this.initCharts();
        },
        
        async loadChartData() {
            try {
                const response = await fetch('/api/dashboard/charts');
                const data = await response.json();
                this.chartData = data;
            } catch (error) {
                console.error('Erreur chargement données graphiques:', error);
                window.toastr.error('Erreur de chargement des statistiques');
            } finally {
                this.loading = false;
            }
        },
        
        initCharts() {
            if (!this.chartData) return;
            
            // Graphique tickets par mois
            if (this.chartData.ticketsPerMonth) {
                this.charts.ticketsPerMonth = createLineChart(
                    'ticketsPerMonthChart',
                    {
                        labels: this.chartData.ticketsPerMonth.labels,
                        datasets: [{
                            label: 'Tickets créés',
                            data: this.chartData.ticketsPerMonth.data,
                            borderColor: '#C83530',
                            backgroundColor: 'rgba(200, 53, 48, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    }
                );
            }
            
            // Graphique tickets par département
            if (this.chartData.ticketsByDepartment) {
                this.charts.ticketsByDepartment = createBarChart(
                    'ticketsByDepartmentChart',
                    {
                        labels: this.chartData.ticketsByDepartment.labels,
                        datasets: [{
                            label: 'Tickets',
                            data: this.chartData.ticketsByDepartment.data,
                            backgroundColor: '#C83530'
                        }]
                    }
                );
            }
            
            // Graphique tickets par statut
            if (this.chartData.ticketsByStatus) {
                this.charts.ticketsByStatus = createDoughnutChart(
                    'ticketsByStatusChart',
                    {
                        labels: this.chartData.ticketsByStatus.labels,
                        datasets: [{
                            data: this.chartData.ticketsByStatus.data,
                            backgroundColor: [
                                '#F9C74F',
                                '#C83530',
                                '#4A3F35',
                                '#90BE6D',
                                '#577590'
                            ]
                        }]
                    }
                );
            }
        },
        
        destroy() {
            Object.values(this.charts).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
        }
    };
}
