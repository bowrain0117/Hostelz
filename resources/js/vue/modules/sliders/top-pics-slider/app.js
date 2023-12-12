import { createApp } from 'vue'

//components
import Slider from './Slider.vue'
import SwiperArrows from '../../../components/slider/SwiperArrows.vue'
import ComparisonIcon from "../../comparison/comparison-icon/ComparisonIcon";
import Icon from '../../../components/Icon.vue'

createApp({})
    .component('slider', Slider)
    .component('svg-icon', Icon)
    .component('comparison-icon', ComparisonIcon)
    .component('swiper-arrows', SwiperArrows)
    .mount('#vue-top-pics-slider')