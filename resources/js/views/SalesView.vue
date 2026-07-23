<template>
  <div>
    <header class="page-header">
      <h1>Ventes blockchain</h1>
      <p>Double signature MetaMask — Admin + Acheteur</p>
    </header>

    <div class="filters card">
      <button :class="['tab', { active: statusFilter === 'pending' }]" @click="switchStatus('pending')">En attente</button>
      <button :class="['tab', { active: statusFilter === 'completed' }]" @click="switchStatus('completed')">Complétées</button>
    </div>

    <div v-if="loading" class="loading">Chargement...</div>
    <div v-else-if="!sales.length" class="card empty">
      <span>⛓</span>
      <p>Aucune vente {{ statusFilter === 'pending' ? 'en attente' : 'complétée' }}</p>
    </div>

    <div v-else class="sales-list">
      <div v-for="sale in sales" :key="sale.id" class="card sale-card">
        <div class="sale-header">
          <div>
            <router-link v-if="sale.vehicle" :to="{ name: 'vehicle-detail', params: { id: sale.vehicle.id } }">
              <strong>{{ sale.vehicle.license_plate }}</strong>
            </router-link>
            <span>{{ sale.vehicle?.brand }} {{ sale.vehicle?.model }}</span>
          </div>
          <span class="price">{{ formatPrice(sale.payload?.sale_price) }}</span>
        </div>
        <div class="sale-meta">
          <div><small>Acheteur</small><code>{{ shorten(sale.buyer_wallet) }}</code></div>
          <div><small>Admin</small><span>{{ sale.initiated_by?.name ?? '—' }}</span></div>
          <div><small>Statut</small><span :class="['status', sale.status]">{{ sale.status }}</span></div>
        </div>
        <div class="signatures">
          <div :class="['sig', { ok: sale.admin_signature }]">Admin {{ sale.admin_signature ? '✓' : '○' }}</div>
          <div :class="['sig', { ok: sale.buyer_signature }]">Acheteur {{ sale.buyer_signature ? '✓' : '○' }}</div>
        </div>
        <button
          v-if="sale.status === 'pending' && canSign(sale)"
          class="btn btn-wallet"
          :disabled="signingId === sale.id"
          @click="signSale(sale)"
        >
          🦊 {{ signingId === sale.id ? 'Signature...' : 'Signer avec MetaMask (Acheteur)' }}
        </button>
        <p v-if="sale.status === 'pending' && !canSign(sale)" class="hint">
          Connectez le wallet acheteur <code>{{ shorten(sale.buyer_wallet) }}</code> via MetaMask.
        </p>
        <p v-if="messages[sale.id]" :class="messageOk[sale.id] ? 'success-msg' : 'alert-error'">{{ messages[sale.id] }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { fetchPendingSales, signSaleWithMetaMask } from '../services/sale';
import { getConnectedAddress, shortenAddress } from '../services/web3';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const sales = ref([]);
const loading = ref(true);
const statusFilter = ref('pending');
const signingId = ref(null);
const messages = ref({});
const messageOk = ref({});
const connectedWallet = ref(null);

function formatPrice(n) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(n || 0);
}
function shorten(addr) { return shortenAddress(addr); }

function canSign(sale) {
  if (!connectedWallet.value) return false;
  return connectedWallet.value === sale.buyer_wallet?.toLowerCase();
}

async function load() {
  loading.value = true;
  connectedWallet.value = await getConnectedAddress();
  sales.value = await fetchPendingSales({ status: statusFilter.value });
  loading.value = false;
}

function switchStatus(s) { statusFilter.value = s; load(); }

async function signSale(sale) {
  signingId.value = sale.id;
  messages.value[sale.id] = '';
  try {
    const result = await signSaleWithMetaMask(sale.id);
    messageOk.value[sale.id] = true;
    messages.value[sale.id] = result.message + (result.on_chain_tx ? ` Tx: ${result.on_chain_tx.slice(0, 14)}...` : '');
    await load();
  } catch (e) {
    messageOk.value[sale.id] = false;
    messages.value[sale.id] = e.response?.data?.message || e.message;
  } finally {
    signingId.value = null;
  }
}

onMounted(load);
</script>

<style scoped>
.filters { display: flex; gap: 0.5rem; margin-bottom: 1rem; padding: 0.75rem; }
.tab { padding: 0.5rem 1rem; border: 1px solid var(--border); background: var(--surface); border-radius: 8px; cursor: pointer; color: var(--text-muted); }
.tab.active { background: var(--primary); color: white; border-color: var(--primary); }
.empty { text-align: center; padding: 2.5rem; }
.empty span { font-size: 2.5rem; display: block; margin-bottom: 0.5rem; }
.sales-list { display: flex; flex-direction: column; gap: 1rem; }
.sale-card { padding: 1.25rem; }
.sale-header { display: flex; justify-content: space-between; margin-bottom: 1rem; }
.sale-header strong { display: block; font-size: 1.125rem; }
.sale-header span { color: var(--text-muted); font-size: 0.875rem; }
.price { font-size: 1.25rem; font-weight: 700; color: var(--primary); }
.sale-meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-bottom: 1rem; font-size: 0.8125rem; }
.sale-meta small { display: block; color: var(--text-muted); margin-bottom: 0.125rem; }
.signatures { display: flex; gap: 0.75rem; margin-bottom: 1rem; }
.sig { flex: 1; text-align: center; padding: 0.5rem; border-radius: 8px; background: var(--bg); border: 1px solid var(--border); font-size: 0.8125rem; }
.sig.ok { border-color: var(--success); color: var(--success); }
.btn-wallet { width: 100%; padding: 0.875rem; background: linear-gradient(135deg, #f6851b, #e2761b); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
.hint { font-size: 0.8125rem; color: var(--text-muted); margin-top: 0.75rem; }
.success-msg { margin-top: 0.75rem; padding: 0.75rem; background: rgba(16,185,129,0.15); border: 1px solid var(--success); border-radius: 8px; color: var(--success); font-size: 0.875rem; }
.status.pending { color: var(--warning); }
.status.completed { color: var(--success); }
</style>
