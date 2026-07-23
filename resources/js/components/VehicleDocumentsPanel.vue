<template>
  <div class="panel card">
    <h3>Documents administratifs</h3>
    <p class="hint">Stockage privé + IPFS pour certificats CT publics</p>

    <form v-if="!readOnly" @submit.prevent="upload" class="upload-form">
      <div class="form-group">
        <label>Type</label>
        <select v-model="form.type" class="input">
          <option value="registration_card">Carte grise</option>
          <option value="insurance">Assurance</option>
          <option value="invoice">Facture</option>
          <option value="technical_inspection">Contrôle technique</option>
          <option value="other">Autre</option>
        </select>
      </div>
      <div class="form-group">
        <label>Titre</label>
        <input v-model="form.title" class="input" required />
      </div>
      <div class="form-group">
        <label>Fichier</label>
        <input type="file" class="input" @change="onFile" accept=".pdf,.jpg,.jpeg,.png" required />
      </div>
      <label class="check"><input type="checkbox" v-model="form.is_public" /> Publier sur IPFS (certificat public)</label>
      <button type="submit" class="btn btn-primary" :disabled="uploading">{{ uploading ? 'Upload...' : 'Enregistrer' }}</button>
    </form>

    <ul class="doc-list">
      <li v-for="d in documents" :key="d.id">
        <div>
          <strong>{{ d.title }}</strong>
          <small>{{ d.type }} · hash {{ d.file_hash?.slice(0, 10) }}...</small>
          <a v-if="d.ipfs_url" :href="d.ipfs_url" target="_blank" class="ipfs">IPFS ↗</a>
        </div>
        <div class="actions">
          <button class="btn btn-ghost btn-sm" @click="downloadDoc(d.id)">Télécharger</button>
          <button v-if="!readOnly" class="btn btn-ghost btn-sm" @click="remove(d.id)">Suppr.</button>
        </div>
      </li>
    </ul>
    <p v-if="!documents.length" class="empty">Aucun document</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';

const props = defineProps({ vehicleId: Number, readOnly: Boolean });
const documents = ref([]);
const uploading = ref(false);
const file = ref(null);
const form = ref({ type: 'insurance', title: '', is_public: false });

async function load() {
  const { data } = await api.get(`/vehicles/${props.vehicleId}/documents`);
  documents.value = data.documents || [];
}

function onFile(e) { file.value = e.target.files[0]; }

async function upload() {
  uploading.value = true;
  const fd = new FormData();
  fd.append('type', form.value.type);
  fd.append('title', form.value.title);
  fd.append('is_public', form.value.is_public ? '1' : '0');
  fd.append('file', file.value);
  await api.post(`/vehicles/${props.vehicleId}/documents`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
  form.value = { type: 'insurance', title: '', is_public: false };
  file.value = null;
  uploading.value = false;
  await load();
}

async function remove(id) {
  await api.delete(`/documents/${id}`);
  await load();
}

async function downloadDoc(id) {
  const { data } = await api.get(`/documents/${id}/download`, { responseType: 'blob' });
  const url = URL.createObjectURL(data);
  const a = document.createElement('a');
  a.href = url;
  a.download = `document-${id}`;
  a.click();
  URL.revokeObjectURL(url);
}

onMounted(load);
</script>

<style scoped>
.panel h3 { margin-bottom: 0.25rem; }
.hint { color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem; }
.upload-form { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
.check { display: flex; gap: 0.5rem; align-items: center; font-size: 0.875rem; margin-bottom: 1rem; }
.doc-list { list-style: none; }
.doc-list li { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border); }
.doc-list small { display: block; color: var(--text-muted); font-size: 0.75rem; }
.ipfs { font-size: 0.75rem; color: var(--success); margin-left: 0.5rem; }
.empty { color: var(--text-muted); text-align: center; padding: 1rem; }
.actions { display: flex; gap: 0.375rem; }
</style>
