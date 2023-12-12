import {createApp} from 'vue';

//components
import Slider from './Slider.vue'
import SwiperArrows from '../../../components/slider/SwiperArrows.vue'

createApp({})
    .component('slider', Slider)
    .component('swiper-arrows', SwiperArrows)
    .mount('#vue-featured-slider');