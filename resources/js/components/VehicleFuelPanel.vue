<template>
  <div class="panel card">
    <h3>Suivi carburant</h3>
    <p v-if="stats?.average_consumption" class="avg">Moyenne : <strong>{{ stats.average_consumption }} L/100 km</strong></p>

    <form v-if="!readOnly" @submit.prevent="submit" class="fuel-form">
      <div class="form-row">
        <div class="form-group"><label>Km</label><input v-model.number="form.mileage_at_fill" type="number" class="input" required /></div>
        <div class="form-group"><label>Litres</label><input v-model.number="form.liters" type="number" step="0.01" class="input" required /></div>
        <div class="form-group"><label>Coût €</label><input v-model.number="form.total_cost" type="number" step="0.01" class="input" /></div>
      </div>
      <button type="submit" class="btn btn-primary">Enregistrer le plein</button>
    </form>

    <ul class="records">
      <li v-for="r in records" :key="r.id">
        <span>{{ formatDate(r.filled_at) }}</span>
        <span>{{ r.liters }} L · {{ r.mileage_at_fill?.toLocaleString('fr-FR') }} km</span>
        <span v-if="r.consumption_per_100km">{{ r.consumption_per_100km }} L/100</span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';

const props = defineProps({ vehicleId: Number, currentMileage: Number, readOnly: Boolean });
const records = ref([]);
const stats = ref(null);
const form = ref({ mileage_at_fill: 0, liters: 0, total_cost: null });

function formatDate(d) { return new Date(d).toLocaleDateString('fr-FR'); }

async function load() {
  const [rec, st] = await Promise.all([
    api.get('/fuel-records', { params: { vehicle_id: props.vehicleId } }),
    api.get(`/vehicles/${props.vehicleId}/fuel-stats`),
  ]);
  records.value = rec.data.data || rec.data;
  stats.value = st.data;
  form.value.mileage_at_fill = props.currentMileage || 0;
}

async function submit() {
  await api.post('/fuel-records', { vehicle_id: props.vehicleId, ...form.value });
  await load();
}

onMounted(load);
</script>

<style scoped>
.avg { margin-bottom: 1rem; color: var(--primary); }
.form-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-bottom: 1rem; }
.records { list-style: none; margin-top: 1rem; }
.records li { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border); font-size: 0.875rem; }
</style>
