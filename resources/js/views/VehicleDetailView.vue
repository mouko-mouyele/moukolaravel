<template>
  <div>
    <header class="page-header">
      <router-link to="/vehicles" style="font-size: 0.875rem; color: var(--text-muted)">← Retour</router-link>
      <div v-if="vehicle" class="header-row">
        <div>
          <h1>{{ vehicle.license_plate }} — {{ vehicle.brand }} {{ vehicle.model }}</h1>
          <p>{{ vehicle.current_mileage?.toLocaleString('fr-FR') }} km · {{ vehicle.fuel_type }}</p>
        </div>
        <span :class="['badge', `badge-${vehicle.status}`]">{{ statusLabel(vehicle.status) }}</span>
      </div>
      <p v-if="auth.isReadOnly" class="auditor-banner">Mode auditeur — consultation seule</p>
    </header>

    <div v-if="loading" class="loading">Chargement...</div>
    <template v-else-if="vehicle">
      <div class="tabs">
        <button :class="{ active: tab === 'timeline' }" @click="tab = 'timeline'">Timeline</button>
        <button :class="{ active: tab === 'documents' }" @click="tab = 'documents'">Documents</button>
        <button v-if="auth.canManageFleet" :class="{ active: tab === 'fuel' }" @click="tab = 'fuel'">Carburant</button>
        <button v-if="auth.canManageFleet" :class="{ active: tab === 'assignments' }" @click="tab = 'assignments'">Affectations</button>
        <button :class="{ active: tab === 'maintenance' }" @click="tab = 'maintenance'">Maintenance</button>
        <button v-if="auth.canManageFleet && !auth.isReadOnly" :class="{ active: tab === 'settings' }" @click="tab = 'settings'">Paramètres</button>
        <button v-if="auth.canInitiateSale && !auth.isReadOnly" :class="{ active: tab === 'sale' }" @click="tab = 'sale'">Vente ⛓</button>
      </div>

      <div v-if="tab === 'timeline'" class="card">
        <h2 style="margin-bottom: 1.5rem">Historique certifié</h2>
        <div v-if="!timeline.length" style="color: var(--text-muted)">Aucun événement</div>
        <div v-else class="timeline">
          <div v-for="(ev, i) in timeline" :key="i" class="timeline-item">
            <span :class="['timeline-dot', { certified: ev.certified }]"></span>
            <div class="timeline-date">{{ formatDate(ev.date) }} · {{ ev.source }}</div>
            <div class="timeline-title">{{ ev.title }}</div>
            <p v-if="ev.description" style="color: var(--text-muted); font-size: 0.875rem">{{ ev.description }}</p>
            <code v-if="ev.tx_hash" style="font-size: 0.7rem; color: var(--success)">{{ ev.tx_hash.slice(0, 20) }}...</code>
          </div>
        </div>
      </div>

      <VehicleDocumentsPanel v-if="tab === 'documents'" :vehicle-id="vehicle.id" :read-only="auth.isReadOnly" />
      <VehicleFuelPanel v-if="tab === 'fuel'" :vehicle-id="vehicle.id" :current-mileage="vehicle.current_mileage" :read-only="auth.isReadOnly" />
      <VehicleAssignmentPanel v-if="tab === 'assignments'" :vehicle-id="vehicle.id" :current-mileage="vehicle.current_mileage" :read-only="auth.isReadOnly" />

      <div v-if="tab === 'maintenance'" class="card">
        <h3 style="margin-bottom:1rem">Historique maintenance</h3>
        <ul v-if="maintenances.length" class="maint-history">
          <li v-for="m in maintenances" :key="m.id">
            <strong>{{ m.intervention_type }}</strong>
            <span>{{ m.service_date }} · {{ m.mileage_at_service?.toLocaleString('fr-FR') }} km</span>
            <small v-if="m.certified_on_chain">⛓ Certifié</small>
          </li>
        </ul>
        <p v-else class="muted">Aucune intervention enregistrée</p>

        <form v-if="auth.isMechanic && !auth.isReadOnly" @submit.prevent="submitMaintenance" style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--border)">
          <h3>Nouvelle intervention</h3>
          <div class="form-group">
            <label>Type</label>
            <input v-model="maintForm.intervention_type" class="input" required />
          </div>
          <div class="form-group">
            <label>Kilométrage</label>
            <input v-model.number="maintForm.mileage_at_service" type="number" class="input" required />
          </div>
          <div class="form-group">
            <label>Coût (€)</label>
            <input v-model.number="maintForm.cost" type="number" step="0.01" class="input" />
          </div>
          <div class="form-group">
            <label>Date</label>
            <input v-model="maintForm.service_date" type="date" class="input" required />
          </div>
          <fieldset class="parts-fieldset">
            <legend>Pièces changées</legend>
            <div v-for="(p, idx) in maintForm.parts_changed" :key="idx" class="parts-row">
              <input v-model="p.name" class="input" placeholder="Nom pièce" required />
              <input v-model="p.reference" class="input" placeholder="Réf." />
              <input v-model.number="p.quantity" type="number" min="1" class="input" />
              <button type="button" class="btn btn-ghost btn-sm" @click="maintForm.parts_changed.splice(idx, 1)">×</button>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" @click="addPart">+ Pièce</button>
          </fieldset>
          <button type="submit" class="btn btn-primary" :disabled="submitting">{{ submitting ? 'Certification...' : 'Certifier maintenance' }}</button>
          <p v-if="txMsg" class="tx-msg">{{ txMsg }}</p>
        </form>
      </div>

      <VehicleStatusPanel
        v-if="tab === 'settings' && auth.canManageFleet"
        :vehicle="vehicle"
        @updated="onVehicleUpdated"
      />

      <VehicleSalePanel v-if="tab === 'sale' && auth.canInitiateSale" :vehicle="vehicle" @initiated="onSaleInitiated" />
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api';
import VehicleSalePanel from '../components/VehicleSalePanel.vue';
import VehicleDocumentsPanel from '../components/VehicleDocumentsPanel.vue';
import VehicleFuelPanel from '../components/VehicleFuelPanel.vue';
import VehicleAssignmentPanel from '../components/VehicleAssignmentPanel.vue';
import VehicleStatusPanel from '../components/VehicleStatusPanel.vue';
import { certifyAfterApi } from '../services/contract';
import { useAuthStore } from '../stores/auth';

const props = defineProps({ id: { type: [String, Number], required: true } });
const route = useRoute();
const auth = useAuthStore();
const router = useRouter();
const vehicle = ref(null);
const timeline = ref([]);
const maintenances = ref([]);
const loading = ref(true);
const submitting = ref(false);
const txMsg = ref('');
const tab = ref(route.query.tab || 'timeline');
const maintForm = ref({
  intervention_type: '', mileage_at_service: 0, cost: null,
  service_date: new Date().toISOString().slice(0, 10), parts_changed: [],
});

const statusLabels = {
  available: 'Disponible', in_mission: 'En mission',
  in_maintenance: 'Maintenance', out_of_service: 'En panne', sold: 'Vendu',
};
function statusLabel(s) { return statusLabels[s] || s; }
function formatDate(d) { return new Date(d).toLocaleString('fr-FR'); }
function addPart() { maintForm.value.parts_changed.push({ name: '', reference: '', quantity: 1 }); }

async function load() {
  loading.value = true;
  const [vRes, tRes, mRes] = await Promise.all([
    api.get(`/vehicles/${props.id}`),
    api.get(`/vehicles/${props.id}/timeline`),
    api.get('/maintenances', { params: { vehicle_id: props.id, per_page: 20 } }),
  ]);
  vehicle.value = vRes.data.vehicle;
  timeline.value = tRes.data.timeline || [];
  maintenances.value = mRes.data.data || mRes.data;
  maintForm.value.mileage_at_service = vehicle.value.current_mileage;
  loading.value = false;
}

function onVehicleUpdated(v) { vehicle.value = v; }

async function submitMaintenance() {
  submitting.value = true;
  txMsg.value = '';
  try {
    const { data } = await api.post('/maintenances', {
      vehicle_id: props.id,
      ...maintForm.value,
      parts_changed: maintForm.value.parts_changed.length ? maintForm.value.parts_changed : null,
      certify_on_chain: true,
    });
    const partsHash = data.parts_hash ? `0x${data.parts_hash}` : `0x${'0'.repeat(64)}`;
    const tx = await certifyAfterApi(data, vehicle.value.uuid, {
      type: 'maintenance', interventionType: maintForm.value.intervention_type,
      mileage: maintForm.value.mileage_at_service, partsHash,
    });
    txMsg.value = tx ? `Tx confirmée : ${tx.slice(0, 18)}...` : 'Enregistré (mode simulé)';
    await load();
    tab.value = 'timeline';
  } catch (e) {
    txMsg.value = e.message || 'Erreur';
  } finally {
    submitting.value = false;
  }
}

function onSaleInitiated() { router.push({ name: 'sales' }); }

onMounted(load);
</script>

<style scoped>
.header-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-top: 0.5rem; }
.auditor-banner { margin-top: 0.75rem; padding: 0.5rem 1rem; background: rgba(59,130,246,0.1); border-radius: 8px; font-size: 0.875rem; color: var(--primary); }
.tabs { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
.tabs button { padding: 0.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; color: var(--text-muted); cursor: pointer; font-weight: 500; }
.tabs button.active { background: var(--primary); color: white; border-color: var(--primary); }
.maint-history { list-style: none; }
.maint-history li { padding: 0.625rem 0; border-bottom: 1px solid var(--border); }
.maint-history span { display: block; font-size: 0.875rem; color: var(--text-muted); }
.maint-history small { color: var(--success); font-size: 0.75rem; }
.muted { color: var(--text-muted); }
.parts-fieldset { border: 1px solid var(--border); border-radius: 8px; padding: 1rem; margin: 1rem 0; }
.parts-row { display: grid; grid-template-columns: 2fr 1fr 80px auto; gap: 0.5rem; margin-bottom: 0.5rem; }
.tx-msg { margin-top: 0.75rem; font-size: 0.875rem; color: var(--success); }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
</style>
