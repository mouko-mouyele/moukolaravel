<template>
  <div>
    <header class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start">
      <div>
        <h1>Véhicules</h1>
        <p>Flotte automobile certifiée</p>
      </div>
      <button v-if="auth.canManageFleet && !auth.isReadOnly" class="btn btn-primary" @click="showForm = !showForm">
        {{ showForm ? 'Annuler' : '+ Ajouter' }}
      </button>
    </header>

    <div v-if="showForm && auth.canManageFleet && !auth.isReadOnly" class="card" style="margin-bottom: 1.5rem">
      <h2 style="margin-bottom: 1rem">Nouveau véhicule</h2>
      <form @submit.prevent="createVehicle" class="form-grid">
        <div class="form-group"><label>VIN</label><input v-model="form.vin" class="input" maxlength="17" required /></div>
        <div class="form-group"><label>Immatriculation</label><input v-model="form.license_plate" class="input" required /></div>
        <div class="form-group"><label>Marque</label><input v-model="form.brand" class="input" required /></div>
        <div class="form-group"><label>Modèle</label><input v-model="form.model" class="input" required /></div>
        <div class="form-group"><label>Année</label><input v-model.number="form.year" type="number" class="input" required /></div>
        <div class="form-group"><label>Carburant</label><input v-model="form.fuel_type" class="input" required /></div>
        <div class="form-group"><label>Kilométrage</label><input v-model.number="form.current_mileage" type="number" class="input" /></div>
        <div class="form-group full"><button type="submit" class="btn btn-primary">Enregistrer</button></div>
      </form>
    </div>

    <div class="filters card">
      <input v-model="search" class="input" placeholder="Rechercher immat., VIN, marque..." @keyup.enter="load" />
      <select v-model="statusFilter" class="input" @change="load">
        <option value="">Tous statuts</option>
        <option value="available">Disponible</option>
        <option value="in_mission">En mission</option>
        <option value="in_maintenance">Maintenance</option>
        <option value="out_of_service">En panne</option>
        <option value="sold">Vendu</option>
      </select>
      <button class="btn btn-secondary" @click="load">Filtrer</button>
    </div>

    <div v-if="loading" class="loading">Chargement...</div>
    <div v-else class="card table-wrap">
      <table>
        <thead>
          <tr><th>Immat.</th><th>Véhicule</th><th>Km</th><th>Statut</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="v in vehicles" :key="v.id">
            <td><strong>{{ v.license_plate }}</strong></td>
            <td>{{ v.brand }} {{ v.model }} ({{ v.year }})</td>
            <td>{{ v.current_mileage?.toLocaleString('fr-FR') }} km</td>
            <td><span :class="['badge', `badge-${v.status}`]">{{ statusLabel(v.status) }}</span></td>
            <td><router-link :to="{ name: 'vehicle-detail', params: { id: v.id } }">Détails →</router-link></td>
          </tr>
        </tbody>
      </table>
      <p v-if="!vehicles.length" class="empty">Aucun véhicule trouvé</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';
import { registerVehicleOnChain, confirmBlockchainRecord, getConfig } from '../services/contract';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const vehicles = ref([]);
const loading = ref(true);
const showForm = ref(false);
const search = ref('');
const statusFilter = ref('');
const form = ref({ vin: '', license_plate: '', brand: '', model: '', year: 2024, fuel_type: 'essence', current_mileage: 0 });

const statusLabels = {
  available: 'Disponible', in_mission: 'En mission', in_maintenance: 'Maintenance',
  out_of_service: 'En panne', sold: 'Vendu',
};
function statusLabel(s) { return statusLabels[s] || s; }

async function load() {
  loading.value = true;
  const params = { per_page: 100 };
  if (search.value) params.search = search.value;
  if (statusFilter.value) params.status = statusFilter.value;
  const { data } = await api.get('/vehicles', { params });
  vehicles.value = data.data || data;
  loading.value = false;
}

async function createVehicle() {
  const { data } = await api.post('/vehicles', form.value);
  const config = await getConfig();
  if (config.enabled && data.vehicle) {
    try {
      const tx = await registerVehicleOnChain(data.vehicle);
      if (tx && data.blockchain_record?.id) await confirmBlockchainRecord(data.blockchain_record.id, tx);
    } catch (e) { console.warn('registerVehicle on-chain:', e.message); }
  }
  showForm.value = false;
  form.value = { vin: '', license_plate: '', brand: '', model: '', year: 2024, fuel_type: 'essence', current_mileage: 0 };
  await load();
}

onMounted(load);
</script>

<style scoped>
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.form-grid .full { grid-column: 1 / -1; }
.filters { display: flex; gap: 0.75rem; margin-bottom: 1rem; padding: 1rem; flex-wrap: wrap; }
.filters .input { flex: 1; min-width: 160px; }
.empty { text-align: center; padding: 2rem; color: var(--text-muted); }
@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
</style>
