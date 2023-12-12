<template>

  <div v-for="(city, cityName) in topCitiesListings">
    <h3>Best Hostels in
      <a
          class="top-cities-link"
          :href="city['url']"
          :title="cityTitle(cityName)"
      >
        {{ cityName }}
      </a>
    </h3>

    <div v-if="city['slps']" class="my-2">
      <h6>We help you pick your hostel like no one else. Check our detailed guides to:</h6>
      <ul>
        <li v-for="(item) in city['slps']" :key="item.slug_id"><a :id="item.slug_id" class="text-primary" :href="item.path" target="_blank">{{ item.title }}</a></li>
      </ul>
    </div>

    <slider
        :listings="city['listings']"
        :city-more="cityMore"
    ></slider>
  </div>

</template>

<script>
export default {
  name: 'SliderWrapper',
  props: ['topCitiesListings', 'cityMore'],
  methods: {
    cityTitle(cityName) {
      return 'Hostels in ' + cityName
    }
  },
}
</script>

<style scoped>
.top-cities-link {
  color: #FF5852;
}
</style>