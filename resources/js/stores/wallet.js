import { defineStore } from 'pinia';
import { BrowserProvider } from 'ethers';
import api from '../api';
import { connectMetaMask, getConnectedAddress, shortenAddress, signMessage, setupMetaMaskListeners, hasMetaMask } from '../services/web3';
import { useAuthStore } from './auth';

export const useWalletStore = defineStore('wallet', {
    state: () => ({
        address: localStorage.getItem('autochain_wallet') || null,
        chainId: null,
        connecting: false,
        linking: false,
        error: null,
    }),

    getters: {
        isConnected: (s) => !!s.address,
        shortAddress: (s) => shortenAddress(s.address),
        isLinked: () => {
            const auth = useAuthStore();
            return !!auth.user?.wallet_address;
        },
    },

    actions: {
        async connect() {
            this.connecting = true;
            this.error = null;
            try {
                const { address, chainId } = await connectMetaMask();
                this.address = address;
                this.chainId = chainId;
                localStorage.setItem('autochain_wallet', address);
                return address;
            } catch (e) {
                this.error = e.message || 'Connexion MetaMask échouée';
                throw e;
            } finally {
                this.connecting = false;
            }
        },

        async syncFromMetaMask() {
            const address = await getConnectedAddress();
            if (address) {
                this.address = address;
                localStorage.setItem('autochain_wallet', address);
            }
            if (hasMetaMask()) {
                const provider = new BrowserProvider(window.ethereum);
                const network = await provider.getNetwork();
                this.chainId = Number(network.chainId);
            }
        },

        initListeners() {
            setupMetaMaskListeners(
                (accounts) => {
                    if (accounts[0]) {
                        this.address = accounts[0].toLowerCase();
                        localStorage.setItem('autochain_wallet', this.address);
                    } else {
                        this.disconnect();
                    }
                },
                () => { this.syncFromMetaMask(); }
            );
        },

        async loginWithMetaMask() {
            const auth = useAuthStore();
            auth.loading = true;
            auth.error = null;

            try {
                const address = await this.connect();
                const { data: challenge } = await api.post('/auth/wallet/challenge', {
                    wallet_address: address,
                });

                const { signer } = await connectMetaMask();
                const signature = await signMessage(signer, challenge.message);

                const { data } = await api.post('/auth/wallet/login', {
                    wallet_address: address,
                    message: challenge.message,
                    signature,
                });

                auth.token = data.token;
                auth.user = data.user;
                localStorage.setItem('autochain_token', data.token);
                localStorage.setItem('autochain_user', JSON.stringify(data.user));

                return true;
            } catch (e) {
                auth.error = e.response?.data?.message || e.message || 'Connexion Web3 échouée';
                return false;
            } finally {
                auth.loading = false;
            }
        },

        async linkToAccount() {
            const auth = useAuthStore();
            if (!auth.isAuthenticated) {
                throw new Error('Connectez-vous d\'abord par email');
            }

            this.linking = true;
            this.error = null;

            try {
                const address = await this.connect();
                const { data: nonceData } = await api.get('/auth/wallet/nonce', {
                    params: { wallet_address: address },
                });

                const { signer } = await connectMetaMask();
                const signature = await signMessage(signer, nonceData.message);

                const { data } = await api.post('/auth/wallet/link', {
                    wallet_address: address,
                    message: nonceData.message,
                    signature,
                });

                auth.user = data.user;
                localStorage.setItem('autochain_user', JSON.stringify(data.user));

                return data.user;
            } catch (e) {
                this.error = e.response?.data?.message || e.message;
                throw e;
            } finally {
                this.linking = false;
            }
        },

        disconnect() {
            this.address = null;
            this.chainId = null;
            localStorage.removeItem('autochain_wallet');
        },
    },
});
