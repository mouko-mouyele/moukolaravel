<template>
  <div class="mobile-layout">
    <header class="mobile-header">
      <div>
        <strong>AutoChain</strong>
        <small>{{ auth.user?.name }}</small>
      </div>
      <button class="btn-logout" @click="logout">Sortir</button>
    </header>
    <main class="mobile-main"><router-view /></main>
    <nav class="mobile-nav">
      <router-link to="/mobile" class="nav-item"><span>🏠</span><small>Accueil</small></router-link>
      <router-link to="/mobile/mission" class="nav-item"><span>🚗</span><small>Mission</small></router-link>
      <router-link to="/mobile/kilometrage" class="nav-item"><span>📊</span><small>Km</small></router-link>
    </nav>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

async function logout() {
  await auth.logout();
  router.push({ name: 'login' });
}
</script>

<style scoped>
.mobile-layout { min-height: 100vh; min-height: 100dvh; display: flex; flex-direction: column; background: var(--bg); }
.mobile-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; background: var(--surface); border-bottom: 1px solid var(--border); }
.mobile-header strong { display: block; font-size: 1rem; }
.mobile-header small { color: var(--text-muted); font-size: 0.75rem; }
.btn-logout { background: transparent; border: 1px solid var(--border); color: var(--text-muted); padding: 0.375rem 0.75rem; border-radius: 8px; font-size: 0.8125rem; cursor: pointer; }
.mobile-main { flex: 1; overflow-y: auto; padding: 1.25rem; padding-bottom: 5rem; }
.mobile-nav { position: fixed; bottom: 0; left: 0; right: 0; display: flex; background: var(--surface); border-top: 1px solid var(--border); z-index: 100; }
.nav-item { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 0.625rem; color: var(--text-muted); text-decoration: none; font-size: 0.6875rem; }
.nav-item span { font-size: 1.375rem; margin-bottom: 0.125rem; }
.nav-item.router-link-active { color: var(--primary); }
</style>
