<template>
  <div class="listing-comparison container my-5">

    <div class="row">
      <div class="col-lg-4 col-sm-6 mb-4 hover-animate comparison-card" v-for="listing in listings">

        <hostel-card-default :listing="listing"></hostel-card-default>

      </div>
    </div>

  </div>
</template>

<script>
export default {
  name: 'HostelCardsDefault',
  data() {
    return {
      listings: [
        { sharedPrice: '11.50', name: 'The Legend Hostel', thumbnailUrl: '/pics/imported/listings/webp_big/35/3914135.webp', keyFeatures: { rating: '9.2', distance: '1.6 km', breakfast: 'not included' }},
        { sharedPrice: '11', name: 'Unforgettable Hostel',thumbnailUrl: '/pics/imported/listings/webp_big/4/3821804.webp', keyFeatures: { rating: '9.0', distance: '0.81 km', breakfast: 'included' }},
        { sharedPrice: '12.50', name: 'A to Z Hostel', thumbnailUrl: '/pics/imported/listings/webp_big/89/4086089.webp', keyFeatures: { rating: '8.9', distance: '0.54 km', breakfast: 'included' }},
      ],
    }
  },
}
</script>

<style scoped>
.comparison-img {
  width: 240px;
  height: 170px;
}
</style>