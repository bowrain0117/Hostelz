<template>
  <swiper
      :modules="modules"
      :slides-per-view="auto"
      :space-between="16"
      :centered-slides="true"
      :centered-slides-bounds="true"
      :grab-cursor="true"
      :lazy="true"
      :pagination="{ clickable: true, dynamicBullets: true }"
  >
    <swiper-slide v-for="(city, name) in this.data">
      <div class="card card-poster dark-overlay hover-animate mb-4 mb-lg-0 position-relative">

        <div class="position-absolute top-0 left-0 z-index-10 p-3">
          <div class="pre-title bg-primary py-1 px-2 cl-light rounded-sm mb-3">
            {{ this.from + ' $' + this.lowestPrice[name] }}
          </div>
        </div>
        <a :href="this.cityUrls[name]" class="tile-link"></a>

        <img v-if="this.thumbnail[name]"
             :src=" this.thumbnail[name]"
             class="swiper-lazy bg-image"
             :alt="name + 'Hostels' + this.from + ' $' + this.lowestPrice[name]">

        <div class="card-body overlay-content text-center">
          <h6 class="card-title text-shadow text-uppercase text-white">{{ name }}</h6>
          <p class="card-text text-sm"></p>
        </div>
      </div>
    </swiper-slide>

    <swiper-arrows :width="44" :height="48"></swiper-arrows>

  </swiper>
</template>

<script>
import {Swiper, SwiperSlide} from "swiper/vue";
import {Navigation, Pagination} from "swiper/modules";

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

export default {
  name: 'Slider',
  components: {
    Swiper,
    SwiperSlide,
  },
  props: {
    data: Object,
    cityUrls: Object,
    thumbnail: Object,
    lowestPrice: Object,
    from: {
      String,
      default: 'from'
    },
  },
  setup() {
    return {
      modules: [Navigation, Pagination],
      auto: 'auto'
    }
  },
}
</script>

<style>
.swiper {
  overflow: unset;
}

.swiper-container {
  overflow: hidden;
}

.swiper-pagination {
  width: 120px;
}

.featured-slider .swiper-slide {
  width: 23%;
}

@media (max-width: 991px) {
  .featured-slider .swiper-slide {
    width: 45%;
  }
}

.swiper-horizontal > .swiper-pagination-bullets .swiper-pagination-bullet,
.swiper-pagination-horizontal.swiper-pagination-bullets .swiper-pagination-bullet {
  margin: 0 8px;
}

.swiper-horizontal > .swiper-pagination-bullets,
.swiper-pagination-bullets.swiper-pagination-horizontal,
.swiper-pagination-custom, .swiper-pagination-fraction {
  bottom: -24px;
}

.swiper-pagination .swiper-pagination-bullet {
  opacity: 1;
  background: #fff;
}

.swiper-pagination .swiper-pagination-bullet-active {
  background: #FF5852;
}

.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-prev,
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-next,
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-next-next,
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-prev-prev {
  transform: none;
}

.swiper-button-prev::after {
  content: '';
}

.swiper-button-next::after {
  content: '';
}
</style>