<template>
  <div class="privacy-page">
    <div class="card content">
      <router-link to="/login" class="back">← Retour connexion</router-link>
      <h1>Politique de confidentialité</h1>
      <p class="subtitle">{{ data?.project }} — Moïse · AutoChain Emma+</p>

      <div v-if="loading" class="loading">Chargement...</div>
      <template v-else-if="data">
        <section v-for="(text, key) in data.policy" :key="key">
          <h2>{{ labels[key] || key }}</h2>
          <p>{{ text }}</p>
        </section>

        <section v-if="auth.isAuthenticated">
          <h2>Vos droits</h2>
          <button class="btn btn-secondary" :disabled="busy" @click="exportData">Exporter mes données (JSON)</button>
          <button class="btn btn-ghost" style="margin-left:0.5rem" :disabled="busy" @click="deleteAccount">Supprimer mon compte</button>
          <p v-if="msg" class="msg">{{ msg }}</p>
        </section>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const data = ref(null);
const loading = ref(true);
const busy = ref(false);
const msg = ref('');

const labels = {
  collecte: 'Données collectées',
  blockchain: 'Blockchain',
  documents: 'Documents',
  droits: 'Vos droits',
  conservation: 'Conservation',
  contact: 'Contact',
};

onMounted(async () => {
  const { data: res } = await api.get('/rgpd/privacy');
  data.value = res;
  loading.value = false;
});

async function exportData() {
  busy.value = true;
  const { data: res } = await api.get('/rgpd/export');
  const blob = new Blob([JSON.stringify(res, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'mes-donnees-autochain.json';
  a.click();
  busy.value = false;
  msg.value = 'Export téléchargé.';
}

async function deleteAccount() {
  if (!confirm('Supprimer définitivement votre compte ?')) return;
  busy.value = true;
  await api.delete('/rgpd/account');
  await auth.logout();
  router.push({ name: 'login' });
}
</script>

<style scoped>
.privacy-page { min-height: 100vh; padding: 2rem; background: var(--bg); }
.content { max-width: 720px; margin: 0 auto; padding: 2rem; }
.back { font-size: 0.875rem; color: var(--text-muted); }
h1 { margin: 1rem 0 0.25rem; }
.subtitle { color: var(--text-muted); margin-bottom: 2rem; }
section { margin-bottom: 1.5rem; }
h2 { font-size: 1rem; margin-bottom: 0.5rem; color: var(--primary); }
p { color: var(--text-muted); line-height: 1.6; font-size: 0.9375rem; }
.msg { margin-top: 1rem; color: var(--success); font-size: 0.875rem; }
</style>
