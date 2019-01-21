import Vue from 'vue';
import VueRouter from 'vue-router';

import AddonRelations from './components/relations/AddonRelations.vue';
import AddonsList from './components/AddonsList.vue';
import ConfigurableOptionRelations from './components/relations/ConfigurableOptionRelations.vue';
import LicensesList from './components/LicensesList.vue';
import ProductRelations from './components/relations/ProductRelations.vue';

Vue.use(VueRouter);

const routes = [
    { path: '/', component: LicensesList },
    { path: '/addons-list', component: AddonsList },
    { path: '/addon-relations', component: AddonRelations },
    { path: '/product-relations', component: ProductRelations },
    { path: '/option-relations', component: ConfigurableOptionRelations },
];

export const router = new VueRouter({
    routes,
    mode: 'hash',
});
