import { createApp } from 'vue';

//components
import Comparison from './Comparison.vue'
import HostelCards from './HostelCards.vue'
import HostelCardsDefault from './HostelCardsDefault.vue'
import HostelCard from './HostelCard.vue'
import HostelCardDefault from './HostelCardDefault.vue'
import HostelFeatures from './HostelFeatures.vue'
import Icon from '../../../components/Icon.vue'
import { store } from '../../../store'

createApp({})
    .use(store)
    .component('comparison', Comparison)
    .component('hostel-cards', HostelCards)
    .component('hostel-cards-default', HostelCardsDefault)
    .component('hostel-card', HostelCard)
    .component('hostel-card-default', HostelCardDefault)
    .component('hostel-features', HostelFeatures)
    .component('svg-icon', Icon)
    .mount('#vue-comparison');