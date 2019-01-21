import Vue from 'vue';
import VueMaterial from 'vue-material';
import Notifications from 'vue-notification';

import 'vue-material/dist/vue-material.min.css';
import 'vue-material/dist/theme/default.css';

import { router } from './routes';
import App from 'components/App.vue';

Vue.config.productionTip = false;
Vue.use(VueMaterial);
Vue.use(Notifications);

new Vue({
    el: '#cl-app',
    router,
    template: '<App/>',
    components: { App },
});
