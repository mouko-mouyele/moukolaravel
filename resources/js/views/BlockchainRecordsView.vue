<template>
  <div>
    <header class="page-header">
      <h1>Registre blockchain</h1>
      <p>Preuves ancrées — {{ auth.isReadOnly ? 'consultation auditeur' : 'traçabilité complète' }}</p>
    </header>

    <div class="filters card">
      <select v-model="filters.status" class="input" @change="load">
        <option value="">Tous statuts</option>
        <option value="confirmed">Confirmé</option>
        <option value="pending">En attente</option>
        <option value="simulated">Simulé</option>
      </select>
      <input v-model.number="filters.vehicle_id" type="number" class="input" placeholder="ID véhicule" @keyup.enter="load" />
      <button class="btn btn-secondary" @click="load">Filtrer</button>
    </div>

    <div v-if="loading" class="loading">Chargement...</div>
    <div v-else class="card table-wrap">
      <table>
        <thead>
          <tr>
            <th>Type</th>
            <th>Véhicule</th>
            <th>Hash données</th>
            <th>Tx</th>
            <th>Statut</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in records" :key="r.id">
            <td><span class="badge">{{ r.record_type }}</span></td>
            <td>
              <router-link v-if="r.vehicle" :to="{ name: 'vehicle-detail', params: { id: r.vehicle.id } }">
                {{ r.vehicle.license_plate }}
              </router-link>
              <span v-else>—</span>
            </td>
            <td><code>{{ r.data_hash?.slice(0, 12) }}...</code></td>
            <td>
              <code v-if="r.tx_hash" class="tx">{{ r.tx_hash.slice(0, 14) }}...</code>
              <span v-else>—</span>
            </td>
            <td><span :class="['badge', `badge-${r.status}`]">{{ r.status }}</span></td>
            <td>{{ formatDate(r.created_at) }}</td>
          </tr>
        </tbody>
      </table>
      <p v-if="!records.length" class="empty">Aucun enregistrement</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const records = ref([]);
const loading = ref(true);
const filters = ref({ status: '', vehicle_id: null });

function formatDate(d) {
  return new Date(d).toLocaleString('fr-FR');
}

async function load() {
  loading.value = true;
  const params = { per_page: 50 };
  if (filters.value.status) params.status = filters.value.status;
  if (filters.value.vehicle_id) params.vehicle_id = filters.value.vehicle_id;
  const { data } = await api.get('/blockchain/records', { params });
  records.value = data.data || data;
  loading.value = false;
}

onMounted(load);
</script>

<style scoped>
.filters { display: flex; gap: 0.75rem; margin-bottom: 1rem; padding: 1rem; flex-wrap: wrap; }
.filters .input { max-width: 200px; }
.tx { font-size: 0.75rem; color: var(--success); }
.empty { text-align: center; padding: 2rem; color: var(--text-muted); }
.badge-confirmed { background: rgba(16,185,129,0.2); color: var(--success); }
.badge-pending { background: rgba(245,158,11,0.2); color: var(--warning); }
.badge-simulated { background: rgba(148,163,184,0.2); color: var(--text-muted); }
</style>
