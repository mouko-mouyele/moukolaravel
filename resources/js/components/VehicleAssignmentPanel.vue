<template>
  <div class="panel card">
    <h3>Affectation chauffeur</h3>

    <form v-if="!readOnly" @submit.prevent="assign">
      <div class="form-group">
        <label>Chauffeur</label>
        <select v-model="driverId" class="input" required>
          <option value="">— Sélectionner —</option>
          <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }} ({{ d.email }})</option>
        </select>
      </div>
      <div class="form-group">
        <label>Km départ</label>
        <input v-model.number="startMileage" type="number" class="input" />
      </div>
      <button type="submit" class="btn btn-primary" :disabled="loading">{{ loading ? '...' : 'Affecter' }}</button>
      <p v-if="msg" class="msg">{{ msg }}</p>
    </form>

    <h4 style="margin-top:1.5rem;margin-bottom:0.75rem">Historique</h4>
    <ul class="list">
      <li v-for="a in assignments" :key="a.id">
        <div class="row">
          <div>
            <strong>{{ a.driver?.name }}</strong>
            <span class="badge">{{ statusLabel(a.status) }}</span>
            <small>{{ a.start_mileage?.toLocaleString('fr-FR') }} → {{ a.end_mileage?.toLocaleString('fr-FR') ?? '...' }} km</small>
          </div>
          <button
            v-if="!readOnly && a.status === 'active'"
            class="btn btn-secondary btn-sm"
            @click="complete(a.id)"
          >Clôturer</button>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';

const props = defineProps({ vehicleId: Number, currentMileage: Number, readOnly: Boolean });
const drivers = ref([]);
const assignments = ref([]);
const driverId = ref('');
const startMileage = ref(0);
const loading = ref(false);
const msg = ref('');

const statusLabels = { active: 'Active', completed: 'Terminée', cancelled: 'Annulée' };
function statusLabel(s) { return statusLabels[s] || s; }

async function load() {
  const [users, assign] = await Promise.all([
    api.get('/users', { params: { role: 'driver', per_page: 50 } }),
    api.get('/assignments', { params: { vehicle_id: props.vehicleId } }),
  ]);
  drivers.value = users.data.data || users.data;
  assignments.value = assign.data.data || assign.data;
  startMileage.value = props.currentMileage || 0;
}

async function assign() {
  loading.value = true;
  msg.value = '';
  try {
    await api.post(`/vehicles/${props.vehicleId}/assignments`, { driver_id: driverId.value, start_mileage: startMileage.value });
    msg.value = 'Chauffeur affecté.';
    await load();
  } catch (e) {
    msg.value = e.response?.data?.message || 'Erreur';
  } finally {
    loading.value = false;
  }
}

async function complete(id) {
  const endMileage = prompt('Kilométrage de fin de mission ?', String(props.currentMileage || 0));
  if (!endMileage) return;
  await api.post(`/assignments/${id}/complete`, { end_mileage: Number(endMileage) });
  await load();
}

onMounted(load);
</script>

<style scoped>
.list { list-style: none; }
.list li { padding: 0.625rem 0; border-bottom: 1px solid var(--border); }
.row { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; }
.list small { display: block; color: var(--text-muted); font-size: 0.8125rem; margin-top: 0.25rem; }
.msg { margin-top: 0.75rem; font-size: 0.875rem; color: var(--success); }
.btn-sm { padding: 0.375rem 0.625rem; font-size: 0.75rem; }
</style>
