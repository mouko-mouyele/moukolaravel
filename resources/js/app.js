import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { useWalletStore } from './stores/wallet';
import '../css/app.css';

const pinia = createPinia();
const app = createApp(App).use(pinia).use(router);

app.mount('#app');

const wallet = useWalletStore();
wallet.syncFromMetaMask();
wallet.initListeners();
