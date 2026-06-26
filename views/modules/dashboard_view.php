<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js"></script>

<style>
  .chart-container {
    height: 320px;
  }


  @media (max-width:767px) {
    .chart-container {
      height: 260px;
    }
  }
</style>

<div class="container-fluid">

  <div class="row">
    <div class="col-12">
      <h5 class="mb-2">ANIMALES</h5>
    </div>

    <!-- Donut: Distribución por etapa productiva -->
    <div class="col-lg-4 col-md-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Distribución por etapa productiva</h5>
        </div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <div>
              <div class="metric-sub">Total animales: <b id="totalAnimales"></b></div>
            </div>
          </div>
          <div id="chart_etapas" class="chart-container"></div>

        </div>
      </div>
    </div>


    <!-- Histogram: Pirámide de edades (usamos histogram) -->
    <div class="col-lg-8 col-md-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Pirámide de edades (Histograma)</h5>
        </div>
        <div class="card-body">
          <div id="chart_edades" class="chart-container"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row: Salud Animal -->
  <div class="row mt-2">
    <div class="col-12">
      <h5 class="mb-2">SALUD ANIMAL</h5>
    </div>

    <!-- Donut: Tratamientos este mes vs mes anterior -->
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Nº de tratamientos (Este mes vs Anterior)</h5>
        </div>
        <div class="card-body">
          <div id="chart_tratamientos" class="chart-container"></div>
        </div>
      </div>
    </div>

    <!-- Bar horizontal: Enfermedades por tipo -->
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Nº de casos de enfermedad por tipo</h5>
        </div>
        <div class="card-body">
          <div id="chart_enfermedades" class="chart-container"></div>
        </div>
      </div>
    </div>

    <!-- Bar vertical: Desenlaces trimestrales (recuperación vs deceso) comparado con año anterior -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Desenlaces de eventos de salud por trimestre (Recuperados vs Decesos)</h5>
        </div>
        <div class="card-body">
          <div id="chart_eventos_trimestrales" class="chart-container" style="height:360px;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row: Pesos -->
  <div class="row mt-2">
    <div class="col-12">
      <h5 class="mb-2">PESOS DE LOS ANIMALES</h5>
    </div>

    <!-- Scatter plot: lista de pesos -->
    <div class="col-lg-8 col-md-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Tabulador de peso (peso vs edad)</h5>
        </div>
        <div class="card-body">
          <div id="chart_pesos_scatter" class="chart-container"></div>
        </div>
      </div>
    </div>

    <!-- Promedio de peso por etapa (No definido -> lo pongo como bar vertical comparativo) -->
    <div class="col-lg-4 col-md-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Promedio de peso por etapa productiva</h5>
        </div>
        <div class="card-body">
          <div id="chart_promedio_peso_etapa" class="chart-container"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row: Camadas bajas -->
  <div class="row mt-2">
    <div class="col-12">
      <h5 class="mb-2">CAMADAS BAJAS (Muertes lactantes)</h5>
    </div>

    <!-- Muertes por mes: Bar vertical -->
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Nº lactantes muertos por mes</h5>
        </div>
        <div class="card-body">
          <div id="chart_muertes_lactantes" class="chart-container"></div>
        </div>
      </div>
    </div>

    <!-- Causa más común: Donut -->
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Causa más común de mortandad</h5>
        </div>
        <div class="card-body">
          <div id="chart_causas_muerte" class="chart-container"></div>
        </div>
      </div>
    </div>

    <!-- Promedio edad a la muerte (métrica simple) -->
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <div class="card-header">Promedio de edad a la muerte (días)</div>
          <div class="metric" id="promedioEdadMuerte">--</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row: Infraestructura -->
  <div class="row mt-2">
    <div class="col-12">
      <h5 class="mb-2">DAÑOS EN INFRAESTRUCTURA</h5>
    </div>

    <!-- Danos mensuales: bar vertical -->
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Nº de daños reportados por mes</h5>
        </div>
        <div class="card-body">
          <div id="chart_danos_mensuales" class="chart-container"></div>

        </div>
      </div>
    </div>

    <!-- Danos por tipo: Donut -->
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Daños por tipo de estructura</h5>
        </div>
        <div class="card-body">
          <div id="chart_danos_por_tipo" class="chart-container"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row: Decesos -->
  <div class="row mt-2">
    <div class="col-12">
      <h5 class="mb-2">DECESOS (Muertes generales)</h5>
    </div>

    <!-- Line: muertes mensuales -->
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Nº de muertes mensuales</h5>
        </div>
        <div class="card-body">
          <div id="chart_decesos_mensuales" class="chart-container"></div>
        </div>
      </div>
    </div>

    <!-- Bar horizontal: top causas probables -->
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Top causas probables</h5>
        </div>
        <div class="card-body">
          <div id="chart_causas_decesos" class="chart-container"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row: Camadas -->
  <div class="row mt-2">
    <div class="col-12">
      <h5 class="mb-2">CAMADAS</h5>
    </div>

    <!-- Line chart: nacidos vs muertos -->
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Nº de lactantes nacidos por mes vs muertes</h5>
        </div>
        <div class="card-body">
          <div id="chart_nacidos_vs_muertos" class="chart-container"></div>
        </div>
      </div>
    </div>

    <!-- Promedio de peso por camada agrupado por mes -> usaremos barras agrupadas -->
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Promedio de peso por camada (agrupado por mes)</h5>
        </div>
        <div class="card-body">
          <div id="chart_peso_promedio_camadas" class="chart-container"></div>
        </div>
      </div>
    </div>

    <!-- Promedio crías por parto por mes -->
    <div class="col-12 mt-2">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Promedio de crías por parto por mes</h5>
        </div>
        <div class="card-body">
          <div id="chart_promedio_crias" class="chart-container" style="height:200px;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Row: Incidencias -->
  <div class="row mt-2">
    <div class="col-12">
      <h5 class="mb-2">INCIDENCIAS</h5>
    </div>

    <!-- Bar horizontal: Nº incidencias por tipo -->
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Nº de incidencias por tipo</h5>
        </div>
        <div class="card-body">
          <div id="chart_incidencias_tipo" class="chart-container"></div>
        </div>
      </div>
    </div>

    <!-- Line: Incidencias mensuales -->
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Incidencias mensuales</h5>
        </div>
        <div class="card-body">
          <div id="chart_incidencias_mensuales" class="chart-container"></div>
        </div>
      </div>
    </div>

    <!-- Donut: Área con mayor número de incidentes -->
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Área o corral con mayor número de incidentes</h5>
        </div>
        <div class="card-body">
          <div id="chart_incidencias_area" class="chart-container" style="height:300px;"></div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Data + Charts initialization -->

<script>
  const baseUrl = "<?= BASE_URL ?>";


  async function cargarDatos() {
    try {
      const response = await fetch(baseUrl + '/api/dashboard', {
        method: 'GET'
      });

      if (!response.ok) {
        throw new Error('Error en la solicitud: ' + response.status);
      }

      const data = await response.json();
      renderizarGraficos(data.data)

    } catch (error) {
      console.error('Ocurrió un error:', error);
      return null;
    }
  }

  cargarDatos();


  /* ------------------------
   Datos: JSONCONSOLIDADO (proveído)
   ------------------------ */
  const JSONCONPRUEBA = {
    "animales": {
      "total_animales": 350,
      "por_etapa": [{
          "etapa": "Levante",
          "cantidad": 120
        },
        {
          "etapa": "Ceba",
          "cantidad": 150
        },
        {
          "etapa": "Reproductor",
          "cantidad": 80
        }
      ],
      "piramide_edades": [{
          "edad_meses": 1,
          "cantidad": 20
        },
        {
          "edad_meses": 2,
          "cantidad": 35
        },
        {
          "edad_meses": 3,
          "cantidad": 50
        },
        {
          "edad_meses": 4,
          "cantidad": 45
        },
        {
          "edad_meses": 5,
          "cantidad": 30
        }
      ]
    },
    "salud": {
      "tratamientos_mes": {
        "actual": 120,
        "anterior": 95
      },
      "enfermedades_por_tipo": [{
          "tipo": "Respiratorias",
          "cantidad": 30
        },
        {
          "tipo": "Digestivas",
          "cantidad": 25
        },
        {
          "tipo": "Piel",
          "cantidad": 10
        }
      ],
      "eventos_trimestrales": [{
          "trimestre": "Q1",
          "recuperados": 40,
          "decesos": 5,
          "recuperados_anio_anterior": 35,
          "decesos_anio_anterior": 7
        },
        {
          "trimestre": "Q2",
          "recuperados": 55,
          "decesos": 4,
          "recuperados_anio_anterior": 50,
          "decesos_anio_anterior": 6
        }
      ]
    },
    "pesos": {
      "lista_pesos": [{
          "animal_id": "A01",
          "peso": 35,
          "edad_meses": 2
        },
        {
          "animal_id": "A02",
          "peso": 42,
          "edad_meses": 3
        },
        {
          "animal_id": "A03",
          "peso": 60,
          "edad_meses": 4
        }
      ],
      "peso_ideal": [{
          "edad_meses": 1,
          "peso": 30
        },
        {
          "edad_meses": 2,
          "peso": 32
        },
        {
          "edad_meses": 3,
          "peso": 35
        },
        {
          "edad_meses": 4,
          "peso": 38
        },
        {
          "edad_meses": 5,
          "peso": 40
        }
      ],
      "promedios_por_etapa": [{
          "etapa": "Levante",
          "promedio_peso": 32
        },
        {
          "etapa": "Ceba",
          "promedio_peso": 78
        }
      ]
    },
    "camadas_bajas": {
      "muertes_por_mes": [{
          "mes": "Enero",
          "cantidad": 12
        },
        {
          "mes": "Febrero",
          "cantidad": 8
        }
      ],
      "causas_muerte": [{
          "causa": "Aplastamiento",
          "cantidad": 20
        },
        {
          "causa": "Baja vitalidad",
          "cantidad": 15
        },
        {
          "causa": "Hipotermia",
          "cantidad": 10
        }
      ],
      "promedio_edad_muerte_dias": 4.3
    },
    "infraestructura": {
      "danos_mensuales": [{
          "mes": "Enero",
          "cantidad": 5
        },
        {
          "mes": "Febrero",
          "cantidad": 9
        }
      ],
      "danos_por_tipo": [{
          "tipo": "Corral",
          "cantidad": 4
        },
        {
          "tipo": "Cercas",
          "cantidad": 6
        },
        {
          "tipo": "Bebederos",
          "cantidad": 3
        }
      ]
    },
    "decesos": {
      "muertes_mensuales": [{
          "mes": "Enero",
          "cantidad": 10
        },
        {
          "mes": "Febrero",
          "cantidad": 7
        }
      ],
      "causas_probables": [{
          "causa": "Digestivas",
          "cantidad": 6
        },
        {
          "causa": "Aparato respiratorio",
          "cantidad": 4
        }
      ]
    },
    "camadas": {
      "nacidos_vs_muertos": [{
          "mes": "Enero",
          "nacidos": 52,
          "muertos": 10
        },
        {
          "mes": "Febrero",
          "nacidos": 60,
          "muertos": 8
        }
      ],
      "peso_promedio_mes": [{
          "mes": "Enero",
          "promedio_peso": 1.4
        },
        {
          "mes": "Febrero",
          "promedio_peso": 1.6
        }
      ],
      "promedio_crias_mes": [{
          "mes": "Enero",
          "promedio": 11.2
        },
        {
          "mes": "Febrero",
          "promedio": 12.0
        }
      ]
    },
    "incidencias": {
      "por_tipo": [{
          "tipo": "Fuga",
          "cantidad": 4
        },
        {
          "tipo": "Agresividad",
          "cantidad": 7
        },
        {
          "tipo": "Aplastamiento",
          "cantidad": 3
        }
      ],
      "mensuales": [{
          "mes": "Enero",
          "cantidad": 10
        },
        {
          "mes": "Febrero",
          "cantidad": 14
        }
      ],
      "por_area": [{
          "area": "Corral 1",
          "cantidad": 6
        },
        {
          "area": "Corral 2",
          "cantidad": 3
        },
        {
          "area": "Aprisco",
          "cantidad": 2
        }
      ]
    }
  };



  const JSONBACK = {
    "animales": {
      "total_animales": 20,
      "por_etapa": [{
        "etapa": "",
        "cantidad": 10
      }, {
        "etapa": "CEBA",
        "cantidad": 7
      }, {
        "etapa": "REPRODUCTOR",
        "cantidad": 2
      }],
      "piramide_edades": [{
        "edad_meses": "0-2",
        "cantidad": 1
      }, {
        "edad_meses": "12-14",
        "cantidad": 1
      }, {
        "edad_meses": "18+",
        "cantidad": 18
      }]
    },
    "salud": {
      "tratamientos_mes": {
        "actual": 3,
        "anterior": 0
      },
      "enfermedades_por_tipo": [{
        "tipo": "fiebre",
        "cantidad": 3
      }],
      "eventos_trimestrales": [{
        "trimestre": "T1",
        "recuperados": 0,
        "decesos": 0,
        "recuperados_anio_anterior": 0,
        "decesos_anio_anterior": 0
      }, {
        "trimestre": "T2",
        "recuperados": 0,
        "decesos": 0,
        "recuperados_anio_anterior": 0,
        "decesos_anio_anterior": 0
      }, {
        "trimestre": "T3",
        "recuperados": 0,
        "decesos": 0,
        "recuperados_anio_anterior": 0,
        "decesos_anio_anterior": 0
      }, {
        "trimestre": "T4",
        "recuperados": 0,
        "decesos": 3,
        "recuperados_anio_anterior": 0,
        "decesos_anio_anterior": 0
      }]
    },
    "pesos": {
      "lista_pesos": [{
        "animal_id": "l23db3a2-93e3-4e3d-97e5-b78e1dfd2aa0",
        "peso": 25,
        "edad_meses": 64
      }, {
        "animal_id": "l23db3a2-93e3-4e3d-97e5-b78e1dfd2aa0",
        "peso": 20,
        "edad_meses": 0
      }],
      "promedios_por_etapa": []
    },
    "camadas_bajas": {
      "muertes_por_mes": [{
        "mes": "November",
        "cantidad": 1
      }],
      "causas_muerte": [{
        "causa": "APLASTAMIENTO",
        "cantidad": 1
      }],
      "promedio_edad_muerte_dias": 0
    },
    "infraestructura": {
      "danos_mensuales": [{
        "mes": "October",
        "cantidad": 1
      }],
      "danos_por_tipo": [{
        "tipo": "GESTACION",
        "cantidad": 1
      }]
    },
    "decesos": {
      "muertes_mensuales": [{
        "mes": "October",
        "cantidad": 3
      }, {
        "mes": "November",
        "cantidad": 3
      }],
      "causas_probables": [{
        "causa": "la vida",
        "cantidad": 3
      }, {
        "causa": "gripe",
        "cantidad": 3
      }]
    },
    "camadas": {
      "nacidos_vs_muertos": [{
        "mes": "November",
        "nacidos": 20,
        "muertos": 1
      }],
      "peso_promedio_mes": [],
      "promedio_crias_mes": [{
        "mes": "November",
        "promedio": 20
      }]
    },
    "incidencias": {
      "por_tipo": [{
        "tipo": "RINA",
        "cantidad": 1
      }],
      "mensuales": [{
        "mes": "November",
        "cantidad": 1
      }],
      "por_area": [{
        "area": "Gestación-Edit-rd20er",
        "cantidad": 1
      }]
    }
  }



  function renderizarGraficos(JSONCONSOLIDADO) {

    /* --- ANIMALES: Distribución por etapa (Donut) --- */
    document.getElementById('totalAnimales').textContent = JSONCONSOLIDADO.animales.total_animales;

    (function() {
      const etapas = JSONCONSOLIDADO.animales.por_etapa.map(e => e.etapa);
      const cantidades = JSONCONSOLIDADO.animales.por_etapa.map(e => e.cantidad);
      const options = {
        chart: {
          type: 'donut',
          height: 320
        },
        series: cantidades,
        labels: etapas,
        legend: {
          position: 'bottom'
        },
        responsive: [{
          breakpoint: 600,
          options: {
            chart: {
              height: 260
            }
          }
        }]
      };
      new ApexCharts(document.querySelector("#chart_etapas"), options).render();
    })();

    /* --- ANIMALES: Edades (histograma) --- */
    (function() {
      const edades = JSONCONSOLIDADO.animales.piramide_edades.map(x => x.edad_meses);
      const cantidades = JSONCONSOLIDADO.animales.piramide_edades.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'bar',
          height: 320
        },
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: '60%'
          }
        },
        series: [{
          name: 'Cantidad',
          data: cantidades
        }],
        xaxis: {
          categories: edades.map(e => e + ' mes(es)')
        }
      };
      new ApexCharts(document.querySelector("#chart_edades"), options).render();
    })();

    /* --- SALUD: Tratamientos mes actual vs anterior (donut) --- */
    (function() {
      const actual = JSONCONSOLIDADO.salud.tratamientos_mes.actual || 0;
      const anterior = JSONCONSOLIDADO.salud.tratamientos_mes.anterior || 0;
      const options = {
        chart: {
          type: 'donut',
          height: 320
        },
        series: [actual, anterior],
        labels: ['Actual', 'Anterior'],
        legend: {
          position: 'bottom'
        }
      };
      new ApexCharts(document.querySelector("#chart_tratamientos"), options).render();
    })();

    /* --- SALUD: Enfermedades por tipo (bar horizontal) --- */
    (function() {
      const tipos = JSONCONSOLIDADO.salud.enfermedades_por_tipo.map(x => x.tipo);
      const cantidades = JSONCONSOLIDADO.salud.enfermedades_por_tipo.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'bar',
          height: 320
        },
        plotOptions: {
          bar: {
            horizontal: true
          }
        },
        series: [{
          name: 'Casos',
          data: cantidades
        }],
        xaxis: {
          categories: tipos
        }
      };
      new ApexCharts(document.querySelector("#chart_enfermedades"), options).render();
    })();







    /* --- SALUD: Eventos trimestrales (bar vertical comparativa años) --- */
    (function() {
      const trimestres = JSONCONSOLIDADO.salud.eventos_trimestrales.map(x => x.trimestre);
      const recuperados = JSONCONSOLIDADO.salud.eventos_trimestrales.map(x => x.recuperados);
      const decesos = JSONCONSOLIDADO.salud.eventos_trimestrales.map(x => x.decesos);
      const rec_prev = JSONCONSOLIDADO.salud.eventos_trimestrales.map(x => x.recuperados_anio_anterior);
      const dec_prev = JSONCONSOLIDADO.salud.eventos_trimestrales.map(x => x.decesos_anio_anterior);

      const options = {
        chart: {
          type: 'bar',
          height: 360,
          stacked: false
        },
        series: [{
            name: 'Recuperados (Este Año)',
            data: recuperados
          },
          {
            name: 'Decesos (Este Año)',
            data: decesos
          },
          {
            name: 'Recuperados (Año Anterior)',
            data: rec_prev
          },
          {
            name: 'Decesos (Año Anterior)',
            data: dec_prev
          }
        ],
        xaxis: {
          categories: trimestres
        },
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: '50%'
          }
        },
        tooltip: {
          shared: true,
          intersect: false
        }
      };
      new ApexCharts(document.querySelector("#chart_eventos_trimestrales"), options).render();
    })();

    /* --- PESOS: Scatter peso vs edad --- */
    (function() {
      const lista = JSONCONSOLIDADO.pesos.lista_pesos.map(p => ({
        x: p.edad_meses,
        y: p.peso,
        id: p.animal_id
      }));

      const peso_ideal = JSONCONSOLIDADO.pesos.peso_ideal.map(p => ({
        x: p.edad_meses,
        y: p.peso
      }));

      const options = {
        chart: {
          type: 'scatter',
          height: 320
        },
        series: [{
            name: 'Peso vs Edad',
            type: 'scatter',
            data: lista
          },
          {
            name: 'Peso Ideal',
            type: 'line',
            data: peso_ideal,
            stroke: {
              curve: 'straight',
              width: 2
            },
            markers: {
              size: 0
            } 
          }
        ],
        xaxis: {
          title: {
            text: 'Edad (meses)'
          }
        },
        yaxis: {
          title: {
            text: 'Peso (kg)'
          }
        },
        tooltip: {
          y: {
            formatter: (val) => val + ' kg'
          },
          x: {
            formatter: (val) => val + ' meses'
          }
        }
      };

      new ApexCharts(document.querySelector("#chart_pesos_scatter"), options).render();
    })();




    /* --- PESOS: Promedio por etapa (bar vertical) --- */
    (function() {
      const etapas = JSONCONSOLIDADO.pesos.promedios_por_etapa.map(x => x.etapa);
      const promedios = JSONCONSOLIDADO.pesos.promedios_por_etapa.map(x => x.promedio_peso);
      const options = {
        chart: {
          type: 'bar',
          height: 320
        },
        series: [{
          name: 'Promedio peso',
          data: promedios
        }],
        xaxis: {
          categories: etapas
        },
        plotOptions: {
          bar: {
            columnWidth: '50%'
          }
        }
      };
      new ApexCharts(document.querySelector("#chart_promedio_peso_etapa"), options).render();
    })();

    /* --- CAMADAS BAJAS: Muertes por mes (bar vertical) --- */
    (function() {
      const meses = JSONCONSOLIDADO.camadas_bajas.muertes_por_mes.map(x => x.mes);
      const cantidades = JSONCONSOLIDADO.camadas_bajas.muertes_por_mes.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'bar',
          height: 320
        },
        series: [{
          name: 'Muertes',
          data: cantidades
        }],
        xaxis: {
          categories: meses
        },
        plotOptions: {
          bar: {
            columnWidth: '50%'
          }
        }
      };
      new ApexCharts(document.querySelector("#chart_muertes_lactantes"), options).render();

      // promedio edad
      document.getElementById('promedioEdadMuerte').textContent = JSONCONSOLIDADO.camadas_bajas.promedio_edad_muerte_dias + ' días';
    })();

    /* --- CAMADAS BAJAS: Causa más común (donut) --- */
    (function() {
      const causas = JSONCONSOLIDADO.camadas_bajas.causas_muerte.map(x => x.causa);
      const cantidades = JSONCONSOLIDADO.camadas_bajas.causas_muerte.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'donut',
          height: 320
        },
        series: cantidades,
        labels: causas,
        legend: {
          position: 'bottom'
        }
      };
      new ApexCharts(document.querySelector("#chart_causas_muerte"), options).render();
    })();

    /* --- INFRAESTRUCTURA: Daños mensuales (bar) --- */
    (function() {
      const meses = JSONCONSOLIDADO.infraestructura.danos_mensuales.map(x => x.mes);
      const cantidades = JSONCONSOLIDADO.infraestructura.danos_mensuales.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'bar',
          height: 320
        },
        series: [{
          name: 'Daños',
          data: cantidades
        }],
        xaxis: {
          categories: meses
        }
      };
      new ApexCharts(document.querySelector("#chart_danos_mensuales"), options).render();
    })();

    /* --- INFRAESTRUCTURA: Daños por tipo (donut) --- */
    (function() {
      const tipos = JSONCONSOLIDADO.infraestructura.danos_por_tipo.map(x => x.tipo);
      const cantidades = JSONCONSOLIDADO.infraestructura.danos_por_tipo.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'donut',
          height: 320
        },
        series: cantidades,
        labels: tipos,
        legend: {
          position: 'bottom'
        }
      };
      new ApexCharts(document.querySelector("#chart_danos_por_tipo"), options).render();
    })();

    /* --- DECESOS: Muertes mensuales (line) --- */
    (function() {
      const meses = JSONCONSOLIDADO.decesos.muertes_mensuales.map(x => x.mes);
      const cantidades = JSONCONSOLIDADO.decesos.muertes_mensuales.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'line',
          height: 320
        },
        series: [{
          name: 'Muertes',
          data: cantidades
        }],
        xaxis: {
          categories: meses
        }
      };
      new ApexCharts(document.querySelector("#chart_decesos_mensuales"), options).render();
    })();

    /* --- DECESOS: Top causas (bar horizontal) --- */
    (function() {
      const causas = JSONCONSOLIDADO.decesos.causas_probables.map(x => x.causa);
      const cantidades = JSONCONSOLIDADO.decesos.causas_probables.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'bar',
          height: 320
        },
        plotOptions: {
          bar: {
            horizontal: true
          }
        },
        series: [{
          name: 'Casos',
          data: cantidades
        }],
        xaxis: {
          categories: causas
        }
      };
      new ApexCharts(document.querySelector("#chart_causas_decesos"), options).render();
    })();

    /* --- CAMADAS: Nacidos vs Muertos (line) --- */
    (function() {
      const meses = JSONCONSOLIDADO.camadas.nacidos_vs_muertos.map(x => x.mes);
      const nacidos = JSONCONSOLIDADO.camadas.nacidos_vs_muertos.map(x => x.nacidos);
      const muertos = JSONCONSOLIDADO.camadas.nacidos_vs_muertos.map(x => x.muertos);
      const options = {
        chart: {
          type: 'line',
          height: 320
        },
        series: [{
            name: 'Nacidos',
            data: nacidos
          },
          {
            name: 'Muertos',
            data: muertos
          }
        ],
        xaxis: {
          categories: meses
        }
      };
      new ApexCharts(document.querySelector("#chart_nacidos_vs_muertos"), options).render();
    })();

    /* --- CAMADAS: Promedio de peso por camada (barra agrupada) --- */
    (function() {
      const meses = JSONCONSOLIDADO.camadas.peso_promedio_mes.map(x => x.mes);
      const datos = JSONCONSOLIDADO.camadas.peso_promedio_mes.map(x => x.promedio_peso);
      const options = {
        chart: {
          type: 'bar',
          height: 320
        },
        series: [{
          name: 'Promedio peso (kg)',
          data: datos
        }],
        xaxis: {
          categories: meses
        },
        plotOptions: {
          bar: {
            columnWidth: '50%'
          }
        }
      };
      new ApexCharts(document.querySelector("#chart_peso_promedio_camadas"), options).render();
    })();

    /* --- CAMADAS: Promedio crías por parto por mes (bar) --- */
    (function() {
      const meses = JSONCONSOLIDADO.camadas.promedio_crias_mes.map(x => x.mes);
      const datos = JSONCONSOLIDADO.camadas.promedio_crias_mes.map(x => x.promedio);
      const options = {
        chart: {
          type: 'bar',
          height: 220
        },
        series: [{
          name: 'Promedio crías',
          data: datos
        }],
        xaxis: {
          categories: meses
        }
      };
      new ApexCharts(document.querySelector("#chart_promedio_crias"), options).render();
    })();

    /* --- INCIDENCIAS: por tipo (bar horizontal) --- */
    (function() {
      const tipos = JSONCONSOLIDADO.incidencias.por_tipo.map(x => x.tipo);
      const cantidades = JSONCONSOLIDADO.incidencias.por_tipo.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'bar',
          height: 320
        },
        plotOptions: {
          bar: {
            horizontal: true
          }
        },
        series: [{
          name: 'Incidencias',
          data: cantidades
        }],
        xaxis: {
          categories: tipos
        }
      };
      new ApexCharts(document.querySelector("#chart_incidencias_tipo"), options).render();
    })();

    /* --- INCIDENCIAS: Mensuales (line) --- */
    (function() {
      const meses = JSONCONSOLIDADO.incidencias.mensuales.map(x => x.mes);
      const datos = JSONCONSOLIDADO.incidencias.mensuales.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'line',
          height: 320
        },
        series: [{
          name: 'Incidencias',
          data: datos
        }],
        xaxis: {
          categories: meses
        }
      };
      new ApexCharts(document.querySelector("#chart_incidencias_mensuales"), options).render();
    })();

    /* --- INCIDENCIAS: por area (donut) --- */
    (function() {
      const areas = JSONCONSOLIDADO.incidencias.por_area.map(x => x.area);
      const datos = JSONCONSOLIDADO.incidencias.por_area.map(x => x.cantidad);
      const options = {
        chart: {
          type: 'donut',
          height: 320
        },
        series: datos,
        labels: areas,
        legend: {
          position: 'bottom'
        }
      };
      new ApexCharts(document.querySelector("#chart_incidencias_area"), options).render();
    })();
  }
</script>

