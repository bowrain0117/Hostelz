<template>
  <swiper
      :modules="modules"
      :slides-per-view="3"
      :space-between="0"
      :centered-slides="true"
      :loop="true"
      :pagination="{ clickable: true, dynamicBullets: true }"
      :breakpoints="{ 480: { slidesPerView: 3 }, 0: { slidesPerView: 1 } }"
  >
    <swiper-slide v-for="(pic, key) in this.picsList" class="fancyboxItem">
      <picture>
        <source :data-srcset="pic.srcs.webp" type="image/webp">

        <img
            :src="pic.srcs.tiny"
            :data-src="pic.srcs.jpg"
            :alt="pic.title"
            :title="pic.title"
            :data-pic-group="this.picGroup"
            :data-fullsize-pic="pic.srcs.big"
            class="lazyload blur-up"
            property="image"
        >
      </picture>
    </swiper-slide>

    <swiper-arrows :width="44" :height="48"></swiper-arrows>

  </swiper>
</template>

<script>
import {Swiper, SwiperSlide} from "swiper/vue";
import {Navigation, Pagination} from "swiper/modules";

export default {
  name: 'Slider',
  components: {
    Swiper,
    SwiperSlide,
    Pagination
  },
  props: ['picsList', 'picGroup'],
  setup() {
    return {
      modules: [Navigation, Pagination],
    }
  },
}
</script>

<style>
.swiper {
  overflow: hidden;
}

.swiper-pagination {
  width: 120px;
  position: absolute;
  text-align: center;
  transform: translateZ(0);
  transition: opacity .3s;
  z-index: 10;
}

.swiper-horizontal > .swiper-pagination-bullets .swiper-pagination-bullet,
.swiper-pagination-horizontal .swiper-pagination-bullets .swiper-pagination-bullet {
  margin: 0 8px;
}

.swiper-pagination .swiper-pagination-bullet {
  opacity: 1;
  background: #fff;
}

.swiper-pagination .swiper-pagination-bullet-active {
  background: #FF5852;
}

.swiper-horizontal > .swiper-pagination-bullets.swiper-pagination-bullets-dynamic {
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
}

.swiper-horizontal > .swiper-pagination-bullets, .swiper-pagination-custom, .swiper-pagination-fraction {
  bottom: 10px;
}

.swiper-horizontal > .swiper-pagination-bullets.swiper-pagination-bullets-dynamic .swiper-pagination-bullet {
  transition: transform .2s, left .2s;
}
</style>