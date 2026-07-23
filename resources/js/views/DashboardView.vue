<template>
  <div>
    <header class="page-header">
      <h1>Tableau de bord</h1>
      <p>État de la flotte en temps réel</p>
    </header>

    <div v-if="loading" class="loading">Chargement...</div>
    <template v-else-if="data">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="value">{{ data.fleet.total }}</div>
          <div class="label">Véhicules total</div>
        </div>
        <div class="stat-card">
          <div class="value" style="color: var(--success)">{{ data.fleet.available }}</div>
          <div class="label">Disponibles</div>
        </div>
        <div class="stat-card">
          <div class="value" style="color: var(--primary)">{{ data.fleet.in_mission }}</div>
          <div class="label">En mission</div>
        </div>
        <div class="stat-card">
          <div class="value" style="color: var(--warning)">{{ data.fleet.in_maintenance }}</div>
          <div class="label">En maintenance</div>
        </div>
        <div class="stat-card">
          <div class="value" style="color: var(--danger)">{{ data.fleet.out_of_service }}</div>
          <div class="label">En panne</div>
        </div>
        <div class="stat-card">
          <div class="value" style="color: var(--danger)">{{ data.alerts_unresolved }}</div>
          <div class="label">Alertes actives</div>
        </div>
        <div class="stat-card">
          <div class="value">{{ data.active_assignments }}</div>
          <div class="label">Affectations actives</div>
        </div>
      </div>

      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
          <h2 style="font-size: 1.125rem; margin:0">Alertes récentes</h2>
          <router-link to="/alerts">Voir tout →</router-link>
        </div>
        <div v-if="!data.recent_alerts?.length" style="color: var(--text-muted)">Aucune alerte</div>
        <ul v-else class="alert-list">
          <li v-for="a in data.recent_alerts" :key="a.id">
            <router-link v-if="a.vehicle" :to="{ name: 'vehicle-detail', params: { id: a.vehicle.id } }">
              <strong>{{ a.title }}</strong>
            </router-link>
            <strong v-else>{{ a.title }}</strong>
            <span>{{ a.vehicle?.license_plate }}</span>
            <p>{{ a.message }}</p>
          </li>
        </ul>
      </div>

      <div class="quick-links card" style="margin-top:1rem">
        <h2 style="margin-bottom:0.75rem;font-size:1.125rem">Accès rapide</h2>
        <div class="links">
          <router-link to="/vehicles" class="btn btn-secondary">Véhicules</router-link>
          <router-link to="/blockchain" class="btn btn-secondary">Registre ⛓</router-link>
          <router-link to="/sales" class="btn btn-secondary">Ventes</router-link>
        </div>
      </div>

      <div class="card" style="margin-top: 1rem">
        <h2 style="margin-bottom: 0.5rem; font-size: 1.125rem">Coûts maintenance</h2>
        <p style="font-size: 1.5rem; font-weight: 700">{{ formatMoney(data.maintenance_costs.total) }}</p>
        <p style="color: var(--text-muted); font-size: 0.875rem">{{ data.maintenance_costs.operations_count }} opérations</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';

const data = ref(null);
const loading = ref(true);

function formatMoney(n) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(n || 0);
}

onMounted(async () => {
  try {
    const { data: res } = await api.get('/dashboard');
    data.value = res;
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.alert-list { list-style: none; }
.alert-list li {
  padding: 1rem 0;
  border-bottom: 1px solid var(--border);
}
.alert-list li:last-child { border-bottom: none; }
.alert-list span {
  margin-left: 0.5rem;
  font-size: 0.8125rem;
  color: var(--text-muted);
  background: var(--bg);
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
}
.alert-list p { margin-top: 0.25rem; font-size: 0.875rem; color: var(--text-muted); }
.links { display: flex; flex-wrap: wrap; gap: 0.75rem; }
</style>
