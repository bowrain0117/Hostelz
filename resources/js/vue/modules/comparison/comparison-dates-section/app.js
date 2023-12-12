import { createApp } from 'vue';

//components
import Comparison from './Comparison.vue'
import Icon from '../../../components/Icon.vue'
import { store } from '../../../store'

createApp({})
    .use(store)
    .component('comparison-dates', Comparison)
    .component('svg-icon', Icon)
    .mount('#vue-comparison-dates');