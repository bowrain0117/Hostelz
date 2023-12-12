<template>

  <div class="border-bottom mb-3 pb-3" v-for="review in sortedReviews" v-if="sortedReviews.length > 0">

    <div v-html="review.schema"></div>

    <div class="row mb-3">
      <div class="col-md-3 d-flex">
        <div v-if="review.profilePhotoUrl">
          <img :src="review.profilePhotoUrl" alt="#" class="avatar mr-2">
        </div>
        <div class="avatar mr-2 bg-gray-800 border border-white" v-else>
          <svg-icon :width="44" :height="48" :icon="'user-icon-dark'"></svg-icon>
        </div>

        <div class="cl-text">
          <div class="font-weight-600 text-break mb-1">{{ review.name }}</div>

          <div class="pre-title" v-if="review.age && review.age >= 16 && review.homeCountry !== ''">
            <div class="mb-1">Age {{ review.age }}</div>
            <svg-icon :width="24" :height="25" :icon="'map-place'"></svg-icon>
            {{ review.homeCountry }}
          </div>
          <div class="pre-title" v-else-if="review.age && review.age >= 16">Age {{ review.age }}</div>
          <div class="pre-title" v-else-if="review.homeCountry">
            <svg-icon :width="24" :height="25" :icon="'map-place'"></svg-icon>
            {{ review.homeCountry }}
          </div>
        </div>

        <div class="ml-1" v-if="!review.systemName">
          <svg-icon :width="24" :height="25" :icon="'verified-user-hostelz'"></svg-icon>
        </div>
      </div>

      <div class="mb-3 col-md-9">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div v-if="review.rating">
            <svg v-for="n in 5"
                 xmlns="http://www.w3.org/2000/svg" width="22" height="21" viewBox="0 0 22 21"
                 class="pl-1"
                 :class="n !== 1 ? 'ml-2' : ''"
            >
            <g><g><g><path :fill="starColor(n, review.rating)" d="M21.375 9.506c.43-.419.582-1.034.396-1.606a1.557 1.557 0 0 0-1.265-1.066l-5.29-.77a.691.691 0 0 1-.52-.378L12.33.893A1.557 1.557 0 0 0 10.925.02c-.6 0-1.14.335-1.405.873L7.154 5.687a.692.692 0 0 1-.52.378l-5.291.77A1.557 1.557 0 0 0 .078 7.9a1.557 1.557 0 0 0 .396 1.606l3.828 3.73c.163.16.238.39.2.613l-.904 5.269c-.08.463.042.914.342 1.27.466.554 1.28.723 1.932.381l4.73-2.487a.708.708 0 0 1 .645 0l4.731 2.487c.23.121.476.183.73.183.462 0 .9-.206 1.201-.564.301-.356.422-.808.342-1.27l-.903-5.269a.692.692 0 0 1 .2-.612z"/></g></g></g>
            </svg>
          </div>
          <div v-if="review.rating === 0">
            <svg v-for="n in 5"
                 xmlns="http://www.w3.org/2000/svg" width="22" height="21" viewBox="0 0 22 21"
                 class="pl-1"
                 :class="n !== 1 ? 'ml-2' : ''"
            >
              <g><g><g><path :fill="'#EFEFEF'" d="M21.375 9.506c.43-.419.582-1.034.396-1.606a1.557 1.557 0 0 0-1.265-1.066l-5.29-.77a.691.691 0 0 1-.52-.378L12.33.893A1.557 1.557 0 0 0 10.925.02c-.6 0-1.14.335-1.405.873L7.154 5.687a.692.692 0 0 1-.52.378l-5.291.77A1.557 1.557 0 0 0 .078 7.9a1.557 1.557 0 0 0 .396 1.606l3.828 3.73c.163.16.238.39.2.613l-.904 5.269c-.08.463.042.914.342 1.27.466.554 1.28.723 1.932.381l4.73-2.487a.708.708 0 0 1 .645 0l4.731 2.487c.23.121.476.183.73.183.462 0 .9-.206 1.201-.564.301-.356.422-.808.342-1.27l-.903-5.269a.692.692 0 0 1 .2-.612z"/></g></g></g>
            </svg>
          </div>
          <div class="pre-title cl-body">
            {{ commentDate(review.commentDate) }}
          </div>
        </div>

        <p class="font-weight-bold" v-if="review.summary">{{ review.summary }}</p>

        <comment :comment="review.comment"/>

        <div class="mt-3 bg-light p-3" v-if="review.ownerResponse">
          <p class="mb-3 mb-3 font-weight-bold">
            <svg-icon :width="24" :height="24" :icon="'speech-bubble'"></svg-icon>
            Response from the accommodation:
          </p>
          <p class="mb-3">{{ review.ownerResponse }}</p>
        </div>
      </div>
    </div>

    <div class="ratingPics" v-if="review.livePics && review.livePics.length > 0">
      <img
          v-for="pic in review.livePics"
          :src="pic.thumbnailUrl"
          :data-fullsize-pic="pic.fullsizePicUrl"
          :alt="pic.caption" :title="pic.caption"
          :data-pic-group="'rating' + review.id"
          property="image"
      >
    </div>

  </div>

  <div class="d-flex justify-content-center mb-3" v-else>
    No reviews yet.
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  name: 'Reviews',
  computed: {
    ...mapState('listingReviewsModules', [
      'sortedReviews',
      'searchString',
    ]),
  },
  methods: {
    starColor(n, rating) {
      return (n - 1) < rating ? '#454545' : '#EFEFEF'
    },
    commentDate(date) {
      const options = { year: 'numeric', month: 'short', day: 'numeric' };

      return new Date(date).toLocaleDateString('en-US', options)
    }
  },
}
</script>

