<template>
  <div class="swiper-wrapper">
    <swiper
        :modules="modules"
        :slides-per-view="1"
        :space-between="0"
        :centered-slides="true"
        :pagination="{ clickable: true, dynamicBullets: true }"
        @slideChange="onSlideChange"
    >
      <swiper-slide v-for="(pic, key) in this.listingPics">
        <a
            :href="this.listingUrl"
            target="_blank"
            class="text-decoration-none cl-text"
            :title="this.listingName"
        >

          <div v-if="key === 5" class="last-slide-text">More Photos</div>

          <picture>
            <source :srcset="pic.src.thumb_webp" type="image/webp">

            <img
                :src="pic.src.tiny"
                :data-src="pic.src.thumb_def"
                class="hostel-tile__img lazyload blur-up"
                :alt="this.listingName"
                style="object-fit: cover;"
                loading="lazy"
            >
          </picture>
        </a>
      </swiper-slide>

      <swiper-arrows v-if="this.listingPics.length !== 1"
                     :width="44" :height="48"
                     :showPrev="showPrev" :showNext="showNext"
      ></swiper-arrows>

    </swiper>
  </div>
</template>

<script>
import {Swiper, SwiperSlide} from "swiper/vue";
import {Navigation, Pagination} from "swiper/modules";

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

import {ref} from "vue";

export default {
  name: 'Slider',
  components: {
    Swiper,
    SwiperSlide,
    Pagination
  },
  props: ['listingName', 'listingUrl', 'listingPics'],
  setup() {
    let showPrev = ref(false)
    let showNext = ref(true)

    const onSlideChange = (swiper) => {
      showPrev.value = true;
      showNext.value = true;

      if (swiper.activeIndex === (swiper.imagesLoaded - 1)) {
        showPrev.value = true;
        showNext.value = false;
      }

      if (swiper.activeIndex === 0) {
        showPrev.value = false;
        showNext.value = true;
      }
    }
    return {
      onSlideChange,
      modules: [Navigation, Pagination],
      showPrev,
      showNext
    }
  },
}
</script>

<style>
.swiper-wrapper .swiper {
  border-radius: 16px;
}

.swiper-wrapper .last-slide-text {
  position: absolute;
  z-index: 9999;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  border-radius: 16px;
  background: #000;
  color: #fff;
  opacity: 0.7;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.swiper-wrapper .swiper-pagination {
  width: 120px;
  position: absolute;
  text-align: center;
  transform: translateZ(0);
  transition: opacity .3s;
  z-index: 10;
}

.swiper-wrapper .swiper-horizontal > .swiper-pagination-bullets .swiper-pagination-bullet,
.swiper-pagination-horizontal .swiper-pagination-bullets .swiper-pagination-bullet {
  margin: 0 8px;
}

.swiper-wrapper .swiper-pagination .swiper-pagination-bullet {
  opacity: 1;
  background: #fff;
}

.swiper-wrapper .swiper-pagination .swiper-pagination-bullet-active {
  background: #FF5852;
}

.swiper-wrapper .swiper-horizontal > .swiper-pagination-bullets.swiper-pagination-bullets-dynamic {
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
}

.swiper-wrapper .swiper-horizontal > .swiper-pagination-bullets, .swiper-pagination-custom, .swiper-pagination-fraction {
  bottom: 10px;
}

.swiper-wrapper .swiper-horizontal > .swiper-pagination-bullets.swiper-pagination-bullets-dynamic .swiper-pagination-bullet {
  transition: transform .2s, left .2s;
}

.listing .swiper-button-prev,
.listing .swiper-button-next {
  display: none;
}

.listing:hover .swiper-button-prev:not(.hide-arrow),
.listing:hover .swiper-button-next:not(.hide-arrow) {
  display: flex;
}

.listing .hide-arrow {
  display: none;
}

@media screen and (max-width: 576px) {
  .listing:hover .swiper-button-prev:not(.hide-arrow),
  .listing:hover .swiper-button-next:not(.hide-arrow) {
    display: none;
  }

  .listing .swiper-button-prev,
  .listing .swiper-button-next {
    display: none;
  }
}
</style>