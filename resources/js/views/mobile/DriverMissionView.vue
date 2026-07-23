<template>
  <div>
    <h1 class="page-title">Ma mission</h1>

    <div v-if="loading" class="loading">Chargement...</div>

    <div v-else-if="!assignment" class="card empty">
      <span>🚗</span>
      <p>Pas de véhicule assigné</p>
    </div>

    <template v-else>
      <div class="card vehicle-card">
        <div class="plate">{{ assignment.vehicle?.license_plate }}</div>
        <h2>{{ assignment.vehicle?.brand }} {{ assignment.vehicle?.model }}</h2>
        <div class="stats">
          <div><small>Kilométrage</small><strong>{{ assignment.vehicle?.current_mileage?.toLocaleString('fr-FR') }} km</strong></div>
          <div><small>Carburant</small><strong>{{ assignment.vehicle?.fuel_type }}</strong></div>
        </div>
      </div>

      <div v-if="!assignment.start_mileage" class="card">
        <h3>Déclarer la prise en charge</h3>
        <form @submit.prevent="declarePickup">
          <div class="form-group">
            <label>Km départ</label>
            <input v-model.number="pickupKm" type="number" class="input" required />
          </div>
          <button type="submit" class="btn btn-primary btn-block" :disabled="submitting">
            {{ submitting ? '...' : 'Confirmer prise en charge' }}
          </button>
        </form>
      </div>

      <div v-else class="card">
        <h3>Terminer la mission</h3>
        <p class="hint">Km départ : {{ assignment.start_mileage?.toLocaleString('fr-FR') }} km</p>
        <form @submit.prevent="completeMission">
          <div class="form-group">
            <label>Km arrivée</label>
            <input v-model.number="endKm" type="number" class="input" :min="assignment.vehicle?.current_mileage" required />
          </div>
          <div class="form-group">
            <label>Notes</label>
            <input v-model="notes" class="input" placeholder="Optionnel" />
          </div>
          <button type="submit" class="btn btn-primary btn-block" :disabled="submitting">
            {{ submitting ? '...' : 'Clôturer la mission' }}
          </button>
        </form>
      </div>

      <p v-if="message" :class="messageOk ? 'success-msg' : 'alert-error'">{{ message }}</p>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api';

const assignment = ref(null);
const loading = ref(true);
const submitting = ref(false);
const pickupKm = ref(0);
const endKm = ref(0);
const notes = ref('');
const message = ref('');
const messageOk = ref(false);

async function load() {
  loading.value = true;
  const { data } = await api.get('/driver/dashboard');
  assignment.value = data.active_assignment;
  if (assignment.value) {
    pickupKm.value = assignment.value.vehicle?.current_mileage || 0;
    endKm.value = assignment.value.vehicle?.current_mileage || 0;
  }
  loading.value = false;
}

async function declarePickup() {
  submitting.value = true;
  message.value = '';
  try {
    await api.post(`/driver/assignments/${assignment.value.id}/pickup`, {
      start_mileage: pickupKm.value,
    });
    messageOk.value = true;
    message.value = 'Prise en charge enregistrée.';
    await load();
  } catch (e) {
    messageOk.value = false;
    message.value = e.response?.data?.message || 'Erreur';
  } finally {
    submitting.value = false;
  }
}

async function completeMission() {
  submitting.value = true;
  message.value = '';
  try {
    await api.post(`/driver/assignments/${assignment.value.id}/complete`, {
      end_mileage: endKm.value,
      notes: notes.value,
    });
    messageOk.value = true;
    message.value = 'Mission terminée.';
    await load();
  } catch (e) {
    messageOk.value = false;
    message.value = e.response?.data?.message || 'Erreur';
  } finally {
    submitting.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.page-title { font-size: 1.375rem; font-weight: 700; margin-bottom: 1.25rem; }
.empty { text-align: center; padding: 2rem; }
.empty span { font-size: 2.5rem; display: block; margin-bottom: 0.5rem; }
.vehicle-card { margin-bottom: 1rem; }
.plate {
  display: inline-block;
  background: var(--primary);
  color: white;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-weight: 700;
  font-size: 1.125rem;
  margin-bottom: 0.5rem;
}
.vehicle-card h2 { font-size: 1.125rem; margin-bottom: 1rem; }
.stats { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.stats small { display: block; color: var(--text-muted); font-size: 0.75rem; }
.stats strong { font-size: 1rem; }
.card h3 { font-size: 1rem; margin-bottom: 1rem; }
.hint { color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem; }
.btn-block { width: 100%; justify-content: center; display: flex; }
.success-msg {
  margin-top: 1rem;
  padding: 0.75rem;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid var(--success);
  border-radius: 8px;
  color: var(--success);
  font-size: 0.875rem;
}
</style>
