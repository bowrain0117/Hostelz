import { createApp } from 'vue';

//components
import Slider from './Slider.vue'
import SwiperArrows from '../../../components/slider/SwiperArrows.vue'
import Icon from '../../../components/Icon.vue'
import ComparisonIcon from '../../../modules/comparison/comparison-icon/ComparisonIcon.vue'

createApp({})
    .component('slider', Slider)
    .component('svg-icon', Icon)
    .component('swiper-arrows', SwiperArrows)
    .component('comparison-icon', ComparisonIcon)
    .mount('.vue-listings-slp-slider');