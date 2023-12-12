<template>
  <div class="card shadow-1 border-0 h-100">
    <div>
      <img class="img-fluid card-img-top" :src="listing.thumbnailUrl" :alt="listing.name">
    </div>
    <div class="card-body">
      <div v-if="listing.sharedPrice > 1">
        <span class="text-xs mr-1">from</span>
        <span class="font-weight-bold text-sm">${{ listing.sharedPrice }}</span>
      </div>
      <h5 class="my-2">
        <div class="cl-black font-weight-bold mb-4 h4">
          {{ listing.name }}
        </div>
      </h5>

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
}
</script>

<style scoped>
</style>