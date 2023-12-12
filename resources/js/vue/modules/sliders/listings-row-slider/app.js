import { createApp } from 'vue';

//components
import Slider from './Slider.vue'
import SliderWrapper from './SliderWrapper.vue'
import SwiperArrows from '../../../components/slider/SwiperArrows.vue'
import Icon from '../../../components/Icon.vue'

createApp({})
    .component('slider', Slider)
    .component('slider-wrapper', SliderWrapper)
    .component('svg-icon', Icon)
    .component('swiper-arrows', SwiperArrows)
    .mount('.vue-listings-row-slider');