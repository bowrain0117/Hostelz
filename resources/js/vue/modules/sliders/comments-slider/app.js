import { createApp } from 'vue';

//components
import Slider from './Slider.vue'
import Comments from './Comments.vue'
import SwiperArrows from '../../../components/slider/SwiperArrows.vue'
import Icon from '../../../components/Icon.vue'

createApp({})
    .component('slider', Slider)
    .component('comments', Comments)
    .component('svg-icon', Icon)
    .component('swiper-arrows', SwiperArrows)
    .mount('.vue-comments-slider');