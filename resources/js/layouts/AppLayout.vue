<template>
  <div class="layout">
    <ChainStatusBanner />
    <div class="body">
      <aside class="sidebar">
        <div class="brand">
          <span class="brand-icon">⛓</span>
          <div>
            <strong>AutoChain Emma+</strong>
            <small>Moïse — Parc auto blockchain</small>
          </div>
        </div>
        <nav>
          <router-link v-if="!auth.isMechanic" to="/" class="nav-link">Tableau de bord</router-link>
          <router-link v-if="auth.isMechanic" to="/workshop" class="nav-link">🔧 Atelier</router-link>
          <router-link to="/vehicles" class="nav-link">Véhicules</router-link>
          <router-link v-if="!auth.isMechanic" to="/alerts" class="nav-link">Alertes</router-link>
          <router-link v-if="auth.canInitiateSale || auth.isAuditor" to="/sales" class="nav-link">⛓ Ventes</router-link>
          <router-link v-if="auth.canViewBlockchain" to="/blockchain" class="nav-link">Registre ⛓</router-link>
        <router-link v-if="auth.canManageFleet || auth.isAuditor" to="/reports" class="nav-link">📄 Rapports</router-link>
          <router-link v-if="auth.isAdmin" to="/admin/users" class="nav-link">👤 Utilisateurs</router-link>
          <router-link v-if="auth.isAdmin" to="/admin/blockchain" class="nav-link">⚙️ Blockchain</router-link>
        </nav>
        <div class="sidebar-footer">
          <WalletButton v-if="!auth.user?.wallet_address" mode="link" @linked="auth.fetchMe()" />
          <div v-else class="wallet-linked-info">🦊 {{ shortenWallet(auth.user.wallet_address) }}</div>
          <div v-if="auth.isReadOnly" class="role-banner">Mode auditeur</div>
          <div class="user-info">
            <span class="user-name">{{ auth.user?.name }}</span>
            <span class="user-role">{{ roleLabel }}</span>
          </div>
          <button class="btn btn-ghost btn-sm" @click="logout">Déconnexion</button>
          <router-link to="/privacy" class="privacy-link">Confidentialité (RGPD)</router-link>
        </div>
      </aside>
      <main class="main"><router-view /></main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import ChainStatusBanner from '../components/ChainStatusBanner.vue';
import WalletButton from '../components/WalletButton.vue';
import { shortenAddress } from '../services/web3';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const roleLabels = {
  super_admin: 'Super Admin', fleet_manager: 'Gestionnaire de parc',
  driver: 'Chauffeur', mechanic: 'Garagiste', auditor: 'Auditeur',
};
const roleLabel = computed(() => roleLabels[auth.user?.role] || auth.user?.role);
function shortenWallet(addr) { return shortenAddress(addr); }
async function logout() { await auth.logout(); router.push({ name: 'login' }); }
</script>

<style scoped>
.layout { display: flex; flex-direction: column; min-height: 100vh; }
.body { display: flex; flex: 1; }
.sidebar {
  width: 260px; background: var(--surface); border-right: 1px solid var(--border);
  display: flex; flex-direction: column; padding: 1.5rem 0;
}
.brand { display: flex; align-items: center; gap: 0.75rem; padding: 0 1.25rem 1.5rem; border-bottom: 1px solid var(--border); margin-bottom: 1rem; }
.brand-icon { font-size: 1.75rem; }
.brand strong { display: block; font-size: 1rem; }
.brand small { color: var(--text-muted); font-size: 0.75rem; }
nav { flex: 1; padding: 0 0.75rem; }
.nav-link { display: block; padding: 0.75rem 1rem; border-radius: 8px; color: var(--text-muted); text-decoration: none; margin-bottom: 0.25rem; font-weight: 500; }
.nav-link:hover { background: var(--surface-hover); color: var(--text); }
.nav-link.router-link-active { background: rgba(59, 130, 246, 0.15); color: var(--primary); }
.wallet-linked-info { font-size: 0.75rem; font-family: monospace; padding: 0.5rem; background: var(--bg); border-radius: 6px; text-align: center; margin-bottom: 0.75rem; }
.role-banner { font-size: 0.75rem; text-align: center; padding: 0.375rem; background: rgba(59,130,246,0.1); color: var(--primary); border-radius: 6px; margin-bottom: 0.5rem; }
.sidebar-footer { padding: 1rem 1.25rem; border-top: 1px solid var(--border); }
.user-name { display: block; font-weight: 600; font-size: 0.9375rem; }
.user-role { font-size: 0.75rem; color: var(--text-muted); }
.btn-sm { padding: 0.5rem; width: 100%; margin-top: 0.75rem; font-size: 0.8125rem; }
.privacy-link { display: block; margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted); text-align: center; text-decoration: none; }
.privacy-link:hover { color: var(--primary); }
.main { flex: 1; padding: 2rem; overflow-y: auto; }
</style>
