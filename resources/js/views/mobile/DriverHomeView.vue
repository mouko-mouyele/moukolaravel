<template>
  <div>
    <h1 class="page-title">Bonjour {{ auth.user?.name?.split(' ')[0] }} 👋</h1>
    <p class="subtitle">Module mobile chauffeur</p>

    <div v-if="loading" class="loading">Chargement...</div>
    <template v-else>
      <div v-if="data?.has_active_mission" class="card mission-card active">
        <span class="badge badge-in_mission">En mission</span>
        <h2>{{ data.active_assignment.vehicle?.license_plate }}</h2>
        <p>{{ data.active_assignment.vehicle?.brand }} {{ data.active_assignment.vehicle?.model }}</p>
        <p class="km">{{ data.active_assignment.vehicle?.current_mileage?.toLocaleString('fr-FR') }} km</p>
        <router-link to="/mobile/mission" class="btn btn-primary btn-block">Voir la mission</router-link>
      </div>

      <div v-else class="card mission-card idle">
        <span class="idle-icon">🅿️</span>
        <p>Aucune mission active</p>
        <small>Contactez votre gestionnaire de parc</small>
      </div>

      <div class="quick-actions">
        <router-link to="/mobile/kilometrage" class="action-card">
          <span>📊</span>
          <strong>Relevé km</strong>
          <small>Certifier sur blockchain</small>
        </router-link>
        <router-link to="/mobile/mission" class="action-card">
          <span>✅</span>
          <strong>Fin de mission</strong>
          <small>Clôturer le trajet</small>
        </router-link>
      </div>

      <div v-if="data?.recent_missions?.length" class="card">
        <h3 class="section-title">Missions récentes</h3>
        <div v-for="m in data.recent_missions" :key="m.id" class="history-row">
          <span>{{ m.vehicle?.license_plate }}</span>
          <span class="status">{{ m.status }}</span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const data = ref(null);
const loading = ref(true);

onMounted(async () => {
  try {
    const { data: res } = await api.get('/driver/dashboard');
    data.value = res;
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.page-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; }
.subtitle { color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.9375rem; }
.mission-card { margin-bottom: 1.25rem; text-align: center; }
.mission-card.active { text-align: left; }
.mission-card h2 { font-size: 1.5rem; margin: 0.5rem 0 0.25rem; }
.mission-card .km { font-size: 1.25rem; font-weight: 700; color: var(--primary); margin: 0.75rem 0 1rem; }
.mission-card.idle { padding: 2rem 1.5rem; }
.idle-icon { font-size: 2.5rem; display: block; margin-bottom: 0.5rem; }
.btn-block { width: 100%; justify-content: center; display: flex; text-decoration: none; }
.quick-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}
.action-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1rem;
  text-decoration: none;
  color: inherit;
  display: block;
}
.action-card span { font-size: 1.5rem; display: block; margin-bottom: 0.375rem; }
.action-card strong { display: block; font-size: 0.9375rem; }
.action-card small { color: var(--text-muted); font-size: 0.75rem; }
.section-title { font-size: 1rem; margin-bottom: 0.75rem; }
.history-row {
  display: flex;
  justify-content: space-between;
  padding: 0.625rem 0;
  border-bottom: 1px solid var(--border);
  font-size: 0.875rem;
}
.history-row:last-child { border-bottom: none; }
.status { color: var(--text-muted); text-transform: capitalize; }
</style>
