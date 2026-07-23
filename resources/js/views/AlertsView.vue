<template>
  <div>
    <header class="page-header">
      <h1>Alertes</h1>
      <p>Entretiens, assurances et contrôles techniques</p>
    </header>

    <div class="filters card">
      <button :class="['tab', { active: filter === 'active' }]" @click="setFilter('active')">Actives</button>
      <button :class="['tab', { active: filter === 'all' }]" @click="setFilter('all')">Toutes</button>
      <button :class="['tab', { active: filter === 'resolved' }]" @click="setFilter('resolved')">Résolues</button>
    </div>

    <div v-if="loading" class="loading">Chargement...</div>
    <div v-else class="card">
      <div v-if="!alerts.length" class="empty">Aucune alerte</div>
      <div v-for="a in alerts" :key="a.id" :class="['alert-row', { unread: !a.is_read }]">
        <div>
          <strong>{{ a.title }}</strong>
          <router-link v-if="a.vehicle" :to="{ name: 'vehicle-detail', params: { id: a.vehicle.id } }" class="plate">
            {{ a.vehicle.license_plate }}
          </router-link>
          <p>{{ a.message }}</p>
          <small v-if="a.due_date">Échéance : {{ formatDate(a.due_date) }}</small>
        </div>
        <div v-if="auth.canResolveAlerts" class="actions">
          <button v-if="!a.is_read" class="btn btn-ghost btn-sm" @click="markRead(a.id)">Lu</button>
          <button v-if="!a.is_resolved" class="btn btn-primary btn-sm" @click="resolve(a.id)">Résoudre</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const alerts = ref([]);
const loading = ref(true);
const filter = ref('active');

function formatDate(d) { return new Date(d).toLocaleDateString('fr-FR'); }

async function load() {
  loading.value = true;
  const params = { per_page: 50 };
  if (filter.value === 'active') params.unresolved = true;
  if (filter.value === 'resolved') params.unresolved = false;
  const { data } = await api.get('/alerts', { params });
  alerts.value = (data.data || data).filter((a) => filter.value !== 'resolved' || a.is_resolved);
  loading.value = false;
}

function setFilter(f) { filter.value = f; load(); }

async function markRead(id) {
  await api.patch(`/alerts/${id}/read`);
  await load();
}

async function resolve(id) {
  if (!confirm('Marquer cette alerte comme résolue ?')) return;
  await api.patch(`/alerts/${id}/resolve`);
  await load();
}

onMounted(load);
</script>

<style scoped>
.filters { display: flex; gap: 0.5rem; margin-bottom: 1rem; padding: 0.75rem; }
.tab { padding: 0.5rem 1rem; border: 1px solid var(--border); background: var(--surface); border-radius: 8px; cursor: pointer; color: var(--text-muted); }
.tab.active { background: var(--primary); color: white; border-color: var(--primary); }
.alert-row { display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid var(--border); }
.alert-row.unread { border-left: 3px solid var(--warning); padding-left: 0.75rem; }
.plate { margin-left: 0.5rem; font-size: 0.8125rem; background: var(--bg); padding: 0.125rem 0.5rem; border-radius: 4px; }
.alert-row p { margin: 0.375rem 0; color: var(--text-muted); font-size: 0.875rem; }
.alert-row small { color: var(--warning); }
.actions { display: flex; flex-direction: column; gap: 0.375rem; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8125rem; white-space: nowrap; }
.empty { color: var(--text-muted); padding: 2rem; text-align: center; }
</style>
