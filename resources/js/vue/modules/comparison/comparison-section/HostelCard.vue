<template>
  <div class="card shadow-1 border-0 h-100">
    <a :href="listing.url" :data-hostel-id="listing.id" target="_blank">
      <img class="img-fluid card-img-top" :src="listing.thumbnailUrl" :alt="listing.name"></a>
    <div class="card-body">
      <div v-if="listing.minPrice">
        <span class="text-xs mr-1">from</span>
        <span class="font-weight-bold text-sm">{{ listing.minPrice }}</span>
      </div>
      <h5 class="my-2">
        <a
            :href="listing.url"
            target="_blank"
            :title="listing.name"
            class="cl-black font-weight-bold mb-4 h4"
        >
          {{ listing.name }}
        </a>
      </h5>

      <p class="text-gray-500 text-sm my-3">
        <svg-icon :width="24" :height="24" :icon="'map-place'"></svg-icon>
        <a
            :href="listing.cityUrl"
            :title="cityLinkTitle"
            target="_blank"
        >{{ listing.city }}</a>, {{ listing.country }}
      </p>
      <div class="mt-4">
        Quick overview:
        <div class="pt-2">
          <div class="d-flex align-items-center mb-2 tx-small">
            <p class="cl-subtext mb-0">
              <svg-icon :width="24" :height="24" :icon="'green-check'"></svg-icon>
              <span class="cl-text ml-1">Rating {{ listing.keyFeatures.rating }}</span>
            </p>
          </div>
          <div class="d-flex align-items-center mb-2 tx-small">
            <p class="cl-subtext mb-0" v-if="listing.keyFeatures.distance">
              <svg-icon :width="24" :height="24" :icon="'green-check'"></svg-icon>
              <span class="cl-text ml-1">{{ listing.keyFeatures.distance }} km to city center</span>
            </p>
          </div>
          <div class="d-flex align-items-center mb-2 tx-small">
            <p class="cl-subtext mb-0" v-if="listing.keyFeatures.breakfast === 'included'">
              <svg-icon :width="24" :height="24" :icon="'green-check'"></svg-icon>
              <span class="cl-text ml-1">Breakfast included</span>
            </p>
            <p class="cl-subtext mb-0" v-else>
              <svg-icon :width="24" :height="24" :icon="'red-restriction'"></svg-icon>
              <span class="cl-text ml-1">Breakfast not included</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HostelCard',
  props: ['listing'],
  computed: {
    cityLinkTitle() {
      return this.listing.city + ',' + this.listing.country
    }
  }
}
</script>

<style scoped>

</style>