<template>
  <div class="shadow-1 py-4 py-lg-5 px-3 px-sm-4 px-lg-6 rounded">
    <div class="accommodationType d-flex justify-content-center radio-group mt-sm-n2 mx-sm-n3 mb-4 mb-sm-5">
      <input
          type="radio"
          value="all"
          class="d-none"
          v-model="activeValue"
          :id="idAll"
      >
      <label
          :for="idAll"
          class="bg-primary-light py-2 px-sm-4 font-weight-600 btn-outline-primary rounded-left display-4"
          @click="showAll"
      >
        {{ this.all }}
      </label>

      <input
          type="radio"
          value="hostelsOnly"
          class="d-none"
          v-model="activeValue"
          :id="idHostel"
      >
      <label
          :for="idHostel"
          class="bg-primary-light py-2 px-sm-4 font-weight-600 btn-outline-primary rounded-right display-4"
          @click="showHostels"
      >
        {{ this.hostels }}
      </label>
    </div>
    <div class="citiesList column mx-lg-n3 mb-n2 position-relative">
      <a v-for="(cityInfo) in this.getData"
         :href="cityInfo.url"
         :title="title(cityInfo)"
         class="d-block"
         :data-city-id="cityInfo.id"
      >
        <div class="d-flex align-items-center justify-content-between pb-5 text-gray-900 citiesList_item w-100">
          <h6 class="font-weight-600 mb-0 mr-1">{{ this.continentsPage ? cityInfo.country : cityInfo.city }}</h6>
          <span class="itemBadge text-primary bg-primary-light px-3 rounded ml-auto font-weight-600">
            {{ this.isHostelsOnly ? cityInfo.hostelCount : cityInfo.totalListingCount }}
          </span>
        </div>
      </a>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Tabs',
  data() {
    return {
      activeValue: 'all',
      continents: this.continentsPage,
    }
  },
  props: {
    data: [Array, Object],
    all: {
      type: String,
      default: 'All Types'
    },
    hostels: {
      type: String,
      default: 'Hostels only'
    },
    continentsPage: {
      type: Boolean,
      default: false
    },
    continent: {
      type: String,
      default: 'country'
    },
    radioName:{
      type: String,
      default: 'cities'
    },
  },
  computed: {
    idAll() {
      return 'all-' + this.radioName;
    },
    idHostel() {
      return 'hostels-' +  this.radioName
    },
    isHostelsOnly() {
      return this.activeValue === 'hostelsOnly';
    },
    getData() {
      if (this.activeValue === 'hostelsOnly') {
        return this.data.filter(function (item) {
          return item.hostelCount > 0;
        });
      }

      return this.data;
    },
  },
  methods: {
    title(cityInfo) {
      let location = this.continentsPage ? cityInfo.country : cityInfo.city
      return 'Hostels in ' + location
    },
    showAll() {
      showAllAccommodationsMapPoints(this.continents)
    },
    showHostels() {
      showHostelsOnlyMapPoints(this.continents)
    },
  }
}
</script>