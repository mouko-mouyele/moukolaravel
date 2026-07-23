<template>
  <div class="login-page">
    <div class="login-card card">
      <div class="login-header">
        <span class="logo">⛓</span>
        <h1>AutoChain Emma+</h1>
        <p>Gestion de parc automobile blockchain</p>
        <small class="author">Projet — Moïse</small>
      </div>

      <!-- Réinitialisation mot de passe (lien email) -->
      <form v-if="resetMode" @submit.prevent="submitReset">
        <h2 class="form-title">Nouveau mot de passe</h2>
        <div v-if="resetMsg" :class="resetError ? 'alert-error' : 'alert-success'">{{ resetMsg }}</div>
        <div class="form-group">
          <label>Email</label>
          <input v-model="resetEmail" type="email" class="input" required readonly />
        </div>
        <div class="form-group">
          <label>Nouveau mot de passe</label>
          <PasswordInput v-model="newPassword" placeholder="Min. 8 caractères" required autocomplete="new-password" />
        </div>
        <div class="form-group">
          <label>Confirmer</label>
          <PasswordInput v-model="newPasswordConfirm" placeholder="Confirmer" required autocomplete="new-password" />
        </div>
        <button type="submit" class="btn btn-primary btn-full" :disabled="resetBusy">
          {{ resetBusy ? 'Enregistrement...' : 'Réinitialiser' }}
        </button>
        <button type="button" class="btn btn-ghost btn-full" @click="cancelReset">Retour connexion</button>
      </form>

      <!-- Mot de passe oublié -->
      <form v-else-if="showForgot" @submit.prevent="submitForgot">
        <h2 class="form-title">Mot de passe oublié</h2>
        <div v-if="forgotMsg" class="alert-success">{{ forgotMsg }}</div>
        <p class="web3-desc">Entrez votre email. Un lien de réinitialisation sera envoyé (Mailpit en dev).</p>
        <div class="form-group">
          <label>Email</label>
          <input v-model="forgotEmail" type="email" class="input" required placeholder="gestionnaire@autochain.local" />
        </div>
        <button type="submit" class="btn btn-primary btn-full" :disabled="forgotBusy">
          {{ forgotBusy ? 'Envoi...' : 'Envoyer le lien' }}
        </button>
        <button type="button" class="btn btn-ghost btn-full" @click="showForgot = false">Retour connexion</button>
      </form>

      <template v-else>
        <div class="tabs">
          <button :class="{ active: tab === 'email' }" type="button" @click="tab = 'email'">Email</button>
          <button :class="{ active: tab === 'web3' }" type="button" @click="tab = 'web3'">MetaMask</button>
        </div>

        <form v-if="tab === 'email'" @submit.prevent="submitEmail">
          <div v-if="auth.error" class="alert-error">{{ auth.error }}</div>
          <div class="form-group">
            <label>Email</label>
            <input v-model="email" type="email" class="input" required placeholder="chauffeur@autochain.local" />
          </div>
          <div class="form-group">
            <label>Mot de passe</label>
            <PasswordInput v-model="password" placeholder="password" required autocomplete="current-password" />
          </div>
          <button type="submit" class="btn btn-primary btn-full" :disabled="auth.loading">
            {{ auth.loading ? 'Connexion...' : 'Se connecter' }}
          </button>
          <p class="forgot-link">
            <button type="button" class="link-btn" @click="openForgot">Mot de passe oublié ?</button>
          </p>
        </form>

        <div v-if="tab === 'email'" class="demo-accounts">
          <p class="demo-label">Comptes démo — mot de passe : <code>password</code></p>
          <button type="button" class="demo-btn" @click="loginAs('gestionnaire@autochain.local')">
            🚗 Gestionnaire de parc
          </button>
          <button type="button" class="demo-btn" @click="loginAs('garagiste@autochain.local')">
            🔧 Garagiste agréé
          </button>
        </div>

        <div v-else class="web3-panel">
          <div v-if="auth.error" class="alert-error">{{ auth.error }}</div>
          <p class="web3-desc">Connectez-vous avec votre portefeuille MetaMask lié à votre compte.</p>
          <WalletButton mode="login" @logged-in="onWeb3Login" />
          <p class="demo-hint">Compte demo wallet : admin ou auditeur (après seed)</p>
        </div>

        <p class="demo-hint">MetaMask : réservé au compte admin (wallet lié)</p>
      </template>

      <p class="demo-hint"><router-link to="/privacy">Politique de confidentialité (RGPD)</router-link></p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import WalletButton from '../components/WalletButton.vue';
import PasswordInput from '../components/PasswordInput.vue';
import api from '../api';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const tab = ref('email');
const email = ref('gestionnaire@autochain.local');
const password = ref('password');

const showForgot = ref(false);
const forgotEmail = ref('');
const forgotMsg = ref('');
const forgotBusy = ref(false);

const resetMode = ref(false);
const resetToken = ref('');
const resetEmail = ref('');
const newPassword = ref('');
const newPasswordConfirm = ref('');
const resetMsg = ref('');
const resetError = ref(false);
const resetBusy = ref(false);

onMounted(() => {
  const token = route.query.token;
  const mail = route.query.email;
  if (token && mail) {
    resetMode.value = true;
    resetToken.value = String(token);
    resetEmail.value = String(mail);
  }
});

function openForgot() {
  forgotEmail.value = email.value;
  forgotMsg.value = '';
  showForgot.value = true;
}

function cancelReset() {
  resetMode.value = false;
  router.replace({ name: 'login' });
}

async function submitForgot() {
  forgotBusy.value = true;
  forgotMsg.value = '';
  try {
    const { data } = await api.post('/auth/forgot-password', { email: forgotEmail.value });
    forgotMsg.value = data.message;
  } catch (e) {
    forgotMsg.value = e.response?.data?.message || 'Erreur lors de l\'envoi.';
  } finally {
    forgotBusy.value = false;
  }
}

async function submitReset() {
  if (newPassword.value !== newPasswordConfirm.value) {
    resetMsg.value = 'Les mots de passe ne correspondent pas.';
    resetError.value = true;
    return;
  }
  resetBusy.value = true;
  resetMsg.value = '';
  resetError.value = false;
  try {
    const { data } = await api.post('/auth/reset-password', {
      token: resetToken.value,
      email: resetEmail.value,
      password: newPassword.value,
      password_confirmation: newPasswordConfirm.value,
    });
    resetMsg.value = data.message + ' Vous pouvez vous connecter.';
    resetError.value = false;
    setTimeout(() => cancelReset(), 2000);
  } catch (e) {
    resetMsg.value = e.response?.data?.message || 'Lien invalide ou expiré.';
    resetError.value = true;
  } finally {
    resetBusy.value = false;
  }
}

async function loginAs(demoEmail) {
  email.value = demoEmail;
  password.value = 'password';
  await submitEmail();
}

function redirectAfterLogin() {
  if (auth.isDriver) router.push({ name: 'mobile-home' });
  else if (auth.isMechanic) router.push({ name: 'mechanic-dashboard' });
  else router.push({ name: 'dashboard' });
}

async function submitEmail() {
  const ok = await auth.login(email.value, password.value);
  if (ok) redirectAfterLogin();
}

function onWeb3Login() {
  redirectAfterLogin();
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: radial-gradient(ellipse at top, #1e3a5f 0%, var(--bg) 60%);
}
.login-card { width: 100%; max-width: 400px; }
.login-header { text-align: center; margin-bottom: 1.5rem; }
.logo { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
.login-header h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
.login-header p { color: var(--text-muted); font-size: 0.9375rem; }
.author { display: block; margin-top: 0.5rem; color: var(--primary); }
.form-title { font-size: 1.125rem; margin-bottom: 1rem; text-align: center; }
.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}
.tabs button {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid var(--border);
  background: var(--bg);
  color: var(--text-muted);
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}
.tabs button.active {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}
.web3-panel { margin-bottom: 0.5rem; }
.web3-desc {
  font-size: 0.875rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
  text-align: center;
}
.btn-full { width: 100%; justify-content: center; margin-top: 0.5rem; }
.forgot-link { text-align: center; margin-top: 0.75rem; }
.link-btn {
  background: none; border: none; color: var(--primary);
  cursor: pointer; font-size: 0.8125rem; text-decoration: underline;
}
.alert-success {
  background: rgba(34, 197, 94, 0.15);
  color: var(--success, #16a34a);
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}
.demo-hint { margin-top: 1.25rem; text-align: center; font-size: 0.8125rem; color: var(--text-muted); }
.demo-accounts { margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border); }
.demo-label { font-size: 0.8125rem; color: var(--text-muted); text-align: center; margin-bottom: 0.75rem; }
.demo-btn {
  width: 100%; padding: 0.75rem; margin-bottom: 0.5rem;
  background: var(--bg); border: 1px solid var(--border); border-radius: 8px;
  cursor: pointer; font-weight: 500; text-align: left;
}
.demo-btn:hover { border-color: var(--primary); color: var(--primary); }
code { background: var(--bg); padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.75rem; }
</style>
