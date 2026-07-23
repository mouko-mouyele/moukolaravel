<template>
  <div>
    <header class="page-header">
      <h1>Atelier garagiste</h1>
      <p>Véhicules en maintenance ou en panne</p>
    </header>

    <div v-if="loading" class="loading">Chargement...</div>
    <template v-else>
      <div class="stats-grid" style="margin-bottom:1.5rem">
        <div class="stat-card"><div class="value">{{ maintenanceCount }}</div><div class="label">En maintenance</div></div>
        <div class="stat-card"><div class="value" style="color:var(--danger)">{{ brokenCount }}</div><div class="label">En panne</div></div>
        <div class="stat-card"><div class="value">{{ recentMaintenances.length }}</div><div class="label">Interventions récentes</div></div>
      </div>

      <div class="card" style="margin-bottom:1rem">
        <h2 style="margin-bottom:1rem">Véhicules à traiter</h2>
        <div v-if="!workshopVehicles.length" class="empty">Aucun véhicule en atelier</div>
        <ul v-else class="vehicle-list">
          <li v-for="v in workshopVehicles" :key="v.id">
            <div>
              <strong>{{ v.license_plate }}</strong> — {{ v.brand }} {{ v.model }}
              <span :class="['badge', `badge-${v.status}`]">{{ statusLabel(v.status) }}</span>
            </div>
            <router-link :to="{ name: 'vehicle-detail', params: { id: v.id }, query: { tab: 'maintenance' } }" class="btn btn-primary btn-sm">
              Intervenir
            </router-link>
          </li>
        </ul>
      </div>

      <div class="card">
        <h2 style="margin-bottom:1rem">Dernières maintenances</h2>
        <ul class="maint-list">
          <li v-for="m in recentMaintenances" :key="m.id">
            <strong>{{ m.intervention_type }}</strong>
            <span>{{ m.vehicle?.license_plate }}</span>
            <small>{{ m.service_date }} · {{ m.mileage_at_service?.toLocaleString('fr-FR') }} km</small>
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../api';

const vehicles = ref([]);
const recentMaintenances = ref([]);
const loading = ref(true);

const statusLabels = {
  in_maintenance: 'Maintenance', out_of_service: 'En panne',
};
function statusLabel(s) { return statusLabels[s] || s; }

const workshopVehicles = computed(() =>
  vehicles.value.filter((v) => ['in_maintenance', 'out_of_service'].includes(v.status))
);
const maintenanceCount = computed(() => vehicles.value.filter((v) => v.status === 'in_maintenance').length);
const brokenCount = computed(() => vehicles.value.filter((v) => v.status === 'out_of_service').length);

async function load() {
  loading.value = true;
  const [vRes, mRes] = await Promise.all([
    api.get('/vehicles', { params: { per_page: 100 } }),
    api.get('/maintenances', { params: { per_page: 10 } }),
  ]);
  vehicles.value = vRes.data.data || vRes.data;
  recentMaintenances.value = mRes.data.data || mRes.data;
  loading.value = false;
}

onMounted(load);
</script>

<style scoped>
.vehicle-list, .maint-list { list-style: none; }
.vehicle-list li, .maint-list li {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.75rem 0; border-bottom: 1px solid var(--border);
}
.maint-list li { flex-direction: column; align-items: flex-start; gap: 0.25rem; }
.maint-list span { color: var(--text-muted); font-size: 0.875rem; }
.maint-list small { color: var(--text-muted); font-size: 0.75rem; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }
.empty { color: var(--text-muted); text-align: center; padding: 1.5rem; }
</style>
