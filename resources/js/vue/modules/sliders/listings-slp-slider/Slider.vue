<template>
  <div class="slider-wrapped">
    <swiper
        :modules="modules"
        :slides-per-view="2"
        :space-between="16"
        :centered-slides="false"
        :centeredSlidesBounds="true"
        :grabCursor="true"
        :watchOverflow="true"
        :lazy="true"
        :breakpoints="{ 991: { slidesPerView: 2 }, 768: { slidesPerView: 2 }, 440: { slidesPerView: 1} }"
        :pagination="{ clickable: true, dynamicBullets: true }"
    >
      <swiper-slide v-for="(listing, key) in this.listings" class="h-auto px-2" :data-listing-id="listing.id">
        <div class="w-100 h-100">
          <div class="card h-100 border-0 shadow">
            <div class="card-img-top slp-img-wrap">
              <picture>
                <source :srcset="listing.pic.src.thumb_webp" type="image/webp">

                <img
                    :src="listing.pic.src.tiny"
                    :data-src="listing.pic.src.thumb_def"
                    :alt="listing.pic.title"
                    :title="listing.pic.title"
                    data-pic-group="slp-slider"
                    class="img-fluid w-100 slp-img  lazyload blur-up"
                    property="image"
                    loading="lazy"
                >
              </picture>

              <a :href="listing.url" class="tile-link"></a>

              <div class="card-img-overlay-top text-right">
                <span v-html="listing.wishlistTemplate"></span>

                <comparison-icon :listing-id="listing.id"></comparison-icon>
              </div>
            </div>

            <div class="card-body d-flex align-items-center">
              <div class="w-100">
                <h6 class="card-title">
                  <a :href="listing.url" class="text-decoration-none text-dark">{{ listing.name }}</a>
                </h6>
                <div class="d-flex card-subtitle mb-3">
                  <p class="flex-grow-1 mb-0 text-sm">{{ listing.cityAlt }}</p>
                  <div v-if="listing.rating >= 1" class="card-rating">
                    <span>{{ listing.rating }}</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </swiper-slide>

      <swiper-arrows :width="44" :height="48"></swiper-arrows>

    </swiper>
  </div>

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
    listings: {
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

.slp-img-wrap {
  height: 150px;
}

.slp-img {
  height: 100%;
  object-fit: cover;
}

@media (min-width: 768px) {
  .slp-img-wrap {
    height: 230px;
  }
}

</style>