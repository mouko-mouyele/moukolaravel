<template>
  <div class="panel card">
    <h3>Gestion du véhicule</h3>
    <form @submit.prevent="save">
      <div class="form-group">
        <label>Statut flotte</label>
        <select v-model="form.status" class="input">
          <option value="available">Disponible</option>
          <option value="in_mission">En mission</option>
          <option value="in_maintenance">En maintenance</option>
          <option value="out_of_service">En panne</option>
          <option value="sold">Vendu</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Assurance expire</label>
          <input v-model="form.insurance_expiry" type="date" class="input" />
        </div>
        <div class="form-group">
          <label>Contrôle technique</label>
          <input v-model="form.technical_inspection_expiry" type="date" class="input" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Prochaine vidange (km)</label>
          <input v-model.number="form.next_oil_change_km" type="number" class="input" />
        </div>
        <div class="form-group">
          <label>Prochain entretien (km)</label>
          <input v-model.number="form.next_maintenance_km" type="number" class="input" />
        </div>
      </div>
      <div class="actions">
        <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? '...' : 'Enregistrer' }}</button>
        <button type="button" class="btn btn-ghost" :disabled="saving" @click="archive">Archiver</button>
      </div>
      <p v-if="msg" :class="msgOk ? 'ok' : 'err'">{{ msg }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';

const props = defineProps({ vehicle: { type: Object, required: true } });
const emit = defineEmits(['updated']);
const router = useRouter();

const saving = ref(false);
const msg = ref('');
const msgOk = ref(true);
const form = ref(buildForm(props.vehicle));

function buildForm(v) {
  return {
    status: v.status,
    insurance_expiry: v.insurance_expiry?.slice(0, 10) || '',
    technical_inspection_expiry: v.technical_inspection_expiry?.slice(0, 10) || '',
    next_oil_change_km: v.next_oil_change_km || null,
    next_maintenance_km: v.next_maintenance_km || null,
  };
}

watch(() => props.vehicle, (v) => { form.value = buildForm(v); }, { deep: true });

async function save() {
  saving.value = true;
  msg.value = '';
  try {
    const payload = { ...form.value };
    if (!payload.insurance_expiry) payload.insurance_expiry = null;
    if (!payload.technical_inspection_expiry) payload.technical_inspection_expiry = null;
    const { data } = await api.put(`/vehicles/${props.vehicle.id}`, payload);
    msgOk.value = true;
    msg.value = 'Véhicule mis à jour.';
    emit('updated', data.vehicle);
  } catch (e) {
    msgOk.value = false;
    msg.value = e.response?.data?.message || 'Erreur';
  } finally {
    saving.value = false;
  }
}

async function archive() {
  if (!confirm('Archiver ce véhicule ?')) return;
  await api.delete(`/vehicles/${props.vehicle.id}`);
  router.push({ name: 'vehicles' });
}
</script>

<style scoped>
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.actions { display: flex; gap: 0.75rem; margin-top: 0.5rem; }
.ok { color: var(--success); font-size: 0.875rem; margin-top: 0.75rem; }
.err { color: var(--danger); font-size: 0.875rem; margin-top: 0.75rem; }
</style>
