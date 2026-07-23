<template>
  <div>
    <header class="page-header">
      <h1>Administration blockchain</h1>
      <p>Configuration smart contract, IPFS et archivage</p>
    </header>

    <div v-if="loading" class="loading">Chargement...</div>
    <template v-else-if="stats">
      <div class="stats-grid" style="margin-bottom:1.5rem">
        <div class="stat-card"><div class="value">{{ stats.active_vehicles }}</div><div class="label">Véhicules actifs</div></div>
        <div class="stat-card"><div class="value">{{ stats.archived_vehicles }}</div><div class="label">Archivés</div></div>
        <div class="stat-card"><div class="value">{{ stats.blockchain.enabled ? 'ON' : 'OFF' }}</div><div class="label">Blockchain</div></div>
        <div class="stat-card"><div class="value">{{ stats.ipfs.enabled ? 'ON' : 'OFF' }}</div><div class="label">IPFS</div></div>
      </div>

      <div class="card" style="margin-bottom:1rem">
        <h2 style="margin-bottom:1rem">Configuration blockchain (.env)</h2>
        <form @submit.prevent="saveConfig" class="form-grid">
          <label class="check full"><input type="checkbox" v-model="config.enabled" /> Blockchain activée</label>
          <div class="form-group"><label>Adresse contrat</label><input v-model="config.contract_address" class="input" placeholder="0x..." /></div>
          <div class="form-group"><label>Chain ID</label><input v-model.number="config.chain_id" type="number" class="input" /></div>
          <div class="form-group full"><label>RPC URL</label><input v-model="config.rpc_url" class="input" /></div>
          <div class="form-group full"><button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? '...' : 'Sauvegarder' }}</button></div>
        </form>
        <p v-if="msg" class="msg">{{ msg }}</p>
      </div>

      <div class="card">
        <h2 style="margin-bottom:0.75rem">Moteur d'alertes</h2>
        <p style="color:var(--text-muted);margin-bottom:1rem;font-size:0.875rem">Exécution manuelle (planifiée à 06h00)</p>
        <button class="btn btn-secondary" :disabled="runningAlerts" @click="runAlerts">
          {{ runningAlerts ? 'Exécution...' : 'Lancer autochain:generate-alerts' }}
        </button>
        <pre v-if="alertOutput" class="output">{{ alertOutput }}</pre>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';

const stats = ref(null);
const loading = ref(true);
const saving = ref(false);
const runningAlerts = ref(false);
const msg = ref('');
const alertOutput = ref('');
const config = ref({ enabled: false, contract_address: '', chain_id: 31337, rpc_url: '' });

async function load() {
  const { data } = await api.get('/admin/stats');
  stats.value = data;
  config.value = { ...data.blockchain, enabled: data.blockchain.enabled };
  loading.value = false;
}

async function saveConfig() {
  saving.value = true;
  msg.value = '';
  try {
    const { data } = await api.put('/admin/blockchain', config.value);
    msg.value = data.message;
    await load();
  } catch (e) {
    msg.value = e.response?.data?.message || 'Erreur';
  } finally {
    saving.value = false;
  }
}

async function runAlerts() {
  runningAlerts.value = true;
  alertOutput.value = '';
  const { data } = await api.post('/admin/alerts/run');
  alertOutput.value = data.output || data.message;
  runningAlerts.value = false;
}

onMounted(load);
</script>

<style scoped>
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.form-grid .full { grid-column: 1 / -1; }
.check { display: flex; gap: 0.5rem; align-items: center; }
.msg { margin-top: 1rem; color: var(--success); font-size: 0.875rem; }
.output { margin-top: 1rem; padding: 1rem; background: var(--bg); border-radius: 8px; font-size: 0.75rem; overflow-x: auto; }
</style>
