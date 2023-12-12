import { createApp } from 'vue';

//components
import Tabs from './Tabs.vue'

createApp({})
    .component('cities-tab', Tabs)
    .mount('.vue-cities-tab');