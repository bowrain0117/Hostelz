<template>
  <swiper
      :modules="modules"
      :slides-per-view="1.5"
      :space-between="15"
      :auto-height="true"
      :centered-slides="true"
      :loop="loop"
      :breakpoints="{ 991: { slidesPerView: 3.5 } }"
      class="p-1"
  >
    <swiper-slide v-for="comment in this.data">
      <div class="card rounded-lg shadow-1 border-0 py-4 px-3 position-relative" style="margin-top: 10px;">
        <div class="text">
          <div class="testimonial-quote position-absolute">
            <i class="fas fa-quote-right"></i>
          </div>
          <p class="testimonial-text commentsSliderItemText"
             v-if="comment.comment"
             v-html="search.length > 0 ? addHighlights(comment.comment, search) : comment.comment">
          </p>
          <p class="testimonial-text commentsSliderItemText" v-else>{{ comment }}</p>
          <small class="font-montserat font-weight-600" v-if="comment.name">
            {{ comment.name }}
          </small>
        </div>
      </div>
    </swiper-slide>

    <swiper-arrows :width="44" :height="48"></swiper-arrows>

  </swiper>

</template>

<script>
import {Swiper, SwiperSlide} from 'swiper/vue'
import {Navigation} from 'swiper/modules'

import 'swiper/css';
import 'swiper/css/navigation';

export default {
  name: 'Slider',
  components: {
    Swiper,
    SwiperSlide,
  },
  props: {
    data: {},
    search: String,
  },
  setup() {
    return {
      modules: [Navigation]
    }
  },
  methods: {
    addHighlights(str, search) {
      const searchIndex = str.toLowerCase().indexOf(search.toLowerCase())
      const searchStringLength = search.length
      if (searchIndex >= 0) {
        return str.substring(0, searchIndex)
            + `<mark>`
            + str.substring(searchIndex, searchIndex + searchStringLength)
            + `</mark>`
            + str.substring(searchIndex + searchStringLength)
      }
      return str
    }
  },
  computed: {
    loop() {
      return this.data.length > 1
    }
  }
}
</script>

<style scoped>
.swiper-slide-active .card {
  background-color: #ECEFF4 !important;
}

.commentsSliderItemText,
.commentsSliderItemName {
  line-height: normal;
  user-select: none;
}
</style>