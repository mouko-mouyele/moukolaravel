<template>
  <div class="sale-panel card">
    <h2>⛓ Vente certifiée blockchain</h2>
    <p class="desc">Double signature MetaMask : administrateur + acheteur</p>

    <div v-if="vehicle.status === 'sold'" class="sold-banner">Ce véhicule a été vendu.</div>

    <form v-else @submit.prevent="submit">
      <div class="form-group">
        <label>Wallet acheteur (MetaMask)</label>
        <input v-model="buyerWallet" class="input" placeholder="0x..." maxlength="42" required />
        <button type="button" class="btn-link" @click="useConnectedWallet">Utiliser wallet connecté</button>
      </div>
      <div class="form-group">
        <label>Prix de vente (EUR)</label>
        <input v-model.number="salePrice" type="number" class="input" min="0" step="0.01" required />
      </div>

      <div v-if="!auth.user?.wallet_address" class="warn-box">
        Liez d'abord votre wallet MetaMask pour signer en tant qu'administrateur.
        <WalletButton mode="link" @linked="auth.fetchMe()" />
      </div>

      <button
        v-else
        type="submit"
        class="btn btn-wallet"
        :disabled="loading"
      >
        🦊 {{ loading ? 'Signature en cours...' : 'Signer & initier la vente' }}
      </button>

      <p v-if="error" class="alert-error">{{ error }}</p>
      <p v-if="success" class="success-msg">{{ success }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import WalletButton from './WalletButton.vue';
import { initiateSaleWithMetaMask } from '../services/sale';
import { getConnectedAddress } from '../services/web3';
import { useAuthStore } from '../stores/auth';

const props = defineProps({
  vehicle: { type: Object, required: true },
});

const emit = defineEmits(['initiated']);

const auth = useAuthStore();
const buyerWallet = ref('0x70997970c51812dc3a010c7d01b50b0d17ef88c8');
const salePrice = ref(12000);
const loading = ref(false);
const error = ref('');
const success = ref('');

async function useConnectedWallet() {
  const addr = await getConnectedAddress();
  if (addr) buyerWallet.value = addr;
}

async function submit() {
  loading.value = true;
  error.value = '';
  success.value = '';
  try {
    const result = await initiateSaleWithMetaMask(
      props.vehicle.id,
      buyerWallet.value,
      salePrice.value
    );
    success.value = result.message + (result.on_chain_tx ? ` Tx: ${result.on_chain_tx.slice(0, 14)}...` : '');
    emit('initiated', result.pending_signature);
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.sale-panel h2 { font-size: 1.125rem; margin-bottom: 0.25rem; }
.desc { color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.25rem; }
.sold-banner {
  padding: 1rem;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid var(--danger);
  border-radius: 8px;
  text-align: center;
}
.btn-link {
  background: none;
  border: none;
  color: var(--primary);
  font-size: 0.8125rem;
  cursor: pointer;
  margin-top: 0.375rem;
  padding: 0;
}
.warn-box {
  padding: 1rem;
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid var(--warning);
  border-radius: 8px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}
.btn-wallet {
  width: 100%;
  display: flex;
  justify-content: center;
  padding: 0.875rem;
  background: linear-gradient(135deg, #f6851b, #e2761b);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}
.btn-wallet:disabled { opacity: 0.6; }
.success-msg {
  margin-top: 1rem;
  padding: 0.75rem;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid var(--success);
  border-radius: 8px;
  color: var(--success);
  font-size: 0.875rem;
}
</style>
