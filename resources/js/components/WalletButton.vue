<template>
  <div class="wallet-widget">
    <button v-if="!wallet.isConnected" type="button" class="btn btn-wallet" :disabled="wallet.connecting || wallet.linking" @click="handleConnect">
      <span class="fox">🦊</span>{{ wallet.connecting ? 'Connexion...' : 'MetaMask' }}
    </button>
    <div v-else class="wallet-linked">
      <span class="wallet-dot"></span>
      <span class="wallet-addr">{{ wallet.shortAddress }}</span>
      <button v-if="auth.isAuthenticated && !auth.user?.wallet_address" type="button" class="btn-link" :disabled="wallet.linking" @click="linkWallet">
        {{ wallet.linking ? '...' : 'Lier' }}
      </button>
      <button type="button" class="btn-disconnect" @click="wallet.disconnect()">×</button>
    </div>
    <p v-if="wallet.error" class="wallet-error">{{ wallet.error }}</p>
  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
import { useWalletStore } from '../stores/wallet';

const props = defineProps({ mode: { type: String, default: 'connect' } });
const emit = defineEmits(['connected', 'linked', 'logged-in']);
const auth = useAuthStore();
const wallet = useWalletStore();

async function handleConnect() {
  if (props.mode === 'login') {
    const ok = await wallet.loginWithMetaMask();
    if (ok) emit('logged-in');
    return;
  }
  await wallet.connect();
  emit('connected');
}

async function linkWallet() {
  await wallet.linkToAccount();
  emit('linked');
}
</script>

<style scoped>
.wallet-widget { width: 100%; }
.btn-wallet { width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem; background: linear-gradient(135deg, #f6851b, #e2761b); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
.wallet-linked { display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; font-size: 0.875rem; }
.wallet-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--success); }
.wallet-addr { font-family: monospace; flex: 1; }
.btn-link { background: var(--primary); color: white; border: none; padding: 0.25rem 0.625rem; border-radius: 6px; font-size: 0.75rem; cursor: pointer; }
.btn-disconnect { background: transparent; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem; }
.wallet-error { margin-top: 0.5rem; font-size: 0.8125rem; color: var(--danger); }
</style>
