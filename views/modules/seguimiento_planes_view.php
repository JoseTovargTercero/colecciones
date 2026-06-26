<div class="container mt-4">
    <h2>Dashboard de Consumo de Planes Alimenticios</h2>

    <!-- Sección por plan -->
     <div id="chartsContainer"></div>
</div>

<!-- ApexCharts JS -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
async function cargarDashboard() {
    try {
        const res = await fetch(`${BASE_URL}api/alimentos_consumo/planes`);
        const json = await res.json();

        if (!json.value) throw json;

        const planes = json.data;
        const container = document.getElementById('chartsContainer');
        container.innerHTML = '';

        planes.forEach(plan => {
            const planId = plan.plan_id;
            const planNombre = plan.nombre;

            // Crear contenedor para cada plan
            const div = document.createElement('div');
            // asigna la clase card y card-body
            div.classList.add('card', 'card-body');
            div.innerHTML = `<h4>${planNombre}</h4><div id="chart-${planId}"></div>`;
            container.appendChild(div);

            // Configurar gráfico de líneas con consumo y animales
            const options = {
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: { show: true }
                },
                series: [
                    {
                        name: 'Consumo (kg)',
                        type: 'line',
                        data: plan.consumo_total
                    },
                    {
                        name: 'Animales presentes',
                        type: 'line',
                        data: plan.animales_total
                    },
                    {
                        name: 'Kg por animal',
                        type: 'line',
                        data: plan.kg_por_animal
                    }
                ],
                xaxis: {
                    categories: plan.fechas
                },
                yaxis: [
                    { title: { text: 'Kg / Consumo' } },
                    { opposite: true, title: { text: 'Animales' } },
                    { opposite: true, title: { text: 'Kg/animal' } }
                ],
                tooltip: {
                    shared: true,
                    intersect: false
                }
            };

            const chart = new ApexCharts(document.querySelector(`#chart-${planId}`), options);
            chart.render();
        });

    } catch (e) {
        console.error('Error al cargar dashboard:', e);
        alert('No se pudo cargar el dashboard');
    }
}

document.addEventListener('DOMContentLoaded', cargarDashboard);
</script>
