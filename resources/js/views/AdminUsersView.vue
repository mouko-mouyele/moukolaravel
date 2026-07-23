<template>
  <div>
    <header class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <h1>Utilisateurs</h1>
        <p>Gestion des comptes AutoChain</p>
      </div>
      <button class="btn btn-primary" @click="showForm = !showForm">{{ showForm ? 'Annuler' : '+ Nouveau' }}</button>
    </header>

    <div v-if="showForm" class="card" style="margin-bottom:1.5rem">
      <h2 style="margin-bottom:1rem">{{ editing ? 'Modifier' : 'Créer' }} un utilisateur</h2>
      <form @submit.prevent="save" class="form-grid">
        <div class="form-group"><label>Nom</label><input v-model="form.name" class="input" required /></div>
        <div class="form-group"><label>Email</label><input v-model="form.email" type="email" class="input" required /></div>
        <div class="form-group"><label>Mot de passe</label><PasswordInput v-model="form.password" :required="!editing" autocomplete="new-password" /></div>
        <div class="form-group">
          <label>Rôle</label>
          <select v-model="form.role" class="input">
            <option value="fleet_manager">Gestionnaire</option>
            <option value="driver">Chauffeur</option>
            <option value="mechanic">Garagiste</option>
            <option value="auditor">Auditeur</option>
            <option value="super_admin">Super Admin</option>
          </select>
        </div>
        <div class="form-group"><label>Wallet (optionnel)</label><input v-model="form.wallet_address" class="input" placeholder="0x..." /></div>
        <div class="form-group full"><button type="submit" class="btn btn-primary">{{ saving ? '...' : 'Enregistrer' }}</button></div>
      </form>
    </div>

    <div class="card table-wrap">
      <table>
        <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Wallet</th><th></th></tr></thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>{{ u.name }}</td>
            <td>{{ u.email }}</td>
            <td><span class="badge">{{ u.role }}</span></td>
            <td><code v-if="u.wallet_address">{{ u.wallet_address.slice(0,10) }}...</code><span v-else>—</span></td>
            <td>
              <button class="btn btn-ghost btn-sm" @click="edit(u)">Modifier</button>
              <button class="btn btn-ghost btn-sm" @click="deactivate(u.id)">Désactiver</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';
import PasswordInput from '../components/PasswordInput.vue';

const users = ref([]);
const showForm = ref(false);
const editing = ref(null);
const saving = ref(false);
const form = ref({ name: '', email: '', password: '', role: 'driver', wallet_address: '' });

async function load() {
  const { data } = await api.get('/users', { params: { per_page: 100 } });
  users.value = data.data || data;
}

function edit(u) {
  editing.value = u.id;
  form.value = { name: u.name, email: u.email, password: '', role: u.role, wallet_address: u.wallet_address || '' };
  showForm.value = true;
}

async function save() {
  saving.value = true;
  const payload = { ...form.value };
  if (!payload.password) delete payload.password;
  if (editing.value) {
    await api.put(`/users/${editing.value}`, payload);
  } else {
    await api.post('/users', payload);
  }
  editing.value = null;
  form.value = { name: '', email: '', password: '', role: 'driver', wallet_address: '' };
  showForm.value = false;
  saving.value = false;
  await load();
}

async function deactivate(id) {
  if (!confirm('Désactiver cet utilisateur ?')) return;
  await api.delete(`/users/${id}`);
  await load();
}

onMounted(load);
</script>

<style scoped>
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.form-grid .full { grid-column: 1 / -1; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; margin-left: 0.25rem; }
</style>
