import {Swiper} from 'swiper';
import {Navigation, Pagination} from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

import Alpine from 'alpinejs'

window.Swiper = Swiper
window.Navigation = Navigation
window.Pagination = Pagination

window.Alpine = Alpine

Alpine.start()