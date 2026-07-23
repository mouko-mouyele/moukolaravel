import { defineStore } from 'pinia';

import api from '../api';



export const useAuthStore = defineStore('auth', {

    state: () => ({

        user: JSON.parse(localStorage.getItem('autochain_user') || 'null'),

        token: localStorage.getItem('autochain_token'),

        loading: false,

        error: null,

    }),



    getters: {

        isAuthenticated: (s) => !!s.token,

        role: (s) => s.user?.role,

        isAdmin: (s) => s.user?.role === 'super_admin',

        isFleetManager: (s) => ['super_admin', 'fleet_manager'].includes(s.user?.role),

        isDriver: (s) => s.user?.role === 'driver',

        isMechanic: (s) => s.user?.role === 'mechanic',

        isAuditor: (s) => s.user?.role === 'auditor',

        isReadOnly: (s) => s.user?.role === 'auditor',

        canWrite: (s) => s.user?.role !== 'auditor',

        canManageFleet: (s) => ['super_admin', 'fleet_manager'].includes(s.user?.role),

        canResolveAlerts: (s) => ['super_admin', 'fleet_manager'].includes(s.user?.role),

        canViewBlockchain: (s) => ['super_admin', 'fleet_manager', 'auditor'].includes(s.user?.role),

        canInitiateSale: (s) => ['super_admin', 'fleet_manager'].includes(s.user?.role),

    },



    actions: {

        async login(email, password) {

            this.loading = true;

            this.error = null;

            try {

                const { data } = await api.post('/auth/login', { email, password });

                this.token = data.token;

                this.user = data.user;

                localStorage.setItem('autochain_token', data.token);

                localStorage.setItem('autochain_user', JSON.stringify(data.user));

                return true;

            } catch (e) {

                this.error = e.response?.data?.message || 'Erreur de connexion';

                return false;

            } finally {

                this.loading = false;

            }

        },



        async logout() {

            try { await api.post('/auth/logout'); } catch (_) {}

            this.token = null;

            this.user = null;

            localStorage.removeItem('autochain_token');

            localStorage.removeItem('autochain_user');

        },



        async fetchMe() {

            const { data } = await api.get('/auth/me');

            this.user = data.user;

            localStorage.setItem('autochain_user', JSON.stringify(data.user));

        },

    },

});

