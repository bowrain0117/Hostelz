<template>
  <div class="slider-wrapped">
    <swiper
        :modules="modules"
        :slides-per-view="1.3"
        :space-between="16"
        :centered-slides="true"
        :centeredSlidesBounds="true"
        :grabCursor="true"
        :watchOverflow="true"
        :activeIndex="1"
        :breakpoints="{ 768: { slidesPerView: 4.3 }, 480: { slidesPerView: 3.3 }, 320: { slidesPerView: 2.3 } }"
        :pagination="{ clickable: true, dynamicBullets: true }"
    >
      <swiper-slide v-for="(rate, key) in this.averageScore">
        <RadialProgress
            :diameter="160"
            :strokeWidth="12"
            :completed-steps="rate"
            :total-steps="100"
            :startColor="rateColor(rate)"
            :stopColor="rateColor(rate)"
            innerStrokeColor="#ffffff"
        >
          <div class="hostel-rate-body">
            <div class="cl-black tx-small">{{ Number(rate).toLocaleString() / 10 }}</div>
            <div class="cl-black">{{ this.importedRatings[key] }}</div>
          </div>
        </RadialProgress>
      </swiper-slide>

      <swiper-arrows :width="44" :height="48"></swiper-arrows>

    </swiper>
  </div>

</template>

<script>
import {Swiper, SwiperSlide} from "swiper/vue";
import {Navigation, Pagination} from "swiper/modules";
import RadialProgress from "vue3-radial-progress";

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

export default {
  name: 'Slider',
  components: {
    Swiper,
    SwiperSlide,
    RadialProgress
  },
  props: {
    averageScore: {
      type: Object,
      default: Array
    },
    importedRatings: {
      type: Object,
      default: Array
    },
  },
  setup() {
    return {
      modules: [Navigation, Pagination],
    }
  },
  methods: {
    rateColor(rate) {
      if (rate < 60) {
        return '#D03131';
      } else if (rate < 80) {
        return '#FDAF2A';
      } else {
        return '#31D0AA';
      }
    }
  },
  mounted() {
    delete this.averageScore['count']
  }
}
</script>

<style scoped>
.slider-wrapped .swiper-wrapper {
  padding-bottom: 32px;
}

.swiper-horizontal > .swiper-pagination-bullets .swiper-pagination-bullet,
.swiper-pagination-horizontal.swiper-pagination-bullets .swiper-pagination-bullet {
  margin: 0 8px;
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

.hostel-rate-body {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
}
</style>