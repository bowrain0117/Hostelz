<template>
  <div :class="classWrapper">
    <swiper
        :modules="modules"
        :slides-per-view="1.3"
        :space-between="16"
        :centered-slides="true"
        :centered-slides-bounds="true"
        :grab-cursor="true"
        :lazy="true"
        :breakpoints="{ 991: { slidesPerView: 3.6 }, 768: { slidesPerView: 2.6 }, 440: { slidesPerView: 1.8} }"
        :pagination="{ clickable: true }"
    >
      <swiper-slide v-for="(listing, key) in this.listings" class="hostel-card bg-white h-auto listingItem"
                    :data-listing-id="listing.id">
        <div class="hostel-card-img-wrap">
          <div v-if="listing.label"
               class="position-absolute top-0 left-0 pre-title bg-text py-1 px-2 cl-light rounded-sm mt-3 ml-3">
            {{ listing.label }}
          </div>

          <a class="cl-text h-100"
             target="_blank"
             :href="listing.url"
             :title="listing.name"
          >
            <picture>
              <source :srcset="listing.pic.src.thumb_webp" type="image/webp">

              <img
                  :src="listing.pic.src.thumb_def"
                  :alt="listing.pic.title"
                  :title="listing.pic.title"
                  class="hostel-card-img w-100 h-100"
                  property="image"
                  loading="lazy"
              >
            </picture>
          </a>
        </div>

        <div class="hostel-card-body p-3">
          <div class="hostel-card-body-header d-flex justify-content-between align-items-stretch">
            <h5 class="hostel-card-title tx-body font-weight-bold mb-0">
              <a class="cl-text"
                 target="_blank"
                 :href="listing.url"
                 :title="listing.name"
              >
                {{ listing.name }}
              </a>
            </h5>

            <div v-if="listing.rating >= 1"
                 class="hostel-card-rating hostel-card-rating-small flex-shrink-0 ml-1">
              {{ listing.rating }}
            </div>

          </div>

          <div v-if="listing.cityAlt !== ''"
               class="my-1 pre-title"
          >
            <svg-icon :width="24" :height="25" :icon="'map-place'"></svg-icon>

            {{ listing.cityAlt }}
          </div>

          <div class="tx-small my-2">
            <span v-if="listing.snippet">
              {{ truncate(listing.snippet, 100) }} ...
            </span>
            <span v-else>
              {{ listing.address }} ...
            </span>
            <a :href="listing.url"
               :title="listing.name"
               target="_blank"
               class="font-weight-600 text-lowercase"
            >
              {{ this.cityMore }}
            </a>
          </div>

          <div v-if="listing.minPrice" class="my-2">
            <div class="pre-title cl-subtext">from</div>
            <span class="tx-body font-weight-bold cl-text">{{ listing.minPrice }}</span>
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
    cityMore: {
      type: String,
      default: null
    },
    isWhite: {
      type: String,
      default: false
    },
  },
  methods: {
    truncate(str, maxLen, separator = ' ') {
      str = str.replace(/(<([^>]+)>)/gi, '')

      if (str.length <= maxLen) return str
      return str.substr(0, str.lastIndexOf(separator, maxLen))
    }
  },
  computed: {
    classWrapper() {
      return {
        'slider-wrapped': !this.isWhite,
        'slider-wrapped-white': this.isWhite
      }
    }
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

.slider-wrapped .swiper-pagination .swiper-pagination-bullet {
  opacity: 1;
  background: #fff;
}

.slider-wrapped .swiper-pagination .swiper-pagination-bullet-active {
  background: #FF5852;
}

.slider-wrapped-white .swiper-pagination .swiper-pagination-bullet {
  opacity: 0.2;
  background: #000;
}

.slider-wrapped-white .swiper-pagination .swiper-pagination-bullet-active {
  background: #4a5268;
  opacity: 1;
}

.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-prev,
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-next,
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-next-next,
.swiper-pagination-bullets-dynamic .swiper-pagination-bullet-active-prev-prev {
  transform: none;
}

</style>