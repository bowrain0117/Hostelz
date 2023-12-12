<template>
  <div class="slider-wrapper">
    <swiper
        :slides-per-view="'auto'"
        :grab-cursor="true"
    >
      <swiper-slide v-for="feature in this.features">
        <span class="flex-shrink-0" v-if="feature['displayType'] === 'labelValuePair'">
          <img :src="this.src" alt="#" class="mr-2" style="height:15px">
          <span class="pre-title cl-subtext listing-feature">
            {{ feature['label'] }}
            <span class="font-weight-bolder" v-if="!!feature['value']">
              ({{ feature['value'] }})
            </span>
          </span>
        </span>

        <span class="pre-title cl-subtext listing-feature" v-else>
          {{ feature['label'] }}
          <span class="font-weight-bolder" v-if="!!feature['value']">
            ({{ feature['value'] }})
          </span>
        </span>
      </swiper-slide>
    </swiper>
  </div>
</template>

<script>
import {Swiper, SwiperSlide} from "swiper/vue"

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
    features: Object,
    listing: Object,
    src: String,
  },
}
</script>

<style scoped>
.slider-wrapper .swiper-wrapper {
  display: flex;
  align-items: center;
}

.slider-wrapper .swiper-slide {
  width: auto;
}

.slider-wrapper .swiper-slide + .swiper-slide:before {
  color: #787d8d;
  content: "·";
  display: inline-block;
  padding: 0 0.5rem;
}
</style>