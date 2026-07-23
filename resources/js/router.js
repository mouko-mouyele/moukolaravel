import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const routes = [
    { path: '/login', name: 'login', component: () => import('./views/LoginView.vue'), meta: { guest: true } },
    { path: '/privacy', name: 'privacy', component: () => import('./views/PrivacyView.vue'), meta: { guest: true } },
    {
        path: '/',
        component: () => import('./layouts/AppLayout.vue'),
        meta: { requiresAuth: true, desktop: true },
        children: [
            { path: '', name: 'dashboard', component: () => import('./views/DashboardView.vue') },
            { path: 'vehicles', name: 'vehicles', component: () => import('./views/VehiclesView.vue') },
            { path: 'vehicles/:id', name: 'vehicle-detail', component: () => import('./views/VehicleDetailView.vue'), props: true },
            { path: 'alerts', name: 'alerts', component: () => import('./views/AlertsView.vue') },
            { path: 'sales', name: 'sales', component: () => import('./views/SalesView.vue') },
            { path: 'reports', name: 'reports', component: () => import('./views/ReportsView.vue'), meta: { reports: true } },
            { path: 'blockchain', name: 'blockchain-records', component: () => import('./views/BlockchainRecordsView.vue') },
            { path: 'workshop', name: 'mechanic-dashboard', component: () => import('./views/MechanicDashboardView.vue'), meta: { mechanic: true } },
            { path: 'admin/users', name: 'admin-users', component: () => import('./views/AdminUsersView.vue'), meta: { admin: true } },
            { path: 'admin/blockchain', name: 'admin-blockchain', component: () => import('./views/AdminBlockchainView.vue'), meta: { admin: true } },
        ],
    },
    {
        path: '/mobile',
        component: () => import('./layouts/MobileDriverLayout.vue'),
        meta: { requiresAuth: true, driver: true },
        children: [
            { path: '', name: 'mobile-home', component: () => import('./views/mobile/DriverHomeView.vue') },
            { path: 'mission', name: 'mobile-mission', component: () => import('./views/mobile/DriverMissionView.vue') },
            { path: 'kilometrage', name: 'mobile-mileage', component: () => import('./views/mobile/DriverMileageView.vue') },
        ],
    },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('./views/NotFoundView.vue') },
];

const router = createRouter({ history: createWebHistory(), routes });

function homeRoute(auth) {
    if (auth.isDriver) return { name: 'mobile-home' };
    if (auth.isMechanic) return { name: 'mechanic-dashboard' };
    return { name: 'dashboard' };
}

router.beforeEach((to, _from, next) => {
    const auth = useAuthStore();
    if (to.meta.requiresAuth && !auth.isAuthenticated) return next({ name: 'login' });
    if (to.meta.guest && auth.isAuthenticated && to.name !== 'privacy') return next(homeRoute(auth));
    if (auth.isAuthenticated && auth.isDriver && to.meta.desktop && !to.path.startsWith('/mobile')) return next({ name: 'mobile-home' });
    if (to.meta.driver && !auth.isDriver) return next(homeRoute(auth));
    if (to.meta.mechanic && !auth.isMechanic) return next({ name: 'dashboard' });
    if (to.meta.admin && !auth.isAdmin) return next({ name: 'dashboard' });
    if (to.meta.reports && !auth.canManageFleet && !auth.isAuditor) return next({ name: 'dashboard' });
    next();
});

export default router;
