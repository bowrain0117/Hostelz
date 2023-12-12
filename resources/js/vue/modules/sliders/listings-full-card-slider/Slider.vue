<template>
  <swiper
      :modules="modules"
      :slides-per-view="1"
      :space-between="0"
      :centered-slides="true"
      :centeredSlidesBounds="true"
      :grabCursor="true"
      :watchOverflow="true"
      :lazy="true"
      :pagination="false"
  >

    <swiper-slide v-for="(pic, key) in this.pics" class="h-100" :key="pic.id">
      <div class="w-100 full-card-wrap">

        <picture>
          <source :srcset="pic.src.web_big" type="image/webp">

          <img
              :src="pic.src.tiny"
              :data-src="pic.src.big"
              :alt="pic.title"
              :title="pic.title"
              data-pic-group="slp-slider"
              class="img-fluid w-100 full-card-img lazyload blur-up"
              property="image"
              loading="lazy"
          >
        </picture>

        <a :href="listing.url" class="tile-link" target="_blank" :title="listing.name"></a>

        <div class="card-img-overlay-bottom z-index-20 text-white">
          <p class="h5 card-text text-white">{{ listing.name }}</p>
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
    pics: {
      type: Object,
      default: Array
    },
    listing: {
      type: Object,
      default: Array
    },
  },
  setup() {
    return {
      modules: [Navigation, Pagination],
    }
  },
}
</script>

<style>
.slider-wrapped .swiper-wrapper,
.slider-wrapped-white .swiper-wrapper {
  padding-bottom: 32px;
}

.swiper-horizontal > .swiper-pagination-bullets .swiper-pagination-bullet,
.swiper-pagination-horizontal.swiper-pagination-bullets .swiper-pagination-bullet {
  margin: 0 8px;
}

.slider-wrapped-white .swiper-pagination .swiper-pagination-bullet {
  opacity: 1;
  background: #fff;
}

.slider-wrapped-white .swiper-pagination .swiper-pagination-bullet-active {
  background: #FF5852;
}

.slider-wrapped .swiper-pagination .swiper-pagination-bullet {
  opacity: 0.2;
  background: #000;
}

.slider-wrapped .swiper-pagination .swiper-pagination-bullet-active {
  background: #4a5268;
  opacity: 1;
}

.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-prev,
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-next,
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-next-next,
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-prev-prev {
  transform: none;
}

.full-card-wrap {
  height: 300px;
}

.full-card-img {
  height: 100%;
  object-fit: cover;
}

@media (min-width: 768px) {
  .full-card-wrap {
    height: 410px;
  }
}

</style>