<template>
  <div v-if="visible" :class="['chain-banner', { warn: !chainOk }]">
    <span v-if="!config.enabled">⛓ Mode simulé — blockchain désactivée</span>
    <span v-else-if="!chainOk">⚠️ MetaMask : réseau {{ wallet.chainId ?? '?' }} — attendu {{ config.chain_id }}</span>
    <span v-else>⛓ Blockchain active · contrat {{ shortContract }}</span>
    <button v-if="!chainOk && config.enabled" class="btn-fix" @click="fixChain">Basculer réseau</button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../api';
import { ensureHardhatNetwork } from '../services/web3';
import { useWalletStore } from '../stores/wallet';

const wallet = useWalletStore();
const config = ref({ enabled: false, chain_id: 31337, contract_address: '' });
const visible = ref(true);

const chainOk = computed(() =>
  !config.value.enabled || !wallet.chainId || wallet.chainId === config.value.chain_id
);

const shortContract = computed(() => {
  const a = config.value.contract_address;
  return a ? `${a.slice(0, 8)}...${a.slice(-4)}` : 'non déployé';
});

async function loadConfig() {
  const { data } = await api.get('/blockchain/config');
  config.value = data;
}

async function fixChain() {
  await ensureHardhatNetwork(config.value.chain_id);
  await wallet.syncFromMetaMask();
}

onMounted(async () => {
  await loadConfig();
  await wallet.syncFromMetaMask();
});
</script>

<style scoped>
.chain-banner {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
  padding: 0.5rem 2rem; font-size: 0.8125rem;
  background: rgba(16, 185, 129, 0.1); color: var(--success);
  border-bottom: 1px solid var(--border);
}
.chain-banner.warn { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
.btn-fix {
  background: var(--warning); color: #000; border: none; padding: 0.25rem 0.625rem;
  border-radius: 6px; font-size: 0.75rem; cursor: pointer; white-space: nowrap;
}
</style>
