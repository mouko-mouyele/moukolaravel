<template>
  <div>
    <h1 class="page-title">Relevé kilométrique</h1>
    <p class="subtitle">Horodaté et certifié blockchain</p>

    <div v-if="loading" class="loading">Chargement...</div>
    <div v-else-if="!vehicle" class="card empty">
      <p>Aucun véhicule en mission</p>
      <router-link to="/mobile/mission">Voir ma mission →</router-link>
    </div>

    <form v-else class="card" @submit.prevent="submit">
      <div class="vehicle-info">
        <strong>{{ vehicle.license_plate }}</strong>
        <span>{{ vehicle.brand }} {{ vehicle.model }}</span>
      </div>
      <div class="form-group">
        <label>Kilométrage actuel</label>
        <input v-model.number="mileage" type="number" class="input input-lg" :min="vehicle.current_mileage" required inputmode="numeric" />
        <small>Minimum : {{ vehicle.current_mileage?.toLocaleString('fr-FR') }} km</small>
      </div>
      <div class="form-group">
        <label>Notes (optionnel)</label>
        <input v-model="notes" class="input" placeholder="Fin de trajet, etc." />
      </div>
      <button type="submit" class="btn btn-primary btn-block" :disabled="submitting">
        {{ submitting ? 'Certification...' : '⛓ Certifier sur blockchain' }}
      </button>
      <p v-if="message" :class="ok ? 'success-msg' : 'alert-error'">{{ message }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api';
import { certifyAfterApi } from '../../services/contract';

const vehicle = ref(null);
const assignmentId = ref(null);
const mileage = ref(0);
const notes = ref('');
const loading = ref(true);
const submitting = ref(false);
const message = ref('');
const ok = ref(false);

onMounted(async () => {
  const { data } = await api.get('/driver/dashboard');
  if (data.active_assignment?.vehicle) {
    vehicle.value = data.active_assignment.vehicle;
    assignmentId.value = data.active_assignment.id;
    mileage.value = vehicle.value.current_mileage;
  }
  loading.value = false;
});

async function submit() {
  submitting.value = true;
  message.value = '';
  try {
    const { data } = await api.post('/mileage-readings', {
      vehicle_id: vehicle.value.id,
      assignment_id: assignmentId.value,
      mileage: mileage.value,
      notes: notes.value,
      certify_on_chain: true,
    });
    const tx = await certifyAfterApi(data, vehicle.value.uuid, { type: 'mileage', mileage: mileage.value });
    ok.value = true;
    message.value = tx ? `Certifié ! Tx: ${tx.slice(0, 16)}...` : 'Kilométrage enregistré (mode simulé)';
    vehicle.value.current_mileage = mileage.value;
  } catch (e) {
    ok.value = false;
    message.value = e.response?.data?.message || e.message || 'Erreur';
  } finally {
    submitting.value = false;
  }
}
</script>

<style scoped>
.page-title { font-size: 1.375rem; font-weight: 700; }
.subtitle { color: var(--text-muted); margin-bottom: 1.25rem; font-size: 0.9375rem; }
.empty { text-align: center; padding: 2rem; }
.vehicle-info { margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
.vehicle-info strong { display: block; font-size: 1.25rem; margin-bottom: 0.25rem; }
.vehicle-info span { color: var(--text-muted); font-size: 0.875rem; }
.input-lg { font-size: 1.5rem; font-weight: 700; text-align: center; padding: 1rem; }
.form-group small { display: block; margin-top: 0.375rem; color: var(--text-muted); font-size: 0.8125rem; }
.btn-block { width: 100%; justify-content: center; display: flex; margin-top: 0.5rem; }
.success-msg { margin-top: 1rem; padding: 0.75rem; background: rgba(16,185,129,0.15); border: 1px solid var(--success); border-radius: 8px; color: var(--success); font-size: 0.875rem; }
</style>
